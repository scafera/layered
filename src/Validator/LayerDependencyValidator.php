<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;

final class LayerDependencyValidator implements ValidatorInterface
{
    /**
     * Layer rules: key = layer directory, value = forbidden import namespaces.
     * Enforces: Controller → Service → Repository → Entity (downward only).
     */
    private const FORBIDDEN_IMPORTS = [
        'Controller' => [
            'App\\Entity' => 'import Entity directly — use a Service instead',
            'App\\Repository' => 'import Repository directly — use a Service instead',
            'App\\Command' => 'import Command — controllers must not depend on console commands',
        ],
        'Repository' => [
            'App\\Controller' => 'import Controller — repositories must not depend on controllers',
            'App\\Service' => 'import Service — repositories must not depend on services',
            'App\\Command' => 'import Command — repositories must not depend on console commands',
        ],
        'Entity' => [
            'App\\Controller' => 'import Controller — entities must not depend on any other layer',
            'App\\Service' => 'import Service — entities must not depend on any other layer',
            'App\\Repository' => 'import Repository — entities must not depend on any other layer',
            'App\\Command' => 'import Command — entities must not depend on any other layer',
        ],
        'Command' => [
            'App\\Controller' => 'import Controller — commands must not depend on controllers',
            'App\\Entity' => 'import Entity directly — use a Service instead',
            'App\\Repository' => 'import Repository directly — use a Service instead',
        ],
    ];

    public function getName(): string
    {
        return 'Layer dependencies';
    }

    public function validate(string $projectDir): array
    {
        $srcDir = $projectDir . '/src';
        if (!is_dir($srcDir)) {
            return [];
        }

        $violations = [];

        foreach (self::FORBIDDEN_IMPORTS as $layer => $forbidden) {
            $layerDir = $srcDir . '/' . $layer;
            if (!is_dir($layerDir)) {
                continue;
            }

            foreach ($this->findPhpFiles($layerDir) as $file) {
                $relative = str_replace($projectDir . '/', '', $file);
                $contents = file_get_contents($file);

                foreach ($forbidden as $namespace => $reason) {
                    $pattern = '/^use\s+' . preg_quote($namespace, '/') . '\\\\/m';
                    if (preg_match($pattern, $contents)) {
                        $violations[] = $relative . ' must not ' . $reason;
                    }
                }
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
