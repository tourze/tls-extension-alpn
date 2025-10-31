<?php

namespace Tourze\TLSExtensionALPN;

use Tourze\TLSExtensionALPN\Exception\ALPNException;
use Tourze\TLSExtensionALPN\Protocol\CommonProtocols;

/**
 * ALPN管理器
 *
 * 提供高级的ALPN扩展管理功能，包括协议集管理、批量操作等
 */
class ALPNManager
{
    /**
     * 协议集合
     *
     * @var array<string, array<string>>
     */
    private array $protocolSets = [];

    /**
     * 协商器
     */
    private ALPNNegotiator $negotiator;

    /**
     * 构造函数
     */
    public function __construct(?ALPNNegotiator $negotiator = null)
    {
        $this->negotiator = $negotiator ?? new ALPNNegotiator();
        $this->initializeCommonProtocolSets();
    }

    /**
     * 初始化常用协议集
     */
    private function initializeCommonProtocolSets(): void
    {
        $this->protocolSets = [
            'web' => CommonProtocols::getHttpProtocols(),
            'email' => CommonProtocols::getEmailProtocols(),
            'all' => CommonProtocols::getAllProtocols(),
            'modern' => [
                CommonProtocols::HTTP_2,
                CommonProtocols::HTTP_3,
                CommonProtocols::GRPC,
                CommonProtocols::WEBSOCKET,
            ],
        ];
    }

    /**
     * 创建默认的web配置
     */
    public static function createWebConfiguration(): self
    {
        return new self();
    }

    /**
     * 创建默认的API配置
     */
    public static function createApiConfiguration(): self
    {
        $manager = new self();
        $manager->registerProtocolSet('default', [
            CommonProtocols::HTTP_2,
            CommonProtocols::GRPC,
            CommonProtocols::HTTP_1_1,
        ]);

        return $manager;
    }

    /**
     * 注册协议集
     *
     * @param array<string> $protocols
     */
    public function registerProtocolSet(string $name, array $protocols): self
    {
        foreach ($protocols as $protocol) {
            if ('' === $protocol) {
                throw ALPNException::emptyProtocolName();
            }
        }

        $this->protocolSets[$name] = array_values(array_unique($protocols));

        return $this;
    }

    /**
     * 获取所有协议集名称
     *
     * @return array<string>
     */
    public function getProtocolSetNames(): array
    {
        return array_keys($this->protocolSets);
    }

    /**
     * 检查协议集是否存在
     */
    public function hasProtocolSet(string $name): bool
    {
        return isset($this->protocolSets[$name]);
    }

    /**
     * 移除协议集
     */
    public function removeProtocolSet(string $name): self
    {
        unset($this->protocolSets[$name]);

        return $this;
    }

    /**
     * 创建ALPN扩展
     *
     * @param string|array<string> $protocols 协议或协议集名称
     */
    public function createExtension($protocols): ALPNExtension
    {
        if (is_string($protocols)) {
            $protocolList = $this->getProtocolSet($protocols);
        } else {
            $protocolList = $protocols;
        }

        return new ALPNExtension($protocolList);
    }

    /**
     * 获取协议集
     *
     * @return array<string>
     *
     * @throws ALPNException 如果协议集不存在
     */
    public function getProtocolSet(string $name): array
    {
        if (!isset($this->protocolSets[$name])) {
            throw ALPNException::invalidProtocolList("Protocol set '{$name}' not found");
        }

        return $this->protocolSets[$name];
    }

    /**
     * 协商协议
     *
     * @param string|array<string> $clientProtocols
     * @param string|array<string> $serverProtocols
     *
     * @throws ALPNException
     */
    public function negotiate($clientProtocols, $serverProtocols): string
    {
        $clientList = is_string($clientProtocols)
            ? $this->getProtocolSet($clientProtocols)
            : $clientProtocols;

        $serverList = is_string($serverProtocols)
            ? $this->getProtocolSet($serverProtocols)
            : $serverProtocols;

        return $this->negotiator->negotiate($clientList, $serverList);
    }

