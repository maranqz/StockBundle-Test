<?php

namespace maranqz\StockBundle\Repository;

use maranqz\StockBundle\Entity\Stock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Stock|null find($id, $lockMode = null, $lockVersion = null)
 * @method Stock|null findOneBy(array $criteria, array $orderBy = null)
 * @method Stock[]    findAll()
 * @method Stock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stock::class);
    }

    public function findByKey(string $sku, string $branch)
    {
        return $this->findByKeys([['sku' => $sku, 'branch' => $branch]])[0] ?? null;
    }

    public function findByKeys(array $keys)
    {
        $qb = $this->createQueryBuilder('s');

        foreach ($keys as $index => $key) {
            $qb->orWhere(
                $qb->expr()->andX(
                    $qb->expr()->eq('s.sku', ':sku' . $index),
                    $qb->expr()->eq('s.branch', ':branch' . $index)
                )
            );

            $qb
                ->setParameter('sku' . $index, $key['sku'])
                ->setParameter('branch' . $index, $key['branch']);
        }

        return $qb->getQuery()->getResult();
    }
}
