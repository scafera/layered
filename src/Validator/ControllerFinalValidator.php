<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;
use Scafera\Kernel\Tool\FileFinder;

final class ControllerFinalValidator implements ValidatorInterface
{
    public function getId(): string
    {
        return 'layered.controller-final';
    }

    public function getName(): string
    {
        return 'Controllers are final';
    }

    public function validate(string $projectDir): array
    {
        $controllerDir = $projectDir . '/src/Controller';
        if (!is_dir($controllerDir)) {
            return [];
        }

        $violations = [];

        foreach (FileFinder::findPhpFiles($controllerDir) as $file) {
            $relative = str_replace($projectDir . '/', '', $file);
            $contents = file_get_contents($file);

            if (!preg_match('/\bclass\s+\w+/', $contents)) {
                continue;
            }

            if (!preg_match('/\bfinal\s+class\b/', $contents)) {
                $violations[] = $relative . ' is not declared final — controllers must be final classes';
            }
        }

        return $violations;
    }
}