    /**
     * 检查协议兼容性
     *
     * @param string|array<string> $clientProtocols
     * @param string|array<string> $serverProtocols
     */
    public function isCompatible($clientProtocols, $serverProtocols): bool
    {
        $clientList = is_string($clientProtocols)
            ? $this->getProtocolSet($clientProtocols)
            : $clientProtocols;

        $serverList = is_string($serverProtocols)
            ? $this->getProtocolSet($serverProtocols)
            : $serverProtocols;

        return $this->negotiator->isCompatible($clientList, $serverList);
    }

    /**
     * 获取协商器
     */
    public function getNegotiator(): ALPNNegotiator
    {
        return $this->negotiator;
    }

    /**
     * 设置协商器
     */
    public function setNegotiator(ALPNNegotiator $negotiator): void
    {
        $this->negotiator = $negotiator;
    }

    /**
     * 获取推荐的协议配置
     *
     * @return array{web: array<string>, api: array<string>, secure: array<string>}
     */
    public function getRecommendedConfigurations(): array
    {
        return [
            'web' => [
                CommonProtocols::HTTP_2,
                CommonProtocols::HTTP_1_1,
            ],
            'api' => [
                CommonProtocols::HTTP_2,
                CommonProtocols::GRPC,
                CommonProtocols::HTTP_1_1,
            ],
            'secure' => [
                CommonProtocols::HTTP_3,
                CommonProtocols::HTTP_2,
            ],
        ];
    }

    /**
     * 验证协议配置
     *
     * @param array<string> $protocols
     *
     * @return array{valid: bool, issues: array<string>, recommendations: array<string>}
     */
    public function validateConfiguration(array $protocols): array
    {
        $result = [
            'valid' => true,
            'issues' => [],
            'recommendations' => [],
        ];

        if ([] === $protocols) {
            $result['valid'] = false;
            $result['issues'][] = 'No protocols specified';

            return $result;
        }

        $analysis = $this->analyzeProtocols($protocols);

        // 检查是否有未知协议
        if ($analysis['unknown'] > 0) {
            $result['issues'][] = sprintf('%d unknown protocol(s) found', $analysis['unknown']);
        }

        // 检查是否有废弃协议
        if ($analysis['deprecated'] > 0) {
            $result['issues'][] = sprintf('%d deprecated protocol(s) found', $analysis['deprecated']);
            $result['recommendations'][] = 'Consider removing deprecated protocols';
        }

        // 检查是否包含现代协议
        $modernProtocols = array_intersect($protocols, [
            CommonProtocols::HTTP_2,
            CommonProtocols::HTTP_3,
        ]);

        if ([] === $modernProtocols) {
            $result['recommendations'][] = 'Consider adding HTTP/2 or HTTP/3 for better performance';
        }

        // 检查是否有HTTP/1.1作为后备
        if (in_array(CommonProtocols::HTTP_2, $protocols, true)
            || in_array(CommonProtocols::HTTP_3, $protocols, true)) {
            if (!in_array(CommonProtocols::HTTP_1_1, $protocols, true)) {
                $result['recommendations'][] = 'Consider adding HTTP/1.1 as fallback protocol';
            }
        }

        return $result;
    }

    /**
     * 分析协议支持情况
     *
     * @param array<string> $protocols
     *
     * @return array{total: int, supported: int, deprecated: int, unknown: int, details: array<string, array{supported: bool, deprecated: bool, description: string}>}
     */
    public function analyzeProtocols(array $protocols): array
    {
        $analysis = [
            'total' => count($protocols),
            'supported' => 0,
            'deprecated' => 0,
            'unknown' => 0,
            'details' => [],
        ];

        $allKnownProtocols = CommonProtocols::getAllProtocols();

        foreach ($protocols as $protocol) {
            $isSupported = in_array($protocol, $allKnownProtocols, true);
            $isDeprecated = CommonProtocols::isDeprecated($protocol);
            $description = CommonProtocols::getProtocolDescription($protocol);

            $analysis['details'][$protocol] = [
                'supported' => $isSupported,
                'deprecated' => $isDeprecated,
                'description' => $description,
            ];

            if ($isSupported) {
                ++$analysis['supported'];
                if ($isDeprecated) {
                    ++$analysis['deprecated'];
                }
            } else {
                ++$analysis['unknown'];
            }
        }

        return $analysis;
    }
}
