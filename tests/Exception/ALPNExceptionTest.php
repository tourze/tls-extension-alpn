<?php

namespace Tourze\TLSExtensionALPN\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TLSCommon\Exception\TLSException;
use Tourze\TLSExtensionALPN\Exception\ALPNException;

/**
 * @internal
 */
#[CoversClass(ALPNException::class)]
final class ALPNExceptionTest extends AbstractExceptionTestCase
{
    public function testExtendsTlsException(): void
    {
        $exception = new ALPNException('test message');
        $this->assertInstanceOf(TLSException::class, $exception);
    }

    public function testInvalidProtocolListCreatesCorrectException(): void
    {
        $message = 'custom error message';
        $exception = ALPNException::invalidProtocolList($message);

        $this->assertInstanceOf(ALPNException::class, $exception);
        $this->assertStringContainsString('Invalid protocol list', $exception->getMessage());
        $this->assertStringContainsString($message, $exception->getMessage());
    }

    public function testInvalidProtocolListWithEmptyMessageCreatesException(): void
    {
        $exception = ALPNException::invalidProtocolList();

        $this->assertInstanceOf(ALPNException::class, $exception);
        $this->assertEquals('Invalid protocol list: ', $exception->getMessage());
    }

    public function testProtocolNameTooLongCreatesCorrectException(): void
    {
        $protocol = 'very-long-protocol-name';
        $maxLength = 100;
        $exception = ALPNException::protocolNameTooLong($protocol, $maxLength);

        $this->assertInstanceOf(ALPNException::class, $exception);
        $this->assertStringContainsString($protocol, $exception->getMessage());
        $this->assertStringContainsString((string) $maxLength, $exception->getMessage());
        $this->assertStringContainsString('too long', $exception->getMessage());
    }

    public function testProtocolNameTooLongWithDefaultMaxLengthUsesDefaultValue(): void
    {
        $protocol = 'test-protocol';
        $exception = ALPNException::protocolNameTooLong($protocol);

        $this->assertStringContainsString('255', $exception->getMessage());
    }

    public function testEmptyProtocolNameCreatesCorrectException(): void
    {
        $exception = ALPNException::emptyProtocolName();

        $this->assertInstanceOf(ALPNException::class, $exception);
        $this->assertStringContainsString('cannot be empty', $exception->getMessage());
    }

    public function testProtocolNotFoundCreatesCorrectException(): void
    {
        $protocol = 'unknown-protocol';
        $exception = ALPNException::protocolNotFound($protocol);

        $this->assertInstanceOf(ALPNException::class, $exception);
        $this->assertStringContainsString($protocol, $exception->getMessage());
        $this->assertStringContainsString('not found', $exception->getMessage());
    }

    public function testNegotiationFailedCreatesCorrectException(): void
    {
        $clientProtocols = ['http/1.1', 'h2'];
        $serverProtocols = ['grpc', 'h3'];
        $exception = ALPNException::negotiationFailed($clientProtocols, $serverProtocols);

        $this->assertInstanceOf(ALPNException::class, $exception);
        $this->assertStringContainsString('negotiation failed', $exception->getMessage());
        $this->assertStringContainsString('http/1.1', $exception->getMessage());
        $this->assertStringContainsString('h2', $exception->getMessage());
        $this->assertStringContainsString('grpc', $exception->getMessage());
        $this->assertStringContainsString('h3', $exception->getMessage());
    }

    public function testNegotiationFailedWithEmptyArraysHandlesCorrectly(): void
    {
        $exception = ALPNException::negotiationFailed([], []);

        $this->assertInstanceOf(ALPNException::class, $exception);
        $this->assertStringContainsString('negotiation failed', $exception->getMessage());
    }

    public function testDecodingErrorCreatesCorrectException(): void
    {
        $message = 'Invalid data format';
        $exception = ALPNException::decodingError($message);

        $this->assertInstanceOf(ALPNException::class, $exception);
        $this->assertStringContainsString('decoding error', $exception->getMessage());
        $this->assertStringContainsString($message, $exception->getMessage());
    }

    public function testEncodingErrorCreatesCorrectException(): void
    {
        $message = 'Data too long';
        $exception = ALPNException::encodingError($message);

        $this->assertInstanceOf(ALPNException::class, $exception);
        $this->assertStringContainsString('encoding error', $exception->getMessage());
        $this->assertStringContainsString($message, $exception->getMessage());
    }

    public function testStaticMethodsReturnDifferentInstances(): void
    {
        $exception1 = ALPNException::emptyProtocolName();
        $exception2 = ALPNException::emptyProtocolName();

        $this->assertNotSame($exception1, $exception2);
        $this->assertEquals($exception1->getMessage(), $exception2->getMessage());
    }

    public function testMessagePreservation(): void
    {
        $originalMessage = 'Original error message';
        $exception = new ALPNException($originalMessage);

        $this->assertEquals($originalMessage, $exception->getMessage());
    }
}
