<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Scafera\Layered\Validator\SingleActionControllerValidator;

class SingleActionControllerValidatorTest extends TestCase
{
    private SingleActionControllerValidator $validator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->validator = new SingleActionControllerValidator();
        $this->tmpDir = sys_get_temp_dir() . '/scafera_test_' . uniqid();
        mkdir($this->tmpDir . '/src/Controller', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testPassesWithInvokableController(): void
    {
        file_put_contents($this->tmpDir . '/src/Controller/Home.php', <<<'PHP'
        <?php
        namespace App\Controller;
        class Home {
            public function __construct(private readonly object $service) {}
            public function __invoke(): void {}
        }
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsWithoutInvoke(): void
    {
        file_put_contents($this->tmpDir . '/src/Controller/Bad.php', <<<'PHP'
        <?php
        namespace App\Controller;
        class Bad {
            public function index(): void {}
        }
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('__invoke', $violations[0]);
        $this->assertStringContainsString('Bad.php', $violations[0]);
    }

    public function testFailsWithExtraPublicMethods(): void
    {
        file_put_contents($this->tmpDir . '/src/Controller/Multi.php', <<<'PHP'
        <?php
        namespace App\Controller;
        class Multi {
            public function __invoke(): void {}
            public function list(): void {}
            public function create(): void {}
        }
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('list', $violations[0]);
        $this->assertStringContainsString('create', $violations[0]);
        $this->assertStringContainsString('single-action', $violations[0]);
    }

    public function testAllowsConstructorAlongsideInvoke(): void
    {
        file_put_contents($this->tmpDir . '/src/Controller/Good.php', <<<'PHP'
        <?php
        namespace App\Controller;
        class Good {
            public function __construct() {}
            public function __invoke(): void {}
        }
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testPassesWithNestedControllers(): void
    {
        mkdir($this->tmpDir . '/src/Controller/Order', 0777, true);
        file_put_contents($this->tmpDir . '/src/Controller/Order/Add.php', <<<'PHP'
        <?php
        namespace App\Controller\Order;
        class Add {
            public function __invoke(): void {}
        }
        PHP);

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
