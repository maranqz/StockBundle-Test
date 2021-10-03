<?php

namespace maranqz\StockBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use maranqz\StockBundle\Entity\Stock;
use maranqz\StockBundle\Exception\StockError;
use maranqz\StockBundle\Form\Type\CreateStockType;
use maranqz\StockBundle\Form\Type\UpdateStockType;
use maranqz\StockBundle\Repository\StockRepository;
use maranqz\StockBundle\Service\StockService;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormFactoryInterface;

class StockImportCommand extends Command
{
    private const BATCH_SIZE = 50;
    private const ARGUMENT_PATH = 'path';
    private const OPTION_BATCH_SIZE = 'batch-size';

    protected static $defaultName = 'stock:import';
    protected static $defaultDescription = 'Read stock data from the provided CSV file';

    /** @var string */
    private $publicPath;
    /** @var EntityManagerInterface */
    private $em;
    /** @var StockRepository */
    private $repository;
    /**
     * @var StockService
     */
    private $stockService;
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    public function __construct(string $publicPath, EntityManagerInterface $em, StockService $stockService, FormFactoryInterface $formFactory, ContainerInterface $container, string $name = null)
    {
        parent::__construct($name);

        $webRoot = $container->get('kernel')->getProjectDir().DIRECTORY_SEPARATOR.'public';
        $publicPath = $webRoot.DIRECTORY_SEPARATOR.$publicPath;

        $this->checkPath($webRoot, $publicPath);

        $this->publicPath = $publicPath;
        $this->em = $em;
        $this->repository = $em->getRepository(Stock::class);
        $this->stockService = $stockService;
        $this->formFactory = $formFactory;
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument(self::ARGUMENT_PATH, InputArgument::REQUIRED, 'CSV file Path, relative to the public')
            ->addOption(self::OPTION_BATCH_SIZE, 'b', InputOption::VALUE_OPTIONAL, '', self::BATCH_SIZE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $this->publicPath.DIRECTORY_SEPARATOR.$input->getArgument(self::ARGUMENT_PATH);
        $this->checkPath($this->publicPath, $path);

        $batchSize = $input->getOption(self::OPTION_BATCH_SIZE);

        if (!file_exists($path)) {
            throw new InvalidArgumentException(sprintf('Path "%s" is not correct.', $path));
        }

        $file = fopen($path, 'r');

        if (false === $file) {
            throw new RuntimeException(error_get_last());
        }

        try {
            $currentBatchSize = $batchSize;
            $items = [];
            fgetcsv($file); // skip the first line
            while (($data = fgetcsv($file, 1000, ',')) !== false) {
                if (empty($data)) {
                    continue;
                }

                $items[] = $data;
                --$currentBatchSize;
                if ($currentBatchSize <= 0) {
                    $currentBatchSize = $batchSize;
                    $this->import($items);
                    $items = [];
                }
            }
            $this->import($items);
        } finally {
            fclose($file);
        }

        return 0;
    }

    private function import(array $items)
    {
        if (empty($items)) {
            return;
        }

        $ids = $itemsIndexBy = [];
        foreach ($items as $item) {
            $ids[] = ['sku' => $item[0], 'branch' => $item[1]];
            $itemsIndexBy[$item[0].'.'.$item[1]] = $item;
        }

        $entitiesIndexBy = [];
        foreach ($this->repository->findByKeys($ids) as $entity) {
            /* @var Stock $entity */
            $entitiesIndexBy[$entity->getSku().'.'.$entity->getBranch()] = $entity;
        }

        foreach ($itemsIndexBy as $key => $item) {
            $data = [
                'count' => (int) $item[2],
            ];

            $entity = $entitiesIndexBy[$key] ?? null;
            $type = UpdateStockType::class;
            $method = [$this->stockService, 'update'];
            if (!$entity) {
                $type = CreateStockType::class;
                $method = [$this->stockService, 'create'];
                $data['sku'] = $item[0];
                $data['branch'] = $item[1];
            }

            $form = ($this->formFactory->create($type, $entity))
                ->submit($data);

            try {
                $method($form, false);
            } catch (StockError $e) {
                throw new RuntimeException(print_r($data, 1).$e->getMessage(), 0, $e);
            }
        }

        $this->stockService->flush();
        $this->em->clear();
    }

    private function checkPath(string $rootPath, string $path)
    {
        if (0 !== strpos(realpath($path), realpath($rootPath))) {
            throw new InvalidArgumentException(sprintf('The path is invalid and should be inside %s', $rootPath));
        }
    }
}
