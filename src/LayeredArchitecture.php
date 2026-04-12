<?php

declare(strict_types=1);

namespace Scafera\Layered;

use Scafera\Kernel\Contract\ArchitecturePackageInterface;
use Scafera\Layered\Advisor\TestSyncAdvisor;
use Scafera\Layered\Generator\CommandGenerator;
use Scafera\Layered\Generator\ControllerGenerator;
use Scafera\Layered\Generator\ServiceGenerator;
use Scafera\Layered\Validator\CommandTestParityValidator;
use Scafera\Layered\Validator\ControllerLocationValidator;
use Scafera\Layered\Validator\ControllerNamingValidator;
use Scafera\Layered\Validator\ControllerTestParityValidator;
use Scafera\Layered\Validator\ImplicitExecutionValidator;
use Scafera\Layered\Validator\LayerDependencyValidator;
use Scafera\Layered\Validator\SingleActionControllerValidator;
use Scafera\Layered\Validator\NamespaceConventionValidator;
use Scafera\Layered\Validator\ServiceFinalValidator;
use Scafera\Layered\Validator\ServiceLocationValidator;
use Scafera\Layered\Validator\TestsDirectoryValidator;

final class LayeredArchitecture implements ArchitecturePackageInterface
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
            'src/Controller' => 'Single-action controllers with attribute routing',
            'src/Service' => 'Business logic services',
            'src/Entity' => 'Doctrine entities',
            'src/Repository' => 'Data access repositories',
            'src/Command' => 'Console commands',
            'tests/Controller' => 'Controller tests (WebTestCase)',
            'tests/Service' => 'Service unit tests',
            'tests/Command' => 'Command tests (CommandTestCase)',
        ];
    }

    public function getValidators(): array
    {
        return [
            new TestsDirectoryValidator(),
            new ControllerLocationValidator(),
            new ControllerTestParityValidator(),
            new CommandTestParityValidator(),
            new ServiceLocationValidator(),
            new ServiceFinalValidator(),
            new NamespaceConventionValidator(),
            new LayerDependencyValidator(),
            new ImplicitExecutionValidator(),
            new SingleActionControllerValidator(),
            new ControllerNamingValidator(),
        ];
    }

    public function getGenerators(): array
    {
        return [
            new ControllerGenerator(),
            new ServiceGenerator(),
            new CommandGenerator(),
        ];
    }

    public function getAdvisors(): array
    {
        return [
            new TestSyncAdvisor(),
        ];
    }
}
