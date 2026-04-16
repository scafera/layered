<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Scafera\Layered\Validator\ConfigFileWhitelistValidator;

class ConfigFileWhitelistValidatorTest extends TestCase
{
    private ConfigFileWhitelistValidator $validator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->validator = new ConfigFileWhitelistValidator();
        $this->tmpDir = sys_get_temp_dir() . '/scafera_test_' . uniqid();
        mkdir($this->tmpDir . '/config', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testPassesWhenOnlyConfigYaml(): void
    {
        file_put_contents($this->tmpDir . '/config/config.yaml', 'env: {}');

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testPassesWhenBothAllowedFilesPresent(): void
    {
        file_put_contents($this->tmpDir . '/config/config.yaml', 'env: {}');
        file_put_contents($this->tmpDir . '/config/config.local.yaml', 'env: {}');

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testPassesWhenConfigDirDoesNotExist(): void
    {
        $emptyDir = sys_get_temp_dir() . '/scafera_empty_' . uniqid();
        mkdir($emptyDir);

        $this->assertSame([], $this->validator->validate($emptyDir));

        rmdir($emptyDir);
    }

    public function testFailsOnConfigExampleYaml(): void
    {
        file_put_contents($this->tmpDir . '/config/config.yaml', 'env: {}');
        file_put_contents($this->tmpDir . '/config/config.example.yaml', 'env: {}');

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('config/config.example.yaml', $violations[0]);
        $this->assertStringContainsString('not allowed', $violations[0]);
    }

    public function testFailsOnReferencePhp(): void
    {
        file_put_contents($this->tmpDir . '/config/config.yaml', 'env: {}');
        file_put_contents($this->tmpDir . '/config/reference.php', '<?php');

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('config/reference.php', $violations[0]);
    }

    public function testFailsOnMultipleExtras(): void
    {
        file_put_contents($this->tmpDir . '/config/config.yaml', 'env: {}');
        file_put_contents($this->tmpDir . '/config/config.example.yaml', 'env: {}');
        file_put_contents($this->tmpDir . '/config/reference.php', '<?php');

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(2, $violations);
    }

    public function testFailsOnUnexpectedSubdirectory(): void
    {
        file_put_contents($this->tmpDir . '/config/config.yaml', 'env: {}');
        mkdir($this->tmpDir . '/config/extra', 0777, true);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('config/extra/', $violations[0]);
    }

    public function testIgnoresGitkeepAndGitignore(): void
    {
        file_put_contents($this->tmpDir . '/config/config.yaml', 'env: {}');
        file_put_contents($this->tmpDir . '/config/.gitkeep', '');
        file_put_contents($this->tmpDir . '/config/.gitignore', 'secrets');

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($it as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($dir);
    }
}
