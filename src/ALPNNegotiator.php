<?php

namespace Tourze\TLSExtensionALPN;

use Tourze\TLSExtensionALPN\Exception\ALPNException;

/**
 * ALPN协议协商器
 *
 * 负责处理ALPN协议协商逻辑，支持多种协商策略
 */
class ALPNNegotiator
{
    /**
     * 服务器优先策略：按照服务器的协议优先级进行协商
     */
    public const STRATEGY_SERVER_PREFERENCE = 'server_preference';

    /**
     * 客户端优先策略：按照客户端的协议优先级进行协商
     */
    public const STRATEGY_CLIENT_PREFERENCE = 'client_preference';

    /**
     * 协商策略
     */
    private string $strategy;

    /**
     * 构造函数
     */
    public function __construct(string $strategy = self::STRATEGY_SERVER_PREFERENCE)
    {
        $this->setStrategy($strategy);
    }

    /**
     * 创建服务器优先协商器
     */
    public static function serverPreference(): self
    {
        return new self(self::STRATEGY_SERVER_PREFERENCE);
    }

    /**
     * 创建客户端优先协商器
     */
    public static function clientPreference(): self
    {
        return new self(self::STRATEGY_CLIENT_PREFERENCE);
    }

    /**
     * 获取协商策略
     */
    public function getStrategy(): string
    {
        return $this->strategy;
    }

    /**
     * 设置协商策略
     *
     * @throws ALPNException 如果策略无效
     */
    public function setStrategy(string $strategy): void
    {
        if (!in_array($strategy, [self::STRATEGY_SERVER_PREFERENCE, self::STRATEGY_CLIENT_PREFERENCE], true)) {
            throw ALPNException::invalidProtocolList('Invalid negotiation strategy: ' . $strategy);
        }

        $this->strategy = $strategy;
    }

    /**
     * 批量协商
     *
     * 对多个客户端-服务器协议对进行批量协商
     *
     * @param array<mixed> $pairs
     *
     * @return array<string> 协商结果
     */
    public function negotiateBatch(array $pairs): array
    {
        $results = [];

        foreach ($pairs as $index => $pair) {
            [$clientProtocols, $serverProtocols] = $this->validateAndExtractProtocols($pair, $index);

            try {
                $results[] = $this->negotiate($clientProtocols, $serverProtocols);
            } catch (ALPNException $e) {
                throw ALPNException::negotiationFailed($clientProtocols, $serverProtocols);
            }
        }

        return $results;
    }

    /**
     * 验证并提取协议列表
     *
     * @param mixed $pair 协议对
     * @param int $index 索引位置
     * @return array{0: array<string>, 1: array<string>} 客户端和服务器协议列表
     * @throws ALPNException 如果验证失败
     */
    private function validateAndExtractProtocols(mixed $pair, int $index): array
    {
        if (!is_array($pair) || !isset($pair['client']) || !isset($pair['server'])) {
            throw ALPNException::invalidProtocolList("Missing client or server protocols at index {$index}");
        }

        $clientProtocols = $pair['client'];
        $serverProtocols = $pair['server'];

        if (!is_array($clientProtocols) || !is_array($serverProtocols)) {
            throw ALPNException::invalidProtocolList("Invalid protocol arrays at index {$index}");
        }

        $this->validateProtocolTypes($clientProtocols, 'client', $index);
        $this->validateProtocolTypes($serverProtocols, 'server', $index);

        /** @var array<string> $clientProtocols */
        /** @var array<string> $serverProtocols */
        return [$clientProtocols, $serverProtocols];
    }

    /**
     * 验证协议类型
     *
     * @param array<mixed> $protocols 协议列表
     * @param string $type 协议类型（client或server）
     * @param int $index 索引位置
     * @throws ALPNException 如果协议类型无效
     */
    private function validateProtocolTypes(array $protocols, string $type, int $index): void
    {
        foreach ($protocols as $protocol) {
            if (!is_string($protocol)) {
                throw ALPNException::invalidProtocolList("Invalid {$type} protocol type at index {$index}");
            }
        }
    }

    /**
     * 执行协议协商
     *
     * @param array<string> $clientProtocols 客户端支持的协议
     * @param array<string> $serverProtocols 服务器支持的协议
     *
     * @throws ALPNException 如果协商失败
     */
    public function negotiate(array $clientProtocols, array $serverProtocols): string
    {
        if ([] === $clientProtocols) {
            throw ALPNException::invalidProtocolList('Client protocols cannot be empty');
        }

        if ([] === $serverProtocols) {
            throw ALPNException::invalidProtocolList('Server protocols cannot be empty');
        }

        return match ($this->strategy) {
            self::STRATEGY_SERVER_PREFERENCE => $this->negotiateServerPreference($clientProtocols, $serverProtocols),
            self::STRATEGY_CLIENT_PREFERENCE => $this->negotiateClientPreference($clientProtocols, $serverProtocols),
            default => throw ALPNException::invalidProtocolList('Invalid negotiation strategy: ' . $this->strategy),
        };
    }

    /**
     * 按服务器优先级协商
     *
     * @param array<string> $clientProtocols
     * @param array<string> $serverProtocols
     *
     * @throws ALPNException
     */
    private function negotiateServerPreference(array $clientProtocols, array $serverProtocols): string
    {
        foreach ($serverProtocols as $serverProtocol) {
            if (in_array($serverProtocol, $clientProtocols, true)) {
                return $serverProtocol;
            }
        }

        throw ALPNException::negotiationFailed($clientProtocols, $serverProtocols);
    }

    /**
     * 按客户端优先级协商
     *
     * @param array<string> $clientProtocols
     * @param array<string> $serverProtocols
     *
     * @throws ALPNException
     */
    private function negotiateClientPreference(array $clientProtocols, array $serverProtocols): string
    {
        foreach ($clientProtocols as $clientProtocol) {
            if (in_array($clientProtocol, $serverProtocols, true)) {
                return $clientProtocol;
            }
        }

        throw ALPNException::negotiationFailed($clientProtocols, $serverProtocols);
    }

    /**
     * 检查协议兼容性
     *
     * @param array<string> $clientProtocols
     * @param array<string> $serverProtocols
     */
    public function isCompatible(array $clientProtocols, array $serverProtocols): bool
    {
        foreach ($clientProtocols as $clientProtocol) {
            if (in_array($clientProtocol, $serverProtocols, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取共同支持的协议
     *
     * @param array<string> $clientProtocols
     * @param array<string> $serverProtocols
     *
     * @return array<string>
     */
    public function getCommonProtocols(array $clientProtocols, array $serverProtocols): array
    {
        return array_values(array_intersect($clientProtocols, $serverProtocols));
    }
}
