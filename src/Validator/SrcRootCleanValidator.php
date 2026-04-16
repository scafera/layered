<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;
use Scafera\Kernel\Tool\ToleratedFiles;

final class SrcRootCleanValidator implements ValidatorInterface
{
    public function getName(): string
    {
        return 'src root cleanliness';
    }

    public function validate(string $projectDir): array
    {
        $srcDir = $projectDir . '/src';
        if (!is_dir($srcDir)) {
            return [];
        }

        $violations = [];
        $iterator = new \FilesystemIterator($srcDir, \FilesystemIterator::SKIP_DOTS);

        foreach ($iterator as $entry) {
            if ($entry->isDir()) {
                continue;
            }

            $name = $entry->getFilename();
            if (in_array($name, ToleratedFiles::names(), true)) {
                continue;
            }

            $violations[] = 'src/' . $name . ' is not allowed at src/ root — move it into the appropriate subfolder (Controller, Service, Entity, Repository, Form, Integration, Command)';
        }

        return $violations;
    }
}
