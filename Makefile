fix: cs-fix composer.normilize

cs-fix:
	php vendor/bin/php-cs-fixer fix

composer.normilize:
	composer normalize

stan:
	php vendor/bin/phpstan analyse src tests

test:
	php vendor/bin/simple-phpunit

coverage:
	XDEBUG_MODE=coverage php vendor/bin/simple-phpunit -c phpunit.xml.dist --coverage-clover ./clover.xml

workflow: workflow.test

workflow.test:
	act -P ubuntu-latest=shivammathur/node:latest -j test