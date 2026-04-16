<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Scafera\Layered\Validator\SrcRootCleanValidator;

class SrcRootCleanValidatorTest extends TestCase
{
    private SrcRootCleanValidator $validator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->validator = new SrcRootCleanValidator();
        $this->tmpDir = sys_get_temp_dir() . '/scafera_test_' . uniqid();
        mkdir($this->tmpDir . '/src', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testPassesWhenOnlyDirectoriesExist(): void
    {
        mkdir($this->tmpDir . '/src/Controller', 0777, true);
        mkdir($this->tmpDir . '/src/Service', 0777, true);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsOnPhpFileAtSrcRoot(): void
    {
        file_put_contents($this->tmpDir . '/src/RegisterUser.php', '<?php class RegisterUser {}');

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('src/RegisterUser.php', $violations[0]);
        $this->assertStringContainsString('not allowed at src/ root', $violations[0]);
    }

    public function testIgnoresGitkeepAndGitignore(): void
    {
        file_put_contents($this->tmpDir . '/src/.gitkeep', '');
        file_put_contents($this->tmpDir . '/src/.gitignore', 'node_modules');

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testPassesWhenSrcDirDoesNotExist(): void
    {
        $emptyDir = sys_get_temp_dir() . '/scafera_empty_' . uniqid();
        mkdir($emptyDir);

        $this->assertSame([], $this->validator->validate($emptyDir));

        rmdir($emptyDir);
    }

    public function testFlagsMultipleLooseFiles(): void
    {
        file_put_contents($this->tmpDir . '/src/Foo.php', '<?php');
        file_put_contents($this->tmpDir . '/src/Bar.php', '<?php');

        $this->assertCount(2, $this->validator->validate($this->tmpDir));
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
