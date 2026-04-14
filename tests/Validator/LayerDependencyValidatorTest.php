<?php

declare(strict_types=1);

namespace Scafera\Layered\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Scafera\Layered\Validator\LayerDependencyValidator;

class LayerDependencyValidatorTest extends TestCase
{
    private LayerDependencyValidator $validator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->validator = new LayerDependencyValidator();
        $this->tmpDir = sys_get_temp_dir() . '/scafera_test_' . uniqid();
        mkdir($this->tmpDir . '/src/Controller', 0777, true);
        mkdir($this->tmpDir . '/src/Service', 0777, true);
        mkdir($this->tmpDir . '/src/Repository', 0777, true);
        mkdir($this->tmpDir . '/src/Entity', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testPassesWhenControllerImportsService(): void
    {
        file_put_contents($this->tmpDir . '/src/Controller/ListOrders.php', <<<'PHP'
        <?php
        namespace App\Controller;
        use App\Service\OrderService;
        class ListOrders { public function __invoke() {} }
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsWhenControllerImportsEntity(): void
    {
        file_put_contents($this->tmpDir . '/src/Controller/ShowOrder.php', <<<'PHP'
        <?php
        namespace App\Controller;
        use App\Entity\Order;
        class ShowOrder { public function __invoke() {} }
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('Controller/ShowOrder.php', $violations[0]);
        $this->assertStringContainsString('Entity directly', $violations[0]);
    }

    public function testFailsWhenControllerImportsRepository(): void
    {
        file_put_contents($this->tmpDir . '/src/Controller/ListOrders.php', <<<'PHP'
        <?php
        namespace App\Controller;
        use App\Repository\OrderRepository;
        class ListOrders { public function __invoke() {} }
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('Controller/ListOrders.php', $violations[0]);
        $this->assertStringContainsString('Repository directly', $violations[0]);
    }

    public function testPassesWhenServiceImportsRepository(): void
    {
        file_put_contents($this->tmpDir . '/src/Service/OrderService.php', <<<'PHP'
        <?php
        namespace App\Service;
        use App\Repository\OrderRepository;
        class OrderService {}
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testPassesWhenServiceImportsEntity(): void
    {
        file_put_contents($this->tmpDir . '/src/Service/OrderService.php', <<<'PHP'
        <?php
        namespace App\Service;
        use App\Entity\Order;
        class OrderService {}
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsWhenRepositoryImportsService(): void
    {
        file_put_contents($this->tmpDir . '/src/Repository/OrderRepository.php', <<<'PHP'
        <?php
        namespace App\Repository;
        use App\Service\OrderService;
        class OrderRepository {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('Repository/OrderRepository.php', $violations[0]);
        $this->assertStringContainsString('Service', $violations[0]);
    }

    public function testFailsWhenRepositoryImportsController(): void
    {
        file_put_contents($this->tmpDir . '/src/Repository/OrderRepository.php', <<<'PHP'
        <?php
        namespace App\Repository;
        use App\Controller\ListOrders;
        class OrderRepository {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('Repository/OrderRepository.php', $violations[0]);
        $this->assertStringContainsString('Controller', $violations[0]);
    }

    public function testPassesWhenRepositoryImportsEntity(): void
    {
        file_put_contents($this->tmpDir . '/src/Repository/OrderRepository.php', <<<'PHP'
        <?php
        namespace App\Repository;
        use App\Entity\Order;
        class OrderRepository {}
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsWhenEntityImportsAnyLayer(): void
    {
        file_put_contents($this->tmpDir . '/src/Entity/Order.php', <<<'PHP'
        <?php
        namespace App\Entity;
        use App\Service\OrderService;
        use App\Repository\OrderRepository;
        class Order {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(2, $violations);
    }

    public function testFailsWhenCommandImportsEntityDirectly(): void
    {
        mkdir($this->tmpDir . '/src/Command', 0777, true);
        file_put_contents($this->tmpDir . '/src/Command/ImportOrders.php', <<<'PHP'
        <?php
        namespace App\Command;
        use App\Entity\Order;
        class ImportOrders {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('Command/ImportOrders.php', $violations[0]);
        $this->assertStringContainsString('Entity directly', $violations[0]);
    }

    public function testPassesWhenCommandImportsService(): void
    {
        mkdir($this->tmpDir . '/src/Command', 0777, true);
        file_put_contents($this->tmpDir . '/src/Command/ImportOrders.php', <<<'PHP'
        <?php
        namespace App\Command;
        use App\Service\ImportService;
        class ImportOrders {}
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testReportsMultipleViolationsInSameFile(): void
    {
        file_put_contents($this->tmpDir . '/src/Controller/BadController.php', <<<'PHP'
        <?php
        namespace App\Controller;
        use App\Entity\Order;
        use App\Repository\OrderRepository;
        class BadController { public function __invoke() {} }
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(2, $violations);
    }

    public function testPassesWhenNoSrcDir(): void
    {
        $emptyDir = sys_get_temp_dir() . '/scafera_empty_' . uniqid();
        mkdir($emptyDir);

        $this->assertSame([], $this->validator->validate($emptyDir));

        rmdir($emptyDir);
    }

    public function testPassesWhenServiceImportsIntegration(): void
    {
        mkdir($this->tmpDir . '/src/Integration', 0777, true);
        file_put_contents($this->tmpDir . '/src/Service/PaymentProcessor.php', <<<'PHP'
        <?php
        namespace App\Service;
        use App\Integration\Stripe\PaymentGateway;
        class PaymentProcessor {}
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testPassesWhenIntegrationImportsEntity(): void
    {
        mkdir($this->tmpDir . '/src/Integration', 0777, true);
        file_put_contents($this->tmpDir . '/src/Integration/PaymentGateway.php', <<<'PHP'
        <?php
        namespace App\Integration\Stripe;
        use App\Entity\Order;
        class PaymentGateway {}
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsWhenIntegrationImportsService(): void
    {
        mkdir($this->tmpDir . '/src/Integration', 0777, true);
        file_put_contents($this->tmpDir . '/src/Integration/PaymentGateway.php', <<<'PHP'
        <?php
        namespace App\Integration\Stripe;
        use App\Service\OrderService;
        class PaymentGateway {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('Integration/PaymentGateway.php', $violations[0]);
        $this->assertStringContainsString('Service', $violations[0]);
    }

    public function testFailsWhenIntegrationImportsRepository(): void
    {
        mkdir($this->tmpDir . '/src/Integration', 0777, true);
        file_put_contents($this->tmpDir . '/src/Integration/PaymentGateway.php', <<<'PHP'
        <?php
        namespace App\Integration\Stripe;
        use App\Repository\OrderRepository;
        class PaymentGateway {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('Integration/PaymentGateway.php', $violations[0]);
        $this->assertStringContainsString('Repository', $violations[0]);
    }

    public function testFailsWhenIntegrationImportsController(): void
    {
        mkdir($this->tmpDir . '/src/Integration', 0777, true);
        file_put_contents($this->tmpDir . '/src/Integration/PaymentGateway.php', <<<'PHP'
        <?php
        namespace App\Integration\Stripe;
        use App\Controller\Order\Show;
        class PaymentGateway {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('Integration/PaymentGateway.php', $violations[0]);
        $this->assertStringContainsString('Controller', $violations[0]);
    }

    public function testFailsWhenControllerImportsIntegration(): void
    {
        mkdir($this->tmpDir . '/src/Integration', 0777, true);
        file_put_contents($this->tmpDir . '/src/Controller/Pay.php', <<<'PHP'
        <?php
        namespace App\Controller;
        use App\Integration\Stripe\PaymentGateway;
        class Pay { public function __invoke() {} }
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('Controller/Pay.php', $violations[0]);
        $this->assertStringContainsString('Integration directly', $violations[0]);
    }

    public function testFailsWhenRepositoryImportsIntegration(): void
    {
        mkdir($this->tmpDir . '/src/Integration', 0777, true);
        file_put_contents($this->tmpDir . '/src/Repository/OrderRepository.php', <<<'PHP'
        <?php
        namespace App\Repository;
        use App\Integration\Stripe\PaymentGateway;
        class OrderRepository {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('Repository/OrderRepository.php', $violations[0]);
        $this->assertStringContainsString('Integration', $violations[0]);
    }

    public function testFailsWhenEntityImportsIntegration(): void
    {
        file_put_contents($this->tmpDir . '/src/Entity/Order.php', <<<'PHP'
        <?php
        namespace App\Entity;
        use App\Integration\Stripe\PaymentGateway;
        class Order {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('Entity/Order.php', $violations[0]);
        $this->assertStringContainsString('Integration', $violations[0]);
    }

    public function testFailsWhenCommandImportsIntegration(): void
    {
        mkdir($this->tmpDir . '/src/Command', 0777, true);
        file_put_contents($this->tmpDir . '/src/Command/SyncPayments.php', <<<'PHP'
        <?php
        namespace App\Command;
        use App\Integration\Stripe\PaymentGateway;
        class SyncPayments {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('Command/SyncPayments.php', $violations[0]);
        $this->assertStringContainsString('Integration directly', $violations[0]);
    }

    public function testPassesWhenControllerImportsForm(): void
    {
        mkdir($this->tmpDir . '/src/Form', 0777, true);
        file_put_contents($this->tmpDir . '/src/Controller/CreateOrder.php', <<<'PHP'
        <?php
        namespace App\Controller;
        use App\Form\OrderForm;
        class CreateOrder { public function __invoke() {} }
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsWhenServiceImportsForm(): void
    {
        mkdir($this->tmpDir . '/src/Form', 0777, true);
        file_put_contents($this->tmpDir . '/src/Service/OrderProcessor.php', <<<'PHP'
        <?php
        namespace App\Service;
        use App\Form\OrderForm;
        class OrderProcessor {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('Service/OrderProcessor.php', $violations[0]);
        $this->assertStringContainsString('Form', $violations[0]);
    }

    public function testFailsWhenFormImportsRepository(): void
    {
        mkdir($this->tmpDir . '/src/Form', 0777, true);
        file_put_contents($this->tmpDir . '/src/Form/OrderForm.php', <<<'PHP'
        <?php
        namespace App\Form;
        use App\Repository\OrderRepository;
        class OrderForm {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('Form/OrderForm.php', $violations[0]);
        $this->assertStringContainsString('Repository', $violations[0]);
    }

    public function testFailsWhenFormImportsService(): void
    {
        mkdir($this->tmpDir . '/src/Form', 0777, true);
        file_put_contents($this->tmpDir . '/src/Form/OrderForm.php', <<<'PHP'
        <?php
        namespace App\Form;
        use App\Service\OrderProcessor;
        class OrderForm {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('Form/OrderForm.php', $violations[0]);
        $this->assertStringContainsString('Service', $violations[0]);
    }

    public function testPassesWhenFormImportsEntity(): void
    {
        mkdir($this->tmpDir . '/src/Form', 0777, true);
        file_put_contents($this->tmpDir . '/src/Form/OrderForm.php', <<<'PHP'
        <?php
        namespace App\Form;
        use App\Entity\Order;
        class OrderForm {}
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testHandlesSubdirectories(): void
    {
        mkdir($this->tmpDir . '/src/Controller/Order', 0777, true);
        file_put_contents($this->tmpDir . '/src/Controller/Order/Show.php', <<<'PHP'
        <?php
        namespace App\Controller\Order;
        use App\Entity\Order;
        class Show { public function __invoke() {} }
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('Controller/Order/Show.php', $violations[0]);
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
