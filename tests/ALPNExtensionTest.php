<?php

namespace Tourze\TLSExtensionALPN\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\TLSExtensionALPN\ALPNExtension;
use Tourze\TLSExtensionALPN\Exception\ALPNException;
use Tourze\TLSExtensionALPN\ExtensionType;

/**
 * @internal
 */
#[CoversClass(ALPNExtension::class)]
final class ALPNExtensionTest extends TestCase
{
    public function testConstructorWithEmptyProtocolsCreatesEmptyExtension(): void
    {
        $extension = new ALPNExtension();

        $this->assertEmpty($extension->getProtocols());
        $this->assertNull($extension->getSelectedProtocol());
    }

    public function testConstructorWithValidProtocolsSetsProtocols(): void
    {
        $protocols = ['http/1.1', 'h2'];
        $extension = new ALPNExtension($protocols);

        $this->assertEquals($protocols, $extension->getProtocols());
    }

    public function testConstructorWithInvalidProtocolThrowsException(): void
    {
        $this->expectException(ALPNException::class);
        $this->expectExceptionMessage('cannot be empty');

        new ALPNExtension(['http/1.1', '']);
    }

    public function testGetTypeReturnsCorrectValue(): void
    {
        $extension = new ALPNExtension();

        $this->assertEquals(ExtensionType::ALPN->value, $extension->getType());
        $this->assertEquals(16, $extension->getType());
    }

    public function testSetProtocolsWithValidProtocolsSetsProtocols(): void
    {
        $extension = new ALPNExtension();
        $protocols = ['http/1.1', 'h2', 'grpc'];

        $extension->setProtocols($protocols);
        $this->assertEquals($protocols, $extension->getProtocols());
    }

    public function testSetProtocolsWithDuplicatesRemoveDuplicates(): void
    {
        $extension = new ALPNExtension();
        $protocols = ['http/1.1', 'h2', 'http/1.1', 'grpc', 'h2'];

        $extension->setProtocols($protocols);

        $this->assertEquals(['http/1.1', 'h2', 'grpc'], $extension->getProtocols());
    }

    public function testSetProtocolsWithEmptyProtocolThrowsException(): void
    {
        $extension = new ALPNExtension();

        $this->expectException(ALPNException::class);
        $this->expectExceptionMessage('cannot be empty');

        $extension->setProtocols(['http/1.1', '']);
    }

    public function testSetProtocolsWithTooLongProtocolThrowsException(): void
    {
        $extension = new ALPNExtension();
        $longProtocol = str_repeat('a', 256);

        $this->expectException(ALPNException::class);
        $this->expectExceptionMessage('too long');

        $extension->setProtocols([$longProtocol]);
    }

    public function testAddProtocolWithValidProtocolAddsProtocol(): void
    {
        $extension = new ALPNExtension(['http/1.1']);

        $result = $extension->addProtocol('h2');

        $this->assertSame($extension, $result);
        $this->assertEquals(['http/1.1', 'h2'], $extension->getProtocols());
    }

    public function testAddProtocolWithExistingProtocolDoesNotAddDuplicate(): void
    {
        $extension = new ALPNExtension(['http/1.1', 'h2']);

        $extension->addProtocol('http/1.1');

        $this->assertEquals(['http/1.1', 'h2'], $extension->getProtocols());
    }

    public function testAddProtocolWithInvalidProtocolThrowsException(): void
    {
        $extension = new ALPNExtension();

        $this->expectException(ALPNException::class);
        $this->expectExceptionMessage('cannot be empty');

        $extension->addProtocol('');
    }

    public function testRemoveProtocolWithExistingProtocolRemovesProtocol(): void
    {
        $extension = new ALPNExtension(['http/1.1', 'h2', 'grpc']);

        $result = $extension->removeProtocol('h2');

        $this->assertSame($extension, $result);
        $this->assertEquals(['http/1.1', 'grpc'], $extension->getProtocols());
    }

    public function testRemoveProtocolWithNonExistingProtocolDoesNothing(): void
    {
        $extension = new ALPNExtension(['http/1.1', 'h2']);

        $extension->removeProtocol('grpc');

        $this->assertEquals(['http/1.1', 'h2'], $extension->getProtocols());
    }

    public function testHasProtocolWithExistingProtocolReturnsTrue(): void
    {
        $extension = new ALPNExtension(['http/1.1', 'h2']);

        $this->assertTrue($extension->hasProtocol('http/1.1'));
        $this->assertTrue($extension->hasProtocol('h2'));
    }

    public function testHasProtocolWithNonExistingProtocolReturnsFalse(): void
    {
        $extension = new ALPNExtension(['http/1.1', 'h2']);

        $this->assertFalse($extension->hasProtocol('grpc'));
        $this->assertFalse($extension->hasProtocol('h3'));
    }

    public function testSetSelectedProtocolWithValidProtocolSetsProtocol(): void
    {
        $extension = new ALPNExtension(['http/1.1', 'h2']);

        $extension->setSelectedProtocol('h2');
        $this->assertEquals('h2', $extension->getSelectedProtocol());
    }

    public function testSetSelectedProtocolWithInvalidProtocolThrowsException(): void
    {
        $extension = new ALPNExtension(['http/1.1', 'h2']);

        $this->expectException(ALPNException::class);
        $this->expectExceptionMessage('not found');

        $extension->setSelectedProtocol('grpc');
    }

    public function testClearSelectedProtocolClearsSelection(): void
    {
        $extension = new ALPNExtension(['http/1.1', 'h2']);
        $extension->setSelectedProtocol('h2');

        $result = $extension->clearSelectedProtocol();

        $this->assertSame($extension, $result);
        $this->assertNull($extension->getSelectedProtocol());
    }

