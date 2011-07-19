.PHONY: help all clean

COMMON=http://www.unicode.org/Public/cldr/2.0.1/common.zip

help:
	@echo "'make all' to download CLDR data and rebuild files."
	@echo "'make clean' to delete the generated LanguageNames*.php files."
	@echo "'make distclean' to delete the CLDR data."

all: LanguageNames.php

distclean:
	rm -f core.zip
	rm -rf core
	rm -f common.zip
	rm -rf common

clean:
	rm -f LanguageNames[A-Z]*.php

LanguageNames.php: common/
	php rebuild.php

common/: common.zip
	unzip common.zip -d common

common.zip:
	curl -C - -O $(COMMON) || wget $(COMMON) || fetch $(COMMON)
