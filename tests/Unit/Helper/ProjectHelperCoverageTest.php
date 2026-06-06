<?php

declare(strict_types=1);

namespace Tests\Unit\Helper;

use Gemvc\Helper\ProjectHelper;
use PHPUnit\Framework\TestCase;

class ProjectHelperCoverageTest extends TestCase
{
    private ?string $originalRootDir = null;
    private string $tempProjectDir = '';
    /** @var array<string, mixed> */
    private array $envBackup = [];
    /** @var array<string, mixed> */
    private array $serverBackup = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempProjectDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gemvc_helper_cov_' . uniqid();
        mkdir($this->tempProjectDir);
        mkdir($this->tempProjectDir . DIRECTORY_SEPARATOR . 'app');

        file_put_contents(
            $this->tempProjectDir . DIRECTORY_SEPARATOR . 'composer.lock',
            (string) json_encode([
                'packages' => [
                    ['name' => 'gemvc/library', 'version' => '5.8.1.0'],
                ],
            ])
        );

        file_put_contents(
            $this->tempProjectDir . DIRECTORY_SEPARATOR . '.env',
            implode("\n", [
                'APP_ENV=dev',
                'APP_ENV_PUBLIC_SERVER_PORT=9550',
                'APP_ENV_API_DEFAULT_SUB_URL=/apiv2',
            ]) . "\n"
        );

        $property = (new \ReflectionClass(ProjectHelper::class))->getProperty('rootDir');
        $this->originalRootDir = $property->getValue();
        $property->setValue(null, $this->tempProjectDir);

        $this->envBackup = $_ENV;
        $this->serverBackup = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_ENV = $this->envBackup;
        $_SERVER = $this->serverBackup;

        $property = (new \ReflectionClass(ProjectHelper::class))->getProperty('rootDir');
        $property->setValue(null, $this->originalRootDir);

