<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;
use Scafera\Kernel\Tool\FileFinder;

final class ServiceFinalValidator implements ValidatorInterface
{
    public function getName(): string
    {
        return 'Services are final';
    }

    public function validate(string $projectDir): array
    {
        $serviceDir = $projectDir . '/src/Service';
        if (!is_dir($serviceDir)) {
            return [];
        }

        $violations = [];

        foreach (FileFinder::findPhpFiles($serviceDir) as $file) {
            $relative = str_replace($projectDir . '/', '', $file);
            $contents = file_get_contents($file);

            if (!preg_match('/\bclass\s+\w+/', $contents)) {
                continue;
            }

            if (!preg_match('/\bfinal\s+class\b/', $contents)) {
                $violations[] = $relative . ' is not declared final — services should be final classes';
            }
        }

        return $violations;
    }
}
