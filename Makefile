.PHONY: help all clean test rebuild

CLDR_CORE_URL=https://www.unicode.org/Public/cldr/46/core.zip

help:
	@echo "'make all' to download CLDR data and rebuild files."
	@echo "'make test' to run the phpunit tests"
	@echo "'make clean' to delete the generated LanguageNames*.php files."
	@echo "'make distclean' to delete the CLDR data."

all: rebuild

distclean:
	rm -f core.zip
	rm -rf core

clean:
	rm -f CldrCurrency/CldrCurrency[A-Z]*.php CldrMain/CldrMain[A-Z]*.php CldrSupplemental/CldrSupplemental[A-Z]*.php

test:
	php ${MW_INSTALL_PATH}/tests/phpunit/phpunit.php tests

rebuild: core/
	php rebuild.php

core/: core.zip
	unzip core.zip -d core

core.zip:
	curl -C - -O $(CLDR_CORE_URL) || wget $(CLDR_CORE_URL) || fetch $(CLDR_CORE_URL)
