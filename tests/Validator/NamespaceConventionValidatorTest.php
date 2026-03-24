<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Scafera\Layered\Validator\NamespaceConventionValidator;

class NamespaceConventionValidatorTest extends TestCase
{
    private NamespaceConventionValidator $validator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->validator = new NamespaceConventionValidator();
        $this->tmpDir = sys_get_temp_dir() . '/scafera_test_' . uniqid();
        mkdir($this->tmpDir . '/src/Controller', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testPassesWithCorrectNamespace(): void
    {
        file_put_contents($this->tmpDir . '/src/Controller/Status.php', <<<'PHP'
        <?php
        namespace App\Controller;
        class Status {}
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsWithWrongNamespace(): void
    {
        file_put_contents($this->tmpDir . '/src/Controller/Status.php', <<<'PHP'
        <?php
        namespace App\Wrong;
        class Status {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('App\\Wrong', $violations[0]);
        $this->assertStringContainsString('App\\Controller', $violations[0]);
    }

    public function testPassesWithNestedNamespace(): void
    {
        mkdir($this->tmpDir . '/src/Controller/Api', 0777, true);
        file_put_contents($this->tmpDir . '/src/Controller/Api/List.php', <<<'PHP'
        <?php
        namespace App\Controller\Api;
        class ListItems {}
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testSkipsFilesWithoutNamespace(): void
    {
        file_put_contents($this->tmpDir . '/src/Controller/helper.php', '<?php function helper() {}');

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
