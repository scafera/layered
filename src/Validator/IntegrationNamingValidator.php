<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;
use Scafera\Kernel\Tool\FileFinder;

final class IntegrationNamingValidator implements ValidatorInterface
{
    public function getId(): string
    {
        return 'layered.integration-naming';
    }

    public function getName(): string
    {
        return 'Integration naming';
    }

    public function validate(string $projectDir): array
    {
        $integrationDir = $projectDir . '/src/Integration';
        if (!is_dir($integrationDir)) {
            return [];
        }

        $violations = [];

        foreach (FileFinder::findPhpFiles($integrationDir) as $file) {
            $relative = str_replace($integrationDir . '/', '', $file);
            $name = pathinfo($file, PATHINFO_FILENAME);
            $parts = explode('/', $relative);

            if (count($parts) < 2) {
                $violations[] = 'src/Integration/' . $relative . ' must be inside a vendor subfolder (e.g. src/Integration/Stripe/' . $name . '.php)';
                continue;
            }

            $vendor = $parts[0];

            if (str_starts_with($name, $vendor)) {
                $clean = substr($name, strlen($vendor));
                $dir = dirname($relative);
                $violations[] = 'src/Integration/' . $relative . ' repeats vendor prefix — rename to ' . $dir . '/' . $clean . '.php';
            }
        }

        return $violations;
    }
}
