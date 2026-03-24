<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Scafera\Layered\Validator\ControllerLocationValidator;

class ControllerLocationValidatorTest extends TestCase
{
    private ControllerLocationValidator $validator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->validator = new ControllerLocationValidator();
        $this->tmpDir = sys_get_temp_dir() . '/scafera_test_' . uniqid();
        mkdir($this->tmpDir . '/src/Controller', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testPassesWhenControllerInCorrectLocation(): void
    {
        file_put_contents($this->tmpDir . '/src/Controller/ListItems.php', <<<'PHP'
        <?php
        namespace App\Controller;
        use Scafera\Kernel\Http\Route;
        #[Route('/items')]
        class ListItems { public function __invoke() {} }
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsWhenControllerOutsideControllerDir(): void
    {
        mkdir($this->tmpDir . '/src/Service', 0777, true);
        file_put_contents($this->tmpDir . '/src/Service/BadController.php', <<<'PHP'
        <?php
        namespace App\Service;
        use Scafera\Kernel\Http\Route;
        #[Route('/bad')]
        class BadController { public function __invoke() {} }
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('Service/BadController.php', $violations[0]);
    }

    public function testIgnoresNonControllerFiles(): void
    {
        mkdir($this->tmpDir . '/src/Service', 0777, true);
        file_put_contents($this->tmpDir . '/src/Service/ItemService.php', <<<'PHP'
        <?php
        namespace App\Service;
        class ItemService {}
        PHP);

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
