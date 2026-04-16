<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;
use Scafera\Kernel\Tool\FileFinder;

final class IntegrationFinalValidator implements ValidatorInterface
{
    public function getId(): string
    {
        return 'layered.integration-final';
    }

    public function getName(): string
    {
        return 'Integrations are final';
    }

    public function validate(string $projectDir): array
    {
        $integrationDir = $projectDir . '/src/Integration';
        if (!is_dir($integrationDir)) {
            return [];
        }

        $violations = [];

        foreach (FileFinder::findPhpFiles($integrationDir) as $file) {
            $relative = str_replace($projectDir . '/', '', $file);
            $contents = file_get_contents($file);

            if (!preg_match('/\bclass\s+\w+/', $contents)) {
                continue;
            }

            if (!preg_match('/\bfinal\s+class\b/', $contents)) {
                $violations[] = $relative . ' is not declared final — integrations must be final classes';
            }
        }

        return $violations;
    }
}
