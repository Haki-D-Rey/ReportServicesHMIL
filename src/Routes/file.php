<?php
namespace App\Routes;

use Slim\App;
use App\Controllers\FileController;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {

    $app -> group('/file', function(RouteCollectorProxy $group){
        $group->post('/upload', FileController::class . ':uploadFile')->setName('file.upload');
        $group->post('/excel', FileController::class . ':downloadExcel')->setName('file.excel');
    });

};
