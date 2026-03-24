<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;

final class SingleActionControllerValidator implements ValidatorInterface
{
    public function getName(): string
    {
        return 'Single-action controllers';
    }

    public function validate(string $projectDir): array
    {
        $controllerDir = $projectDir . '/src/Controller';
        if (!is_dir($controllerDir)) {
            return [];
        }

        $violations = [];

        foreach ($this->findPhpFiles($controllerDir) as $file) {
            $relative = str_replace($projectDir . '/', '', $file);
            $contents = file_get_contents($file);

            if (!preg_match('/public\s+function\s+__invoke\s*\(/', $contents)) {
                $violations[] = $relative . ' is not invokable — controllers must define __invoke()';
                continue;
            }

            preg_match_all('/public\s+function\s+(\w+)\s*\(/', $contents, $matches);
            $publicMethods = array_diff($matches[1], ['__construct', '__invoke']);

            if ($publicMethods !== []) {
                $violations[] = $relative . ' has extra public methods (' . implode(', ', $publicMethods) . ') — controllers must be single-action (__invoke only)';
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
