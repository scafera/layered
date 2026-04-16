<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;
use Scafera\Kernel\Tool\FileFinder;

final class ServiceNamingValidator implements ValidatorInterface
{
    public function getId(): string
    {
        return 'layered.service-naming';
    }

    public function getName(): string
    {
        return 'Service naming';
    }

    public function validate(string $projectDir): array
    {
        $serviceDir = $projectDir . '/src/Service';
        if (!is_dir($serviceDir)) {
            return [];
        }

        $violations = [];

        foreach (FileFinder::findPhpFiles($serviceDir) as $file) {
            $relative = str_replace($serviceDir . '/', '', $file);
            $name = pathinfo($file, PATHINFO_FILENAME);

            if (str_ends_with($name, 'Service')) {
                $clean = substr($name, 0, -7);
                $dir = dirname($relative);
                $suggestion = $dir === '.' ? $clean . '.php' : $dir . '/' . $clean . '.php';
                $violations[] = 'src/Service/' . $relative . ' uses Service suffix — rename to ' . $suggestion;
            }
        }

        return $violations;
    }
}
