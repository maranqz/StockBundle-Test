<?php

namespace maranqz\StockBundle\Tests\Controller;

use maranqz\StockBundle\Tests\Factory\StockFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mime\Email;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class StockControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

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
            'create_stock[sku]' => StockFactory::DEFAULT_SKU,
            'create_stock[branch]' => StockFactory::DEFAULT_BRANCH,
            'create_stock[count]' => (string) StockFactory::DEFAULT_COUNT,
        ]);

        $this->assertResponseRedirects($this->router()->generate('stock.update', [
            'sku' => StockFactory::DEFAULT_SKU,
            'branch' => StockFactory::DEFAULT_BRANCH,
        ]));

        StockFactory::assert()->exists([
            'sku' => StockFactory::DEFAULT_SKU,
            'branch' => StockFactory::DEFAULT_BRANCH,
            'count' => StockFactory::DEFAULT_COUNT,
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
            'create_stock[count]' => '-1',
        ]);

        $this->assertCount(3, $crawler->filter('ul'), 'Error counts');
    }

    public function testUpdateValid()
    {
        $stock = StockFactory::createOne([
            'sku' => StockFactory::DEFAULT_SKU,
            'branch' => StockFactory::DEFAULT_BRANCH,
            'count' => StockFactory::DEFAULT_COUNT,
        ]);
        $expCount = StockFactory::DEFAULT_COUNT + 1;

        $client = static::createClient();

        $crawler = $client->request(
            'GET',
            $this->router()->generate('stock.update', ['sku' => $stock->getSku(), 'branch' => $stock->getBranch()])
        );

        $form = $crawler->selectButton('update_stock[save]')->form();

        $this->assertCount(2, $form->all(), 'Count of fields in the form');

        $client->submit($form, [
            'update_stock[count]' => $expCount,
        ]);

        $this->assertSame($expCount, StockFactory::find([
            'sku' => StockFactory::DEFAULT_SKU,
            'branch' => StockFactory::DEFAULT_BRANCH,
        ])->getCount());
    }

    /**
     * @dataProvider dataTestMessage
     */
    public function testMessageSending(callable $factory, callable $url, array $data, ?Email $expMessage)
    {
        $factory($this);

        $client = static::createClient();

        $crawler = $client->request('GET', $url($this));

        $client->submit($crawler->filter('form')->form(), $data);

        /** @var Email $message */
        $message = $this->getMailerMessage();

        if (isset($expMessage)) {
            $this->assertEquals($expMessage->getFrom(), $message->getFrom());
            $this->assertEquals($expMessage->getTo(), $message->getTo());
            $this->assertEquals($expMessage->getSubject(), $message->getSubject());
            $this->assertEquals($expMessage->getTextBody(), $message->getTextBody());
        } else {
            $this->assertNull($message);
        }
    }

    public function dataTestMessage(): array
    {
        $expMessage = (new Email())
            ->from('sender@email.com')
            ->to('receiver@email.com')
            ->subject('Stock out in branch for sku')
            ->text('sku stocks need to be replenished in branch');

        $createFormName = 'create_stock';
        $updateFormName = 'update_stock';

        return [
            'create with empty data' => [
                function () {
                },
                function (StockControllerTest $self) {
                    return $self->router()->generate('stock.create');
                },
                [
                    $createFormName.'[sku]' => StockFactory::DEFAULT_SKU,
                    $createFormName.'[branch]' => StockFactory::DEFAULT_BRANCH,
                    $createFormName.'[count]' => 0,
                ],
                $expMessage,
            ],
            'update data' => [
                function () {
                    StockFactory::createOne([
                        'sku' => StockFactory::DEFAULT_SKU,
                        'branch' => StockFactory::DEFAULT_BRANCH,
                        'count' => StockFactory::DEFAULT_COUNT,
                    ]);
                },
                function (StockControllerTest $self) {
                    return $self->router()->generate('stock.update', [
                        'sku' => StockFactory::DEFAULT_SKU,
                        'branch' => StockFactory::DEFAULT_BRANCH,
                    ]);
                },
                [$updateFormName.'[count]' => 0],
                $expMessage,
            ],
            'without update stock' => [
                function () {
                    StockFactory::createOne([
                        'sku' => StockFactory::DEFAULT_SKU,
                        'branch' => StockFactory::DEFAULT_BRANCH,
                        'count' => 0,
                    ]);
                },
                function (StockControllerTest $self) {
                    return $self->router()->generate('stock.update', [
                        'sku' => StockFactory::DEFAULT_SKU,
                        'branch' => StockFactory::DEFAULT_BRANCH,
                    ]);
                },
                [$updateFormName.'[count]' => 0],
                null,
            ],
        ];
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
