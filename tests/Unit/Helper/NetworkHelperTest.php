<?php

declare(strict_types=1);

namespace Tests\Unit\Helper;

use Gemvc\Helper\NetworkHelper;
use PHPUnit\Framework\TestCase;

class NetworkHelperTest extends TestCase
{
    public function testGetNetworkInterfacesReturnsFilteredArray(): void
    {
        $interfaces = NetworkHelper::getNetworkInterfaces();

        $this->assertIsArray($interfaces);
        foreach ($interfaces as $interface) {
            $this->assertIsString($interface);
            $this->assertNotSame('', $interface);
            $this->assertNotContains($interface, ['lo', 'lo0', 'Loopback']);
        }
    }

    public function testGetNetworkStatsReturnsExpectedStructure(): void
    {
        $stats = NetworkHelper::getNetworkStats();

        $this->assertArrayHasKey('interfaces', $stats);
        $this->assertArrayHasKey('totals', $stats);
        $this->assertIsArray($stats['interfaces']);
        $this->assertArrayHasKey('bytes_received', $stats['totals']);
        $this->assertArrayHasKey('bytes_received_mb', $stats['totals']);
        $this->assertArrayHasKey('bytes_sent', $stats['totals']);
        $this->assertArrayHasKey('bytes_sent_mb', $stats['totals']);
        $this->assertArrayHasKey('packets_received', $stats['totals']);
        $this->assertArrayHasKey('packets_sent', $stats['totals']);
    }

    public function testGetNetworkStatsTotalsAreNonNegative(): void
    {
        $stats = NetworkHelper::getNetworkStats();

        $this->assertGreaterThanOrEqual(0, $stats['totals']['bytes_received']);
        $this->assertGreaterThanOrEqual(0, $stats['totals']['bytes_sent']);
        $this->assertGreaterThanOrEqual(0, $stats['totals']['packets_received']);
        $this->assertGreaterThanOrEqual(0, $stats['totals']['packets_sent']);
    }

    public function testGetInterfaceStatsFallbackForUnknownInterface(): void
    {
        $method = new \ReflectionMethod(NetworkHelper::class, 'getInterfaceStats');
        $result = $method->invoke(null, 'gemvc-nonexistent-interface-xyz');

        $this->assertSame('gemvc-nonexistent-interface-xyz', $result['interface']);
        $this->assertSame(0, $result['bytes_received']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testGetInterfaceStatsForRealInterfaceWhenAvailable(): void
    {
        $interfaces = NetworkHelper::getNetworkInterfaces();
        if ($interfaces === []) {
            $this->markTestSkipped('No active network interfaces detected on this host');
        }

        $method = new \ReflectionMethod(NetworkHelper::class, 'getInterfaceStats');
        $result = $method->invoke(null, $interfaces[0]);

        $this->assertSame($interfaces[0], $result['interface']);
        $this->assertArrayHasKey('bytes_received', $result);
        $this->assertArrayHasKey('bytes_sent', $result);
        $this->assertArrayHasKey('packets_received', $result);
        $this->assertArrayHasKey('packets_sent', $result);
    }
}
