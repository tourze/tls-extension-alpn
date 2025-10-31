<?php

namespace Tourze\TLSExtensionALPN\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\TLSExtensionALPN\ALPNNegotiator;
use Tourze\TLSExtensionALPN\Exception\ALPNException;

/**
 * @internal
 */
#[CoversClass(ALPNNegotiator::class)]
final class ALPNNegotiatorTest extends TestCase
{
    public function testConstructorWithDefaultStrategyUsesServerPreference(): void
    {
        $negotiator = new ALPNNegotiator();

        $this->assertEquals(ALPNNegotiator::STRATEGY_SERVER_PREFERENCE, $negotiator->getStrategy());
    }

    public function testConstructorWithExplicitStrategySetsStrategy(): void
    {
        $negotiator = new ALPNNegotiator(ALPNNegotiator::STRATEGY_CLIENT_PREFERENCE);

        $this->assertEquals(ALPNNegotiator::STRATEGY_CLIENT_PREFERENCE, $negotiator->getStrategy());
    }

    public function testSetStrategyWithValidStrategySetsStrategy(): void
    {
        $negotiator = new ALPNNegotiator();

        $negotiator->setStrategy(ALPNNegotiator::STRATEGY_CLIENT_PREFERENCE);
        $this->assertEquals(ALPNNegotiator::STRATEGY_CLIENT_PREFERENCE, $negotiator->getStrategy());
    }

    public function testSetStrategyWithInvalidStrategyThrowsException(): void
    {
        $negotiator = new ALPNNegotiator();

        $this->expectException(ALPNException::class);
        $this->expectExceptionMessage('Invalid negotiation strategy');

        $negotiator->setStrategy('invalid_strategy');
    }

    public function testNegotiateServerPreferenceReturnsServerFirstMatch(): void
    {
        $negotiator = new ALPNNegotiator(ALPNNegotiator::STRATEGY_SERVER_PREFERENCE);
        $clientProtocols = ['http/1.1', 'h2', 'grpc'];
        $serverProtocols = ['grpc', 'h2', 'h3'];

        $result = $negotiator->negotiate($clientProtocols, $serverProtocols);

        $this->assertEquals('grpc', $result);
    }

    public function testNegotiateClientPreferenceReturnsClientFirstMatch(): void
    {
        $negotiator = new ALPNNegotiator(ALPNNegotiator::STRATEGY_CLIENT_PREFERENCE);
        $clientProtocols = ['http/1.1', 'h2', 'grpc'];
        $serverProtocols = ['grpc', 'h2', 'h3'];

        $result = $negotiator->negotiate($clientProtocols, $serverProtocols);

        $this->assertEquals('h2', $result);
    }

    public function testNegotiateWithEmptyClientProtocolsThrowsException(): void
    {
        $negotiator = new ALPNNegotiator();
        $serverProtocols = ['h2', 'grpc'];

        $this->expectException(ALPNException::class);
        $this->expectExceptionMessage('Client protocols cannot be empty');

        $negotiator->negotiate([], $serverProtocols);
    }

    public function testNegotiateWithEmptyServerProtocolsThrowsException(): void
    {
        $negotiator = new ALPNNegotiator();
        $clientProtocols = ['http/1.1', 'h2'];

        $this->expectException(ALPNException::class);
        $this->expectExceptionMessage('Server protocols cannot be empty');

        $negotiator->negotiate($clientProtocols, []);
    }

    public function testNegotiateWithNoCommonProtocolsThrowsException(): void
    {
        $negotiator = new ALPNNegotiator();
        $clientProtocols = ['http/1.1', 'h2'];
        $serverProtocols = ['grpc', 'h3'];

        $this->expectException(ALPNException::class);
        $this->expectExceptionMessage('negotiation failed');

        $negotiator->negotiate($clientProtocols, $serverProtocols);
    }

    public function testNegotiateBatchWithValidPairsReturnsResults(): void
    {
        $negotiator = new ALPNNegotiator(ALPNNegotiator::STRATEGY_SERVER_PREFERENCE);
        $pairs = [
            ['client' => ['http/1.1', 'h2'], 'server' => ['h2', 'grpc']],
            ['client' => ['grpc', 'h3'], 'server' => ['grpc', 'h2']],
        ];

        $results = $negotiator->negotiateBatch($pairs);

        $this->assertEquals(['h2', 'grpc'], $results);
    }

    public function testNegotiateBatchWithInvalidPairThrowsException(): void
    {
        $negotiator = new ALPNNegotiator();
        $pairs = [
            ['client' => ['http/1.1', 'h2']],  // Missing 'server' key
        ];

        $this->expectException(ALPNException::class);
        $this->expectExceptionMessage('Missing client or server protocols');

        /* @phpstan-ignore-next-line */
        $negotiator->negotiateBatch($pairs);
    }

    public function testNegotiateBatchWithFailedNegotiationThrowsException(): void
    {
        $negotiator = new ALPNNegotiator();
        $pairs = [
            ['client' => ['http/1.1'], 'server' => ['grpc']],  // No common protocols
        ];

        $this->expectException(ALPNException::class);
        $this->expectExceptionMessage('negotiation failed');

        $negotiator->negotiateBatch($pairs);
    }

