<?php

declare(strict_types=1);

use Hyperf\HttpServer\Router\Router;

Router::addRoute(['GET', 'HEAD'], '/', [App\Controller\IndexController::class, 'index']);

Router::get('/favicon.ico', function () {
    return '';
});
