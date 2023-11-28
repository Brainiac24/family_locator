build: up composer migration initial_supervisor websocket

up:
	docker-compose \
		-f .infrastructure/.docker-compose/docker-compose.yml \
		up -d --build --remove-orphans
	docker exec php-fpm chmod -R 777 /application/var/
	docker exec php-fpm chmod -R 777 /application/public/

composer:
	docker exec -t php-fpm bash -c 'COMPOSER_MEMORY_LIMIT=-1 composer install  --no-interaction'


migration:
	docker exec -t php-fpm bash -c 'bin/console doctrine:migrations:migrate --no-interaction'

initial_supervisor:
	docker exec -t php-fpm bash -c 'supervisord -c /etc/supervisor/supervisord.conf || supervisorctl stop all'

websocket:
	docker exec -t php-fpm bash -c 'supervisorctl reread && supervisorctl update && supervisorctl start all && supervisorctl status'