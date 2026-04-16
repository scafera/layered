<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;
use Scafera\Kernel\Tool\FileFinder;

final class RepositoryFinalValidator implements ValidatorInterface
{
    public function getName(): string
    {
        return 'Repositories are final';
    }

    public function validate(string $projectDir): array
    {
        $repositoryDir = $projectDir . '/src/Repository';
        if (!is_dir($repositoryDir)) {
            return [];
        }

        $violations = [];

        foreach (FileFinder::findPhpFiles($repositoryDir) as $file) {
            $relative = str_replace($projectDir . '/', '', $file);
            $contents = file_get_contents($file);

            if (!preg_match('/\bclass\s+\w+/', $contents)) {
                continue;
            }

            if (!preg_match('/\bfinal\s+class\b/', $contents)) {
                $violations[] = $relative . ' is not declared final — repositories must be final classes';
            }
        }

        return $violations;
    }
}
