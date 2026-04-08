<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;
use Scafera\Kernel\Tool\FileFinder;

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

        foreach (FileFinder::findPhpFiles($commandDir) as $file) {
            $relative = str_replace($commandDir . '/', '', $file);
            $testFile = $projectDir . '/tests/Command/' . str_replace('.php', 'Test.php', $relative);

            if (!is_file($testFile)) {
                $violations[] = 'src/Command/' . $relative . ' has no matching test. Expected: tests/Command/' . str_replace('.php', 'Test.php', $relative);
            }
        }

        return $violations;
    }
}
