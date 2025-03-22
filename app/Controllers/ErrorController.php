<?php

/**
 * ErrorController.php
 * Controlador dedicado para gestionar los errores del sistema
 * Versión con namespace PSR-4
 */

namespace App\Controllers;

use Exception;

/**
 * Clase para gestionar los errores del sistema
 * 
 * @package App\Controllers
 */
class ErrorController
{
    /**
     * Registra un mensaje de error en el archivo de logs
     * 
     * @param string $mensaje Mensaje de error a registrar
     * @param string $tipo Tipo de error (404, 500, etc.)
     * @param string $detalles Detalles adicionales del error (opcional)
     * @return bool Retorna true si se registró correctamente, false en caso contrario
     */
    private static function registrarError(string $mensaje, string $tipo, string $detalles = ''): bool
    {
        try {
            // Definir la ruta del directorio de logs
            $directorioLogs = dirname(__DIR__, 2) . '/logs';

            // Verificar si el directorio existe, si no, crearlo
            if (!is_dir($directorioLogs)) {
                mkdir($directorioLogs, 0755, true);
            }

            // Definir el archivo de log
            $archivoLog = $directorioLogs . '/errores.log';

            // Verificar si es un error relacionado con Arduino y guardar en archivo específico
            if (
                strpos($mensaje, 'COM') !== false ||
                strpos($mensaje, 'puerto serial') !== false ||
                strpos($mensaje, 'Arduino') !== false ||
                strpos($detalles, 'COM') !== false
            ) {
                $archivoLog = $directorioLogs . '/arduino_errors.log';
            }

            // Formatear el mensaje de error con fecha y hora
            $fecha = date('Y-m-d H:i:s');
            $logMensaje = "[{$fecha}] [{$tipo}] {$mensaje}";

            // Agregar detalles si están disponibles
            if (!empty($detalles)) {
                $logMensaje .= " | Detalles: {$detalles}";
            }

            // Agregar IP del cliente y URI solicitada
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            $logMensaje .= " | IP: {$ip} | URI: {$uri}";

            // Agregar información de entorno para errores de Arduino
            if (strpos($archivoLog, 'arduino_errors') !== false) {
                $logMensaje .= "\nInfo Entorno: " .
                    "OS: " . PHP_OS . ", " .
                    "PHP: " . PHP_VERSION . ", " .
                    "Puerto: " . ($_ENV['ARDUINO_PORT'] ?? 'No configurado') . ", " .
                    "Baudrate: " . ($_ENV['ARDUINO_BAUDRATE'] ?? 'No configurado');
            }

            $logMensaje .= "\n";

            // Escribir en el archivo de log (modo append)
            return file_put_contents($archivoLog, $logMensaje, FILE_APPEND) !== false;
        } catch (Exception $e) {
            // Si ocurre un error, usar el error_log estándar de PHP
            error_log("Error al registrar en log: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra errores específicos de Arduino en un archivo dedicado
     * 
     * @param string $mensaje Mensaje de error
     * @param array $contexto Información adicional de contexto
     * @return bool Retorna true si se registró correctamente
     */
    public static function registrarErrorArduino(string $mensaje, array $contexto = []): bool
    {
        try {
            // Definir la ruta del directorio de logs
            $directorioLogs = dirname(__DIR__, 2) . '/logs';

            // Verificar si el directorio existe, si no, crearlo
            if (!is_dir($directorioLogs)) {
                mkdir($directorioLogs, 0755, true);
            }

            // Archivo específico para errores de Arduino
            $archivoLog = $directorioLogs . '/arduino_debug.log';

            // Formatear el mensaje
            $fecha = date('Y-m-d H:i:s');
            $logMensaje = "[{$fecha}] [ARDUINO] {$mensaje}\n";

            // Agregar información de contexto
            if (!empty($contexto)) {
                $logMensaje .= "Contexto: " . json_encode($contexto, JSON_UNESCAPED_SLASHES) . "\n";
            }

            // Agregar información de sistema
            $logMensaje .= "Sistema: OS=" . PHP_OS .
                ", PHP=" . PHP_VERSION .
                ", Puerto=" . ($_ENV['ARDUINO_PORT'] ?? 'No configurado') .
                ", Baudrate=" . ($_ENV['ARDUINO_BAUDRATE'] ?? 'No configurado') . "\n";

            $logMensaje .= "----------------------------------------\n";

            // Escribir en el archivo
            return file_put_contents($archivoLog, $logMensaje, FILE_APPEND) !== false;
        } catch (Exception $e) {
            error_log("Error al registrar error de Arduino: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Renderiza la plantilla de error con contenido específico
     * 
     * @param string $vista Ruta a la vista específica de error
     * @param array $data Datos a pasar a la vista
     * @return void
     */
    private static function renderizarError(string $vista, array $data = []): void
    {
        // Definir variable global para indicar que estamos en una página de error
        if (!defined('ES_PAGINA_ERROR')) {
            define('ES_PAGINA_ERROR', true);
        }

        // Limpiar cualquier buffer de salida existente
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Extraer los datos para que estén disponibles en la vista
        extract($data);

        // Iniciar un nuevo buffer para capturar la vista específica
        ob_start();
        include $vista;
        $contenido_error = ob_get_clean();

        // Renderizar la plantilla de error con el contenido
        include_once dirname(__DIR__) . "/views/templates/plantilla_error.php";

        // Detener la ejecución
        exit();
    }

    /**
     * Muestra la página de error 404 (página no encontrada)
     * 
     * @param string $mensaje Mensaje de error personalizado (opcional)
     * @return void
     */
    public static function error404(string $mensaje = ""): void
    {
        http_response_code(404);
        $data = [
            "mensaje" => $mensaje ?: "Lo sentimos, la página que buscas no existe.",
            "codigo" => 404,
            "titulo" => "Página No Encontrada"
        ];

        // Registrar error en el log personalizado
        self::registrarError($mensaje, "404");

        // Registrar error en el log del sistema
        error_log("Error 404: $mensaje");

        // Cargar y renderizar la vista de error 404
        self::renderizarError(dirname(__DIR__) . "/views/errors/error_404.php", $data);
    }

    /**
     * Muestra la página de error 403 (acceso prohibido)
     * 
     * @param string $mensaje Mensaje de error personalizado (opcional)
     * @return void
     */
    public static function error403(string $mensaje = ""): void
    {
        // Establecer código de estado HTTP 403
        http_response_code(403);

        // Si no hay mensaje personalizado, usar el default
        if (empty($mensaje)) {
            $mensaje = "No tienes permisos para acceder a este recurso.";
        }

        // Enviar variables a la vista
        $data = [
            "mensaje" => $mensaje,
            "codigo" => 403,
            "titulo" => "Acceso Prohibido"
        ];

        // Registrar error en el log personalizado
        self::registrarError($mensaje, "403");

        // Registrar error en el log del sistema
        error_log("Error 403: $mensaje");

        // Cargar y renderizar la vista de error 403
        self::renderizarError(dirname(__DIR__) . "/views/errors/error_403.php", $data);
    }

    /**
     * Muestra la página de error de conexión a base de datos
     * 
     * @param string $mensaje Mensaje detallado del error
     * @return void
     */
    public static function errorDb(string $mensaje = ""): void
    {
        // Establecer código de estado HTTP 500
        http_response_code(500);

        // Si no hay mensaje personalizado, usar el default
        if (empty($mensaje)) {
            $mensaje = "Error de conexión a la base de datos. Por favor, intente más tarde.";
        }

        // Enviar variables a la vista
        $data = [
            "mensaje" => $mensaje,
            "codigo" => 500,
            "titulo" => "Error de Conexión",
            "mensaje_detallado" => $_ENV['APP_DEBUG'] === 'true' ? $mensaje : null
        ];

        // Registrar error en el log personalizado
        self::registrarError($mensaje, "DB", isset($data['mensaje_detallado']) ? $data['mensaje_detallado'] : '');

        // Registrar error en el log con información detallada
        error_log("Error de BD: $mensaje");

        // Cargar y renderizar la vista de error de base de datos
        self::renderizarError(dirname(__DIR__) . "/views/errors/error_db.php", $data);
    }

    /**
     * Muestra una página de error genérico
     * 
     * @param int $codigo Código de error HTTP
     * @param string $titulo Título del error
     * @param string $mensaje Mensaje detallado del error
     * @param string $mensajeDetallado Información técnica adicional (opcional)
     * @return void
     */
    public static function errorGenerico(int $codigo, string $titulo, string $mensaje, string $mensajeDetallado = ""): void
    {
        // Establecer código de estado HTTP
        http_response_code($codigo);

        // Enviar variables a la vista
        $data = [
            "mensaje" => $mensaje,
            "codigo" => $codigo,
            "titulo" => $titulo,
            "mensaje_detallado" => $_ENV['APP_DEBUG'] === 'true' ? $mensajeDetallado : null
        ];

        // Registrar error en el log personalizado
        self::registrarError($mensaje, $codigo, $mensajeDetallado);

        // Registrar error en el log
        error_log("Error $codigo: $titulo - $mensaje");

        // Cargar y renderizar la vista de error genérico
        self::renderizarError(dirname(__DIR__) . "/views/errors/error_generico.php", $data);
    }
}
