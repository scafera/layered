<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;
use Scafera\Kernel\Tool\FileFinder;

final class CommandLocationValidator implements ValidatorInterface
{
    public function getId(): string
    {
        return 'layered.command-location';
    }

    public function getName(): string
    {
        return 'Command location';
    }

    public function validate(string $projectDir): array
    {
        $srcDir = $projectDir . '/src';
        if (!is_dir($srcDir)) {
            return [];
        }

        $violations = [];

        foreach (FileFinder::findPhpFiles($srcDir) as $file) {
            $contents = file_get_contents($file);

            if (!$this->isCommand($contents)) {
                continue;
            }

            $relative = str_replace($srcDir . '/', '', $file);

            if (!str_starts_with($relative, 'Command/')) {
                $violations[] = 'src/' . $relative . ' is a command but is not in src/Command/ — move it to src/Command/';
            }
        }

        return $violations;
    }

    private function isCommand(string $contents): bool
    {
        // Short-form #[AsCommand] requires the matching use import to be the Scafera one.
        if (
            preg_match('/use\s+Scafera\\\\Kernel\\\\Console\\\\Attribute\\\\AsCommand\b/', $contents)
            && preg_match('/#\[AsCommand\b/', $contents)
        ) {
            return true;
        }

        // FQN-form attribute stands on its own.
        if (preg_match('/#\[\\\\?Scafera\\\\Kernel\\\\Console\\\\Attribute\\\\AsCommand\b/', $contents)) {
            return true;
        }

        // Short-form `extends Command` requires the matching use import to be the Scafera one.
        if (
            preg_match('/use\s+Scafera\\\\Kernel\\\\Console\\\\Command\b/', $contents)
            && preg_match('/\bextends\s+Command\b/', $contents)
        ) {
            return true;
        }

        // FQN-form extends stands on its own.
        if (preg_match('/\bextends\s+\\\\?Scafera\\\\Kernel\\\\Console\\\\Command\b/', $contents)) {
            return true;
        }

        return false;
    }
}
