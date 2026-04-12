<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests\Generator;

use PHPUnit\Framework\TestCase;
use Scafera\Kernel\Generator\FileWriter;
use Scafera\Layered\Generator\CommandGenerator;

class CommandGeneratorTest extends TestCase
{
    private CommandGenerator $generator;
    private FileWriter $writer;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->generator = new CommandGenerator();
        $this->writer = new FileWriter();
        $this->tmpDir = sys_get_temp_dir() . '/scafera_test_' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testName(): void
    {
        $this->assertSame('command', $this->generator->getName());
    }

    public function testGeneratesCommandAndTest(): void
    {
        $result = $this->generator->generate($this->tmpDir, ['name' => 'ImportUsers'], $this->writer);

        $this->assertCount(2, $result->filesCreated);
        $this->assertSame('src/Command/ImportUsers.php', $result->filesCreated[0]);
        $this->assertSame('tests/Command/ImportUsersTest.php', $result->filesCreated[1]);
        $this->assertFileExists($this->tmpDir . '/src/Command/ImportUsers.php');
        $this->assertFileExists($this->tmpDir . '/tests/Command/ImportUsersTest.php');
    }

    public function testCommandContentHasCorrectNamespace(): void
    {
        $this->generator->generate($this->tmpDir, ['name' => 'ImportUsers'], $this->writer);

        $content = file_get_contents($this->tmpDir . '/src/Command/ImportUsers.php');
        $this->assertStringContainsString('namespace App\Command;', $content);
        $this->assertStringContainsString('final class ImportUsers extends Command', $content);
        $this->assertStringContainsString("declare(strict_types=1);", $content);
        $this->assertStringContainsString("#[AsCommand('app:import-users')]", $content);
        $this->assertStringContainsString('use Scafera\Kernel\Console\Command;', $content);
        $this->assertStringContainsString('protected function handle(Input $input, Output $output): int', $content);
    }

    public function testNestedCommandHasCorrectNamespaceAndName(): void
    {
        $this->generator->generate($this->tmpDir, ['name' => 'Report/Generate'], $this->writer);

        $content = file_get_contents($this->tmpDir . '/src/Command/Report/Generate.php');
        $this->assertStringContainsString('namespace App\Command\Report;', $content);
        $this->assertStringContainsString('final class Generate extends Command', $content);
        $this->assertStringContainsString("#[AsCommand('app:report:generate')]", $content);
    }

    public function testTestFileHasCorrectNamespace(): void
    {
        $this->generator->generate($this->tmpDir, ['name' => 'Report/Generate'], $this->writer);

        $content = file_get_contents($this->tmpDir . '/tests/Command/Report/GenerateTest.php');
        $this->assertStringContainsString('namespace App\Tests\Command\Report;', $content);
        $this->assertStringContainsString('class GenerateTest extends CommandTestCase', $content);
        $this->assertStringContainsString("'app:report:generate'", $content);
    }

    public function testRefusesIfCommandAlreadyExists(): void
    {
        $this->generator->generate($this->tmpDir, ['name' => 'ImportUsers'], $this->writer);
        $result = $this->generator->generate($this->tmpDir, ['name' => 'ImportUsers'], $this->writer);

        $this->assertEmpty($result->filesCreated);
        $this->assertNotEmpty($result->messages);
        $this->assertStringContainsString('already exists', $result->messages[0]);
    }

    public function testNormalizesLowercaseInput(): void
    {
        $result = $this->generator->generate($this->tmpDir, ['name' => 'importUsers'], $this->writer);

        $this->assertSame('src/Command/ImportUsers.php', $result->filesCreated[0]);
        $content = file_get_contents($this->tmpDir . '/src/Command/ImportUsers.php');
        $this->assertStringContainsString('final class ImportUsers extends Command', $content);
    }

    public function testNormalizesNestedLowercaseInput(): void
    {
        $result = $this->generator->generate($this->tmpDir, ['name' => 'report/generate'], $this->writer);

        $this->assertSame('src/Command/Report/Generate.php', $result->filesCreated[0]);
        $content = file_get_contents($this->tmpDir . '/src/Command/Report/Generate.php');
        $this->assertStringContainsString('namespace App\Command\Report;', $content);
    }

    public function testRefusesCommandSuffix(): void
    {
        $result = $this->generator->generate($this->tmpDir, ['name' => 'ImportUsersCommand'], $this->writer);

        $this->assertEmpty($result->filesCreated);
        $this->assertStringContainsString("Do not use the 'Command' suffix", $result->messages[0]);
        $this->assertStringContainsString('ImportUsers', $result->messages[0]);
    }

    public function testRefusesCommandSuffixNested(): void
    {
        $result = $this->generator->generate($this->tmpDir, ['name' => 'Report/GenerateCommand'], $this->writer);

        $this->assertEmpty($result->filesCreated);
        $this->assertStringContainsString("Do not use the 'Command' suffix", $result->messages[0]);
        $this->assertStringContainsString('Report/Generate', $result->messages[0]);
    }

    public function testCommandNameConvertsToKebabCase(): void
    {
        $this->generator->generate($this->tmpDir, ['name' => 'SyncInventory'], $this->writer);

        $content = file_get_contents($this->tmpDir . '/src/Command/SyncInventory.php');
        $this->assertStringContainsString("#[AsCommand('app:sync-inventory')]", $content);
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
