<?php

namespace Tourze\TLSExtensionALPN;

use Tourze\TLSExtensionALPN\Exception\ALPNException;

/**
 * ALPN (Application-Layer Protocol Negotiation) 扩展实现
 *
 * 根据 RFC 7301 实现的 ALPN 扩展，用于在 TLS 握手过程中协商应用层协议
 */
class ALPNExtension
{
    /**
     * 支持的协议列表
     *
     * @var array<string>
     */
    private array $protocols = [];

    /**
     * 协商选中的协议
     */
    private ?string $selectedProtocol = null;

    /**
     * 构造函数
     *
     * @param array<string> $protocols 支持的协议列表
     *
     * @throws ALPNException 如果协议列表无效
     */
    public function __construct(array $protocols = [])
    {
        $this->setProtocols($protocols);
    }

    /**
     * 从二进制数据解码
     *
     * @throws ALPNException 如果解码失败
     */
    public static function decode(string $data): self
    {
        self::validateMinimumDataLength($data);

        try {
            [$protocolListLength, $offset] = self::readProtocolListLength($data);
            self::validateProtocolListData($data, $offset, $protocolListLength);

            $protocols = self::parseProtocols($data, $offset, $offset + $protocolListLength);

            return new self($protocols);
        } catch (ALPNException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw ALPNException::decodingError($e->getMessage());
        }
    }

    /**
     * 验证最小数据长度
     *
     * @throws ALPNException
     */
    private static function validateMinimumDataLength(string $data): void
    {
        if (strlen($data) < 2) {
            throw ALPNException::decodingError('Data too short');
        }
    }

    /**
     * 读取协议列表长度
     *
     * @return array{int, int} [协议列表长度, 偏移量]
     */
    private static function readProtocolListLength(string $data): array
    {
        $unpacked = unpack('n', substr($data, 0, 2));
        if (false === $unpacked) {
            throw ALPNException::decodingError('Failed to unpack protocol list length');
        }
        $protocolListLength = $unpacked[1];

        return [$protocolListLength, 2];
    }

    /**
     * 验证协议列表数据完整性
     *
     * @throws ALPNException
     */
    private static function validateProtocolListData(string $data, int $offset, int $protocolListLength): void
    {
        if (strlen($data) < $offset + $protocolListLength) {
            throw ALPNException::decodingError('Incomplete protocol list data');
        }
    }

    /**
     * 解析协议列表
     *
     * @return array<string>
     *
     * @throws ALPNException
     */
    private static function parseProtocols(string $data, int $offset, int $listEnd): array
    {
        $protocols = [];

        while ($offset < $listEnd) {
            [$protocol, $offset] = self::parseProtocol($data, $offset);
            $protocols[] = $protocol;
        }

        return $protocols;
    }

    /**
     * 解析单个协议
     *
     * @return array{string, int} [协议名称, 新的偏移量]
     *
     * @throws ALPNException
     */
    private static function parseProtocol(string $data, int $offset): array
    {
        if ($offset >= strlen($data)) {
            throw ALPNException::decodingError('Unexpected end of data');
        }

        $protocolLength = ord($data[$offset]);
        ++$offset;

        if (0 === $protocolLength) {
            throw ALPNException::emptyProtocolName();
        }

        if ($offset + $protocolLength > strlen($data)) {
            throw ALPNException::decodingError('Incomplete protocol name data');
        }

        $protocol = substr($data, $offset, $protocolLength);
        $offset += $protocolLength;

        return [$protocol, $offset];
    }

    /**
     * 协商协议
     *
     * 根据客户端和服务器的协议列表，按照服务器的优先级选择协议
     *
     * @param array<string> $clientProtocols 客户端支持的协议
     * @param array<string> $serverProtocols 服务器支持的协议
     *
     * @throws ALPNException 如果协商失败
     */
    public static function negotiate(array $clientProtocols, array $serverProtocols): string
    {
        // 按服务器优先级进行协商
        foreach ($serverProtocols as $serverProtocol) {
            if (in_array($serverProtocol, $clientProtocols, true)) {
                return $serverProtocol;
            }
        }

        throw ALPNException::negotiationFailed($clientProtocols, $serverProtocols);
    }