    public function testIsCompatibleWithCommonProtocolsReturnsTrue(): void
    {
        $negotiator = new ALPNNegotiator();
        $clientProtocols = ['http/1.1', 'h2', 'grpc'];
        $serverProtocols = ['grpc', 'h3'];

        $result = $negotiator->isCompatible($clientProtocols, $serverProtocols);

        $this->assertTrue($result);
    }

    public function testIsCompatibleWithNoCommonProtocolsReturnsFalse(): void
    {
        $negotiator = new ALPNNegotiator();
        $clientProtocols = ['http/1.1', 'h2'];
        $serverProtocols = ['grpc', 'h3'];

        $result = $negotiator->isCompatible($clientProtocols, $serverProtocols);

        $this->assertFalse($result);
    }

    public function testIsCompatibleWithEmptyProtocolsReturnsFalse(): void
    {
        $negotiator = new ALPNNegotiator();

        $this->assertFalse($negotiator->isCompatible([], ['h2']));
        $this->assertFalse($negotiator->isCompatible(['h2'], []));
        $this->assertFalse($negotiator->isCompatible([], []));
    }

    public function testGetCommonProtocolsReturnsIntersection(): void
    {
        $negotiator = new ALPNNegotiator();
        $clientProtocols = ['http/1.1', 'h2', 'grpc'];
        $serverProtocols = ['grpc', 'h2', 'h3'];

        $common = $negotiator->getCommonProtocols($clientProtocols, $serverProtocols);

        $this->assertEquals(['h2', 'grpc'], $common);
    }

    public function testGetCommonProtocolsWithNoCommonReturnsEmpty(): void
    {
        $negotiator = new ALPNNegotiator();
        $clientProtocols = ['http/1.1', 'websocket'];
        $serverProtocols = ['grpc', 'h3'];

        $common = $negotiator->getCommonProtocols($clientProtocols, $serverProtocols);

        $this->assertEmpty($common);
    }

    public function testGetCommonProtocolsPreservesOrder(): void
    {
        $negotiator = new ALPNNegotiator();
        $clientProtocols = ['a', 'b', 'c', 'd'];
        $serverProtocols = ['d', 'b', 'e', 'f'];

        $common = $negotiator->getCommonProtocols($clientProtocols, $serverProtocols);

        $this->assertEquals(['b', 'd'], $common);
    }

    public function testServerPreferenceCreatesCorrectNegotiator(): void
    {
        $negotiator = ALPNNegotiator::serverPreference();

        $this->assertInstanceOf(ALPNNegotiator::class, $negotiator);
        $this->assertEquals(ALPNNegotiator::STRATEGY_SERVER_PREFERENCE, $negotiator->getStrategy());
    }

    public function testClientPreferenceCreatesCorrectNegotiator(): void
    {
        $negotiator = ALPNNegotiator::clientPreference();

        $this->assertInstanceOf(ALPNNegotiator::class, $negotiator);
        $this->assertEquals(ALPNNegotiator::STRATEGY_CLIENT_PREFERENCE, $negotiator->getStrategy());
    }

    public function testNegotiateDifferentStrategiesProduceDifferentResults(): void
    {
        $clientProtocols = ['http/1.1', 'h2', 'grpc'];
        $serverProtocols = ['grpc', 'h2', 'h3'];

        $serverPreferenceNegotiator = ALPNNegotiator::serverPreference();
        $clientPreferenceNegotiator = ALPNNegotiator::clientPreference();

        $serverResult = $serverPreferenceNegotiator->negotiate($clientProtocols, $serverProtocols);
        $clientResult = $clientPreferenceNegotiator->negotiate($clientProtocols, $serverProtocols);

        $this->assertEquals('grpc', $serverResult);
        $this->assertEquals('h2', $clientResult);
        $this->assertNotEquals($serverResult, $clientResult);
    }

    public function testNegotiateSingleCommonProtocolSameResultRegardlessOfStrategy(): void
    {
        $clientProtocols = ['http/1.1', 'websocket'];
        $serverProtocols = ['grpc', 'websocket', 'h3'];

        $serverPreferenceNegotiator = ALPNNegotiator::serverPreference();
        $clientPreferenceNegotiator = ALPNNegotiator::clientPreference();

        $serverResult = $serverPreferenceNegotiator->negotiate($clientProtocols, $serverProtocols);
        $clientResult = $clientPreferenceNegotiator->negotiate($clientProtocols, $serverProtocols);

        $this->assertEquals('websocket', $serverResult);
        $this->assertEquals('websocket', $clientResult);
        $this->assertEquals($serverResult, $clientResult);
    }

    public function testNegotiateBatchWithMixedResultsHandlesCorrectly(): void
    {
        $negotiator = new ALPNNegotiator(ALPNNegotiator::STRATEGY_SERVER_PREFERENCE);
        $pairs = [
            ['client' => ['http/1.1', 'h2'], 'server' => ['h2', 'grpc']],
            ['client' => ['websocket'], 'server' => ['websocket', 'h3']],
            ['client' => ['grpc', 'h3'], 'server' => ['h3', 'grpc']],
        ];

        $results = $negotiator->negotiateBatch($pairs);

        $this->assertEquals(['h2', 'websocket', 'h3'], $results);
    }
}
