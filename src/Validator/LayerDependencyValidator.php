<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;
use Scafera\Kernel\Tool\FileFinder;

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
            'App\\Integration' => 'import Integration directly — use a Service instead',
            'App\\Command' => 'import Command — controllers must not depend on console commands',
            // App\Form is allowed — controllers are the only layer that uses forms
        ],
        'Service' => [
            'App\\Form' => 'import Form — forms are an HTTP concern, not business logic. Services receive DTOs, not form objects',
        ],
        'Repository' => [
            'App\\Controller' => 'import Controller — repositories must not depend on controllers',
            'App\\Service' => 'import Service — repositories must not depend on services',
            'App\\Form' => 'import Form — repositories must not depend on forms',
            'App\\Integration' => 'import Integration — repositories must not depend on integrations',
            'App\\Command' => 'import Command — repositories must not depend on console commands',
        ],
        'Form' => [
            'App\\Controller' => 'import Controller — forms must not depend on controllers',
            'App\\Service' => 'import Service — forms must not contain business logic',
            'App\\Repository' => 'import Repository — forms must not query the database (receive data, don\'t fetch it)',
            'App\\Integration' => 'import Integration — forms must not call third-party services',
            'App\\Command' => 'import Command — forms must not depend on console commands',
            // App\Entity is allowed — forms may reference entities for type hints and structure
        ],
        'Entity' => [
            'App\\Controller' => 'import Controller — entities must not depend on any other layer',
            'App\\Service' => 'import Service — entities must not depend on any other layer',
            'App\\Repository' => 'import Repository — entities must not depend on any other layer',
            'App\\Form' => 'import Form — entities must not depend on any other layer',
            'App\\Integration' => 'import Integration — entities must not depend on any other layer',
            'App\\Command' => 'import Command — entities must not depend on any other layer',
        ],
        'Command' => [
            'App\\Controller' => 'import Controller — commands must not depend on controllers',
            'App\\Entity' => 'import Entity directly — use a Service instead',
            'App\\Repository' => 'import Repository directly — use a Service instead',
            'App\\Form' => 'import Form — commands must not depend on forms',
            'App\\Integration' => 'import Integration directly — use a Service instead',
        ],
        'Integration' => [
            'App\\Controller' => 'import Controller — integrations must not depend on controllers',
            'App\\Service' => 'import Service — integrations must not depend on services',
            'App\\Repository' => 'import Repository — integrations must not depend on repositories',
            'App\\Form' => 'import Form — integrations must not depend on forms',
            'App\\Command' => 'import Command — integrations must not depend on console commands',
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

            foreach (FileFinder::findPhpFiles($layerDir) as $file) {
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
}
