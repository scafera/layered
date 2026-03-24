<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Scafera\Layered\Validator\CommandTestParityValidator;

class CommandTestParityValidatorTest extends TestCase
{
    private CommandTestParityValidator $validator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->validator = new CommandTestParityValidator();
        $this->tmpDir = sys_get_temp_dir() . '/scafera_test_' . uniqid();
        mkdir($this->tmpDir . '/src/Command', 0777, true);
        mkdir($this->tmpDir . '/tests/Command', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testPassesWhenTestExists(): void
    {
        file_put_contents($this->tmpDir . '/src/Command/Import.php', '<?php class Import {}');
        file_put_contents($this->tmpDir . '/tests/Command/ImportTest.php', '<?php class ImportTest {}');

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsWhenTestMissing(): void
    {
        file_put_contents($this->tmpDir . '/src/Command/Import.php', '<?php class Import {}');

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('Import.php', $violations[0]);
        $this->assertStringContainsString('ImportTest.php', $violations[0]);
    }

    public function testPassesWhenNoCommandDir(): void
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
