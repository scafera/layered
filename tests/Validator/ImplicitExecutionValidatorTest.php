<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Scafera\Layered\Validator\ImplicitExecutionValidator;

class ImplicitExecutionValidatorTest extends TestCase
{
    private ImplicitExecutionValidator $validator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->validator = new ImplicitExecutionValidator();
        $this->tmpDir = sys_get_temp_dir() . '/scafera_test_' . uniqid();
        mkdir($this->tmpDir . '/src/Service', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testPassesWithCleanService(): void
    {
        file_put_contents($this->tmpDir . '/src/Service/OrderService.php', <<<'PHP'
        <?php
        namespace App\Service;
        class OrderService {}
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsWhenUsingEventSubscriberInterface(): void
    {
        file_put_contents($this->tmpDir . '/src/Service/BadListener.php', <<<'PHP'
        <?php
        namespace App\Service;
        use Symfony\Component\EventDispatcher\EventSubscriberInterface;
        class BadListener implements EventSubscriberInterface {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('EventSubscriberInterface', $violations[0]);
        $this->assertStringContainsString('BadListener.php', $violations[0]);
    }

    public function testFailsWhenUsingAsEventListenerAttribute(): void
    {
        file_put_contents($this->tmpDir . '/src/Service/BadHandler.php', <<<'PHP'
        <?php
        namespace App\Service;
        use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
        #[AsEventListener]
        class BadHandler {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('#[AsEventListener]', $violations[0]);
        $this->assertStringContainsString('BadHandler.php', $violations[0]);
    }

    public function testReportsBothViolationsInSameFile(): void
    {
        file_put_contents($this->tmpDir . '/src/Service/DoubleBad.php', <<<'PHP'
        <?php
        namespace App\Service;
        use Symfony\Component\EventDispatcher\EventSubscriberInterface;
        #[AsEventListener]
        class DoubleBad implements EventSubscriberInterface {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(2, $violations);
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
