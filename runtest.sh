#!/bin/bash

docker compose exec db dropdb -U crm crm_test 2>/dev/null || true
docker compose exec backend sh -c '
    APP_ENV=test php bin/console doctrine:database:create &&
    APP_ENV=test php bin/console doctrine:migrations:migrate --no-interaction &&
    APP_ENV=test php bin/console doctrine:fixtures:load --no-interaction &&
    APP_ENV=test php bin/phpunit --testdox
  '

