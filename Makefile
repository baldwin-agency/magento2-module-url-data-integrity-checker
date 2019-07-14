# set default target which is executed when no explicit target is provided on the cli
.DEFAULT_GOAL := default

.PHONY: default
default:
	# do nothing

.PHONY: check
check: checkstyle checkquality

.PHONY: checkstyle
checkstyle:
	vendor/bin/php-cs-fixer fix --dry-run --diff --stop-on-violation --allow-risky=yes
	vendor/bin/phpcs --standard=Magento2 --ignore=./vendor/ .
	composer normalize --dry-run

.PHONY: checkquality
checkquality:
	vendor/bin/phpstan analyse
