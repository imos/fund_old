help:
	@echo 'Usage: make (fetch)'

fetch:
	php src/fetch.php

bootstrap:
	mkdir -p bin
	c++ -std=c++0x -O2 -o bin/bootstrap src/bootstrap.cc
