<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Scafera\Layered\Validator\ServiceLocationValidator;

class ServiceLocationValidatorTest extends TestCase
{
    private ServiceLocationValidator $validator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->validator = new ServiceLocationValidator();
        $this->tmpDir = sys_get_temp_dir() . '/scafera_test_' . uniqid();
        mkdir($this->tmpDir . '/src', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testPassesWithAllowedDirs(): void
    {
        mkdir($this->tmpDir . '/src/Controller');
        mkdir($this->tmpDir . '/src/Service');
        mkdir($this->tmpDir . '/src/Entity');
        mkdir($this->tmpDir . '/src/Command');

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsWithUnrecognizedDir(): void
    {
        mkdir($this->tmpDir . '/src/Controller');
        mkdir($this->tmpDir . '/src/Helper');

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('src/Helper/', $violations[0]);
    }

    public function testIgnoresFiles(): void
    {
        file_put_contents($this->tmpDir . '/src/something.php', '<?php');

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testPassesWhenNoSrcDir(): void
    {
        $emptyDir = sys_get_temp_dir() . '/scafera_empty_' . uniqid();
        mkdir($emptyDir);

        $this->assertSame([], $this->validator->validate($emptyDir));

        rmdir($emptyDir);
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
