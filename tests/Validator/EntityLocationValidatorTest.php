<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Scafera\Layered\Validator\EntityLocationValidator;

class EntityLocationValidatorTest extends TestCase
{
    private EntityLocationValidator $validator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->validator = new EntityLocationValidator();
        $this->tmpDir = sys_get_temp_dir() . '/scafera_test_' . uniqid();
        mkdir($this->tmpDir . '/src/Entity', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testPassesWhenEntityInEntityFolder(): void
    {
        file_put_contents($this->tmpDir . '/src/Entity/User.php', <<<'PHP'
        <?php
        use Scafera\Database\Mapping\Table;
        #[Table]
        class User {}
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsWhenEntityOutsideEntityFolder(): void
    {
        mkdir($this->tmpDir . '/src/Model', 0777, true);
        file_put_contents($this->tmpDir . '/src/Model/User.php', <<<'PHP'
        <?php
        use Scafera\Database\Mapping\Table;
        #[Table]
        class User {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('src/Model/User.php', $violations[0]);
        $this->assertStringContainsString('move it to src/Entity/', $violations[0]);
    }

    public function testSkipsFilesWithoutTableAttribute(): void
    {
        mkdir($this->tmpDir . '/src/Service', 0777, true);
        file_put_contents($this->tmpDir . '/src/Service/DoThing.php', '<?php class DoThing {}');

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testSkipsFilesThatOnlyImportTableButDoNotUseIt(): void
    {
        mkdir($this->tmpDir . '/src/Service', 0777, true);
        file_put_contents($this->tmpDir . '/src/Service/EntityFactory.php', <<<'PHP'
        <?php
        use Scafera\Database\Mapping\Table;
        final class EntityFactory {
            public function describe(): string { return Table::class; }
        }
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testPassesWhenSrcDoesNotExist(): void
    {
        $emptyDir = sys_get_temp_dir() . '/scafera_empty_' . uniqid();
        mkdir($emptyDir);

        $this->assertSame([], $this->validator->validate($emptyDir));

        rmdir($emptyDir);
    }

    public function testDetectsFqnAttribute(): void
    {
        mkdir($this->tmpDir . '/src/Loose', 0777, true);
        file_put_contents($this->tmpDir . '/src/Loose/Order.php', <<<'PHP'
        <?php
        #[\Scafera\Database\Mapping\Table]
        class Order {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('src/Loose/Order.php', $violations[0]);
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
