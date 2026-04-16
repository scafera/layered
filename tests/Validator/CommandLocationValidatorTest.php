<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Scafera\Layered\Validator\CommandLocationValidator;

class CommandLocationValidatorTest extends TestCase
{
    private CommandLocationValidator $validator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->validator = new CommandLocationValidator();
        $this->tmpDir = sys_get_temp_dir() . '/scafera_test_' . uniqid();
        mkdir($this->tmpDir . '/src/Command', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testPassesWhenCommandInCommandFolder(): void
    {
        file_put_contents($this->tmpDir . '/src/Command/SyncCommand.php', <<<'PHP'
        <?php
        use Scafera\Kernel\Console\Command;
        use Scafera\Kernel\Console\Attribute\AsCommand;
        #[AsCommand('app:sync')]
        final class SyncCommand extends Command {}
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsWhenCommandOutsideCommandFolder(): void
    {
        mkdir($this->tmpDir . '/src/Scripts', 0777, true);
        file_put_contents($this->tmpDir . '/src/Scripts/SyncCommand.php', <<<'PHP'
        <?php
        use Scafera\Kernel\Console\Command;
        final class SyncCommand extends Command {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('src/Scripts/SyncCommand.php', $violations[0]);
        $this->assertStringContainsString('move it to src/Command/', $violations[0]);
    }

    public function testDetectsAsCommandAttribute(): void
    {
        mkdir($this->tmpDir . '/src/Loose', 0777, true);
        file_put_contents($this->tmpDir . '/src/Loose/Ping.php', <<<'PHP'
        <?php
        use Scafera\Kernel\Console\Attribute\AsCommand;
        #[AsCommand('app:ping')]
        final class Ping {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('src/Loose/Ping.php', $violations[0]);
    }

    public function testSkipsFilesWithoutCommandMarker(): void
    {
        mkdir($this->tmpDir . '/src/Service', 0777, true);
        file_put_contents($this->tmpDir . '/src/Service/DoThing.php', '<?php class DoThing {}');

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testSkipsFilesThatOnlyImportAsCommandButDoNotUseIt(): void
    {
        mkdir($this->tmpDir . '/src/Service', 0777, true);
        file_put_contents($this->tmpDir . '/src/Service/CommandRegistry.php', <<<'PHP'
        <?php
        use Scafera\Kernel\Console\Attribute\AsCommand;
        final class CommandRegistry {
            public function describe(): string { return AsCommand::class; }
        }
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testPassesWhenSrcDoesNotExist(): void
    {
        $emptyDir = sys_get_temp_dir() . '/scafera_empty_' . uniqid();
        mkdir($emptyDir);

        $this->assertSame([], $this->validator->validate($emptyDir));

        rmdir($emptyDir);
    }

    public function testDetectsFqnExtends(): void
    {
        mkdir($this->tmpDir . '/src/Loose', 0777, true);
        file_put_contents($this->tmpDir . '/src/Loose/BootCommand.php', <<<'PHP'
        <?php
        final class BootCommand extends \Scafera\Kernel\Console\Command {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('src/Loose/BootCommand.php', $violations[0]);
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
