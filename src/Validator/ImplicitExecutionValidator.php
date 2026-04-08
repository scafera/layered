<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;
use Scafera\Kernel\Tool\FileFinder;

final class ImplicitExecutionValidator implements ValidatorInterface
{
    public function getName(): string
    {
        return 'No implicit execution';
    }

    public function validate(string $projectDir): array
    {
        $srcDir = $projectDir . '/src';
        if (!is_dir($srcDir)) {
            return [];
        }

        $violations = [];

        foreach (FileFinder::findPhpFiles($srcDir) as $file) {
            $relative = str_replace($projectDir . '/', '', $file);
            $contents = file_get_contents($file);

            if (preg_match('/use\s+Symfony\\\\Component\\\\EventDispatcher\\\\EventSubscriberInterface\b/', $contents)) {
                $violations[] = $relative . ' implements EventSubscriberInterface — implicit execution is not supported in userland';
            }

            if (preg_match('/#\[AsEventListener\b/', $contents)) {
                $violations[] = $relative . ' uses #[AsEventListener] — implicit execution is not supported in userland';
            }
        }

        return $violations;
    }
}
