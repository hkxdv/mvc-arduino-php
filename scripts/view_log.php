<?php
/**
 * view_log.php
 * Script para ver el contenido de un archivo de log
 * Complemento de logs_debug.php
 */

// Verificar que se proporcionó un nombre de archivo
$file = isset($_GET['file']) ? $_GET['file'] : '';

// Validar el nombre del archivo
if (empty($file) || !preg_match('/^[\w\-\.]+\.log$/', $file) || strpos($file, '..') !== false) {
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Nombre de archivo no válido',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Ruta completa al archivo (ajustada para ubicación en scripts/)
$filePath = dirname(__DIR__) . '/logs/' . $file;

// Verificar si existe
if (!file_exists($filePath)) {
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'El archivo no existe',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Configurar cabeceras apropiadas para texto plano
header('Content-Type: text/plain; charset=UTF-8');
header('Content-Disposition: inline; filename="' . $file . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Mostrar contenido del archivo
readfile($filePath); 