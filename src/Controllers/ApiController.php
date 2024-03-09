<?php

namespace App\Controllers;

use App\Models\DB;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDOException;

class ApiController
{
    public function getAll(Request $request, Response $response)
    {
        $sql = "SELECT aux_apenom, aux_apepat, aux_apemat, aux_nom, aux_dni, clie_codsap, clie_subv, clie_activo, clie_vip, aux_unid, aux_subdiv, aux_ccosto, aux_cat, aux_zona, user_id, aux_cargo, aux_ccosto_id, aux_sede_id FROM dmona.aux_clientes";

        try {
            $db = new DB();
            $conn = $db->connect();
            $stmt = $conn->query($sql);
            $customers = $stmt->fetchAll(PDO::FETCH_OBJ);
            $db = null;

            $response->getBody()->write(json_encode($customers));
            return $response
                ->withHeader('content-type', 'application/json')
                ->withStatus(200);
        } catch (PDOException $e) {
            $error = ["message" => $e->getMessage()];
            $response->getBody()->write(json_encode($error));
            return $response
                ->withHeader('content-type', 'application/json')
                ->withStatus(500);
        }
    }
}