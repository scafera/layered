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

        $repoStatus = $this->checkGitRepo($projectDir);

        if ($repoStatus !== null) {
            return AdvisorStatus::skipped($repoStatus);
        }

        return AdvisorStatus::ready();
    }

    public function advise(string $projectDir): array
    {
        $output = $this->exec($this->git($projectDir) . ' status --porcelain 2>/dev/null');
        $allChanged = $this->parseFiles($output);
        $unstaged = $this->parseFiles($output, unstagedOnly: true);

        $hints = [];

        // Case 1: file changed (any status) but its test is not in git status at all
        $this->checkParity($allChanged, $allChanged, $hints, 'was modified but %s was not — consider updating the test');

        // Case 2: file has unstaged working tree changes but its test doesn't
        // e.g. controller is AM (staged + edited again) but test is A (staged only)
        $this->checkParity($unstaged, $unstaged, $hints, 'has unstaged changes but %s does not — did you forget to update the test?');

        return $hints;
    }

    /** @param list<string> $hints */
    private function checkParity(array $sourceFiles, array $targetFiles, array &$hints, string $message): void
    {
        $pairs = [
            '#^src/Controller/(.+)\.php$#' => 'tests/Controller/',
            '#^src/Command/(.+)\.php$#' => 'tests/Command/',
        ];

        foreach ($pairs as $pattern => $testDir) {
            foreach ($sourceFiles as $file) {
                if (!preg_match($pattern, $file, $m)) {
                    continue;
                }

                $testFile = $testDir . $m[1] . 'Test.php';

                if (!in_array($testFile, $targetFiles, true)) {
                    $hints[] = $file . ' ' . sprintf($message, $testFile);
                }
            }
        }
    }

    private function hasGit(): bool
    {
        $result = $this->exec('which git 2>/dev/null');

        return trim($result) !== '';
    }

    /** @return string|null null = ready, string = skip reason */
    private function checkGitRepo(string $projectDir): ?string
    {
        $result = $this->exec($this->git($projectDir) . ' rev-parse --is-inside-work-tree 2>&1');

        if (trim($result) === 'true') {
            return null;
        }

        return 'not a git repository';
    }

    private function git(string $projectDir): string
    {
        $dir = escapeshellarg($projectDir);

        return 'git -c safe.directory=' . $dir . ' -C ' . $dir;
    }

    /**
     * Parses git status --porcelain output.
     *
     * Porcelain format: XY filename
     * - X = index status, Y = working tree status
     * - unstagedOnly: only files where Y is not a space (working tree changes)
     *
     * @return list<string>
     */
    private function parseFiles(string $output, bool $unstagedOnly = false): array
    {
        $files = [];

        foreach (explode("\n", $output) as $line) {
            if (strlen($line) < 4) {
                continue;
            }

            if ($unstagedOnly && $line[1] === ' ') {
                continue;
            }

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
