<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Scafera\Layered\Validator\ControllerTestParityValidator;

class ControllerTestParityValidatorTest extends TestCase
{
    private ControllerTestParityValidator $validator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->validator = new ControllerTestParityValidator();
        $this->tmpDir = sys_get_temp_dir() . '/scafera_test_' . uniqid();
        mkdir($this->tmpDir . '/src/Controller', 0777, true);
        mkdir($this->tmpDir . '/tests/Controller', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testPassesWhenTestExists(): void
    {
        file_put_contents($this->tmpDir . '/src/Controller/Status.php', '<?php class Status {}');
        file_put_contents($this->tmpDir . '/tests/Controller/StatusTest.php', '<?php class StatusTest {}');

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsWhenTestMissing(): void
    {
        file_put_contents($this->tmpDir . '/src/Controller/Status.php', '<?php class Status {}');

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('Status.php', $violations[0]);
        $this->assertStringContainsString('StatusTest.php', $violations[0]);
    }

    public function testPassesWhenNoControllerDir(): void
    {
        $emptyDir = sys_get_temp_dir() . '/scafera_empty_' . uniqid();
        mkdir($emptyDir);

        $this->assertSame([], $this->validator->validate($emptyDir));

        rmdir($emptyDir);
    }

    public function testHandlesNestedControllers(): void
    {
        mkdir($this->tmpDir . '/src/Controller/Api', 0777, true);
        mkdir($this->tmpDir . '/tests/Controller/Api', 0777, true);
        file_put_contents($this->tmpDir . '/src/Controller/Api/Create.php', '<?php class Create {}');
        file_put_contents($this->tmpDir . '/tests/Controller/Api/CreateTest.php', '<?php class CreateTest {}');

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
