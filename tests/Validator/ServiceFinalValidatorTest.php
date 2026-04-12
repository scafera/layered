<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Scafera\Layered\Validator\ServiceFinalValidator;

class ServiceFinalValidatorTest extends TestCase
{
    private ServiceFinalValidator $validator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->validator = new ServiceFinalValidator();
        $this->tmpDir = sys_get_temp_dir() . '/scafera_test_' . uniqid();
        mkdir($this->tmpDir . '/src/Service', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testPassesWhenClassIsFinal(): void
    {
        file_put_contents($this->tmpDir . '/src/Service/OrderService.php', <<<'PHP'
        <?php
        final class OrderService {}
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsWhenClassIsNotFinal(): void
    {
        file_put_contents($this->tmpDir . '/src/Service/OrderService.php', <<<'PHP'
        <?php
        class OrderService {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('src/Service/OrderService.php', $violations[0]);
        $this->assertStringContainsString('not declared final', $violations[0]);
    }

    public function testSkipsFilesWithoutClassDeclaration(): void
    {
        file_put_contents($this->tmpDir . '/src/Service/helpers.php', <<<'PHP'
        <?php
        function helper(): void {}
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testPassesWhenServiceDirDoesNotExist(): void
    {
        $emptyDir = sys_get_temp_dir() . '/scafera_empty_' . uniqid();
        mkdir($emptyDir);

        $this->assertSame([], $this->validator->validate($emptyDir));

        rmdir($emptyDir);
    }

    public function testHandlesSubdirectories(): void
    {
        mkdir($this->tmpDir . '/src/Service/Payment', 0777, true);

        file_put_contents($this->tmpDir . '/src/Service/Payment/ChargeService.php', <<<'PHP'
        <?php
        final class ChargeService {}
        PHP);

        file_put_contents($this->tmpDir . '/src/Service/Payment/RefundService.php', <<<'PHP'
        <?php
        class RefundService {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('RefundService.php', $violations[0]);
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
