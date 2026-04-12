<?php

declare(strict_types=1);

namespace Scafera\Layered\Generator;

use Scafera\Kernel\Contract\GeneratorInterface;
use Scafera\Kernel\Generator\FileWriter;
use Scafera\Kernel\Generator\GeneratorInput;
use Scafera\Kernel\Generator\GeneratorResult;

final class CommandGenerator implements GeneratorInterface
{
    public function getName(): string
    {
        return 'command';
    }

    public function getDescription(): string
    {
        return 'Create a new console command with its test';
    }

    public function getInputs(): array
    {
        return [
            new GeneratorInput('name', 'Command name (e.g. ImportUsers, Report/Generate)'),
        ];
    }

    public function generate(string $projectDir, array $inputs, FileWriter $writer): GeneratorResult
    {
        $name = $this->normalizeName($inputs['name']);
        $className = $this->resolveClassName($name);

        if (str_ends_with($className, 'Command')) {
            $clean = substr($className, 0, -7);

            return new GeneratorResult([], [
                "Do not use the 'Command' suffix. Use: scafera make command {$this->replaceClassName($name, $clean)}",
            ]);
        }

        $commandPath = 'src/Command/' . $name . '.php';
        $testPath = 'tests/Command/' . $name . 'Test.php';

        if ($writer->exists($projectDir, $commandPath)) {
            return new GeneratorResult([], ["Command already exists: {$commandPath}"]);
        }

        $namespace = $this->resolveNamespace('App\\Command', $name);
        $testNamespace = $this->resolveNamespace('App\\Tests\\Command', $name);
        $commandName = $this->resolveCommandName($name);

        $writer->write($projectDir, $commandPath, $this->commandTemplate($namespace, $className, $commandName));
        $writer->write($projectDir, $testPath, $this->testTemplate($testNamespace, $className, $commandName));

        return new GeneratorResult([$commandPath, $testPath]);
    }

    private function commandTemplate(string $namespace, string $className, string $commandName): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$namespace};

        use Scafera\Kernel\Console\Attribute\AsCommand;
        use Scafera\Kernel\Console\Command;
        use Scafera\Kernel\Console\Input;
        use Scafera\Kernel\Console\Output;

        #[AsCommand('{$commandName}')]
        final class {$className} extends Command
        {
            // TODO: Create a service with "scafera make service" and inject it here.
            // public function __construct(
            //     private readonly YourService \$service,
            // ) {
            //     parent::__construct();
            // }

            protected function handle(Input \$input, Output \$output): int
            {
                // TODO: Replace with a call to an injected service.
                // Commands should not contain business logic.
                \$output->writeln('{$className} executed.');

                return self::SUCCESS;
            }
        }

        PHP;
    }

    private function testTemplate(string $namespace, string $className, string $commandName): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$namespace};

        use Scafera\Kernel\Test\CommandTestCase;

        class {$className}Test extends CommandTestCase
        {
            public function testExecutes(): void
            {
                \$result = \$this->runCommand('{$commandName}');

                \$result->assertSuccessful();
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
        array_pop($parts);

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

    private function resolveCommandName(string $name): string
    {
        $parts = explode('/', $name);
        $segments = array_map(fn(string $part) => $this->toKebabCase($part), $parts);

        return 'app:' . implode(':', $segments);
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
