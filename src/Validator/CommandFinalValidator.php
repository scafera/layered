<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;
use Scafera\Kernel\Tool\FileFinder;

final class CommandFinalValidator implements ValidatorInterface
{
    public function getId(): string
    {
        return 'layered.command-final';
    }

    public function getName(): string
    {
        return 'Commands are final';
    }

    public function validate(string $projectDir): array
    {
        $commandDir = $projectDir . '/src/Command';
        if (!is_dir($commandDir)) {
            return [];
        }

        $violations = [];

        foreach (FileFinder::findPhpFiles($commandDir) as $file) {
            $relative = str_replace($projectDir . '/', '', $file);
            $contents = file_get_contents($file);

            if (!preg_match('/\bclass\s+\w+/', $contents)) {
                continue;
            }

            if (!preg_match('/\bfinal\s+class\b/', $contents)) {
                $violations[] = $relative . ' is not declared final — commands must be final classes';
            }
        }

        return $violations;
    }
}
