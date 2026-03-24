<?php

declare(strict_types=1);

namespace Scafera\Layered\Advisor;

use Scafera\Kernel\Contract\AdvisorInterface;
use Scafera\Kernel\Contract\AdvisorStatus;

class TestSyncAdvisor implements AdvisorInterface
{
    public function getName(): string
    {
        return 'Test sync';
    }

    public function canRun(string $projectDir): AdvisorStatus
    {
        if (!$this->hasGit()) {
            return AdvisorStatus::skipped('git is not installed');
        }

        if (!$this->isGitRepo($projectDir)) {
            return AdvisorStatus::skipped('not a git repository');
        }

        return AdvisorStatus::ready();
    }

    public function advise(string $projectDir): array
    {
        $modified = $this->getModifiedFiles($projectDir);
        if ($modified === []) {
            return [];
        }

        $hints = [];

        foreach ($modified as $file) {
            if (!preg_match('#^src/Controller/(.+)\.php$#', $file, $m)) {
                continue;
            }

            $testFile = 'tests/Controller/' . $m[1] . 'Test.php';

            if (!in_array($testFile, $modified, true)) {
                $hints[] = $file . ' was modified but ' . $testFile . ' was not — consider updating the test';
            }
        }

        foreach ($modified as $file) {
            if (!preg_match('#^src/Command/(.+)\.php$#', $file, $m)) {
                continue;
            }

            $testFile = 'tests/Command/' . $m[1] . 'Test.php';

            if (!in_array($testFile, $modified, true)) {
                $hints[] = $file . ' was modified but ' . $testFile . ' was not — consider updating the test';
            }
        }

        return $hints;
    }

    private function hasGit(): bool
    {
        $result = $this->exec('which git 2>/dev/null');

        return trim($result) !== '';
    }

    private function isGitRepo(string $projectDir): bool
    {
        $result = $this->exec('git -C ' . escapeshellarg($projectDir) . ' rev-parse --is-inside-work-tree 2>/dev/null');

        return trim($result) === 'true';
    }

    /** @return list<string> */
    private function getModifiedFiles(string $projectDir): array
    {
        $output = $this->exec('git -C ' . escapeshellarg($projectDir) . ' status --porcelain 2>/dev/null');
        $files = [];

        foreach (explode("\n", $output) as $line) {
            if (strlen($line) < 4) {
                continue;
            }

            // porcelain format: XY filename (status is first 2 chars, space, then path)
            $file = substr($line, 3);

            // Handle renames: "R  old -> new"
            if (str_contains($file, ' -> ')) {
                $file = explode(' -> ', $file)[1];
            }

            $files[] = $file;
        }

        return $files;
    }

    /** @internal visible for testing */
    protected function exec(string $command): string
    {
        return (string) shell_exec($command);
    }
}
