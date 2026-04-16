<?php

declare(strict_types=1);

namespace Scafera\Layered;

use Scafera\Kernel\Contract\ArchitecturePackageInterface;
use Scafera\Layered\Advisor\TestSyncAdvisor;
use Scafera\Layered\Generator\CommandGenerator;
use Scafera\Layered\Generator\ControllerGenerator;
use Scafera\Layered\Generator\ServiceGenerator;
use Scafera\Layered\Validator\CommandFinalValidator;
use Scafera\Layered\Validator\CommandLocationValidator;
use Scafera\Layered\Validator\CommandTestParityValidator;
use Scafera\Layered\Validator\ConfigFileWhitelistValidator;
use Scafera\Layered\Validator\ControllerFinalValidator;
use Scafera\Layered\Validator\ControllerLocationValidator;
use Scafera\Layered\Validator\ControllerNamingValidator;
use Scafera\Layered\Validator\ControllerTestParityValidator;
use Scafera\Layered\Validator\EntityLocationValidator;
use Scafera\Layered\Validator\ImplicitExecutionValidator;
use Scafera\Layered\Validator\IntegrationFinalValidator;
use Scafera\Layered\Validator\LayerDependencyValidator;
use Scafera\Layered\Validator\RepositoryFinalValidator;
use Scafera\Layered\Validator\SingleActionControllerValidator;
use Scafera\Layered\Validator\NamespaceConventionValidator;
use Scafera\Layered\Validator\ServiceFinalValidator;
use Scafera\Layered\Validator\ServiceLocationValidator;
use Scafera\Layered\Validator\IntegrationNamingValidator;
use Scafera\Layered\Validator\ServiceNamingValidator;
use Scafera\Layered\Validator\SrcRootCleanValidator;
use Scafera\Layered\Validator\SupportRootCleanValidator;
use Scafera\Layered\Validator\TestsDirectoryValidator;
use Scafera\Layered\Validator\TestsRootCleanValidator;

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
            'src/Form' => 'Complex form definitions (Symfony Form types allowed)',
            'src/Integration' => 'Third-party service wrappers',
            'src/Command' => 'Console commands',
            'tests/Controller' => 'Controller tests (WebTestCase)',
            'tests/Service' => 'Service unit tests',
            'tests/Command' => 'Command tests (CommandTestCase)',
        ];
    }

    public function getValidators(): array
    {
        return [
            // Directory cleanliness
            new TestsDirectoryValidator(),
            new ConfigFileWhitelistValidator(),
            new SrcRootCleanValidator(),
            new SupportRootCleanValidator(),
            new TestsRootCleanValidator(),

            // Controllers
            new ControllerLocationValidator(),
            new ControllerFinalValidator(),
            new ControllerTestParityValidator(),
            new ControllerNamingValidator(),
            new SingleActionControllerValidator(),

            // Commands
            new CommandTestParityValidator(),
            new CommandFinalValidator(),
            new CommandLocationValidator(),

            // Entities
            new EntityLocationValidator(),

            // Services / Repositories / Integrations / layers at large
            new ServiceLocationValidator(),
            new ServiceFinalValidator(),
            new ServiceNamingValidator(),
            new RepositoryFinalValidator(),
            new IntegrationNamingValidator(),
            new IntegrationFinalValidator(),

            // Cross-cutting
            new NamespaceConventionValidator(),
            new LayerDependencyValidator(),
            new ImplicitExecutionValidator(),
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

    public function getEntityMapping(): ?array
    {
        return ['dir' => 'src/Entity', 'namespace' => 'App\\Entity'];
    }

    public function getTranslationsDir(): ?string
    {
        return 'resources/translations';
    }

    public function getStorageDir(): ?string
    {
        return 'var/uploads';
    }

    public function getAssetsDir(): ?string
    {
        return 'resources/assets';
    }

    public function getTemplatesDir(): ?string
    {
        return 'resources/templates';
    }
}
