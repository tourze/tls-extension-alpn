<?php

namespace Tourze\TLSExtensionALPN;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * TLS扩展类型常量
 *
 * 根据RFC 7301定义ALPN扩展类型
 */
enum ExtensionType: int implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    /**
     * ALPN扩展类型ID (RFC 7301)
     */
    case ALPN = 16;

    /**
     * NPN扩展类型ID (Next Protocol Negotiation)
     */
    case NPN = 13172;

    /**
     * 获取扩展类型名称
     */
    public function getName(): string
    {
        return match ($this) {
            self::ALPN => 'application_layer_protocol_negotiation',
            self::NPN => 'next_protocol_negotiation',
        };
    }

    /**
     * 获取扩展描述
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::ALPN => 'Application-Layer Protocol Negotiation (RFC 7301)',
            self::NPN => 'Next Protocol Negotiation (deprecated)',
        };
    }

    /**
     * 获取标签
     */
    public function getLabel(): string
    {
        return $this->getDescription();
    }
}
