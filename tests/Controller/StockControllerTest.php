<?php

namespace maranqz\StockBundle\Tests\Controller;

use maranqz\StockBundle\Tests\Factory\StockFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class StockControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;
    public const SKU = 'sku';
    public const BRANCH = 'branch';
    public const COUNT = 5;

    public function testPagination()
    {
        StockFactory::createMany(30);

        $client = static::createClient();

        $crawler = $client->request(
            'GET',
            $this->router()->generate('stock.list', ['page' => 2])
        );

        $this->assertCount(2, $crawler->filter('.page'));
    }

    public function testCreateValid()
    {
        $client = static::createClient();

        $crawler = $client->request(
            'GET',
            $this->router()->generate('stock.create')
        );

        $form = $crawler->selectButton('create_stock[save]')->form();
        $client->submit($form, [
            'create_stock[sku]' => self::SKU,
            'create_stock[branch]' => self::BRANCH,
            'create_stock[count]' => (string) self::COUNT,
        ]);

        $this->assertResponseRedirects($this->router()->generate('stock.update', [
            'sku' => self::SKU,
            'branch' => self::BRANCH,
        ]));

        StockFactory::assert()->exists([
            'sku' => self::SKU,
            'branch' => self::BRANCH,
            'count' => self::COUNT,
        ]);
    }

    public function testShowingErrorMessage()
    {
        $client = static::createClient();

        $crawler = $client->request(
            'GET',
            $this->router()->generate('stock.create')
        );

        $form = $crawler->selectButton('create_stock[save]')->form();
        $crawler = $client->submit($form, [
            'create_stock[sku]' => '',
            'create_stock[branch]' => '',
            'create_stock[count]' => (string) (self::COUNT - self::COUNT - 1),
        ]);

        $this->assertCount(3, $crawler->filter('ul'), 'Error counts');
    }

    public function testUpdateValid()
    {
        $stock = StockFactory::createOne([
            'sku' => self::SKU,
            'branch' => self::BRANCH,
            'count' => self::COUNT,
        ]);
        $expCount = self::COUNT + 1;

        $client = static::createClient();

        $crawler = $client->request(
            'GET',
            $this->router()->generate('stock.update', ['sku' => $stock->getSku(), 'branch' => $stock->getBranch()])
        );

        $form = $crawler->selectButton('update_stock[save]')->form();

        $this->assertCount(2, $form->all(), 'Count of fields in the form');

        $crawler = $client->submit($form, [
            'update_stock[count]' => $expCount,
        ]);

        $this->assertSame($expCount, StockFactory::find([
            'sku' => self::SKU,
            'branch' => self::BRANCH,
        ])->getCount());
    }

    protected static function createClient(array $options = [], array $server = [])
    {
        static::ensureKernelShutdown();

        return parent::createClient($options, $server);
    }

    protected function router()
    {
        return static::$kernel->getContainer()->get('router');
    }
}
