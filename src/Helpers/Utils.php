<?php

namespace App\Helpers;

use stdClass;

class Utils
{
    public function __construct() {}

    public static function transformArray(array $devices, array $template, bool $groupByDevice = false, string $groupField = 'DeviceSerial'): array
    {
        if ($groupByDevice) {
            return self::groupAndTransform($devices, $template, $groupField);
        } else {
            return array_map(function ($device) use ($template) {
                return self::transformItem($device, $template);
            }, $devices);
        }
    }

    private static function groupAndTransform(array $devices, array $template, string $groupField): array
    {
        $grouped = [];

        foreach ($devices as $device) {
            $groupValue = $device->{$template[$groupField]};

            if (!isset($grouped[$groupValue])) {
                $grouped[$groupValue] = self::initializeGroupedItem($device, $template, $groupField);
            }

            $dataToAdd = [];
            foreach ($template['Data'] as $key => $field) {
                if (property_exists($device, $field)) {
                    $dataToAdd[$key] = $device->{$field};
                } else {
                    $dataToAdd[$key] = $field;
                }
            }

            // Agregar los datos dinámicos al grupo
            $grouped[$groupValue]['Data'][] = $dataToAdd;
        }

        return array_values($grouped);
    }


    private static function initializeGroupedItem(stdClass $device, array $template, string $groupField): array
    {
        $result = [];

        // Iterar sobre la plantilla para inicializar el nuevo elemento agrupado
        foreach ($template as $key => $value) {
            if ($key === $groupField) {
                // Asignar el valor del campo de agrupación
                $result[$key] = $device->{$value};
            } elseif (is_array($value)) {
                // Inicializar "Data" como un array vacío para almacenar luego
                $result[$key] = [];
            } elseif (property_exists($device, $value)) {
                // Si el valor es una propiedad existente del objeto, asignarla
                $result[$key] = $device->{$value};
            } else {
                // Si no hay un valor correspondiente en el objeto, asignar el valor por defecto de la plantilla
                $result[$key] = $value;
            }
        }

        return $result;
    }


    // Método para transformar un solo objeto stdClass basado en el template
    private static function transformItem(stdClass $device, array $template): array
    {
        $result = [];

        foreach ($template as $key => $value) {
            if (is_array($value)) {
                // Si el valor es un array, hacemos una llamada recursiva
                $result[$key] = self::transformItem($device, $value);
            } elseif (property_exists($device, $value)) {
                // Si existe la propiedad en el objeto, la asignamos
                $result[$key] = $device->{$value};
            } else {
                // Si no existe, se deja el valor por defecto o vacío
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
