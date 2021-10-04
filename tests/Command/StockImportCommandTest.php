<?php

namespace maranqz\StockBundle\Tests\Command;

use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use maranqz\StockBundle\Command\StockImportCommand;
use maranqz\StockBundle\Service\StockService;
use maranqz\StockBundle\Tests\Factory\StockFactory;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Form\FormFactoryInterface;
use Throwable;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class StockImportCommandTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;
    use MailerAssertionsTrait;

    public const COMMAND = 'stock:import';

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    /**
     * @dataProvider dataProviderTestDirPath
     */
    public function testDirPath(string $path, bool $expException)
    {
        $hasException = false;
        try {
            $this->getCommandWithMocks($path);
        } catch (InvalidArgumentException $e) {
            $hasException = true;
        }

        $this->assertEquals($expException, $hasException);
    }

    public function dataProviderTestDirPath(): array
    {
        return [
            'empty path' => ['', false],
            'valid path with /' => ['/some_path/', false],
            'valid path without /' => ['some_path/', false],
            'invalid path with /' => ['../', true],
            'invalid path without /' => ['/../', true],
        ];
    }

    private function getCommandWithMocks(string $path): StockImportCommand
    {
        return new StockImportCommand(
            $path,
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(StockService::class),
            $this->createMock(FormFactoryInterface::class),
            self::$kernel->getContainer()
        );
    }

    public function testInvalidCSVPath()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectErrorMessageMatches('/The path is invalid and should be inside /');

        $application = $this->createApplication();
        $commandTester = new CommandTester($application->find(self::COMMAND));

        $commandTester->execute([
            'command' => self::COMMAND,
            'path' => '../',
        ]);
    }

    /**
     * @dataProvider dataProviderTestImport
     */
    public function testImport(callable $factory, ?string $exception, bool $hasMessage = true, int $expCount = 1, array $data = [])
    {
        $factory($this);

        if ($exception) {
            $this->expectException($exception);
        }

        if (empty($data)) {
            $data = [StockFactory::DEFAULT_SKU, StockFactory::DEFAULT_BRANCH, 0];
        }

        $application = $this->createApplication();
        $commandTester = new CommandTester($application->find(self::COMMAND));

        $filepath = $this->createCSVFile();
        try {
            $this->fillData($filepath, [$data]);

            $commandTester->execute([
                'command' => self::COMMAND,
                'path' => basename($filepath),
            ]);

            $this->assertEquals($expCount, StockFactory::count());
            StockFactory::assert()->exists([
                'sku' => $data[0],
                'branch' => $data[1],
                'count' => $data[2],
            ]);

            if ($hasMessage) {
                $this->assertEmailCount(1);
            }
        } catch (Throwable $e) {
            throw $e;
        } finally {
            $filepath && file_exists($filepath) && unlink($filepath);
        }
    }

    public function dataProviderTestImport(): array
    {
        return [
            'create' => [
                function () {
                },
                null,
            ],
            'update' => [
                function () {
                    StockFactory::createOne([
                        'sku' => StockFactory::DEFAULT_SKU,
                        'branch' => StockFactory::DEFAULT_BRANCH,
                    ]);
                },
                null,
            ],
            'there is validation' => [
                function () {
                    StockFactory::createOne([
                        'sku' => StockFactory::DEFAULT_SKU,
                        'branch' => StockFactory::DEFAULT_BRANCH,
                    ]);
                },
                RuntimeException::class,
                false,
                0,
                [StockFactory::DEFAULT_SKU, StockFactory::DEFAULT_BRANCH, -5],
            ],
        ];
    }

    private function createCSVFile(): string
    {
        $dir = static::$kernel->getProjectDir().DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR;

        return tempnam($dir, '');
    }

    private function fillData($filepath, array $data)
    {
        $file = fopen($filepath, 'w');

        fputcsv($file, []);
        foreach ($data as $row) {
            fputcsv($file, $row);
        }

        fclose($file);
    }

    private function createApplication(): Application
    {
        return new Application(static::$kernel);
    }
}
