#!/bin/bash

docker compose exec backend sh -c '
    APP_ENV=test php bin/console doctrine:database:create &&
    APP_ENV=test php bin/console doctrine:migrations:migrate --no-interaction &&
    APP_ENV=test php bin/console doctrine:fixtures:load --no-interaction &&
    APP_ENV=test php bin/phpunit --testdox
  '

