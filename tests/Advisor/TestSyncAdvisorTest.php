<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests\Advisor;

use PHPUnit\Framework\TestCase;
use Scafera\Layered\Advisor\TestSyncAdvisor;

class TestSyncAdvisorTest extends TestCase
{
    public function testSkippedWhenGitNotInstalled(): void
    {
        $advisor = new FakeAdvisor(hasGit: false, isGit: false, files: []);

        $status = $advisor->canRun('/dummy');
        $this->assertFalse($status->ready);
        $this->assertSame('git is not installed', $status->reason);
    }

    public function testSkippedWhenNotGitRepo(): void
    {
        $advisor = new FakeAdvisor(hasGit: true, isGit: false, files: []);

        $status = $advisor->canRun('/dummy');
        $this->assertFalse($status->ready);
        $this->assertSame('not a git repository', $status->reason);
    }

    public function testReadyWhenGitRepoExists(): void
    {
        $advisor = new FakeAdvisor(hasGit: true, isGit: true, files: []);

        $status = $advisor->canRun('/dummy');
        $this->assertTrue($status->ready);
    }

    public function testNoHintsWhenNoModifiedFiles(): void
    {
        $advisor = new FakeAdvisor(hasGit: true, isGit: true, files: []);

        $this->assertSame([], $advisor->advise('/dummy'));
    }

    public function testNoHintWhenControllerAndTestBothModified(): void
    {
        $advisor = new FakeAdvisor(hasGit: true, isGit: true, files: [
            'src/Controller/Home.php',
            'tests/Controller/HomeTest.php',
        ]);

        $this->assertSame([], $advisor->advise('/dummy'));
    }

    public function testHintWhenControllerModifiedButTestNot(): void
    {
        $advisor = new FakeAdvisor(hasGit: true, isGit: true, files: [
            'src/Controller/Home.php',
        ]);

        $hints = $advisor->advise('/dummy');
        $this->assertCount(1, $hints);
        $this->assertStringContainsString('src/Controller/Home.php', $hints[0]);
        $this->assertStringContainsString('tests/Controller/HomeTest.php', $hints[0]);
    }

    public function testHintForNestedController(): void
    {
        $advisor = new FakeAdvisor(hasGit: true, isGit: true, files: [
            'src/Controller/Api/Item/Create.php',
        ]);

        $hints = $advisor->advise('/dummy');
        $this->assertCount(1, $hints);
        $this->assertStringContainsString('tests/Controller/Api/Item/CreateTest.php', $hints[0]);
    }

    public function testHintWhenCommandModifiedButTestNot(): void
    {
        $advisor = new FakeAdvisor(hasGit: true, isGit: true, files: [
            'src/Command/Import.php',
        ]);

        $hints = $advisor->advise('/dummy');
        $this->assertCount(1, $hints);
        $this->assertStringContainsString('src/Command/Import.php', $hints[0]);
        $this->assertStringContainsString('tests/Command/ImportTest.php', $hints[0]);
    }

    public function testNoHintWhenCommandAndTestBothModified(): void
    {
        $advisor = new FakeAdvisor(hasGit: true, isGit: true, files: [
            'src/Command/Import.php',
            'tests/Command/ImportTest.php',
        ]);

        $this->assertSame([], $advisor->advise('/dummy'));
    }

    public function testIgnoresNonControllerNonCommandFiles(): void
    {
        $advisor = new FakeAdvisor(hasGit: true, isGit: true, files: [
            'src/Service/OrderService.php',
            'config/overrides.yaml',
        ]);

        $this->assertSame([], $advisor->advise('/dummy'));
    }

    public function testMultipleHints(): void
    {
        $advisor = new FakeAdvisor(hasGit: true, isGit: true, files: [
            'src/Controller/Home.php',
            'src/Controller/Api/Status.php',
            'tests/Controller/Api/StatusTest.php',
        ]);

        $hints = $advisor->advise('/dummy');
        $this->assertCount(1, $hints);
        $this->assertStringContainsString('src/Controller/Home.php', $hints[0]);
    }
}

/**
 * Test double that avoids real git/shell calls.
 */
class FakeAdvisor extends TestSyncAdvisor
{
    /** @param list<string> $files */
    public function __construct(
        private readonly bool $hasGit,
        private readonly bool $isGit,
        private readonly array $files,
    ) {
    }

    protected function exec(string $command): string
    {
        if (str_contains($command, 'which git')) {
            return $this->hasGit ? '/usr/bin/git' : '';
        }

        if (str_contains($command, 'rev-parse')) {
            return $this->isGit ? 'true' : 'false';
        }

        if (str_contains($command, 'status --porcelain')) {
            return implode("\n", array_map(fn(string $f) => ' M ' . $f, $this->files));
        }

        return '';
    }
}
