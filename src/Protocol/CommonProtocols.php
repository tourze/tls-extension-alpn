<?php

namespace Tourze\TLSExtensionALPN\Protocol;

/**
 * 常用的ALPN协议定义
 *
 * 包含标准化的应用层协议标识符
 */
class CommonProtocols
{
    /**
     * HTTP/1.1 协议
     */
    public const HTTP_1_1 = 'http/1.1';

    /**
     * HTTP/2 协议
     */
    public const HTTP_2 = 'h2';

    /**
     * HTTP/2 明文传输协议
     */
    public const HTTP_2_CLEARTEXT = 'h2c';

    /**
     * HTTP/3 协议
     */
    public const HTTP_3 = 'h3';

    /**
     * WebSocket 协议
     */
    public const WEBSOCKET = 'websocket';

    /**
     * SPDY/2 协议（已废弃）
     */
    public const SPDY_2 = 'spdy/2';

    /**
     * SPDY/3 协议（已废弃）
     */
    public const SPDY_3 = 'spdy/3';

    /**
     * SPDY/3.1 协议（已废弃）
     */
    public const SPDY_3_1 = 'spdy/3.1';

    /**
     * gRPC over HTTP/2 协议
     */
    public const GRPC = 'grpc';

    /**
     * MQTT over TLS 协议
     */
    public const MQTT = 'mqtt';

    /**
     * XMPP Client Connections
     */
    public const XMPP_CLIENT = 'xmpp-client';

    /**
     * XMPP Server Connections
     */
    public const XMPP_SERVER = 'xmpp-server';

    /**
     * FTP over TLS 协议
     */
    public const FTP = 'ftp';

    /**
     * IMAP over TLS 协议
     */
    public const IMAP = 'imap';

    /**
     * POP3 over TLS 协议
     */
    public const POP3 = 'pop3';

    /**
     * SMTP over TLS 协议
     */
    public const SMTP = 'smtp';

    /**
     * ACME TLS协议
     */
    public const ACME_TLS = 'acme-tls/1';

    /**
     * 获取所有定义的协议
     *
     * @return array<string>
     */
    public static function getAllProtocols(): array
    {
        $reflection = new \ReflectionClass(self::class);
        $constants = $reflection->getConstants();

        return array_values(array_filter($constants, static fn ($value): bool => is_string($value)));
    }

    /**
     * 检查协议是否为HTTP协议族
     */
    public static function isHttpProtocol(string $protocol): bool
    {
        return in_array($protocol, self::getHttpProtocols(), true);
    }

    /**
     * 获取HTTP协议族
     *
     * @return array<string>
     */
    public static function getHttpProtocols(): array
    {
        return [
            self::HTTP_1_1,
            self::HTTP_2,
            self::HTTP_2_CLEARTEXT,
            self::HTTP_3,
        ];
    }

    /**
     * 检查协议是否已废弃
     */
    public static function isDeprecated(string $protocol): bool
    {
        return in_array($protocol, self::getDeprecatedProtocols(), true);
    }

    /**
     * 获取已废弃的协议
     *
     * @return array<string>
     */
    public static function getDeprecatedProtocols(): array
    {
        return [
            self::SPDY_2,
            self::SPDY_3,
            self::SPDY_3_1,
        ];
    }

    /**
     * 检查协议是否为邮件协议
     */
    public static function isEmailProtocol(string $protocol): bool
    {
        return in_array($protocol, self::getEmailProtocols(), true);
    }

    /**
     * 获取邮件协议
     *
     * @return array<string>
     */
    public static function getEmailProtocols(): array
    {
        return [
            self::IMAP,
            self::POP3,
            self::SMTP,
        ];
    }

    /**
     * 获取协议描述
     */
    public static function getProtocolDescription(string $protocol): string
    {
        return match ($protocol) {
            self::HTTP_1_1 => 'Hypertext Transfer Protocol version 1.1',
            self::HTTP_2 => 'Hypertext Transfer Protocol version 2',
            self::HTTP_2_CLEARTEXT => 'HTTP/2 over cleartext',
            self::HTTP_3 => 'Hypertext Transfer Protocol version 3',
            self::WEBSOCKET => 'WebSocket Protocol',
            self::SPDY_2 => 'SPDY Protocol version 2 (deprecated)',
            self::SPDY_3 => 'SPDY Protocol version 3 (deprecated)',
            self::SPDY_3_1 => 'SPDY Protocol version 3.1 (deprecated)',
            self::GRPC => 'gRPC over HTTP/2',
            self::MQTT => 'Message Queuing Telemetry Transport',
            self::XMPP_CLIENT => 'Extensible Messaging and Presence Protocol (Client)',
            self::XMPP_SERVER => 'Extensible Messaging and Presence Protocol (Server)',
            self::FTP => 'File Transfer Protocol',
            self::IMAP => 'Internet Message Access Protocol',
            self::POP3 => 'Post Office Protocol version 3',
            self::SMTP => 'Simple Mail Transfer Protocol',
            self::ACME_TLS => 'Automatic Certificate Management Environment',
            default => 'Unknown protocol',
        };
    }

    /**
     * 获取协议的RFC参考
     */
    public static function getProtocolRFC(string $protocol): ?string
    {
        return match ($protocol) {
            self::HTTP_1_1 => 'RFC 7230-7237',
            self::HTTP_2 => 'RFC 7540',
            self::HTTP_3 => 'RFC 9114',
            self::WEBSOCKET => 'RFC 6455',
            self::GRPC => 'gRPC Specification',
            self::MQTT => 'ISO/IEC 20922',
            self::XMPP_CLIENT, self::XMPP_SERVER => 'RFC 6120',
            self::FTP => 'RFC 959',
            self::IMAP => 'RFC 3501',
            self::POP3 => 'RFC 1939',
            self::SMTP => 'RFC 5321',
            self::ACME_TLS => 'RFC 8737',
            default => null,
        };
    }
}
