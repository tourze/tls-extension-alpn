<?php

namespace Tourze\TLSExtensionALPN\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\TLSExtensionALPN\ALPNExtension;
use Tourze\TLSExtensionALPN\ALPNManager;
use Tourze\TLSExtensionALPN\ALPNNegotiator;
use Tourze\TLSExtensionALPN\Exception\ALPNException;
use Tourze\TLSExtensionALPN\Protocol\CommonProtocols;

/**
 * @internal
 */
#[CoversClass(ALPNManager::class)]
final class ALPNManagerTest extends TestCase
{
    public function testConstructorWithDefaultNegotiatorCreatesManager(): void
    {
        $manager = new ALPNManager();

        $this->assertInstanceOf(ALPNNegotiator::class, $manager->getNegotiator());
        $this->assertTrue($manager->hasProtocolSet('web'));
        $this->assertTrue($manager->hasProtocolSet('email'));
    }

    public function testConstructorWithCustomNegotiatorUsesCustom(): void
    {
        $customNegotiator = ALPNNegotiator::clientPreference();
        $manager = new ALPNManager($customNegotiator);

        $this->assertSame($customNegotiator, $manager->getNegotiator());
    }

    public function testRegisterProtocolSetWithValidProtocolsRegistersSet(): void
    {
        $manager = new ALPNManager();
        $protocols = ['custom/1.0', 'custom/2.0'];

        $result = $manager->registerProtocolSet('custom', $protocols);

        $this->assertSame($manager, $result);
        $this->assertTrue($manager->hasProtocolSet('custom'));
        $this->assertEquals($protocols, $manager->getProtocolSet('custom'));
    }

    public function testRegisterProtocolSetWithDuplicatesRemoveDuplicates(): void
    {
        $manager = new ALPNManager();
        $protocols = ['custom/1.0', 'custom/2.0', 'custom/1.0'];

        $manager->registerProtocolSet('custom', $protocols);

        $this->assertEquals(['custom/1.0', 'custom/2.0'], $manager->getProtocolSet('custom'));
    }

    public function testRegisterProtocolSetWithEmptyProtocolThrowsException(): void
    {
        $manager = new ALPNManager();

        $this->expectException(ALPNException::class);
        $this->expectExceptionMessage('cannot be empty');

        $manager->registerProtocolSet('invalid', ['valid', '']);
    }

    public function testGetProtocolSetWithExistingSetReturnsProtocols(): void
    {
        $manager = new ALPNManager();

        $webProtocols = $manager->getProtocolSet('web');

        $this->assertContains(CommonProtocols::HTTP_1_1, $webProtocols);
        $this->assertContains(CommonProtocols::HTTP_2, $webProtocols);
    }

    public function testGetProtocolSetWithNonExistingSetThrowsException(): void
    {
        $manager = new ALPNManager();

        $this->expectException(ALPNException::class);
        $this->expectExceptionMessage('not found');

        $manager->getProtocolSet('nonexistent');
    }

    public function testGetProtocolSetNamesReturnsAllNames(): void
    {
        $manager = new ALPNManager();

        $names = $manager->getProtocolSetNames();

        $this->assertContains('web', $names);
        $this->assertContains('email', $names);
        $this->assertContains('all', $names);
        $this->assertContains('modern', $names);
    }

    public function testHasProtocolSetWithExistingSetReturnsTrue(): void
    {
        $manager = new ALPNManager();

        $this->assertTrue($manager->hasProtocolSet('web'));
        $this->assertTrue($manager->hasProtocolSet('email'));
    }

    public function testHasProtocolSetWithNonExistingSetReturnsFalse(): void
    {
        $manager = new ALPNManager();

        $this->assertFalse($manager->hasProtocolSet('nonexistent'));
    }

    public function testRemoveProtocolSetRemovesSet(): void
    {
        $manager = new ALPNManager();
        $manager->registerProtocolSet('temporary', ['temp/1.0']);

        $result = $manager->removeProtocolSet('temporary');

        $this->assertSame($manager, $result);
        $this->assertFalse($manager->hasProtocolSet('temporary'));
    }

