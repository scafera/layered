<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests\Advisor;

use PHPUnit\Framework\TestCase;
use Scafera\Layered\Advisor\TestSyncAdvisor;

class TestSyncAdvisorTest extends TestCase
{
    public function testSkippedWhenGitNotInstalled(): void
    {
        $advisor = new FakeAdvisor(hasGit: false, revParseOutput: '', porcelain: '');

        $this->assertSame('git is not installed', $advisor->skipped('/dummy'));
    }

    public function testSkippedWhenNotGitRepo(): void
    {
        $advisor = new FakeAdvisor(hasGit: true, revParseOutput: 'fatal: not a git repository', porcelain: '');

        $this->assertSame('not a git repository', $advisor->skipped('/dummy'));
    }

    public function testReadyWhenGitRepoExists(): void
    {
        $advisor = new FakeAdvisor(hasGit: true, revParseOutput: 'true', porcelain: '');

        $this->assertNull($advisor->skipped('/dummy'));
    }

    public function testNoHintsWhenNoModifiedFiles(): void
    {
        $advisor = new FakeAdvisor(hasGit: true, revParseOutput: 'true', porcelain: '');

        $this->assertSame([], $advisor->advise('/dummy'));
    }

    public function testNoHintWhenControllerAndTestBothModified(): void
    {
        $advisor = new FakeAdvisor(hasGit: true, revParseOutput: 'true', porcelain:
            " M src/Controller/Home.php\n" .
            " M tests/Controller/HomeTest.php\n"
        );

        $this->assertSame([], $advisor->advise('/dummy'));
    }

    public function testHintWhenControllerModifiedButTestNotPresent(): void
    {
        $advisor = new FakeAdvisor(hasGit: true, revParseOutput: 'true', porcelain:
            " M src/Controller/Home.php\n"
        );

        $hints = $advisor->advise('/dummy');
        $this->assertCount(1, $hints);
        $this->assertStringContainsString('src/Controller/Home.php', $hints[0]);
        $this->assertStringContainsString('was modified', $hints[0]);
    }

    public function testHintWhenControllerHasUnstagedChangesButTestStagedOnly(): void
    {
        // AM = staged + working tree changes, A  = staged only
        $advisor = new FakeAdvisor(hasGit: true, revParseOutput: 'true', porcelain:
            "AM src/Controller/Home.php\n" .
            "A  tests/Controller/HomeTest.php\n"
        );

        $hints = $advisor->advise('/dummy');
        $this->assertCount(1, $hints);
        $this->assertStringContainsString('unstaged changes', $hints[0]);
        $this->assertStringContainsString('HomeTest.php', $hints[0]);
    }

    public function testNoHintWhenBothHaveUnstagedChanges(): void
    {
        $advisor = new FakeAdvisor(hasGit: true, revParseOutput: 'true', porcelain:
            "AM src/Controller/Home.php\n" .
            "AM tests/Controller/HomeTest.php\n"
        );

        $this->assertSame([], $advisor->advise('/dummy'));
    }

    public function testHintForNestedController(): void
    {
        $advisor = new FakeAdvisor(hasGit: true, revParseOutput: 'true', porcelain:
            " M src/Controller/Api/Item/Create.php\n"
        );

        $hints = $advisor->advise('/dummy');
        $this->assertCount(1, $hints);
        $this->assertStringContainsString('tests/Controller/Api/Item/CreateTest.php', $hints[0]);
    }

    public function testHintWhenCommandModifiedButTestNotPresent(): void
    {
        $advisor = new FakeAdvisor(hasGit: true, revParseOutput: 'true', porcelain:
            " M src/Command/Import.php\n"
        );

        $hints = $advisor->advise('/dummy');
        $this->assertCount(1, $hints);
        $this->assertStringContainsString('src/Command/Import.php', $hints[0]);
        $this->assertStringContainsString('tests/Command/ImportTest.php', $hints[0]);
    }

    public function testNoHintWhenCommandAndTestBothModified(): void
    {
        $advisor = new FakeAdvisor(hasGit: true, revParseOutput: 'true', porcelain:
            " M src/Command/Import.php\n" .
            " M tests/Command/ImportTest.php\n"
        );

        $this->assertSame([], $advisor->advise('/dummy'));
    }

    public function testIgnoresNonControllerNonCommandFiles(): void
    {
        $advisor = new FakeAdvisor(hasGit: true, revParseOutput: 'true', porcelain:
            " M src/Service/OrderService.php\n" .
            " M config/overrides.yaml\n"
        );

        $this->assertSame([], $advisor->advise('/dummy'));
    }

    public function testMultipleHints(): void
    {
        $advisor = new FakeAdvisor(hasGit: true, revParseOutput: 'true', porcelain:
            " M src/Controller/Home.php\n" .
            " M src/Controller/Api/Status.php\n" .
            " M tests/Controller/Api/StatusTest.php\n"
        );

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
    public function __construct(
        private readonly bool $hasGit,
        private readonly string $revParseOutput,
        private readonly string $porcelain,
    ) {
    }

    protected function exec(string $command): string
    {
        if (str_contains($command, 'which git')) {
            return $this->hasGit ? '/usr/bin/git' : '';
        }

        if (str_contains($command, 'rev-parse')) {
            return $this->revParseOutput;
        }

        if (str_contains($command, 'status --porcelain')) {
            return $this->porcelain;
        }

        return '';
    }
}
