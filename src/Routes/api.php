<?php

namespace App\Routes;

use App\Controllers\ApiController;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app -> group('/api', function(RouteCollectorProxy $group){
        $group->get('/all', ApiController::class . ':getAll')->setName('api.all');
    });

};