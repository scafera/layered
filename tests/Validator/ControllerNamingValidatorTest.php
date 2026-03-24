<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Scafera\Layered\Validator\ControllerNamingValidator;

class ControllerNamingValidatorTest extends TestCase
{
    private ControllerNamingValidator $validator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->validator = new ControllerNamingValidator();
        $this->tmpDir = sys_get_temp_dir() . '/scafera_test_' . uniqid();
        mkdir($this->tmpDir . '/src/Controller', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testPassesSingleWordName(): void
    {
        file_put_contents($this->tmpDir . '/src/Controller/Home.php', '<?php class Home {}');
        file_put_contents($this->tmpDir . '/src/Controller/Status.php', '<?php class Status {}');

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsMultiWordNameAtRoot(): void
    {
        file_put_contents($this->tmpDir . '/src/Controller/AddOrder.php', '<?php class AddOrder {}');

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('AddOrder.php', $violations[0]);
        $this->assertStringContainsString('subfolder', $violations[0]);
    }

    public function testSuggestsSubfolderPath(): void
    {
        file_put_contents($this->tmpDir . '/src/Controller/ListUsers.php', '<?php class ListUsers {}');

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('Users/List.php', $violations[0]);
    }

    public function testRejectsControllerSuffixAtRoot(): void
    {
        file_put_contents($this->tmpDir . '/src/Controller/HomeController.php', '<?php class HomeController {}');

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('Controller suffix', $violations[0]);
        $this->assertStringContainsString('Home.php', $violations[0]);
    }

    public function testRejectsControllerSuffixInSubfolders(): void
    {
        mkdir($this->tmpDir . '/src/Controller/Api', 0777, true);
        file_put_contents($this->tmpDir . '/src/Controller/Api/StatusController.php', '<?php class StatusController {}');

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('Controller suffix', $violations[0]);
        $this->assertStringContainsString('Api/Status.php', $violations[0]);
    }

    public function testAllowsMultiWordInSubfolders(): void
    {
        mkdir($this->tmpDir . '/src/Controller/Order', 0777, true);
        file_put_contents($this->tmpDir . '/src/Controller/Order/AddItem.php', '<?php class AddItem {}');

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testIgnoresNonPhpFiles(): void
    {
        file_put_contents($this->tmpDir . '/src/Controller/.gitkeep', '');

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testPassesWhenNoControllerDir(): void
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
