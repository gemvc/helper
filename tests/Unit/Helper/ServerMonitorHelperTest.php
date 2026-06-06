<?php

declare(strict_types=1);

namespace Tests\Unit\Helper;

use Gemvc\Helper\ServerMonitorHelper;
use PHPUnit\Framework\TestCase;

class ServerMonitorHelperTest extends TestCase
{
    public function testGetMemoryUsageReturnsPhpMetrics(): void
    {
        $usage = ServerMonitorHelper::getMemoryUsage();

        $this->assertGreaterThan(0, $usage['php_current']);
        $this->assertGreaterThan(0, $usage['php_peak']);
        $this->assertGreaterThan(0, $usage['php_current_mb']);
        $this->assertGreaterThan(0, $usage['php_peak_mb']);
        $this->assertArrayHasKey('system_total', $usage);
        $this->assertArrayHasKey('system_usage_percent', $usage);
    }

    public function testGetDockerContainerMemoryUsageReturnsExpectedKeys(): void
    {
        $usage = ServerMonitorHelper::getDockerContainerMemoryUsage();

        $this->assertArrayHasKey('container_usage_mb', $usage);
        $this->assertArrayHasKey('container_limit_mb', $usage);
        $this->assertArrayHasKey('container_usage_percent', $usage);
        $this->assertArrayHasKey('php_peak_usage_mb', $usage);
        $this->assertGreaterThanOrEqual(0, $usage['container_usage_mb']);
        $this->assertGreaterThanOrEqual(0, $usage['container_usage_percent']);
    }

    public function testGetCpuLoadReturnsStructure(): void
    {
        $load = ServerMonitorHelper::getCpuLoad();

        $this->assertArrayHasKey('load_1min', $load);
        $this->assertArrayHasKey('load_5min', $load);
        $this->assertArrayHasKey('load_15min', $load);
        $this->assertArrayHasKey('available', $load);
        $this->assertIsBool($load['available']);
    }

    public function testGetCpuCoresReturnsAtLeastOne(): void
    {
        $cores = ServerMonitorHelper::getCpuCores();

        $this->assertGreaterThanOrEqual(1, $cores);
    }

    public function testGetCpuUsageReturnsStructure(): void
    {
        $usage = ServerMonitorHelper::getCpuUsage();

        $this->assertArrayHasKey('cores', $usage);
        $this->assertArrayHasKey('usage_percent', $usage);
        $this->assertArrayHasKey('usage_from_load', $usage);
        $this->assertArrayHasKey('load_average', $usage);
        $this->assertGreaterThanOrEqual(1, $usage['cores']);
    }

    public function testGetDockerContainerCpuLoadWhenCgroupUnavailable(): void
    {
        if (file_exists('/sys/fs/cgroup/cpu.stat') || file_exists('/sys/fs/cgroup/cpuacct/cpuacct.usage')) {
            $this->markTestSkipped('Docker cgroup stats are available on this host');
        }

        $load = ServerMonitorHelper::getDockerContainerCpuLoad();

        $this->assertFalse($load['available']);
        $this->assertSame('Cgroup stats not accessible', $load['message']);
    }

    public function testGetRawCpuUsageReadsV2StatFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'cpu-stat-');
        $this->assertNotFalse($tempFile);
        file_put_contents($tempFile, "usage_usec 1234567\n");

        $method = new \ReflectionMethod(ServerMonitorHelper::class, 'getRawCpuUsage');
        $usage = $method->invoke(null, $tempFile, true);

        @unlink($tempFile);

        $this->assertSame(1234567, $usage);
    }

    public function testGetRawCpuUsageReadsV1UsageFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'cpu-usage-');
        $this->assertNotFalse($tempFile);
        file_put_contents($tempFile, "9876543210\n");

        $method = new \ReflectionMethod(ServerMonitorHelper::class, 'getRawCpuUsage');
        $usage = $method->invoke(null, $tempFile, false);

        @unlink($tempFile);

        $this->assertSame(9876543210, $usage);
    }

    public function testGetRawCpuUsageReturnsZeroForMissingFile(): void
    {
        $method = new \ReflectionMethod(ServerMonitorHelper::class, 'getRawCpuUsage');
        $usage = $method->invoke(null, '/tmp/gemvc-helper-missing-cpu-stat-' . uniqid(), true);

        $this->assertSame(0, $usage);
    }

    public function testGetDockerCpuCoresDefaultsToOneWithoutLimits(): void
    {
        $method = new \ReflectionMethod(ServerMonitorHelper::class, 'getDockerCpuCores');
        $cores = $method->invoke(null);

        $this->assertGreaterThan(0, $cores);
    }
}