        $this->removeDirectory($this->tempProjectDir);
        parent::tearDown();
    }

    public function testGetVersionReturnsInstalledLibraryVersion(): void
    {
        $this->assertSame('5.8.1.0', ProjectHelper::getVersion());
    }

    public function testGetVersionReturnsUnknownWhenLibraryMissing(): void
    {
        file_put_contents(
            $this->tempProjectDir . DIRECTORY_SEPARATOR . 'composer.lock',
            (string) json_encode(['packages' => []])
        );

        $this->assertSame('unknown', ProjectHelper::getVersion());
    }

    public function testGetVersionChecksPackagesDev(): void
    {
        file_put_contents(
            $this->tempProjectDir . DIRECTORY_SEPARATOR . 'composer.lock',
            (string) json_encode([
                'packages' => [],
                'packages-dev' => [
                    ['name' => 'gemvc/library', 'pretty_version' => '5.8.0'],
                ],
            ])
        );

        $this->assertSame('5.8.0', ProjectHelper::getVersion());
    }

    public function testGetBaseUrlUsesHostAndConfiguredPort(): void
    {
        $_ENV['APP_ENV_PUBLIC_SERVER_PORT'] = '9550';
        $_SERVER['HTTP_HOST'] = 'localhost';

        $this->assertSame('http://localhost:9550', ProjectHelper::getBaseUrl());
    }

    public function testGetBaseUrlUsesHttpsAndPortFromHostHeader(): void
    {
        $_ENV['APP_ENV_PUBLIC_SERVER_PORT'] = '80';
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'example.test:8443';

        $this->assertSame('https://example.test:8443', ProjectHelper::getBaseUrl());
    }

    public function testGetBaseUrlOmitsStandardPortDisplay(): void
    {
        $_ENV['APP_ENV_PUBLIC_SERVER_PORT'] = '443';
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'secure.example.test';

        $this->assertSame('https://secure.example.test', ProjectHelper::getBaseUrl());
    }

    public function testGetApiBaseUrlAppendsConfiguredSubPath(): void
    {
        $_ENV['APP_ENV_PUBLIC_SERVER_PORT'] = '9550';
        $_ENV['APP_ENV_API_DEFAULT_SUB_URL'] = '/apiv2';
        $_SERVER['HTTP_HOST'] = 'localhost';

        $this->assertSame('http://localhost:9550/apiv2', ProjectHelper::getApiBaseUrl());
    }

    public function testIsDevEnvironmentAndGetAppEnv(): void
    {
        $_ENV['APP_ENV'] = 'dev';

        $this->assertTrue(ProjectHelper::isDevEnvironment());
        $this->assertSame('dev', ProjectHelper::getAppEnv());
    }

    public function testGetAppEnvDefaultsToProduction(): void
    {
        unset($_ENV['APP_ENV']);

        $this->assertFalse(ProjectHelper::isDevEnvironment());
        $this->assertSame('production', ProjectHelper::getAppEnv());
    }

    public function testGetAppEnvFallsBackWhenValueIsNotString(): void
    {
        $_ENV['APP_ENV'] = 123;

        $this->assertSame('production', ProjectHelper::getAppEnv());
    }

    public function testDisableOpcacheIfDevRunsInDevEnvironment(): void
    {
        $_ENV['APP_ENV'] = 'dev';
        ProjectHelper::disableOpcacheIfDev();
        $this->assertTrue(true);
    }

    public function testDisableOpcacheIfDevSkipsOutsideDev(): void
    {
        $_ENV['APP_ENV'] = 'production';
        ProjectHelper::disableOpcacheIfDev();
        $this->assertTrue(true);
    }

    public function testLoadEnvLoadsRootEnvFile(): void
    {
        ProjectHelper::loadEnv();
        $this->assertSame('dev', $_ENV['APP_ENV']);
        $this->assertSame('9550', $_ENV['APP_ENV_PUBLIC_SERVER_PORT']);
    }

    public function testAppDirReturnsExistingDirectory(): void
    {
        $expected = $this->tempProjectDir . DIRECTORY_SEPARATOR . 'app';
        $this->assertSame($expected, ProjectHelper::appDir());
    }

    public function testUpdateEnvVariablesUpdatesExistingValue(): void
    {
        $this->assertTrue(ProjectHelper::updateEnvVariables(['APP_ENV' => 'staging']));

        $envContent = (string) file_get_contents($this->tempProjectDir . DIRECTORY_SEPARATOR . '.env');
        $this->assertStringContainsString('APP_ENV=staging', $envContent);
    }

    public function testUpdateEnvVariablesAddsNewValueAfterAppEnv(): void
    {
        $this->assertTrue(ProjectHelper::updateEnvVariables(['NEW_SETTING' => 'enabled']));

        $envContent = (string) file_get_contents($this->tempProjectDir . DIRECTORY_SEPARATOR . '.env');
        $this->assertStringContainsString('NEW_SETTING=enabled', $envContent);
    }

    public function testUpdateEnvVariablesQuotesValuesWithSpaces(): void
    {
        $this->assertTrue(ProjectHelper::updateEnvVariables(['QUOTED_VALUE' => 'hello world']));

        $envContent = (string) file_get_contents($this->tempProjectDir . DIRECTORY_SEPARATOR . '.env');
        $this->assertStringContainsString('QUOTED_VALUE="hello world"', $envContent);
    }

    public function testUpdateEnvVariablesReturnsFalseWhenEnvFileMissing(): void
    {
        unlink($this->tempProjectDir . DIRECTORY_SEPARATOR . '.env');

        $this->assertFalse(ProjectHelper::updateEnvVariables(['APP_ENV' => 'dev']));
    }

    public function testGetLibrarySystemPagesPathThrowsWhenUnavailable(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('gemvc/library package not found');

        ProjectHelper::getLibrarySystemPagesPath();
    }

    public function testLoadEnvThrowsWhenNoEnvFilesExist(): void
    {
        unlink($this->tempProjectDir . DIRECTORY_SEPARATOR . '.env');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No .env file found');

        ProjectHelper::loadEnv();
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
