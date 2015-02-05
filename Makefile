help:
	@echo 'Usage: make (fetch)'

fetch:
	php src/fetch.php

bootstrap: bin/bootstrap

evaluate: bootstrap
	php src/evaluate_all.php

result/%: bootstrap
	php src/evaluate.php data/$* > result/$*

bin/bootstrap: src/bootstrap.cc
	mkdir -p bin
	c++ -std=c++0x -O2 -o bin/bootstrap src/bootstrap.cc

analyze:
	php src/analyze.php
