<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;
use Scafera\Kernel\Tool\ToleratedFiles;

final class SupportRootCleanValidator implements ValidatorInterface
{
    public function getId(): string
    {
        return 'layered.support-root-clean';
    }

    public function getName(): string
    {
        return 'support root cleanliness';
    }

    public function validate(string $projectDir): array
    {
        $supportDir = $projectDir . '/support';
        if (!is_dir($supportDir)) {
            return [];
        }

        $violations = [];
        $iterator = new \FilesystemIterator($supportDir, \FilesystemIterator::SKIP_DOTS);

        foreach ($iterator as $entry) {
            if ($entry->isDir()) {
                continue;
            }

            $name = $entry->getFilename();
            if (in_array($name, ToleratedFiles::names(), true)) {
                continue;
            }

            $violations[] = 'support/' . $name . ' is not allowed at support/ root — move it into migrations/, seeds/, or patches/';
        }

        return $violations;
    }
}