    public function testCreateExtensionWithProtocolSetNameCreatesExtension(): void
    {
        $manager = new ALPNManager();

        $extension = $manager->createExtension('web');

        $this->assertInstanceOf(ALPNExtension::class, $extension);
        $webProtocols = $manager->getProtocolSet('web');
        $this->assertEquals($webProtocols, $extension->getProtocols());
    }

    public function testCreateExtensionWithProtocolArrayCreatesExtension(): void
    {
        $manager = new ALPNManager();
        $protocols = ['custom/1.0', 'custom/2.0'];

        $extension = $manager->createExtension($protocols);

        $this->assertInstanceOf(ALPNExtension::class, $extension);
        $this->assertEquals($protocols, $extension->getProtocols());
    }

    public function testNegotiateWithProtocolSetNamesPerformsNegotiation(): void
    {
        $manager = new ALPNManager();
        $manager->registerProtocolSet('client', ['http/1.1', 'h2']);
        $manager->registerProtocolSet('server', ['h2', 'grpc']);

        $result = $manager->negotiate('client', 'server');

        $this->assertEquals('h2', $result);
    }

    public function testNegotiateWithProtocolArraysPerformsNegotiation(): void
    {
        $manager = new ALPNManager();
        $clientProtocols = ['http/1.1', 'h2'];
        $serverProtocols = ['h2', 'grpc'];

        $result = $manager->negotiate($clientProtocols, $serverProtocols);

        $this->assertEquals('h2', $result);
    }

    public function testNegotiateWithMixedTypesPerformsNegotiation(): void
    {
        $manager = new ALPNManager();
        $manager->registerProtocolSet('client', ['http/1.1', 'h2']);
        $serverProtocols = ['h2', 'grpc'];

        $result = $manager->negotiate('client', $serverProtocols);

        $this->assertEquals('h2', $result);
    }

    public function testIsCompatibleWithCompatibleProtocolsReturnsTrue(): void
    {
        $manager = new ALPNManager();
        $manager->registerProtocolSet('client', ['http/1.1', 'h2']);
        $manager->registerProtocolSet('server', ['h2', 'grpc']);

        $result = $manager->isCompatible('client', 'server');

        $this->assertTrue($result);
    }

    public function testIsCompatibleWithIncompatibleProtocolsReturnsFalse(): void
    {
        $manager = new ALPNManager();
        $manager->registerProtocolSet('client', ['http/1.1']);
        $manager->registerProtocolSet('server', ['grpc']);

        $result = $manager->isCompatible('client', 'server');

        $this->assertFalse($result);
    }

    public function testSetNegotiatorChangesNegotiator(): void
    {
        $manager = new ALPNManager();
        $newNegotiator = ALPNNegotiator::clientPreference();

        $manager->setNegotiator($newNegotiator);
        $this->assertSame($newNegotiator, $manager->getNegotiator());
    }

    public function testAnalyzeProtocolsWithMixedProtocolsReturnsAnalysis(): void
    {
        $manager = new ALPNManager();
        $protocols = [
            CommonProtocols::HTTP_2,
            CommonProtocols::SPDY_2,  // deprecated
            'unknown-protocol',       // unknown
        ];

        $analysis = $manager->analyzeProtocols($protocols);

        $this->assertEquals(3, $analysis['total']);
        $this->assertEquals(2, $analysis['supported']);
        $this->assertEquals(1, $analysis['deprecated']);
        $this->assertEquals(1, $analysis['unknown']);

        $this->assertArrayHasKey('details', $analysis);
        $this->assertCount(3, $analysis['details']);

        // 检查详细信息
        $this->assertTrue($analysis['details'][CommonProtocols::HTTP_2]['supported']);
        $this->assertFalse($analysis['details'][CommonProtocols::HTTP_2]['deprecated']);

        $this->assertTrue($analysis['details'][CommonProtocols::SPDY_2]['supported']);
        $this->assertTrue($analysis['details'][CommonProtocols::SPDY_2]['deprecated']);

        $this->assertFalse($analysis['details']['unknown-protocol']['supported']);
        $this->assertFalse($analysis['details']['unknown-protocol']['deprecated']);
    }

