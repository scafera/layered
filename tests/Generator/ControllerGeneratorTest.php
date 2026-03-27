<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests\Generator;

use PHPUnit\Framework\TestCase;
use Scafera\Kernel\Generator\FileWriter;
use Scafera\Layered\Generator\ControllerGenerator;

class ControllerGeneratorTest extends TestCase
{
    private ControllerGenerator $generator;
    private FileWriter $writer;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->generator = new ControllerGenerator();
        $this->writer = new FileWriter();
        $this->tmpDir = sys_get_temp_dir() . '/scafera_test_' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testName(): void
    {
        $this->assertSame('controller', $this->generator->getName());
    }

    public function testGeneratesControllerAndTest(): void
    {
        $result = $this->generator->generate($this->tmpDir, ['name' => 'Home'], $this->writer);

        $this->assertCount(2, $result->filesCreated);
        $this->assertSame('src/Controller/Home.php', $result->filesCreated[0]);
        $this->assertSame('tests/Controller/HomeTest.php', $result->filesCreated[1]);
        $this->assertFileExists($this->tmpDir . '/src/Controller/Home.php');
        $this->assertFileExists($this->tmpDir . '/tests/Controller/HomeTest.php');
    }

    public function testControllerContentHasCorrectNamespace(): void
    {
        $this->generator->generate($this->tmpDir, ['name' => 'Home'], $this->writer);

        $content = file_get_contents($this->tmpDir . '/src/Controller/Home.php');
        $this->assertStringContainsString('namespace App\Controller;', $content);
        $this->assertStringContainsString('final class Home', $content);
        $this->assertStringContainsString("declare(strict_types=1);", $content);
        $this->assertStringContainsString('#[Route', $content);
        $this->assertStringContainsString('__invoke', $content);
        $this->assertStringContainsString('use Scafera\Kernel\Http\Response;', $content);
        $this->assertStringContainsString('private readonly YourService', $content);
    }

    public function testNestedControllerHasCorrectNamespaceAndRoute(): void
    {
        $this->generator->generate($this->tmpDir, ['name' => 'Api/Status'], $this->writer);

        $content = file_get_contents($this->tmpDir . '/src/Controller/Api/Status.php');
        $this->assertStringContainsString('namespace App\Controller\Api;', $content);
        $this->assertStringContainsString('final class Status', $content);
        $this->assertStringContainsString("'/api/status'", $content);
    }

    public function testTestFileHasCorrectNamespace(): void
    {
        $this->generator->generate($this->tmpDir, ['name' => 'Api/Status'], $this->writer);

        $content = file_get_contents($this->tmpDir . '/tests/Controller/Api/StatusTest.php');
        $this->assertStringContainsString('namespace App\Tests\Controller\Api;', $content);
        $this->assertStringContainsString('class StatusTest extends WebTestCase', $content);
        $this->assertStringContainsString("'/api/status'", $content);
    }

    public function testRefusesIfControllerAlreadyExists(): void
    {
        $this->generator->generate($this->tmpDir, ['name' => 'Home'], $this->writer);
        $result = $this->generator->generate($this->tmpDir, ['name' => 'Home'], $this->writer);

        $this->assertEmpty($result->filesCreated);
        $this->assertNotEmpty($result->messages);
        $this->assertStringContainsString('already exists', $result->messages[0]);
    }

    public function testNormalizesLowercaseInput(): void
    {
        $result = $this->generator->generate($this->tmpDir, ['name' => 'health'], $this->writer);

        $this->assertSame('src/Controller/Health.php', $result->filesCreated[0]);
        $content = file_get_contents($this->tmpDir . '/src/Controller/Health.php');
        $this->assertStringContainsString('final class Health', $content);
    }

    public function testNormalizesNestedLowercaseInput(): void
    {
        $result = $this->generator->generate($this->tmpDir, ['name' => 'api/status'], $this->writer);

        $this->assertSame('src/Controller/Api/Status.php', $result->filesCreated[0]);
        $content = file_get_contents($this->tmpDir . '/src/Controller/Api/Status.php');
        $this->assertStringContainsString('namespace App\Controller\Api;', $content);
    }

    public function testRefusesMultiWordNameAtRootLevel(): void
    {
        $result = $this->generator->generate($this->tmpDir, ['name' => 'UserProfile'], $this->writer);

        $this->assertEmpty($result->filesCreated);
        $this->assertNotEmpty($result->messages);
        $this->assertStringContainsString('multi-word name', $result->messages[0]);
        $this->assertStringContainsString('<Group>/UserProfile', $result->messages[0]);
    }

    public function testRefusesMultiWordLowercaseNameAtRootLevel(): void
    {
        $result = $this->generator->generate($this->tmpDir, ['name' => 'orderHistory'], $this->writer);

        $this->assertEmpty($result->filesCreated);
        $this->assertStringContainsString('multi-word name', $result->messages[0]);
    }

    public function testRefusesControllerSuffix(): void
    {
        $result = $this->generator->generate($this->tmpDir, ['name' => 'Api/StatusController'], $this->writer);

        $this->assertEmpty($result->filesCreated);
        $this->assertStringContainsString("Do not use the 'Controller' suffix", $result->messages[0]);
        $this->assertStringContainsString('Api/Status', $result->messages[0]);
    }

    public function testRefusesControllerSuffixAtRoot(): void
    {
        $result = $this->generator->generate($this->tmpDir, ['name' => 'DashboardController'], $this->writer);

        $this->assertEmpty($result->filesCreated);
        $this->assertStringContainsString("Do not use the 'Controller' suffix", $result->messages[0]);
        $this->assertStringContainsString('Dashboard', $result->messages[0]);
    }

    public function testRouteConvertsToKebabCase(): void
    {
        $this->generator->generate($this->tmpDir, ['name' => 'Api/UserProfile'], $this->writer);

        $content = file_get_contents($this->tmpDir . '/src/Controller/Api/UserProfile.php');
        $this->assertStringContainsString("'/api/user-profile'", $content);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($it as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($dir);
    }
}
