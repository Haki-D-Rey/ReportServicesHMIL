<?php

namespace App\Routes;

use App\Controllers\ApiController;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {

    $app ->get('/', ApiController:: class . ':index');

    $app -> group('/api', function(RouteCollectorProxy $group){
        $group->get('/all', ApiController::class . ':getAll')->setName('api.all');
    });

};