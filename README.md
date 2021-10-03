[![codecov](https://codecov.io/gh/maranqz/StockBundle-Test/branch/master/graph/badge.svg?token=RLBJYVKMCN)](https://codecov.io/gh/maranqz/StockBundle-Test)

# Get started
Need to
1. add in composer.json
    ```json
    {
      "repositories": [
        {
          "type": "vcs",
          "url": "https://github.com/maranqz/StockBundle-Test"
        }
      ],
      "minimum-stability": "dev"
    }
    ```
1. call composer require maranqz/stock-bundle
1. add in routes.yaml
    ```yaml
    stock_bundle:
        resource: '@StockBundle/config/routes.yaml'
        prefix:   /
    ```
1. add in bundles.php
    ```php
    maranqz\StockBundle\StockBundle::class => ['all' => true],
    ```
1. create a migration and apply it
    ```bash
    php bin/console doctrine:migrations:diff -q
    php bin/console doctrine:migrations:migrate -q
    ```


#Task

1. Create a Symfony bundle to handle product inventory. It should be installable in a vanilla Symfony 4 or 5 app using MySQL as the database.
2. Create a command to read stock data from the provided CSV file. The directory location should be configurable and should be relative to the web root.
3. Create a doctrine entity for the stock data
4. Save the stock data into the database - perform an update when the stock and location values already exist
5. Create a controller action to present the stock data (styling not important)
6. Create a controller action to accept posted stock data and save into the db
7. When processing stock data determine when a stock item is going out of stock (so value changing from a positive value to 0)
8. Trigger a message using the messenger component (https://symfony.com/doc/current/messenger.html) when an item goes out of stock
9. Create a message handler to send an email notification to a configurable email address. The email should contain text describing that the SKU is out of stock at a particular location
10. For the above, create unit/functional tests where appropriate