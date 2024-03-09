<?php

namespace App\Controllers;

class FileController
{
    protected $storageDirectory;

    public function __construct()
    {
        $this->storageDirectory = __DIR__ . '/storage/';
    }

    public function uploadFile()
    {
        // Verificar si se recibió un archivo
        if (isset($_FILES['file'])) {
            $uploadedFile = $_FILES['file'];

            // Nombre del archivo
            $fileName = basename($uploadedFile['name']);

            // Ruta completa del archivo en el directorio de almacenamiento
            $filePath = $this->storageDirectory . $fileName;

            // Mover el archivo al directorio de almacenamiento
            if (move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
                // Respuesta exitosa
                echo json_encode(['message' => 'Archivo subido con éxito.', 'file' => $filePath]);
            } else {
                // Error al mover el archivo
                echo json_encode(['error' => 'Error al subir el archivo.']);
            }
        } else {
            // No se recibió ningún archivo
            echo json_encode(['error' => 'No se recibió ningún archivo.']);
        }
    }
}