    public function testGetRecommendedConfigurationsReturnsConfigurations(): void
    {
        $manager = new ALPNManager();

        $configurations = $manager->getRecommendedConfigurations();

        $this->assertArrayHasKey('web', $configurations);
        $this->assertArrayHasKey('api', $configurations);
        $this->assertArrayHasKey('secure', $configurations);

        $this->assertContains(CommonProtocols::HTTP_2, $configurations['web']);
        $this->assertContains(CommonProtocols::GRPC, $configurations['api']);
        $this->assertContains(CommonProtocols::HTTP_3, $configurations['secure']);
    }

    public function testValidateConfigurationWithEmptyProtocolsReturnsInvalid(): void
    {
        $manager = new ALPNManager();

        $result = $manager->validateConfiguration([]);

        $this->assertFalse($result['valid']);
        $this->assertContains('No protocols specified', $result['issues']);
    }

    public function testValidateConfigurationWithValidProtocolsReturnsValid(): void
    {
        $manager = new ALPNManager();
        $protocols = [CommonProtocols::HTTP_2, CommonProtocols::HTTP_1_1];

        $result = $manager->validateConfiguration($protocols);

        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('issues', $result);
        $this->assertArrayHasKey('recommendations', $result);
    }

    public function testValidateConfigurationWithDeprecatedProtocolsFlagsIssues(): void
    {
        $manager = new ALPNManager();
        $protocols = [CommonProtocols::HTTP_2, CommonProtocols::SPDY_2];

        $result = $manager->validateConfiguration($protocols);

        $this->assertStringContainsString('deprecated protocol', $result['issues'][0]);
        $this->assertStringContainsString('removing deprecated', $result['recommendations'][0]);
    }

    public function testValidateConfigurationWithoutModernProtocolsRecommendsUpgrade(): void
    {
        $manager = new ALPNManager();
        $protocols = [CommonProtocols::HTTP_1_1];

        $result = $manager->validateConfiguration($protocols);

        $recommendationText = implode(' ', $result['recommendations']);
        $this->assertStringContainsString('HTTP/2', $recommendationText);
    }

    public function testValidateConfigurationWithoutFallbackRecommendsFallback(): void
    {
        $manager = new ALPNManager();
        $protocols = [CommonProtocols::HTTP_2];

        $result = $manager->validateConfiguration($protocols);

        $recommendationText = implode(' ', $result['recommendations']);
        $this->assertStringContainsString('fallback', $recommendationText);
    }

    public function testCreateWebConfigurationReturnsConfiguredManager(): void
    {
        $manager = ALPNManager::createWebConfiguration();

        $this->assertInstanceOf(ALPNManager::class, $manager);
        $this->assertTrue($manager->hasProtocolSet('web'));
    }

    public function testCreateApiConfigurationReturnsConfiguredManager(): void
    {
        $manager = ALPNManager::createApiConfiguration();

        $this->assertInstanceOf(ALPNManager::class, $manager);
        $this->assertTrue($manager->hasProtocolSet('default'));

        $defaultProtocols = $manager->getProtocolSet('default');
        $this->assertContains(CommonProtocols::HTTP_2, $defaultProtocols);
        $this->assertContains(CommonProtocols::GRPC, $defaultProtocols);
        $this->assertContains(CommonProtocols::HTTP_1_1, $defaultProtocols);
    }

    public function testProtocolSetsInitializationContainsExpectedSets(): void
    {
        $manager = new ALPNManager();

        // 验证预定义的协议集
        $this->assertTrue($manager->hasProtocolSet('web'));
        $this->assertTrue($manager->hasProtocolSet('email'));
        $this->assertTrue($manager->hasProtocolSet('all'));
        $this->assertTrue($manager->hasProtocolSet('modern'));

        // 验证modern协议集内容
        $modernProtocols = $manager->getProtocolSet('modern');
        $this->assertContains(CommonProtocols::HTTP_2, $modernProtocols);
        $this->assertContains(CommonProtocols::HTTP_3, $modernProtocols);
        $this->assertContains(CommonProtocols::GRPC, $modernProtocols);
        $this->assertContains(CommonProtocols::WEBSOCKET, $modernProtocols);
    }
}
