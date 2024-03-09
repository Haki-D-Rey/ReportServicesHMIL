<?php

namespace App\Routes;

use Slim\App;
use App\Controllers\EmailController;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {

    $app -> group('/email', function(RouteCollectorProxy $group){

        $group->post('/send', EmailController::class . ':sendEmail')->setName('email.send');
    });

};
