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

.PHONY: checkquality
checkquality:
	vendor/bin/phpstan analyse
