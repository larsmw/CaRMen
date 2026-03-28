#!/bin/bash
docker compose exec backend sh -c 'APP_ENV=test php bin/phpunit --testdox'

