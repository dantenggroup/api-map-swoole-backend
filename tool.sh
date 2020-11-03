#!/usr/bin/env bash

php bin/hyperf.php migrate:refresh
php bin/hyperf.php db:seed

php bin/hyperf.php gen:model --with-comments