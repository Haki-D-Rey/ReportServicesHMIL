<?php

namespace App\Routes;

use App\Controllers\ApiController;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {

    $app ->get('/', ApiController:: class . ':index');
 
    $app -> group('/api', function(RouteCollectorProxy $group){
        $group->get('/all', ApiController::class . ':getAll')->setName('api.all');
        $group->get('/excel', ApiController::class . ':getExcelReporteServiciosAlimentacion')->setName('api.getExcel');
        $group->get('/reporte-eventos', ApiController::class . ':getExcelReportInscripcionesEvent')->setName('api.reportEvents');
        $group->get('/data-eventos', ApiController::class . ':getPlanInscripcionEvents')->setName('api.dataEventos');
        $group->get('/con', ApiController::class . ':getConnection')->setName('api.con');
        $group->get('/data-siservi-clientes', ApiController::class . ':getExcelReporteServiciosAlimentacionDatosClientes')->setName('api.dataSiserviClientes');
        $group->post('/insertquery-marks', ApiController::class . ':getBigDataInsertMarks')->setName('api.insertquery_marks');
    });

};