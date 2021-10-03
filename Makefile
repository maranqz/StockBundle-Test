cs-fix:
	php vendor/bin/php-cs-fixer fix

test:
	php vendor/bin/simple-phpunit

coverage:
	XDEBUG_MODE=coverage php vendor/bin/simple-phpunit -c phpunit.xml.dist --coverage-clover ./clover.xml

workflow:
	act -P ubuntu-latest=shivammathur/node:latest