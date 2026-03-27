<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests\Generator;

use PHPUnit\Framework\TestCase;
use Scafera\Kernel\Generator\FileWriter;
use Scafera\Layered\Generator\ServiceGenerator;

class ServiceGeneratorTest extends TestCase
{
    private ServiceGenerator $generator;
    private FileWriter $writer;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->generator = new ServiceGenerator();
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
        $this->assertSame('service', $this->generator->getName());
    }

    public function testGeneratesServiceFile(): void
    {
        $result = $this->generator->generate($this->tmpDir, ['name' => 'OrderProcessor'], $this->writer);

        $this->assertCount(1, $result->filesCreated);
        $this->assertSame('src/Service/OrderProcessor.php', $result->filesCreated[0]);
        $this->assertFileExists($this->tmpDir . '/src/Service/OrderProcessor.php');
    }

    public function testServiceContentHasCorrectNamespace(): void
    {
        $this->generator->generate($this->tmpDir, ['name' => 'OrderProcessor'], $this->writer);

        $content = file_get_contents($this->tmpDir . '/src/Service/OrderProcessor.php');
        $this->assertStringContainsString('namespace App\Service;', $content);
        $this->assertStringContainsString('final class OrderProcessor', $content);
        $this->assertStringContainsString("declare(strict_types=1);", $content);
        $this->assertStringContainsString('public function execute(): mixed', $content);
    }

    public function testNestedServiceHasCorrectNamespace(): void
    {
        $this->generator->generate($this->tmpDir, ['name' => 'Payment/StripeProcessor'], $this->writer);

        $content = file_get_contents($this->tmpDir . '/src/Service/Payment/StripeProcessor.php');
        $this->assertStringContainsString('namespace App\Service\Payment;', $content);
        $this->assertStringContainsString('final class StripeProcessor', $content);
    }

    public function testRefusesIfServiceAlreadyExists(): void
    {
        $this->generator->generate($this->tmpDir, ['name' => 'OrderProcessor'], $this->writer);
        $result = $this->generator->generate($this->tmpDir, ['name' => 'OrderProcessor'], $this->writer);

        $this->assertEmpty($result->filesCreated);
        $this->assertNotEmpty($result->messages);
        $this->assertStringContainsString('already exists', $result->messages[0]);
    }

    public function testDoesNotCreateTestFile(): void
    {
        $result = $this->generator->generate($this->tmpDir, ['name' => 'OrderProcessor'], $this->writer);

        $this->assertCount(1, $result->filesCreated);
        $this->assertFalse($this->writer->exists($this->tmpDir, 'tests/Service/OrderProcessorTest.php'));
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
