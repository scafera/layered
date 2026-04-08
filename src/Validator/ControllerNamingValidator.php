<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;
use Scafera\Kernel\Tool\FileFinder;

final class ControllerNamingValidator implements ValidatorInterface
{
    public function getName(): string
    {
        return 'Controller naming';
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
            $name = pathinfo($file, PATHINFO_FILENAME);

            if (str_ends_with($name, 'Controller')) {
                $clean = substr($name, 0, -10);
                $dir = dirname($relative);
                $suggestion = $dir === '.' ? $clean . '.php' : $dir . '/' . $clean . '.php';
                $violations[] = 'src/Controller/' . $relative . ' uses Controller suffix — rename to ' . $suggestion;
                continue;
            }

            if (!str_contains($relative, '/') && !preg_match('/^[A-Z][a-z0-9]*$/', $name)) {
                $violations[] = 'src/Controller/' . $relative . ' must be a single-word name. Move to a subfolder (e.g. src/Controller/' . $this->suggestPath($name) . ')';
            }
        }

        return $violations;
    }

    private function suggestPath(string $name): string
    {
        $words = preg_split('/(?=[A-Z])/', $name, -1, PREG_SPLIT_NO_EMPTY);

        if (count($words) < 2) {
            return $name . '/' . $name . '.php';
        }

        $action = array_shift($words);
        $domain = implode('', $words);

        return $domain . '/' . $action . '.php';
    }
}
