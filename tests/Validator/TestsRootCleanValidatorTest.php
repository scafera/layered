<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Scafera\Layered\Validator\TestsRootCleanValidator;

class TestsRootCleanValidatorTest extends TestCase
{
    private TestsRootCleanValidator $validator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->validator = new TestsRootCleanValidator();
        $this->tmpDir = sys_get_temp_dir() . '/scafera_test_' . uniqid();
        mkdir($this->tmpDir . '/tests', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testPassesWhenOnlyDirectoriesExist(): void
    {
        mkdir($this->tmpDir . '/tests/Controller', 0777, true);
        mkdir($this->tmpDir . '/tests/Service', 0777, true);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsOnTestFileAtTestsRoot(): void
    {
        file_put_contents($this->tmpDir . '/tests/IndexTest.php', '<?php');

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('tests/IndexTest.php', $violations[0]);
        $this->assertStringContainsString('mirror source layout', $violations[0]);
    }

    public function testIgnoresPhpunitConfig(): void
    {
        file_put_contents($this->tmpDir . '/tests/phpunit.xml', '<phpunit/>');
        file_put_contents($this->tmpDir . '/tests/phpunit.dist.xml', '<phpunit/>');
        file_put_contents($this->tmpDir . '/tests/phpunit.xml.dist', '<phpunit/>');

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testIgnoresBootstrap(): void
    {
        file_put_contents($this->tmpDir . '/tests/bootstrap.php', '<?php');

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testIgnoresEnvTest(): void
    {
        file_put_contents($this->tmpDir . '/tests/.env.test', 'APP_ENV=test');
        file_put_contents($this->tmpDir . '/tests/.env.test.local', 'SECRET=x');

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testIgnoresGitkeep(): void
    {
        file_put_contents($this->tmpDir . '/tests/.gitkeep', '');

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsOnOrphanPhpClass(): void
    {
        file_put_contents($this->tmpDir . '/tests/Helpers.php', '<?php');

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('tests/Helpers.php', $violations[0]);
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
