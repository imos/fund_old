help:
	@echo 'Usage: make (fetch)'

fetch:
	php src/fetch.php

bootstrap: bin/bootstrap

evaluate: bootstrap
	mkdir -p result
	php src/evaluate_all.php

result/%: bootstrap
	mkdir -p result
	BOOTSTRAP_PERIOD='$(word 1,$(subst /, ,$*))' \
	BOOTSTRAP_LEAP='$(word 2,$(subst /, ,$*))' \
	php src/evaluate.php data/$(notdir $*) > result/$*

bin/bootstrap: src/bootstrap.cc
	mkdir -p bin
	c++ -std=c++0x -O2 -o bin/bootstrap src/bootstrap.cc

analyze:
	php src/analyze.php
