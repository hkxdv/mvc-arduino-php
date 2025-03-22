<?php
/**
 * logs_debug.php
 * Script de depuración para logs
 * Despliega una lista de logs directamente, evitando el enrutamiento MVC
 */

// Configurar cabeceras para JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Access-Control-Allow-Origin: *');

try {
    // Directorio de logs (ajustado para ubicación en scripts/)
    $logDir = dirname(__DIR__) . '/logs';
    $logs = [];

    // Verificar directorio
    if (!is_dir($logDir)) {
        throw new Exception("El directorio de logs no existe: $logDir");
    }

    // Escanear directorio en busca de archivos .log
    $files = scandir($logDir);
    
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'log') {
            $filePath = $logDir . '/' . $file;
            
            // Formato de tamaño de archivo
            $size = filesize($filePath);
            $formattedSize = formatFileSize($size);

            $logs[] = [
                'filename' => $file,
                'size' => $formattedSize,
                'modified' => date('Y-m-d H:i:s', filemtime($filePath))
            ];
        }
    }

    // Ordenar por fecha de modificación (más recientes primero)
    usort($logs, function ($a, $b) {
        return strcmp($b['modified'], $a['modified']);
    });

    // Construir respuesta JSON
    $response = [
        'logs' => $logs,
        'timestamp' => date('Y-m-d H:i:s'),
        'source' => 'scripts/logs_debug.php'
    ];

    // Devolver JSON
    echo json_encode($response);

} catch (Exception $e) {
    // Error en formato JSON
    echo json_encode([
        'logs' => [],
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
        'source' => 'scripts/logs_debug.php'
    ]);
}

/**
 * Formatea el tamaño de un archivo para mostrar
 * 
 * @param int $size Tamaño en bytes
 * @return string Tamaño formateado
 */
function formatFileSize(int $size): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;

    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }

    return round($size, 2) . ' ' . $units[$i];
} 