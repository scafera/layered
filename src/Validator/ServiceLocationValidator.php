<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;

final class ServiceLocationValidator implements ValidatorInterface
{
    private const ALLOWED_DIRS = ['Controller', 'Service', 'Entity', 'Repository', 'Form', 'Integration', 'Command'];

    public function getId(): string
    {
        return 'layered.service-location';
    }

    public function getName(): string
    {
        return 'Service location';
    }

    public function validate(string $projectDir): array
    {
        $srcDir = $projectDir . '/src';
        if (!is_dir($srcDir)) {
            return [];
        }

        $violations = [];
        $iterator = new \DirectoryIterator($srcDir);

        foreach ($iterator as $entry) {
            if ($entry->isDot() || !$entry->isDir()) {
                continue;
            }

            $name = $entry->getFilename();
            if (!in_array($name, self::ALLOWED_DIRS, true)) {
                $violations[] = 'src/' . $name . '/ is not a recognized layered architecture directory. Expected: ' . implode(', ', self::ALLOWED_DIRS);
            }
        }

        return $violations;
    }
}
