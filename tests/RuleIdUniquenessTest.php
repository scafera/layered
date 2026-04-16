<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests;

use PHPUnit\Framework\TestCase;
use Scafera\Kernel\Validator\ConfigParameterValidator;
use Scafera\Kernel\Validator\KernelStructureValidator;
use Scafera\Layered\LayeredArchitecture;

/**
 * Guards against collisions in rule IDs across the kernel + layered rule set
 * — the two rule groups guaranteed to run in any `scafera/layered` project.
 *
 * Capability-package validators (database, log, etc.) are tagged via DI and
 * not visible from this test; their uniqueness is enforced by convention.
 */
final class RuleIdUniquenessTest extends TestCase
{
    public function testAllRuleIdsAreUnique(): void
    {
        $arch = new LayeredArchitecture();

        $ids = [];
        $source = [];

        foreach ($this->kernelValidators() as $v) {
            $ids[] = $v->getId();
            $source[$v->getId()][] = $v::class;
        }

        foreach ($arch->getValidators() as $v) {
            $ids[] = $v->getId();
            $source[$v->getId()][] = $v::class;
        }

        foreach ($arch->getAdvisors() as $a) {
            $ids[] = $a->getId();
            $source[$a->getId()][] = $a::class;
        }

        $counts = array_count_values($ids);
        $duplicates = array_filter($counts, static fn (int $n): bool => $n > 1);

        if (!empty($duplicates)) {
            $lines = [];
            foreach ($duplicates as $id => $n) {
                $lines[] = sprintf('%s (%d: %s)', $id, $n, implode(', ', $source[$id]));
            }
            self::fail("Duplicate rule IDs:\n  " . implode("\n  ", $lines));
        }

        self::assertSame(count($ids), count(array_unique($ids)));
    }

    public function testEveryIdFollowsPackageDotSlugConvention(): void
    {
        $arch = new LayeredArchitecture();

        $all = array_merge(
            $this->kernelValidators(),
            $arch->getValidators(),
            $arch->getAdvisors(),
        );

        foreach ($all as $rule) {
            self::assertMatchesRegularExpression(
                '/^[a-z][a-z0-9]*\.[a-z0-9]+(-[a-z0-9]+)*$/',
                $rule->getId(),
                sprintf('%s::getId() = %s violates <package>.<rule-slug> convention', $rule::class, $rule->getId()),
            );
        }
    }

    /** @return list<\Scafera\Kernel\Contract\ValidatorInterface> */
    private function kernelValidators(): array
    {
        return [
            new KernelStructureValidator(),
            new ConfigParameterValidator(),
        ];
    }
}
