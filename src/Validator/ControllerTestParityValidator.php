<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;
use Scafera\Kernel\Tool\FileFinder;

final class ControllerTestParityValidator implements ValidatorInterface
{
    public function getId(): string
    {
        return 'layered.controller-test-parity';
    }

    public function getName(): string
    {
        return 'Controller test parity';
    }

    public function validate(string $projectDir): array
    {
        $controllerDir = $projectDir . '/src/Controller';
        if (!is_dir($controllerDir)) {
            return [];
        }

        $violations = [];

        foreach (FileFinder::findPhpFiles($controllerDir) as $file) {
            $relative = str_replace($controllerDir . '/', '', $file);
            $testFile = $projectDir . '/tests/Controller/' . str_replace('.php', 'Test.php', $relative);

            if (!is_file($testFile)) {
                $violations[] = 'src/Controller/' . $relative . ' has no matching test. Expected: tests/Controller/' . str_replace('.php', 'Test.php', $relative);
            }
        }

        return $violations;
    }
}
