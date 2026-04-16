<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Scafera\Layered\Validator\SupportRootCleanValidator;

class SupportRootCleanValidatorTest extends TestCase
{
    private SupportRootCleanValidator $validator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->validator = new SupportRootCleanValidator();
        $this->tmpDir = sys_get_temp_dir() . '/scafera_test_' . uniqid();
        mkdir($this->tmpDir . '/support', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testPassesWhenOnlyDirectoriesExist(): void
    {
        mkdir($this->tmpDir . '/support/migrations', 0777, true);
        mkdir($this->tmpDir . '/support/seeds', 0777, true);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsOnPhpFileAtSupportRoot(): void
    {
        file_put_contents($this->tmpDir . '/support/Version20260415211801.php', '<?php');

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('support/Version20260415211801.php', $violations[0]);
        $this->assertStringContainsString('migrations/, seeds/, or patches/', $violations[0]);
    }

    public function testIgnoresGitkeep(): void
    {
        file_put_contents($this->tmpDir . '/support/.gitkeep', '');

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testPassesWhenSupportDirDoesNotExist(): void
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
