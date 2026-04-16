<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;

final class TestsDirectoryValidator implements ValidatorInterface
{
    public function getId(): string
    {
        return 'layered.tests-directory';
    }

    public function getName(): string
    {
        return 'Tests directory';
    }

    public function validate(string $projectDir): array
    {
        if (is_dir($projectDir . '/tests')) {
            return [];
        }

        return ["Directory 'tests/' does not exist. Create it to hold your test suite."];
    }
}
