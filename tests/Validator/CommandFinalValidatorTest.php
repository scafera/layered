<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Scafera\Layered\Validator\CommandFinalValidator;

class CommandFinalValidatorTest extends TestCase
{
    private CommandFinalValidator $validator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->validator = new CommandFinalValidator();
        $this->tmpDir = sys_get_temp_dir() . '/scafera_test_' . uniqid();
        mkdir($this->tmpDir . '/src/Command', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testPassesWhenClassIsFinal(): void
    {
        file_put_contents($this->tmpDir . '/src/Command/SyncCommand.php', <<<'PHP'
        <?php
        final class SyncCommand {}
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsWhenClassIsNotFinal(): void
    {
        file_put_contents($this->tmpDir . '/src/Command/SyncCommand.php', <<<'PHP'
        <?php
        class SyncCommand {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('src/Command/SyncCommand.php', $violations[0]);
        $this->assertStringContainsString('not declared final', $violations[0]);
    }

    public function testSkipsFilesWithoutClassDeclaration(): void
    {
        file_put_contents($this->tmpDir . '/src/Command/helpers.php', <<<'PHP'
        <?php
        function helper(): void {}
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testPassesWhenCommandDirDoesNotExist(): void
    {
        $emptyDir = sys_get_temp_dir() . '/scafera_empty_' . uniqid();
        mkdir($emptyDir);

        $this->assertSame([], $this->validator->validate($emptyDir));

        rmdir($emptyDir);
    }

    public function testHandlesSubdirectories(): void
    {
        mkdir($this->tmpDir . '/src/Command/Sync', 0777, true);

        file_put_contents($this->tmpDir . '/src/Command/Sync/RunCommand.php', <<<'PHP'
        <?php
        final class RunCommand {}
        PHP);

        file_put_contents($this->tmpDir . '/src/Command/Sync/ResetCommand.php', <<<'PHP'
        <?php
        class ResetCommand {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('ResetCommand.php', $violations[0]);
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
