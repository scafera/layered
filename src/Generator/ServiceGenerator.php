<?php

declare(strict_types=1);

namespace Scafera\Layered\Generator;

use Scafera\Kernel\Contract\GeneratorInterface;
use Scafera\Kernel\Generator\FileWriter;
use Scafera\Kernel\Generator\GeneratorInput;
use Scafera\Kernel\Generator\GeneratorResult;

final class ServiceGenerator implements GeneratorInterface
{
    public function getName(): string
    {
        return 'service';
    }

    public function getDescription(): string
    {
        return 'Create a new service';
    }

    public function getInputs(): array
    {
        return [
            new GeneratorInput('name', 'Service name (e.g. OrderProcessor)'),
        ];
    }

    public function generate(string $projectDir, array $inputs, FileWriter $writer): GeneratorResult
    {
        $name = $this->normalizeName($inputs['name']);
        $path = 'src/Service/' . $name . '.php';

        if ($writer->exists($projectDir, $path)) {
            return new GeneratorResult([], ["Service already exists: {$path}"]);
        }

        $namespace = $this->resolveNamespace($name);
        $className = $this->resolveClassName($name);

        $writer->write($projectDir, $path, $this->template($namespace, $className));

        return new GeneratorResult([$path]);
    }

    private function template(string $namespace, string $className): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$namespace};

        final class {$className}
        {
            public function execute(): mixed
            {
                // TODO: Implement business logic here.
            }
        }

        PHP;
    }

    private function resolveNamespace(string $name): string
    {
        if (!str_contains($name, '/')) {
            return 'App\\Service';
        }

        $parts = explode('/', $name);
        array_pop($parts);

        return 'App\\Service\\' . implode('\\', $parts);
    }

    private function normalizeName(string $name): string
    {
        $parts = explode('/', $name);
        $parts = array_map(fn(string $part) => ucfirst($part), $parts);

        return implode('/', $parts);
    }

    private function resolveClassName(string $name): string
    {
        if (str_contains($name, '/')) {
            $parts = explode('/', $name);

            return end($parts);
        }

        return $name;
    }
}
