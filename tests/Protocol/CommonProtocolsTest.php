<?php

namespace Tourze\TLSExtensionALPN\Tests\Protocol;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\TLSExtensionALPN\Protocol\CommonProtocols;

/**
 * @internal
 */
#[CoversClass(CommonProtocols::class)]
final class CommonProtocolsTest extends TestCase
{
    public function testConstantsHaveCorrectValues(): void
    {
        $this->assertEquals('http/1.1', CommonProtocols::HTTP_1_1);
        $this->assertEquals('h2', CommonProtocols::HTTP_2);
        $this->assertEquals('h2c', CommonProtocols::HTTP_2_CLEARTEXT);
        $this->assertEquals('h3', CommonProtocols::HTTP_3);
        $this->assertEquals('websocket', CommonProtocols::WEBSOCKET);
        $this->assertEquals('grpc', CommonProtocols::GRPC);
        $this->assertEquals('mqtt', CommonProtocols::MQTT);
    }

    public function testDeprecatedProtocolsHaveCorrectValues(): void
    {
        $this->assertEquals('spdy/2', CommonProtocols::SPDY_2);
        $this->assertEquals('spdy/3', CommonProtocols::SPDY_3);
        $this->assertEquals('spdy/3.1', CommonProtocols::SPDY_3_1);
    }

    public function testEmailProtocolsHaveCorrectValues(): void
    {
        $this->assertEquals('imap', CommonProtocols::IMAP);
        $this->assertEquals('pop3', CommonProtocols::POP3);
        $this->assertEquals('smtp', CommonProtocols::SMTP);
    }

    public function testGetAllProtocolsReturnsAllConstants(): void
    {
        $protocols = CommonProtocols::getAllProtocols();
        $this->assertContains(CommonProtocols::HTTP_1_1, $protocols);
        $this->assertContains(CommonProtocols::HTTP_2, $protocols);
        $this->assertContains(CommonProtocols::HTTP_3, $protocols);
        $this->assertContains(CommonProtocols::GRPC, $protocols);
        $this->assertContains(CommonProtocols::SPDY_2, $protocols);
        $this->assertGreaterThan(10, count($protocols));
    }

    public function testGetHttpProtocolsReturnsHttpProtocolsOnly(): void
    {
        $httpProtocols = CommonProtocols::getHttpProtocols();

        $expected = [
            CommonProtocols::HTTP_1_1,
            CommonProtocols::HTTP_2,
            CommonProtocols::HTTP_2_CLEARTEXT,
            CommonProtocols::HTTP_3,
        ];

        $this->assertEquals($expected, $httpProtocols);
    }

    public function testGetDeprecatedProtocolsReturnsDeprecatedOnly(): void
    {
        $deprecatedProtocols = CommonProtocols::getDeprecatedProtocols();

        $expected = [
            CommonProtocols::SPDY_2,
            CommonProtocols::SPDY_3,
            CommonProtocols::SPDY_3_1,
        ];

        $this->assertEquals($expected, $deprecatedProtocols);
    }

    public function testGetEmailProtocolsReturnsEmailProtocolsOnly(): void
    {
        $emailProtocols = CommonProtocols::getEmailProtocols();

        $expected = [
            CommonProtocols::IMAP,
            CommonProtocols::POP3,
            CommonProtocols::SMTP,
        ];

        $this->assertEquals($expected, $emailProtocols);
    }

    public function testIsHttpProtocolWithHttpProtocolsReturnsTrue(): void
    {
        $this->assertTrue(CommonProtocols::isHttpProtocol(CommonProtocols::HTTP_1_1));
        $this->assertTrue(CommonProtocols::isHttpProtocol(CommonProtocols::HTTP_2));
        $this->assertTrue(CommonProtocols::isHttpProtocol(CommonProtocols::HTTP_2_CLEARTEXT));
        $this->assertTrue(CommonProtocols::isHttpProtocol(CommonProtocols::HTTP_3));
    }

    public function testIsHttpProtocolWithNonHttpProtocolsReturnsFalse(): void
    {
        $this->assertFalse(CommonProtocols::isHttpProtocol(CommonProtocols::GRPC));
        $this->assertFalse(CommonProtocols::isHttpProtocol(CommonProtocols::WEBSOCKET));
        $this->assertFalse(CommonProtocols::isHttpProtocol(CommonProtocols::MQTT));
        $this->assertFalse(CommonProtocols::isHttpProtocol(CommonProtocols::SPDY_2));
        $this->assertFalse(CommonProtocols::isHttpProtocol('unknown'));
    }

    public function testIsDeprecatedWithDeprecatedProtocolsReturnsTrue(): void
    {
        $this->assertTrue(CommonProtocols::isDeprecated(CommonProtocols::SPDY_2));
        $this->assertTrue(CommonProtocols::isDeprecated(CommonProtocols::SPDY_3));
        $this->assertTrue(CommonProtocols::isDeprecated(CommonProtocols::SPDY_3_1));
    }

    public function testIsDeprecatedWithNonDeprecatedProtocolsReturnsFalse(): void
    {
        $this->assertFalse(CommonProtocols::isDeprecated(CommonProtocols::HTTP_1_1));
        $this->assertFalse(CommonProtocols::isDeprecated(CommonProtocols::HTTP_2));
        $this->assertFalse(CommonProtocols::isDeprecated(CommonProtocols::GRPC));
        $this->assertFalse(CommonProtocols::isDeprecated('unknown'));
    }

    public function testIsEmailProtocolWithEmailProtocolsReturnsTrue(): void
    {
        $this->assertTrue(CommonProtocols::isEmailProtocol(CommonProtocols::IMAP));
        $this->assertTrue(CommonProtocols::isEmailProtocol(CommonProtocols::POP3));
        $this->assertTrue(CommonProtocols::isEmailProtocol(CommonProtocols::SMTP));
    }

