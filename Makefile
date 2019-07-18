# set default target which is executed when no explicit target is provided on the cli
.DEFAULT_GOAL := default

.PHONY: default
default:
	# do nothing

.PHONY: check
check: checkstyle checkquality test

.PHONY: checkstyle
checkstyle:
	vendor/bin/php-cs-fixer fix --dry-run --diff --stop-on-violation --allow-risky=yes
	vendor/bin/phpcs --standard=Magento2 --ignore=./vendor/ .
	composer normalize --dry-run

.PHONY: checkquality
checkquality:
	xmllint --noout --schema vendor/magento/module-backend/etc/menu.xsd            etc/adminhtml/menu.xml
	xmllint --noout --schema vendor/magento/framework/App/etc/routes.xsd           etc/adminhtml/routes.xml
	xmllint --noout --schema vendor/magento/framework/Acl/etc/acl.xsd              etc/acl.xml
	# xmllint --noout --schema vendor/magento/framework/ObjectManager/etc/config.xsd etc/di.xml
	xmllint --noout --schema vendor/magento/framework/Module/etc/module.xsd        etc/module.xml

	vendor/bin/phpstan analyse

.PHONY: test
test:
	vendor/bin/phpunit Test/
