<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;
use Scafera\Kernel\Tool\ToleratedFiles;

final class TestsRootCleanValidator implements ValidatorInterface
{
    private const EXTRA_TOLERATED = [
        'phpunit.xml',
        'phpunit.dist.xml',
        'phpunit.xml.dist',
        'bootstrap.php',
        '.env.test',
        '.env.test.local',
    ];

    public function getId(): string
    {
        return 'layered.tests-root-clean';
    }

    public function getName(): string
    {
        return 'tests root cleanliness';
    }

    public function validate(string $projectDir): array
    {
        $testsDir = $projectDir . '/tests';
        if (!is_dir($testsDir)) {
            return [];
        }

        $violations = [];
        $iterator = new \FilesystemIterator($testsDir, \FilesystemIterator::SKIP_DOTS);

        foreach ($iterator as $entry) {
            if ($entry->isDir()) {
                continue;
            }

            $name = $entry->getFilename();
            if (
                in_array($name, ToleratedFiles::names(), true)
                || in_array($name, self::EXTRA_TOLERATED, true)
            ) {
                continue;
            }

            $violations[] = 'tests/' . $name . ' is not allowed at tests/ root — tests must mirror source layout (e.g., tests/Controller/, tests/Service/, tests/Command/)';
        }

        return $violations;
    }
}
