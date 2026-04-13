<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Scafera\Layered\Validator\IntegrationNamingValidator;

class IntegrationNamingValidatorTest extends TestCase
{
    private IntegrationNamingValidator $validator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->validator = new IntegrationNamingValidator();
        $this->tmpDir = sys_get_temp_dir() . '/scafera_test_' . uniqid();
        mkdir($this->tmpDir . '/src/Integration/Stripe', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testPassesCleanName(): void
    {
        file_put_contents($this->tmpDir . '/src/Integration/Stripe/PaymentGateway.php', '<?php class PaymentGateway {}');

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testRejectsVendorPrefixInClassName(): void
    {
        file_put_contents($this->tmpDir . '/src/Integration/Stripe/StripePaymentGateway.php', '<?php class StripePaymentGateway {}');

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('repeats vendor prefix', $violations[0]);
        $this->assertStringContainsString('Stripe/PaymentGateway.php', $violations[0]);
    }

    public function testRejectsFileAtRootLevel(): void
    {
        file_put_contents($this->tmpDir . '/src/Integration/SomeClient.php', '<?php class SomeClient {}');

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('vendor subfolder', $violations[0]);
    }

    public function testPassesMultipleVendors(): void
    {
        mkdir($this->tmpDir . '/src/Integration/Mailgun', 0777, true);
        file_put_contents($this->tmpDir . '/src/Integration/Stripe/PaymentGateway.php', '<?php class PaymentGateway {}');
        file_put_contents($this->tmpDir . '/src/Integration/Mailgun/Mailer.php', '<?php class Mailer {}');

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testPassesWhenNoIntegrationDir(): void
    {
        $emptyDir = sys_get_temp_dir() . '/scafera_empty_' . uniqid();
        mkdir($emptyDir);

        $this->assertSame([], $this->validator->validate($emptyDir));

        rmdir($emptyDir);
    }

    public function testIgnoresNonPhpFiles(): void
    {
        file_put_contents($this->tmpDir . '/src/Integration/Stripe/.gitkeep', '');

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
