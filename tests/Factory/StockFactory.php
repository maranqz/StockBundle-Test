<?php

namespace maranqz\StockBundle\Tests\Factory;

use maranqz\StockBundle\Entity\Stock;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<Stock>
 *
 * @method static      Stock|Proxy createOne(array $attributes = [])
 * @method static      Stock[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static      Stock|Proxy find(object|array|mixed $criteria)
 * @method static      Stock|Proxy findOrCreate(array $attributes)
 * @method static      Stock|Proxy first(string $sortedField = 'id')
 * @method static      Stock|Proxy last(string $sortedField = 'id')
 * @method static      Stock|Proxy random(array $attributes = [])
 * @method static      Stock|Proxy randomOrCreate(array $attributes = [])
 * @method static      Stock[]|Proxy[] all()
 * @method static      Stock[]|Proxy[] findBy(array $attributes)
 * @method static      Stock[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static      Stock[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method Stock|Proxy create(array|callable $attributes = [])
 */
final class StockFactory extends ModelFactory
{
    public const DEFAULT_SKU = 'sku';
    public const DEFAULT_BRANCH = 'branch';
    public const DEFAULT_COUNT = 5;

    protected function getDefaults(): array
    {
        return [
            'sku' => self::faker()->lexify(),
            'branch' => self::faker()->unique()->countryISOAlpha3(),
            'count' => self::faker()->randomNumber(),
        ];
    }

    protected static function getClass(): string
    {
        return Stock::class;
    }
}
