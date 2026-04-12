<?php

declare(strict_types=1);

namespace Scafera\Layered\Generator;

use Scafera\Kernel\Contract\GeneratorInterface;
use Scafera\Kernel\Generator\FileWriter;
use Scafera\Kernel\Generator\GeneratorInput;
use Scafera\Kernel\Generator\GeneratorResult;

final class ControllerGenerator implements GeneratorInterface
{
    public function getName(): string
    {
        return 'controller';
    }

    public function getDescription(): string
    {
        return 'Create a new controller with its test';
    }

    public function getInputs(): array
    {
        return [
            new GeneratorInput('name', 'Controller name (e.g. Home, Api/Status)'),
        ];
    }

    public function generate(string $projectDir, array $inputs, FileWriter $writer): GeneratorResult
    {
        $name = $this->normalizeName($inputs['name']);

        $className = $this->resolveClassName($name);

        if (str_ends_with($className, 'Controller')) {
            $clean = substr($className, 0, -10);

            return new GeneratorResult([], [
                "Do not use the 'Controller' suffix. Use: scafera make:controller {$this->replaceClassName($name, $clean)}",
            ]);
        }

        if (!str_contains($name, '/') && !preg_match('/^[A-Z][a-z0-9]*$/', $name)) {
            return new GeneratorResult([], [
                "'{$name}' is a multi-word name and cannot live at root level. Use: scafera make:controller <Group>/{$name}",
            ]);
        }

        $controllerPath = 'src/Controller/' . $name . '.php';
        $testPath = 'tests/Controller/' . $name . 'Test.php';

        if ($writer->exists($projectDir, $controllerPath)) {
            return new GeneratorResult([], ["Controller already exists: {$controllerPath}"]);
        }

        $namespace = $this->resolveNamespace('App\\Controller', $name);
        $testNamespace = $this->resolveNamespace('App\\Tests\\Controller', $name);
        $className = $this->resolveClassName($name);
        $route = $this->resolveRoute($name);

        $writer->write($projectDir, $controllerPath, $this->controllerTemplate($namespace, $className, $route));
        $writer->write($projectDir, $testPath, $this->testTemplate($testNamespace, $className, $route));

        return new GeneratorResult([$controllerPath, $testPath]);
    }

    private function controllerTemplate(string $namespace, string $className, string $route): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$namespace};

        use Scafera\Kernel\Http\Response;
        use Scafera\Kernel\Http\Route;

        #[Route('{$route}', methods: 'GET')]
        final class {$className}
        {
            // TODO: Create a service with "scafera make:service" and inject it here.
            // public function __construct(
            //     private readonly YourService \$service,
            // ) {
            // }

            public function __invoke(): Response
            {
                // TODO: Replace with a call to an injected service.
                // Controllers should not contain business logic.
                return new Response('{$className}');
            }
        }

        PHP;
    }

    private function testTemplate(string $namespace, string $className, string $route): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$namespace};

        use Scafera\Kernel\Test\WebTestCase;

        class {$className}Test extends WebTestCase
        {
            public function testReturns200(): void
            {
                \$response = \$this->get('{$route}');

                \$response->assertOk();
            }
        }

        PHP;
    }

    private function resolveNamespace(string $base, string $name): string
    {
        if (!str_contains($name, '/')) {
            return $base;
        }

        $parts = explode('/', $name);
        array_pop($parts); // Remove class name

        return $base . '\\' . implode('\\', $parts);
    }

    private function resolveClassName(string $name): string
    {
        if (str_contains($name, '/')) {
            $parts = explode('/', $name);

            return end($parts);
        }

        return $name;
    }

    private function normalizeName(string $name): string
    {
        $parts = explode('/', $name);
        $parts = array_map(fn(string $part) => ucfirst($part), $parts);

        return implode('/', $parts);
    }

    private function resolveRoute(string $name): string
    {
        $parts = explode('/', $name);
        $segments = array_map(fn(string $part) => $this->toKebabCase($part), $parts);

        return '/' . implode('/', $segments);
    }

    private function replaceClassName(string $name, string $newClassName): string
    {
        if (!str_contains($name, '/')) {
            return $newClassName;
        }

        $parts = explode('/', $name);
        $parts[array_key_last($parts)] = $newClassName;

        return implode('/', $parts);
    }

    private function toKebabCase(string $value): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $value));
    }

}
