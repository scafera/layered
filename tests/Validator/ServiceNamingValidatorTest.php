<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Scafera\Layered\Validator\ServiceNamingValidator;

class ServiceNamingValidatorTest extends TestCase
{
    private ServiceNamingValidator $validator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->validator = new ServiceNamingValidator();
        $this->tmpDir = sys_get_temp_dir() . '/scafera_test_' . uniqid();
        mkdir($this->tmpDir . '/src/Service', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testPassesCleanName(): void
    {
        file_put_contents($this->tmpDir . '/src/Service/OrderProcessor.php', '<?php class OrderProcessor {}');
        file_put_contents($this->tmpDir . '/src/Service/InvoiceCalculator.php', '<?php class InvoiceCalculator {}');

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testRejectsServiceSuffixAtRoot(): void
    {
        file_put_contents($this->tmpDir . '/src/Service/OrderService.php', '<?php class OrderService {}');

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('Service suffix', $violations[0]);
        $this->assertStringContainsString('Order.php', $violations[0]);
    }

    public function testRejectsServiceSuffixInSubfolders(): void
    {
        mkdir($this->tmpDir . '/src/Service/Payment', 0777, true);
        file_put_contents($this->tmpDir . '/src/Service/Payment/RefundService.php', '<?php class RefundService {}');

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('Service suffix', $violations[0]);
        $this->assertStringContainsString('Payment/Refund.php', $violations[0]);
    }

    public function testPassesWhenNoServiceDir(): void
    {
        $emptyDir = sys_get_temp_dir() . '/scafera_empty_' . uniqid();
        mkdir($emptyDir);

        $this->assertSame([], $this->validator->validate($emptyDir));

        rmdir($emptyDir);
    }

    public function testIgnoresNonPhpFiles(): void
    {
        file_put_contents($this->tmpDir . '/src/Service/.gitkeep', '');

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
