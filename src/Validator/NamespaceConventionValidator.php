<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;

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

        foreach ($this->findPhpFiles($srcDir) as $file) {
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
