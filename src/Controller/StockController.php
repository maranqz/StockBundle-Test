<?php

namespace maranqz\StockBundle\Controller;

use maranqz\StockBundle\Entity\Stock;
use maranqz\StockBundle\Form\Type\CreateStockType;
use maranqz\StockBundle\Form\Type\UpdateStockType;
use maranqz\StockBundle\Repository\StockRepository;
use maranqz\StockBundle\Service\StockService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/stock", name="stock.")
 */
class StockController extends AbstractController
{
    /**
     * @var StockRepository
     */
    private $repository;
    /**
     * @var StockService
     */
    private $stockService;

    public function __construct(EntityManagerInterface $em, StockService $stockService)
    {
        $this->repository = $em->getRepository(Stock::class);
        $this->stockService = $stockService;
    }

    /**
     * @Route("/", name="list", methods={"GET"})
     */
    public function list(Request $request, PaginatorInterface $paginator): Response
    {
        $count = $this->repository
            ->createQueryBuilder('s')
            ->select('COUNT(s)')
            ->getQuery()
            ->getSingleScalarResult();

        $pagination = $paginator->paginate(
            $this->repository->createQueryBuilder('s')
                ->getQuery()
                ->setHint('knp_paginator.count', $count),
            $request->query->getInt('page', 1),
            10,
            ['distinct' => false]
        );

        return $this->render('@Stock/stock.list.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    /**
     * @Route("/create", name="create")
     */
    public function create(Request $request): Response
    {
        $form = $this->createForm(CreateStockType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $stock = $this->stockService->create($form);

            return $this->redirectToRoute('stock.update', [
                'sku' => $stock->getSku(),
                'branch' => $stock->getBranch(),
            ]);
        }

        return $this->render('@Stock/stock.create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{sku}/{branch}", name="update")
     */
    public function update(string $sku, string $branch, Request $request): Response
    {
        $stock = $this->repository->findByKey($sku, $branch);
        if (empty($stock)) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(UpdateStockType::class, $stock);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->stockService->update($form);

            return $this->redirectToRoute('stock.update', [
                'sku' => $sku,
                'branch' => $branch,
            ]);
        }

        return $this->render('@Stock/stock.update.html.twig', [
            'form' => $form->createView(),
            'stock' => $stock,
        ]);
    }
}
