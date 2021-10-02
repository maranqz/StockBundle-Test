<?php

namespace App\StockBundle\Command;

use App\StockBundle\Entity\Stock;
use App\StockBundle\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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

    public function __construct(string $publicPath, EntityManagerInterface $em, string $name = null)
    {
        parent::__construct($name);

        $this->publicPath = $publicPath;
        $this->em = $em;
        $this->repository = $em->getRepository(Stock::class);
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
        // We can use realpath function to check that path only in web directory
        $path = $this->publicPath . $input->getArgument(self::ARGUMENT_PATH);
        $batchSize = $input->getOption(self::OPTION_BATCH_SIZE);

        if (!file_exists($path)) {
            throw new InvalidArgumentException(sprintf('Path "%s" is not correct.', $path));
        }

        $file = fopen($path, 'r');

        if ($file === false) {
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
                $currentBatchSize--;
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
            $itemsIndexBy[$item[0] . '.' . $item[1]] = $item;
        }

        $entitiesIndexBy = [];
        foreach ($this->repository->findByKeys($ids) as $entity) {
            /** @var Stock $entity */
            $entitiesIndexBy[$entity->getSku() . '.' . $entity->getBranch()] = $entity;
        }

        foreach ($itemsIndexBy as $key => $item) {
            $entity = $entitiesIndexBy[$key] ?? false;
            if (!$entity) {
                $entity = new Stock($item[0], $item[1]);
                $this->em->persist($entity);
            }

            // TODO 0 stock
            $entity->setStock((int)$item[2]);
        }

        $this->em->flush();
        $this->em->clear();
    }
}
