<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;
use Scafera\Kernel\Tool\FileFinder;

final class ControllerLocationValidator implements ValidatorInterface
{
    public function getId(): string
    {
        return 'layered.controller-location';
    }

    public function getName(): string
    {
        return 'Controller location';
    }

    public function validate(string $projectDir): array
    {
        $controllerDir = $projectDir . '/src/Controller';
        if (!is_dir($controllerDir)) {
            return [];
        }

        $violations = [];
        $srcDir = $projectDir . '/src';

        foreach (FileFinder::findPhpFiles($srcDir) as $file) {
            $relative = str_replace($srcDir . '/', '', $file);
            $contents = file_get_contents($file);

            if (!$this->isController($contents)) {
                continue;
            }

            if (!str_starts_with($relative, 'Controller/')) {
                $violations[] = 'src/' . $relative . ' contains a controller but is not in src/Controller/';
            }
        }

        return $violations;
    }

    private function isController(string $contents): bool
    {
        return (bool) preg_match('/use\s+Scafera\\\\Kernel\\\\Http\\\\Route\b/', $contents);
    }
}
