<?php

declare(strict_types=1);

namespace Scafera\Layered;

use Scafera\Kernel\Contract\ArchitecturePackageInterface;

class LayeredArchitecture implements ArchitecturePackageInterface
{
    public function getName(): string
    {
        return 'layered';
    }

    public function getServiceDiscovery(string $projectDir): array
    {
        return [
            'namespace' => 'App\\',
            'resource'  => 'src/',
            'exclude'   => ['src/Entity'],
        ];
    }

    public function getControllerPaths(): array
    {
        return ['src/Controller/'];
    }

    public function getStructure(): array
    {
        return [
            'src/Controller' => 'HTTP controllers with attribute routing',
            'src/Service' => 'Business logic services',
            'src/Entity' => 'Doctrine entities',
            'src/Command' => 'Console commands',
            'tests/Controller' => 'Controller tests (WebTestCase)',
            'tests/Service' => 'Service unit tests',
            'tests/Command' => 'Command tests (KernelTestCase)',
        ];
    }

    public function getValidators(): array
    {
        return [];
    }

    public function getGenerators(): array
    {
        return [];
    }
}
