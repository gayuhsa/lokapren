ENGINE ?= docker
IMAGE_NAME = lokapren

build:
	$(ENGINE) build -t $(IMAGE_NAME) .

run:
	$(ENGINE) run --rm -p 8080:80 -v "$(CURDIR)":/var/www/html -v /var/www/html/writable -v /var/www/html/vendor $(IMAGE_NAME)

shell:
	$(ENGINE) run -it --rm -p 8080:80 -v "$(CURDIR)":/var/www/html -v /var/www/html/writable -v /var/www/html/vendor $(IMAGE_NAME) bash

clean:
	-$(ENGINE) rm -f $$($(ENGINE) ps -a -q --filter ancestor=$(IMAGE_NAME))
	-$(ENGINE) rmi $(IMAGE_NAME)

