<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;
use Scafera\Kernel\Tool\FileFinder;

final class EntityLocationValidator implements ValidatorInterface
{
    public function getId(): string
    {
        return 'layered.entity-location';
    }

    public function getName(): string
    {
        return 'Entity location';
    }

    public function validate(string $projectDir): array
    {
        $srcDir = $projectDir . '/src';
        if (!is_dir($srcDir)) {
            return [];
        }

        $violations = [];

        foreach (FileFinder::findPhpFiles($srcDir) as $file) {
            $contents = file_get_contents($file);

            if (!$this->isEntity($contents)) {
                continue;
            }

            $relative = str_replace($srcDir . '/', '', $file);

            if (!str_starts_with($relative, 'Entity/')) {
                $violations[] = 'src/' . $relative . ' carries #[Table] but is not in src/Entity/ — move it to src/Entity/';
            }
        }

        return $violations;
    }

    private function isEntity(string $contents): bool
    {
        // Short-form attribute requires the matching use import to be a Scafera entity.
        $hasImport = (bool) preg_match('/use\s+Scafera\\\\Database\\\\Mapping\\\\Table\b/', $contents);
        $hasShortAttr = (bool) preg_match('/#\[Table\b/', $contents);

        if ($hasImport && $hasShortAttr) {
            return true;
        }

        // FQN-form attribute stands on its own.
        return (bool) preg_match('/#\[\\\\?Scafera\\\\Database\\\\Mapping\\\\Table\b/', $contents);
    }
}