    public function testEncodeWithValidProtocolsReturnsCorrectData(): void
    {
        $extension = new ALPNExtension(['http/1.1', 'h2']);

        $encoded = $extension->encode();

        // 验证编码格式
        $this->assertGreaterThan(0, strlen($encoded));

        // 验证协议列表长度字段
        $unpacked = unpack('n', substr($encoded, 0, 2));
        $this->assertNotFalse($unpacked);
        $protocolListLength = $unpacked[1];
        $this->assertEquals(strlen($encoded) - 2, $protocolListLength);
    }

    public function testEncodeWithEmptyProtocolsThrowsException(): void
    {
        $extension = new ALPNExtension();

        $this->expectException(ALPNException::class);
        $this->expectExceptionMessage('No protocols to encode');

        $extension->encode();
    }

    public function testDecodeWithValidDataReturnsExtension(): void
    {
        $originalExtension = new ALPNExtension(['http/1.1', 'h2', 'grpc']);
        $encoded = $originalExtension->encode();

        $decodedExtension = ALPNExtension::decode($encoded);

        $this->assertEquals($originalExtension->getProtocols(), $decodedExtension->getProtocols());
    }

    public function testDecodeWithEmptyDataThrowsException(): void
    {
        $this->expectException(ALPNException::class);
        $this->expectExceptionMessage('Data too short');

        ALPNExtension::decode('');
    }

    public function testDecodeWithShortDataThrowsException(): void
    {
        $this->expectException(ALPNException::class);
        $this->expectExceptionMessage('Data too short');

        ALPNExtension::decode("\x00");
    }

    public function testDecodeWithIncompleteDataThrowsException(): void
    {
        $this->expectException(ALPNException::class);
        $this->expectExceptionMessage('Incomplete');

        // 声明长度为10但只提供2字节数据
        ALPNExtension::decode("\x00\x0A\x08");
    }

    public function testDecodeWithEmptyProtocolNameThrowsException(): void
    {
        $this->expectException(ALPNException::class);
        $this->expectExceptionMessage('cannot be empty');

        // 协议名称长度为0
        ALPNExtension::decode("\x00\x01\x00");
    }

    public function testRoundTripEncodeDecodePreservesData(): void
    {
        $protocols = ['http/1.1', 'h2', 'grpc', 'websocket'];
        $originalExtension = new ALPNExtension($protocols);

        $encoded = $originalExtension->encode();
        $decodedExtension = ALPNExtension::decode($encoded);

        $this->assertEquals($protocols, $decodedExtension->getProtocols());
    }

    public function testNegotiateWithMatchingProtocolsReturnsServerPreferred(): void
    {
        $clientProtocols = ['http/1.1', 'h2', 'grpc'];
        $serverProtocols = ['grpc', 'h2', 'h3'];

        $result = ALPNExtension::negotiate($clientProtocols, $serverProtocols);

        $this->assertEquals('grpc', $result);
    }

    public function testNegotiateWithNoMatchingProtocolsThrowsException(): void
    {
        $clientProtocols = ['http/1.1', 'h2'];
        $serverProtocols = ['grpc', 'h3'];

        $this->expectException(ALPNException::class);
        $this->expectExceptionMessage('negotiation failed');

        ALPNExtension::negotiate($clientProtocols, $serverProtocols);
    }

    public function testForClientCreatesClientExtension(): void
    {
        $protocols = ['http/1.1', 'h2'];

        $extension = ALPNExtension::forClient($protocols);

        $this->assertEquals($protocols, $extension->getProtocols());
        $this->assertNull($extension->getSelectedProtocol());
    }

    public function testForServerCreatesServerExtension(): void
    {
        $selectedProtocol = 'h2';

        $extension = ALPNExtension::forServer($selectedProtocol);

        $this->assertEquals([$selectedProtocol], $extension->getProtocols());
        $this->assertEquals($selectedProtocol, $extension->getSelectedProtocol());
    }

    public function testToStringWithoutSelectionReturnsCorrectFormat(): void
    {
        $extension = new ALPNExtension(['http/1.1', 'h2']);

        $string = (string) $extension;

        $this->assertStringContainsString('ALPN Extension', $string);
        $this->assertStringContainsString('http/1.1', $string);
        $this->assertStringContainsString('h2', $string);
        $this->assertStringNotContainsString('selected:', $string);
    }

    public function testToStringWithSelectionIncludesSelection(): void
    {
        $extension = new ALPNExtension(['http/1.1', 'h2']);
        $extension->setSelectedProtocol('h2');

        $string = (string) $extension;

        $this->assertStringContainsString('ALPN Extension', $string);
        $this->assertStringContainsString('selected: h2', $string);
    }

    public function testEncodeWithSpecialCharactersHandlesCorrectly(): void
    {
        $protocols = ['test/1.0', 'test+proto', 'test_proto-2'];
        $extension = new ALPNExtension($protocols);

        $encoded = $extension->encode();
        $decoded = ALPNExtension::decode($encoded);

        $this->assertEquals($protocols, $decoded->getProtocols());
    }

    public function testEncodeWithMaxLengthProtocolHandlesCorrectly(): void
    {
        $maxLengthProtocol = str_repeat('a', 255);
        $extension = new ALPNExtension([$maxLengthProtocol]);

        $encoded = $extension->encode();
        $decoded = ALPNExtension::decode($encoded);

        $this->assertEquals([$maxLengthProtocol], $decoded->getProtocols());
    }
}
