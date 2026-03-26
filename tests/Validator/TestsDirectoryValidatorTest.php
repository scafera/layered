<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Scafera\Layered\Validator\TestsDirectoryValidator;

class TestsDirectoryValidatorTest extends TestCase
{
    private TestsDirectoryValidator $validator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->validator = new TestsDirectoryValidator();
        $this->tmpDir = sys_get_temp_dir() . '/scafera_test_' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testPassesWhenTestsDirectoryExists(): void
    {
        mkdir($this->tmpDir . '/tests', 0777, true);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsWhenTestsDirectoryMissing(): void
    {
        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('tests/', $violations[0]);
    }

    public function testName(): void
    {
        $this->assertSame('Tests directory', $this->validator->getName());
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
