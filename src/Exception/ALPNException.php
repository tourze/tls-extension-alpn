<?php

namespace Tourze\TLSExtensionALPN\Exception;

use Tourze\TLSCommon\Exception\TLSException;

/**
 * ALPN扩展相关异常
 */
class ALPNException extends TLSException
{
    /**
     * 无效的协议列表异常
     */
    public static function invalidProtocolList(string $message = ''): self
    {
        return new self('Invalid protocol list: ' . $message);
    }

    /**
     * 协议名称过长异常
     */
    public static function protocolNameTooLong(string $protocol, int $maxLength = 255): self
    {
        return new self(sprintf(
            'Protocol name "%s" is too long. Maximum length is %d bytes.',
            $protocol,
            $maxLength
        ));
    }

    /**
     * 空协议名称异常
     */
    public static function emptyProtocolName(): self
    {
        return new self('Protocol name cannot be empty');
    }

    /**
     * 协议未找到异常
     */
    public static function protocolNotFound(string $protocol): self
    {
        return new self(sprintf('Protocol "%s" not found in negotiated protocols', $protocol));
    }

    /**
     * 协议协商失败异常
     *
     * @param array<string> $clientProtocols
     * @param array<string> $serverProtocols
     */
    public static function negotiationFailed(array $clientProtocols, array $serverProtocols): self
    {
        return new self(sprintf(
            'ALPN negotiation failed. Client protocols: [%s], Server protocols: [%s]',
            implode(', ', $clientProtocols),
            implode(', ', $serverProtocols)
        ));
    }

    /**
     * 解码错误异常
     */
    public static function decodingError(string $message): self
    {
        return new self('ALPN extension decoding error: ' . $message);
    }

    /**
     * 编码错误异常
     */
    public static function encodingError(string $message): self
    {
        return new self('ALPN extension encoding error: ' . $message);
    }
}
