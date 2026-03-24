<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;

final class CommandTestParityValidator implements ValidatorInterface
{
    public function getName(): string
    {
        return 'Command test parity';
    }

    public function validate(string $projectDir): array
    {
        $commandDir = $projectDir . '/src/Command';
        if (!is_dir($commandDir)) {
            return [];
        }

        $violations = [];

        foreach ($this->findPhpFiles($commandDir) as $file) {
            $relative = str_replace($commandDir . '/', '', $file);
            $testFile = $projectDir . '/tests/Command/' . str_replace('.php', 'Test.php', $relative);

            if (!is_file($testFile)) {
                $violations[] = 'src/Command/' . $relative . ' has no matching test. Expected: tests/Command/' . str_replace('.php', 'Test.php', $relative);
            }
        }

        return $violations;
    }

    /** @return list<string> */
    private function findPhpFiles(string $dir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
