<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Scafera\Layered\Validator\IntegrationFinalValidator;

class IntegrationFinalValidatorTest extends TestCase
{
    private IntegrationFinalValidator $validator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->validator = new IntegrationFinalValidator();
        $this->tmpDir = sys_get_temp_dir() . '/scafera_test_' . uniqid();
        mkdir($this->tmpDir . '/src/Integration', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testPassesWhenClassIsFinal(): void
    {
        file_put_contents($this->tmpDir . '/src/Integration/StripeGateway.php', <<<'PHP'
        <?php
        final class StripeGateway {}
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsWhenClassIsNotFinal(): void
    {
        file_put_contents($this->tmpDir . '/src/Integration/StripeGateway.php', <<<'PHP'
        <?php
        class StripeGateway {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('src/Integration/StripeGateway.php', $violations[0]);
        $this->assertStringContainsString('not declared final', $violations[0]);
    }

    public function testSkipsFilesWithoutClassDeclaration(): void
    {
        file_put_contents($this->tmpDir . '/src/Integration/helpers.php', <<<'PHP'
        <?php
        function helper(): void {}
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testPassesWhenIntegrationDirDoesNotExist(): void
    {
        $emptyDir = sys_get_temp_dir() . '/scafera_empty_' . uniqid();
        mkdir($emptyDir);

        $this->assertSame([], $this->validator->validate($emptyDir));

        rmdir($emptyDir);
    }

    public function testHandlesSubdirectories(): void
    {
        mkdir($this->tmpDir . '/src/Integration/Stripe', 0777, true);

        file_put_contents($this->tmpDir . '/src/Integration/Stripe/PaymentGateway.php', <<<'PHP'
        <?php
        final class PaymentGateway {}
        PHP);

        file_put_contents($this->tmpDir . '/src/Integration/Stripe/RefundGateway.php', <<<'PHP'
        <?php
        class RefundGateway {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('RefundGateway.php', $violations[0]);
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
