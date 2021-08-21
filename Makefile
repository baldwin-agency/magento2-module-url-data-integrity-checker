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
	vendor/bin/phpcs --standard=PHPCompatibility --runtime-set testVersion 7.0- --ignore=./vendor/,./Test/ .
	vendor/bin/phpcs --standard=PHPCompatibility --runtime-set testVersion 7.1- ./Test/
	composer normalize --dry-run

.PHONY: checkquality
checkquality:
	vendor/bin/phpstan analyse

    xmllint --noout --schema ./vendor/magento/module-store/etc/config.xsd          etc/config.xml
	xmllint --noout --schema vendor/magento/module-backend/etc/menu.xsd            etc/adminhtml/menu.xml
	xmllint --noout --schema vendor/magento/framework/App/etc/routes.xsd           etc/adminhtml/routes.xml
	xmllint --noout --schema vendor/magento/module-config/etc/system.xsd           etc/adminhtml/system.xml
	xmllint --noout --schema vendor/magento/framework/Acl/etc/acl.xsd              etc/acl.xml
	xmllint --noout --schema vendor/magento/module-cron/etc/cron_groups.xsd        etc/cron_groups.xml
	xmllint --noout --schema vendor/magento/module-cron/etc/crontab.xsd            etc/crontab.xml
	xmllint --noout                                                                etc/di.xml # schema validation doesn't work here since the xsd includes another xsd ..
	xmllint --noout --schema vendor/magento/framework/Module/etc/module.xsd        etc/module.xml

	xmllint --noout                                                                view/adminhtml/layout/baldwin_urldataintegritychecker_catalog_category_urlkey_index.xml # schema validation doesn't work here since the xsd includes another xsd ..
	xmllint --noout                                                                view/adminhtml/layout/baldwin_urldataintegritychecker_catalog_category_urlpath_index.xml # schema validation doesn't work here since the xsd includes another xsd ..
	xmllint --noout                                                                view/adminhtml/layout/baldwin_urldataintegritychecker_catalog_product_urlkey_index.xml # schema validation doesn't work here since the xsd includes another xsd ..
	xmllint --noout                                                                view/adminhtml/layout/baldwin_urldataintegritychecker_catalog_product_urlpath_index.xml # schema validation doesn't work here since the xsd includes another xsd ..
	xmllint --noout                                                                view/adminhtml/ui_component/baldwin_urldataintegritychecker_grid_catalog_category_urlkey.xml # schema validation doesn't work here since the xsd includes another xsd ..
	xmllint --noout                                                                view/adminhtml/ui_component/baldwin_urldataintegritychecker_grid_catalog_category_urlpath.xml # schema validation doesn't work here since the xsd includes another xsd ..
	xmllint --noout                                                                view/adminhtml/ui_component/baldwin_urldataintegritychecker_grid_catalog_product_urlkey.xml # schema validation doesn't work here since the xsd includes another xsd ..
	xmllint --noout                                                                view/adminhtml/ui_component/baldwin_urldataintegritychecker_grid_catalog_product_urlpath.xml # schema validation doesn't work here since the xsd includes another xsd ..

.PHONY: test
test:
	vendor/bin/phpunit -c Test/phpunit.xml Test/