    /**
     * 创建客户端ALPN扩展
     *
     * @param array<string> $protocols 客户端支持的协议列表
     */
    public static function forClient(array $protocols): self
    {
        return new self($protocols);
    }

    /**
     * 创建服务器ALPN扩展
     *
     * @param string $selectedProtocol 服务器选择的协议
     */
    public static function forServer(string $selectedProtocol): self
    {
        $extension = new self([$selectedProtocol]);
        $extension->setSelectedProtocol($selectedProtocol);

        return $extension;
    }

    /**
     * 获取扩展类型
     */
    public function getType(): int
    {
        return ExtensionType::ALPN->value;
    }

    /**
     * 获取支持的协议列表
     *
     * @return array<string>
     */
    public function getProtocols(): array
    {
        return $this->protocols;
    }

    /**
     * 设置支持的协议列表
     *
     * @param array<string> $protocols 协议列表
     *
     * @throws ALPNException 如果协议无效
     */
    public function setProtocols(array $protocols): void
    {
        foreach ($protocols as $protocol) {
            $this->validateProtocol($protocol);
        }

        $this->protocols = array_values(array_unique($protocols));
    }

    /**
     * 添加协议
     *
     * @throws ALPNException 如果协议无效
     */
    public function addProtocol(string $protocol): self
    {
        $this->validateProtocol($protocol);

        if (!in_array($protocol, $this->protocols, true)) {
            $this->protocols[] = $protocol;
        }

        return $this;
    }

    /**
     * 验证协议名称
     *
     * @throws ALPNException 如果协议无效
     */
    private function validateProtocol(string $protocol): void
    {
        if ('' === $protocol) {
            throw ALPNException::emptyProtocolName();
        }

        if (strlen($protocol) > 255) {
            throw ALPNException::protocolNameTooLong($protocol);
        }
    }

    /**
     * 移除协议
     */
    public function removeProtocol(string $protocol): self
    {
        $this->protocols = array_values(array_filter(
            $this->protocols,
            fn ($p) => $p !== $protocol
        ));

        return $this;
    }

    /**
     * 检查是否支持指定协议
     */
    public function hasProtocol(string $protocol): bool
    {
        return in_array($protocol, $this->protocols, true);
    }

    /**
     * 获取选中的协议
     */
    public function getSelectedProtocol(): ?string
    {
        return $this->selectedProtocol;
    }

    /**
     * 设置选中的协议
     *
     * @throws ALPNException 如果协议未在支持列表中
     */
    public function setSelectedProtocol(string $protocol): void
    {
        if (!$this->hasProtocol($protocol)) {
            throw ALPNException::protocolNotFound($protocol);
        }

        $this->selectedProtocol = $protocol;
    }

    /**
     * 清除选中的协议
     */
    public function clearSelectedProtocol(): self
    {
        $this->selectedProtocol = null;

        return $this;
    }

    /**
     * 编码为二进制格式
     *
     * 格式：
     * - ProtocolNameList length (2 bytes)
     * - ProtocolNameList:
     *   - ProtocolName length (1 byte)
     *   - ProtocolName (variable)
     *   - ... (repeat for each protocol)
     *
     * @throws ALPNException 如果编码失败
     */
    public function encode(): string
    {
        if ([] === $this->protocols) {
            throw ALPNException::encodingError('No protocols to encode');
        }

        try {
            $protocolListData = '';

            foreach ($this->protocols as $protocol) {
                $protocolLength = strlen($protocol);
                if ($protocolLength > 255) {
                    throw ALPNException::protocolNameTooLong($protocol);
                }

                $protocolListData .= chr($protocolLength) . $protocol;
            }

            $protocolListLength = strlen($protocolListData);
            if ($protocolListLength > 65535) {
                throw ALPNException::encodingError('Protocol list too long');
            }

            return pack('n', $protocolListLength) . $protocolListData;
        } catch (\Throwable $e) {
            throw ALPNException::encodingError($e->getMessage());
        }
    }

    /**
     * 转换为字符串表示
     */
    public function __toString(): string
    {
        $info = sprintf('ALPN Extension (protocols: %s)', implode(', ', $this->protocols));

        if (null !== $this->selectedProtocol) {
            $info .= sprintf(' [selected: %s]', $this->selectedProtocol);
        }

        return $info;
    }
}
