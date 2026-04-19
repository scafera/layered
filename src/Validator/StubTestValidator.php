<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;
use Scafera\Kernel\Tool\FileFinder;

final class StubTestValidator implements ValidatorInterface
{
    public function getId(): string
    {
        return 'layered.stub-tests';
    }

    public function getName(): string
    {
        return 'Stub tests';
    }

    public function validate(string $projectDir): array
    {
        $testsDir = $projectDir . '/tests';
        if (!is_dir($testsDir)) {
            return [];
        }

        $violations = [];

        foreach (FileFinder::findPhpFiles($testsDir) as $file) {
            $contents = file_get_contents($file);
            $relative = str_replace($projectDir . '/', '', $file);

            if (preg_match('/(self|static|\$this)\s*(?:::|->)\s*markTestIncomplete\s*\(/', $contents)) {
                $violations[] = $relative . ': contains markTestIncomplete() — write a real test or remove the file';
            }
            if (preg_match('/->assertTrue\s*\(\s*true\s*\)/', $contents)) {
                $violations[] = $relative . ': contains assertTrue(true) — write a real assertion';
            }
            if (preg_match('/->assertFalse\s*\(\s*false\s*\)/', $contents)) {
                $violations[] = $relative . ': contains assertFalse(false) — write a real assertion';
            }
        }

        return $violations;
    }
}
