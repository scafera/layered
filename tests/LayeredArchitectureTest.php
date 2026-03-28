<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests;

use PHPUnit\Framework\TestCase;
use Scafera\Layered\LayeredArchitecture;

class LayeredArchitectureTest extends TestCase
{
    private LayeredArchitecture $arch;

    protected function setUp(): void
    {
        $this->arch = new LayeredArchitecture();
    }

    public function testNameIsLayered(): void
    {
        $this->assertSame('layered', $this->arch->getName());
    }

    public function testServiceDiscoveryNamespace(): void
    {
        $discovery = $this->arch->getServiceDiscovery('/dummy');

        $this->assertSame('App\\', $discovery['namespace']);
    }

    public function testServiceDiscoveryResource(): void
    {
        $discovery = $this->arch->getServiceDiscovery('/dummy');

        $this->assertSame('src/', $discovery['resource']);
    }

    public function testServiceDiscoveryExcludesEntity(): void
    {
        $discovery = $this->arch->getServiceDiscovery('/dummy');

        $this->assertContains('src/Entity', $discovery['exclude']);
    }

    public function testControllerPaths(): void
    {
        $this->assertSame(['src/Controller/'], $this->arch->getControllerPaths());
    }

    public function testStructureContainsAllFolders(): void
    {
        $structure = $this->arch->getStructure();

        $this->assertArrayHasKey('src/Controller', $structure);
        $this->assertArrayHasKey('src/Service', $structure);
        $this->assertArrayHasKey('src/Entity', $structure);
        $this->assertArrayHasKey('src/Command', $structure);
        $this->assertArrayHasKey('tests/Controller', $structure);
        $this->assertArrayHasKey('tests/Service', $structure);
        $this->assertArrayHasKey('tests/Command', $structure);
    }

    public function testValidatorsReturnTenClasses(): void
    {
        $this->assertCount(10, $this->arch->getValidators());
    }

    public function testGeneratorsReturnTwoClasses(): void
    {
        $this->assertCount(2, $this->arch->getGenerators());
    }

    public function testAdvisorsReturnOneClass(): void
    {
        $this->assertCount(1, $this->arch->getAdvisors());
    }
}