    public function testIsEmailProtocolWithNonEmailProtocolsReturnsFalse(): void
    {
        $this->assertFalse(CommonProtocols::isEmailProtocol(CommonProtocols::HTTP_1_1));
        $this->assertFalse(CommonProtocols::isEmailProtocol(CommonProtocols::GRPC));
        $this->assertFalse(CommonProtocols::isEmailProtocol(CommonProtocols::WEBSOCKET));
        $this->assertFalse(CommonProtocols::isEmailProtocol('unknown'));
    }

    public function testGetProtocolDescriptionWithKnownProtocolsReturnsDescriptions(): void
    {
        $this->assertStringContainsString('Hypertext Transfer Protocol',
            CommonProtocols::getProtocolDescription(CommonProtocols::HTTP_1_1));
        $this->assertStringContainsString('version 1.1',
            CommonProtocols::getProtocolDescription(CommonProtocols::HTTP_1_1));

        $this->assertStringContainsString('version 2',
            CommonProtocols::getProtocolDescription(CommonProtocols::HTTP_2));

        $this->assertStringContainsString('gRPC',
            CommonProtocols::getProtocolDescription(CommonProtocols::GRPC));

        $this->assertStringContainsString('deprecated',
            CommonProtocols::getProtocolDescription(CommonProtocols::SPDY_2));
    }

    public function testGetProtocolDescriptionWithUnknownProtocolReturnsUnknown(): void
    {
        $description = CommonProtocols::getProtocolDescription('unknown-protocol');

        $this->assertEquals('Unknown protocol', $description);
    }

    public function testGetProtocolRFCWithKnownProtocolsReturnsRFCs(): void
    {
        $http11Rfc = CommonProtocols::getProtocolRFC(CommonProtocols::HTTP_1_1);
        $this->assertNotNull($http11Rfc);
        $this->assertStringContainsString('RFC', $http11Rfc);

        $this->assertEquals('RFC 7540',
            CommonProtocols::getProtocolRFC(CommonProtocols::HTTP_2));
        $this->assertEquals('RFC 9114',
            CommonProtocols::getProtocolRFC(CommonProtocols::HTTP_3));
        $this->assertEquals('RFC 6455',
            CommonProtocols::getProtocolRFC(CommonProtocols::WEBSOCKET));
    }

    public function testGetProtocolRFCWithUnknownProtocolReturnsNull(): void
    {
        $rfc = CommonProtocols::getProtocolRFC('unknown-protocol');

        $this->assertNull($rfc);
    }

    public function testGetProtocolRFCWithGrpcReturnsSpecification(): void
    {
        $rfc = CommonProtocols::getProtocolRFC(CommonProtocols::GRPC);

        $this->assertNotNull($rfc);
        $this->assertStringContainsString('gRPC', $rfc);
        $this->assertStringContainsString('Specification', $rfc);
    }

    public function testProtocolCollectionsDoNotOverlap(): void
    {
        $httpProtocols = CommonProtocols::getHttpProtocols();
        $deprecatedProtocols = CommonProtocols::getDeprecatedProtocols();
        $emailProtocols = CommonProtocols::getEmailProtocols();

        // HTTP和已废弃协议不应重叠
        $httpDeprecatedIntersection = array_intersect($httpProtocols, $deprecatedProtocols);
        $this->assertEmpty($httpDeprecatedIntersection);

        // HTTP和邮件协议不应重叠
        $httpEmailIntersection = array_intersect($httpProtocols, $emailProtocols);
        $this->assertEmpty($httpEmailIntersection);

        // 已废弃和邮件协议不应重叠
        $deprecatedEmailIntersection = array_intersect($deprecatedProtocols, $emailProtocols);
        $this->assertEmpty($deprecatedEmailIntersection);
    }

    public function testAllProtocolCollectionsAreSubsetsOfAll(): void
    {
        $allProtocols = CommonProtocols::getAllProtocols();
        $httpProtocols = CommonProtocols::getHttpProtocols();
        $deprecatedProtocols = CommonProtocols::getDeprecatedProtocols();
        $emailProtocols = CommonProtocols::getEmailProtocols();

        // 所有子集都应该是全集的子集
        $this->assertEmpty(array_diff($httpProtocols, $allProtocols));
        $this->assertEmpty(array_diff($deprecatedProtocols, $allProtocols));
        $this->assertEmpty(array_diff($emailProtocols, $allProtocols));
    }

    public function testGetAllProtocolsContainsExpectedProtocols(): void
    {
        $allProtocols = CommonProtocols::getAllProtocols();

        // 验证包含关键协议
        $expectedProtocols = [
            CommonProtocols::HTTP_1_1,
            CommonProtocols::HTTP_2,
            CommonProtocols::HTTP_3,
            CommonProtocols::WEBSOCKET,
            CommonProtocols::GRPC,
            CommonProtocols::MQTT,
            CommonProtocols::SPDY_2,
            CommonProtocols::IMAP,
            CommonProtocols::SMTP,
            CommonProtocols::ACME_TLS,
        ];

        foreach ($expectedProtocols as $protocol) {
            $this->assertContains($protocol, $allProtocols, "Protocol {$protocol} should be in all protocols");
        }
    }

    public function testProtocolConstantsAreUnique(): void
    {
        $allProtocols = CommonProtocols::getAllProtocols();
        $uniqueProtocols = array_unique($allProtocols);

        $this->assertCount(count($allProtocols), $uniqueProtocols, 'All protocol constants should be unique');
    }
}
