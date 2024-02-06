SHELL = /bin/sh

USER := $(shell id -u):$(shell id -g)
PWD := $(shell pwd)

define rector-command =
	docker run -it --rm \
		-u ${USER} \
		-v ${PWD}:/app -w /app \
		composer:latest \
		php ./vendors/bin/rector process $(1)
endef

rector-test:
	$(call rector-command,"--dry-run")

rector:
	$(call rector-command)
