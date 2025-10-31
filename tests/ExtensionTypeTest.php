<?php

namespace Tourze\TLSExtensionALPN\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\TLSExtensionALPN\ExtensionType;

/**
 * @internal
 */
#[CoversClass(ExtensionType::class)]
final class ExtensionTypeTest extends AbstractEnumTestCase
{
    public function testAlpnHasCorrectValue(): void
    {
        $this->assertEquals(16, ExtensionType::ALPN->value);
    }

    public function testNpnHasCorrectValue(): void
    {
        $this->assertEquals(13172, ExtensionType::NPN->value);
    }

    public function testGetNameAlpnReturnsCorrectName(): void
    {
        $this->assertEquals('application_layer_protocol_negotiation', ExtensionType::ALPN->getName());
    }

    public function testGetNameNpnReturnsCorrectName(): void
    {
        $this->assertEquals('next_protocol_negotiation', ExtensionType::NPN->getName());
    }

    public function testGetDescriptionAlpnReturnsCorrectDescription(): void
    {
        $description = ExtensionType::ALPN->getDescription();
        $this->assertStringContainsString('Application-Layer Protocol Negotiation', $description);
        $this->assertStringContainsString('RFC 7301', $description);
    }

    public function testGetDescriptionNpnReturnsCorrectDescription(): void
    {
        $description = ExtensionType::NPN->getDescription();
        $this->assertStringContainsString('Next Protocol Negotiation', $description);
        $this->assertStringContainsString('deprecated', $description);
    }

    public function testCasesContainsAllExpectedValues(): void
    {
        $cases = ExtensionType::cases();
        $values = array_map(fn ($case) => $case->value, $cases);

        $this->assertContains(16, $values);
        $this->assertContains(13172, $values);
        $this->assertCount(2, $cases);
    }

    public function testFromWithValidValueReturnsCorrectCase(): void
    {
        $this->assertEquals(ExtensionType::ALPN, ExtensionType::from(16));
        $this->assertEquals(ExtensionType::NPN, ExtensionType::from(13172));
    }

    public function testTryFromWithValidValueReturnsCorrectCase(): void
    {
        $this->assertEquals(ExtensionType::ALPN, ExtensionType::tryFrom(16));
        $this->assertEquals(ExtensionType::NPN, ExtensionType::tryFrom(13172));
    }

    public function testToArray(): void
    {
        // 测试toArray方法，这个方法由ItemTrait提供
        $alpnExtension = ExtensionType::ALPN;
        $array = $alpnExtension->toArray();

        // 验证返回的是数组
        $this->assertIsArray($array);

        // 验证ALPN枚举项的数组结构
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertEquals(16, $array['value']);
        $this->assertEquals('Application-Layer Protocol Negotiation (RFC 7301)', $array['label']);

        // 测试NPN枚举项
        $npnArray = ExtensionType::NPN->toArray();
        $this->assertEquals(13172, $npnArray['value']);
        $this->assertEquals('Next Protocol Negotiation (deprecated)', $npnArray['label']);
    }
}
