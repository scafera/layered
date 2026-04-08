<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;
use Scafera\Kernel\Tool\FileFinder;

final class NamespaceConventionValidator implements ValidatorInterface
{
    public function getName(): string
    {
        return 'Namespace conventions';
    }

    public function validate(string $projectDir): array
    {
        $srcDir = $projectDir . '/src';
        if (!is_dir($srcDir)) {
            return [];
        }

        $violations = [];

        foreach (FileFinder::findPhpFiles($srcDir) as $file) {
            $relative = str_replace($srcDir . '/', '', $file);
            $contents = file_get_contents($file);

            if (!preg_match('/namespace\s+([^;]+);/', $contents, $m)) {
                continue;
            }

            $actualNamespace = $m[1];
            $expectedNamespace = 'App\\' . str_replace('/', '\\', dirname($relative));

            if ($expectedNamespace === 'App\\.') {
                $expectedNamespace = 'App';
            }

            if ($actualNamespace !== $expectedNamespace) {
                $violations[] = 'src/' . $relative . ': namespace is "' . $actualNamespace . '", expected "' . $expectedNamespace . '"';
            }
        }

        return $violations;
    }
}
