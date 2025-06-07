init:
	composer install && \
	NVM_DIR="$${HOME}/.nvm" && . "$${NVM_DIR}/nvm.sh" && nvm use && \
	npm install

composer-update:
	composer update

fix:
	composer run fix

lint:
	composer run lint
