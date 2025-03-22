<?php

/**
 * ArduinoController.php
 * Controlador para gestionar las peticiones relacionadas con el sensor de temperatura LM35
 */

namespace App\Controllers;

use Exception;

/**
 * Clase para gestionar las interacciones con el sensor de temperatura LM35
 * 
 * @package App\Controllers
 */
class ArduinoController
{
    private $puerto;
    private $baudrate;
    private $arduinoModel;

    public function __construct()
    {
        $this->puerto = $_ENV['ARDUINO_PORT'] ?? 'COM6';
        $this->baudrate = intval($_ENV['ARDUINO_BAUDRATE'] ?? 9600);
    }

    /**
     * Método principal que muestra la lista de sensores disponibles
     * 
     * @return void
     */
    public function index(): void
    {
        try {
            // Obtener configuración actual
            $puerto = $_ENV['ARDUINO_PORT'] ?? 'COM3';
            $baudrate = intval($_ENV['ARDUINO_BAUDRATE'] ?? 9600);

            // Verificar si hay mensaje de éxito de configuración
            $configOk = isset($_GET['msg']) && $_GET['msg'] === 'config_ok';

            // Verificar si el servidor WebSocket está activo
            $websocketActivo = \App\Models\ArduinoSensorModel::verificarServidorWebSocket();

            // Intentar iniciar el WebSocket si no está activo
            if (!$websocketActivo) {
                \App\Models\ArduinoSensorModel::iniciarServidorWebSocket();
                // Verificar nuevamente después del intento
                $websocketActivo = \App\Models\ArduinoSensorModel::verificarServidorWebSocket();
            }

            // Incluir la vista de listado de sensores
            include dirname(__DIR__) . "/Views/modules/arduino/listar_sensores.modulo.php";
        } catch (Exception $e) {
            ErrorController::errorGenerico(500, 'Error del servidor', $e->getMessage());
        }
    }

    /**
     * Muestra los datos del sensor de temperatura
     * 
     * @return void
     */
    public function mostrar(): void
    {
        try {
            // Cargar valores de configuración
            $puerto = $_ENV['ARDUINO_PORT'] ?? 'COM3';
            $baudrate = intval($_ENV['ARDUINO_BAUDRATE'] ?? 9600);

            // Verificar si el servidor WebSocket está activo
            $websocketActivo = \App\Models\ArduinoSensorModel::verificarServidorWebSocket();

            // Si el servidor WebSocket no está activo, intentar iniciarlo
            if (!$websocketActivo) {
                $iniciadoExitosamente = \App\Models\ArduinoSensorModel::iniciarServidorWebSocket();
                if ($iniciadoExitosamente) {
                    error_log("Servidor WebSocket iniciado automáticamente");
                } else {
                    error_log("No se pudo iniciar automáticamente el servidor WebSocket");
                }

                // Verificar nuevamente si se inició correctamente
                $websocketActivo = \App\Models\ArduinoSensorModel::verificarServidorWebSocket();
            }

            // Incluir la vista (ahora usa WebSockets para actualización en tiempo real)
            include dirname(__DIR__) . "/Views/modules/arduino/mostrar_sensor.modulo.php";
        } catch (Exception $e) {
            ErrorController::errorGenerico(500, 'Error del servidor', $e->getMessage());
        }
    }

    /**
     * Obtiene los datos del sensor y los devuelve en formato JSON
     * Útil para actualizaciones mediante AJAX
     * 
     * @return void
     */
    public function datos(): void
    {
        try {
            // Verificar si el servidor WebSocket está activo e iniciarlo si es necesario
            if (!$this->arduinoModel) {
                $this->arduinoModel = new \App\Models\ArduinoSensorModel($this->puerto, $this->baudrate);
            }

            $websocketActivo = \App\Models\ArduinoSensorModel::verificarServidorWebSocket();
            if (!$websocketActivo) {
                // Intentar iniciar automáticamente el servidor WebSocket
                \App\Models\ArduinoSensorModel::iniciarServidorWebSocket();
            }

            // Leer datos (ahora prioriza el WebSocket)
            $datos = $this->arduinoModel->leerDatos();

            // Verificar si estamos en modo simulación
            $modoSimulacion = \App\Models\ArduinoSensorModel::isModoSimulacion();

            // Verificar si tenemos datos
            if (empty($datos)) {
                // No hay datos, devolver respuesta de error
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se pudieron leer datos del sensor',
                    'data' => null,
                    'simulado' => $modoSimulacion,
                    'origen' => $modoSimulacion ? 'log_websocket' : 'error',
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                return;
            }

            // Procesar datos de temperatura
            $temperatura = $datos[0];
            $temperaturaFormateada = number_format($temperatura, 1) . '°C';

            // Asignar color según rango de temperatura
            $color = $this->determinarColorTemperatura($temperatura);

            // Determinar origen de los datos
            $origen = 'serial';
            if (\App\Models\ArduinoSensorModel::verificarServidorWebSocket()) {
                $origen = 'websocket';
            } elseif ($modoSimulacion) {
                $origen = 'log_websocket';
            }

            // Devolver respuesta JSON con información completa
            echo json_encode([
                'status' => 'success',
                'temperatura' => $temperatura,
                'temperaturaFormateada' => $temperaturaFormateada,
                'simulado' => $modoSimulacion,
                'origen' => $origen,
                'color' => $color,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Registrar el error
            $this->registrarError('Error al obtener datos: ' . $e->getMessage());

            // Devolver respuesta de error
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al leer datos: ' . $e->getMessage(),
                'simulado' => \App\Models\ArduinoSensorModel::isModoSimulacion(),
                'origen' => 'error',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }

    /**
     * Muestra la página de diagnóstico para solucionar problemas de conexión
     * 
     * @return void
     */
    public function diagnostico(): void
    {
        try {
            // Incluir la vista de diagnóstico
            include dirname(__DIR__) . "/Views/modules/arduino/diagnostico.modulo.php";
        } catch (Exception $e) {
            ErrorController::errorGenerico(500, 'Error del servidor', $e->getMessage());
        }
    }

    /**
     * Configura los parámetros de conexión con el Arduino
     * 
     * @return void
     */
    public function configurar(): void
    {
        try {
            // Obtener valores actuales desde .env o valores por defecto
            $puertoActual = $_ENV['ARDUINO_PORT'] ?? 'COM6';
            $baudrateActual = intval($_ENV['ARDUINO_BAUDRATE'] ?? 9600);
            $simulateActual = $_ENV['ARDUINO_SIMULATE'] ?? 'false';
            $websocketPortActual = $_ENV['WEBSOCKET_PORT'] ?? '8080';

            // Detectar sistema operativo
            $sistemaOperativo = 'Windows';
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'LIN') {
                $sistemaOperativo = 'Linux';
            } elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'DAR') {
                $sistemaOperativo = 'Mac';
            }

            // Variables para mensaje y error
            $mensaje = '';
            $error = '';

            // Lista predefinida de puertos comunes
            $puertosComunes = [];
            if ($sistemaOperativo === 'Windows') {
                // Puertos COM comunes en Windows
                for ($i = 1; $i <= 20; $i++) {
                    $puertosComunes[] = "COM{$i}";
                }
            } elseif ($sistemaOperativo === 'Linux') {
                // Puertos tty comunes en Linux
                $puertosComunes = ['/dev/ttyUSB0', '/dev/ttyUSB1', '/dev/ttyACM0', '/dev/ttyACM1'];
            } else {
                // Puertos cu comunes en Mac
                $puertosComunes = ['/dev/cu.usbmodem1101', '/dev/cu.usbmodem1201', '/dev/cu.usbserial-1410'];
            }

            // Intentar detectar puertos disponibles
            $puertosDetectados = [];

            if ($sistemaOperativo === 'Windows' && function_exists('exec')) {
                exec('mode', $salida);
                foreach ($salida as $linea) {
                    if (strpos($linea, 'COM') !== false) {
                        preg_match('/COM\d+/', $linea, $coincidencias);
                        if (!empty($coincidencias)) {
                            $puertosDetectados[] = $coincidencias[0];
                        }
                    }
                }
            } elseif ($sistemaOperativo === 'Linux' && function_exists('glob')) {
                $puertosDetectados = array_merge(glob('/dev/ttyUSB*'), glob('/dev/ttyACM*'));
            } elseif ($sistemaOperativo === 'Mac' && function_exists('glob')) {
                $puertosDetectados = array_merge(glob('/dev/cu.usbmodem*'), glob('/dev/cu.usbserial*'));
            }

            // Procesar formulario POST
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $envFile = dirname(dirname(dirname(__DIR__))) . '/.env';
                $envContent = file_get_contents($envFile);
                $updated = false;

                // Determinar el tipo de configuración (Arduino o WebSocket)
                $configType = filter_input(INPUT_POST, 'config_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? 'arduino';

                if ($configType === 'websocket') {
                    // Procesar configuración de WebSocket
                    $simulate = filter_input(INPUT_POST, 'simulate', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    $websocketPort = filter_input(INPUT_POST, 'websocket_port', FILTER_SANITIZE_NUMBER_INT);

                    // Validación básica
                    if ($websocketPort < 1024 || $websocketPort > 65535) {
                        $error = "El puerto WebSocket debe estar entre 1024 y 65535";
                    } else {
                        // Actualizar ARDUINO_SIMULATE en .env
                        if (strpos($envContent, 'ARDUINO_SIMULATE=') !== false) {
                            $envContent = preg_replace('/ARDUINO_SIMULATE=.*(\r?\n|$)/', "ARDUINO_SIMULATE=$simulate$1", $envContent);
                        } else {
                            $envContent .= "\nARDUINO_SIMULATE=$simulate\n";
                        }

                        // Actualizar WEBSOCKET_PORT en .env
                        if (strpos($envContent, 'WEBSOCKET_PORT=') !== false) {
                            $envContent = preg_replace('/WEBSOCKET_PORT=.*(\r?\n|$)/', "WEBSOCKET_PORT=$websocketPort$1", $envContent);
                        } else {
                            $envContent .= "WEBSOCKET_PORT=$websocketPort\n";
                        }

                        // Guardar cambios
                        file_put_contents($envFile, $envContent);

                        // Actualizar variables de entorno en tiempo de ejecución
                        $_ENV['ARDUINO_SIMULATE'] = $simulate;
                        $_ENV['WEBSOCKET_PORT'] = $websocketPort;

                        // Verificar si necesitamos reiniciar el WebSocket
                        $websocketActivo = \App\Models\ArduinoSensorModel::verificarServidorWebSocket();
                        if ($websocketActivo) {
                            // Detener WebSocket actual para que se reinicie con la nueva configuración
                            \App\Models\ArduinoSensorModel::detenerServidorWebSocket();
                        }

                        $mensaje = "Configuración de WebSocket actualizada correctamente.";
                        $updated = true;
                    }
                } else {
                    // Configuración de Arduino (puerto serial)
                    $puerto = filter_input(INPUT_POST, 'puerto', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    $baudrate = filter_input(INPUT_POST, 'baudrate', FILTER_SANITIZE_NUMBER_INT);

                    // Si se seleccionó "personalizado", usar el valor del campo personalizado
                    if ($puerto === 'personalizado') {
                        $puerto = filter_input(INPUT_POST, 'puertoPersonalizado', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    }

                    // Validar datos
                    if (empty($puerto)) {
                        $error = "El puerto no puede estar vacío";
                    } else if (empty($baudrate) || !is_numeric($baudrate)) {
                        $error = "El baudrate debe ser un valor numérico";
                    } else {
                        // Intentar actualizar el archivo .env
                        try {
                            // Verificar si ya existe la configuración de Arduino
                            if (strpos($envContent, 'ARDUINO_PORT') !== false) {
                                // Actualizar valores existentes
                                $envContent = preg_replace('/ARDUINO_PORT=.*(\r?\n|$)/', "ARDUINO_PORT=$puerto$1", $envContent);
                                $envContent = preg_replace('/ARDUINO_BAUDRATE=.*(\r?\n|$)/', "ARDUINO_BAUDRATE=$baudrate$1", $envContent);
                            } else {
                                // Agregar configuración al final del archivo
                                $envContent .= "\n# Configuración de Arduino\nARDUINO_PORT=$puerto\nARDUINO_BAUDRATE=$baudrate\n";
                            }

                            // Guardar cambios
                            file_put_contents($envFile, $envContent);

                            // Actualizar variables de entorno en tiempo de ejecución
                            $_ENV['ARDUINO_PORT'] = $puerto;
                            $_ENV['ARDUINO_BAUDRATE'] = $baudrate;

                            // Si el WebSocket está activo, reiniciarlo para que tome la nueva configuración
                            $websocketActivo = \App\Models\ArduinoSensorModel::verificarServidorWebSocket();
                            if ($websocketActivo) {
                                \App\Models\ArduinoSensorModel::detenerServidorWebSocket();
                            }

                            $updated = true;
                        } catch (Exception $e) {
                            $error = "Error al guardar la configuración: " . $e->getMessage();
                        }
                    }
                }

                // Redirigir a la página principal con mensaje de éxito si todo salió bien
                if ($updated) {
                    header('Location: index.php?option=arduino&msg=config_ok');
                    exit;
                }
            }

            // Incluir la vista de configuración
            include dirname(__DIR__) . "/Views/modules/arduino/configurar_arduino.modulo.php";
        } catch (Exception $e) {
            ErrorController::errorGenerico(500, 'Error del servidor', $e->getMessage());
        }
    }

    /**
     * Inicia el servidor WebSocket para Arduino
     * 
     * @return void
     */
    public function iniciarWebSocket(): void
    {
        try {
            // Verificar si ya está corriendo
            if (\App\Models\ArduinoSensorModel::verificarServidorWebSocket()) {
                header('Location: /monitor');
                exit;
            }

            // Inicializar ArduinoModel con configuración
            \App\Models\ArduinoSensorModel::inicializar($this->puerto, $this->baudrate);

            // Iniciar servidor WebSocket en segundo plano
            $rootPath = dirname(dirname(__DIR__));
            $command = 'php ' . $rootPath . '/public/websocket_server.php';

            if (PHP_OS_FAMILY === 'Windows') {
                // En Windows, usar start /B para ejecutar en segundo plano
                pclose(popen('start /B ' . $command, 'r'));
            } else {
                // En Unix, usar nohup para ejecutar en segundo plano
                exec('nohup ' . $command . ' > /dev/null 2>&1 &');
            }

            sleep(1); // Pequeña pausa para permitir que el servidor inicie

            // Verificar si hay que usar simulación
            $modoSimulacion = \App\Models\ArduinoSensorModel::isModoSimulacion();

            // Redireccionar a la página de monitoreo
            header('Location: /monitor' . ($modoSimulacion ? '?simulacion=1' : ''));
            exit;
        } catch (Exception $e) {
            $this->registrarErrorControlador("Error al iniciar WebSocket", $e);
            echo "Error al iniciar el servidor WebSocket: " . $e->getMessage();
        }
    }

    /**
     * Verifica el estado del servidor de WebSocket
     *
     * @return void
     */
    public function estadoWebSocket(): void
    {
        try {
            // Limpiar cualquier salida previa
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Configurar cabeceras para JSON
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            $estado = [
                'activo' => \App\Models\ArduinoSensorModel::verificarServidorWebSocket(),
                'simulacion' => \App\Models\ArduinoSensorModel::isModoSimulacion(),
                'timestamp' => date('Y-m-d H:i:s')
            ];

            echo json_encode($estado, JSON_THROW_ON_ERROR);
            exit;
        } catch (\Throwable $e) {
            // Limpiar cualquier salida previa en caso de error
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Registrar el error
            error_log("Error en estadoWebSocket: " . $e->getMessage());

            // Configurar cabeceras para JSON
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            echo json_encode([
                'activo' => false,
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_THROW_ON_ERROR);
            exit;
        }
    }

    /**
     * Detiene el servidor WebSocket
     * 
     * @return void
     */
    public function detenerWebSocket(): void
    {
        try {
            // Verificar si el servidor está corriendo
            $websocketActivo = \App\Models\ArduinoSensorModel::verificarServidorWebSocket();

            if (!$websocketActivo) {
                // Redirigir con mensaje
                header('Location: index.php?option=arduino/mostrar&msg=ws_no_activo');
                exit;
            }

            // Intentar detener el servidor
            $resultado = \App\Models\ArduinoSensorModel::detenerServidorWebSocket();

            if ($resultado) {
                // Redirigir con mensaje de éxito
                header('Location: index.php?option=arduino/mostrar&msg=ws_detenido');
            } else {
                // Redirigir con mensaje de error
                header('Location: index.php?option=arduino/mostrar&msg=ws_error_detener');
            }

            exit;
        } catch (Exception $e) {
            ErrorController::errorGenerico(500, 'Error al detener WebSocket', $e->getMessage());
        }
    }

    /**
     * Muestra la interfaz para administrar el servidor WebSocket
     * 
     * @return void
     */
    public function webserver(): void
    {
        try {
            // Incluir la vista
            include dirname(__DIR__) . "/Views/modules/arduino/webserver.modulo.php";
        } catch (Exception $e) {
            ErrorController::errorGenerico(500, 'Error del servidor', $e->getMessage());
        }
    }

    /**
     * Lista archivos de log para visualización en la interfaz
     * 
     * @return void
     */
    public function listarLogs(): void
    {
        try {
            // Limpiar cualquier salida previa
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Configurar cabeceras para JSON
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            // Directorio de logs
            $logDir = dirname(dirname(__DIR__)) . '/logs';
            $logs = [];

            if (is_dir($logDir)) {
                // Escanear directorio en busca de archivos .log
                $files = scandir($logDir);

                foreach ($files as $file) {
                    if (pathinfo($file, PATHINFO_EXTENSION) === 'log') {
                        $fullPath = $logDir . '/' . $file;
                        $fileStats = stat($fullPath);

                        // Obtener la primera línea o datos básicos para el resumen
                        $tipo = 'Sistema';
                        $mensaje = $file;

                        // Intentar leer la primera línea para extraer más info
                        $handle = @fopen($fullPath, 'r');
                        if ($handle) {
                            $firstLine = fgets($handle);
                            fclose($handle);

                            // Intentar extraer fecha y tipo
                            if (preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) \[([^\]]+)\]/', $firstLine, $matches)) {
                                $tipo = $matches[2];
                            }

                            // Extraer un fragmento del mensaje
                            $mensaje = substr($firstLine, 0, 100) . (strlen($firstLine) > 100 ? '...' : '');
                        }

                        $logs[] = [
                            'archivo' => $file,
                            'ruta' => basename($fullPath),
                            'fecha' => date('Y-m-d H:i:s', $fileStats['mtime']),
                            'tamano' => $this->formatFileSize($fileStats['size']),
                            'tipo' => $tipo,
                            'mensaje' => $mensaje
                        ];
                    }
                }

                // Ordenar por fecha de modificación (más reciente primero)
                usort($logs, function ($a, $b) {
                    return strtotime($b['fecha']) - strtotime($a['fecha']);
                });
            }

            echo json_encode(['logs' => $logs], JSON_THROW_ON_ERROR);
            exit;
        } catch (\Throwable $e) {
            // Limpiar cualquier salida previa en caso de error
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Registrar el error
            error_log("Error al listar logs: " . $e->getMessage());

            // Configurar cabeceras para JSON
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            echo json_encode([
                'success' => false,
                'logs' => [],
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_THROW_ON_ERROR);
            exit;
        }
    }

    /**
     * Muestra el contenido de un archivo de log
     * 
     * @return void
     */
    public function verLog(): void
    {
        try {
            // Limpiar cualquier salida previa para evitar contenido mixto
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Obtener nombre del archivo
            $archivo = $_GET['archivo'] ?? '';

            // Validar nombre del archivo (solo permitir archivos .log)
            if (empty($archivo) || pathinfo($archivo, PATHINFO_EXTENSION) !== 'log' || strpos($archivo, '..') !== false) {
                throw new \Exception('Archivo de log no válido');
            }

            // Ruta completa al archivo
            $rutaArchivo = dirname(dirname(__DIR__)) . '/logs/' . $archivo;

            // Verificar si existe
            if (!file_exists($rutaArchivo) || !is_readable($rutaArchivo)) {
                throw new \Exception('El archivo de log no existe o no se puede leer');
            }

            // Configurar cabeceras
            header('Content-Type: text/plain; charset=UTF-8');
            header('Content-Disposition: inline; filename="' . $archivo . '"');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            // Mostrar contenido directamente usando readfile para archivos grandes
            readfile($rutaArchivo);
            exit;
        } catch (\Throwable $e) {
            // Limpiar buffer en caso de error
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Registrar el error
            error_log("Error al mostrar log: " . $e->getMessage());

            // Establecer cabeceras para texto plano y error
            header('Content-Type: text/plain; charset=UTF-8');
            header('HTTP/1.1 404 Not Found');
            echo "Error: " . $e->getMessage();
            exit;
        }
    }

    /**
     * Formatea el tamaño de un archivo para mostrar
     * 
     * @param int $size Tamaño en bytes
     * @return string Tamaño formateado
     */
    private function formatFileSize(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2) . ' ' . $units[$i];
    }

    private function registrarErrorControlador(string $mensaje, ?\Exception $e = null): void
    {
        $logFile = dirname(dirname(__DIR__)) . '/logs/arduino_errors.log';
        $timestamp = date('Y-m-d H:i:s');

        $contexto = [
            'OS' => PHP_OS,
            'PHP_VERSION' => PHP_VERSION,
            'USER' => get_current_user(),
            'SERVER_SOFTWARE' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
            'CURRENT_PORT' => $_ENV['ARDUINO_PORT'] ?? 'No configurado',
            'ARDUINO_BAUDRATE' => $_ENV['ARDUINO_BAUDRATE'] ?? 'No configurado'
        ];

        $logMessage = "$timestamp [ARDUINO ERROR] ";
        if ($e !== null) {
            $logMessage .= "Level: {$e->getCode()}, Message: {$e->getMessage()}, File: {$e->getFile()}:{$e->getLine()}\n";
            $logMessage .= "Context: " . json_encode($contexto, JSON_UNESCAPED_SLASHES) . "\n";
            $logMessage .= "Stack trace: " . $e->getTraceAsString() . "\n";
        } else {
            $logMessage .= "$mensaje\n";
            $logMessage .= "Context: " . json_encode($contexto, JSON_UNESCAPED_SLASHES) . "\n";
        }
        $logMessage .= "----------------------------------------\n";

        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    private function registrarError(string $mensaje): void
    {
        $this->registrarErrorControlador($mensaje);
    }

    private function determinarColorTemperatura(float $temperatura): string
    {
        if ($temperatura > 30) {
            return '#e53e3e';
        } elseif ($temperatura < 10) {
            return '#3182ce';
        } else {
            return '#38a169';
        }
    }

    /**
     * Verificar el estado del servidor WebSocket vía AJAX
     * @return void Envía respuesta JSON
     */
    public function verificarWebSocketAjax()
    {
        // Limpiar cualquier salida previa
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Establecer cabeceras para JSON
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        try {
            // Verificar el estado usando el modelo
            $estado = \App\Models\ArduinoSensorModel::verificarServidorWebSocket();
            $simulacion = \App\Models\ArduinoSensorModel::isModoSimulacion();

            // Preparar respuesta exitosa
            $respuesta = [
                'success' => true,
                'activo' => $estado,
                'simulacion' => $simulacion,
                'message' => $estado ? 'El servidor WebSocket está activo' : 'El servidor WebSocket está inactivo',
                'timestamp' => date('Y-m-d H:i:s')
            ];

            // Enviar respuesta JSON
            echo json_encode($respuesta, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            // Log del error
            error_log('Error al verificar el servidor WebSocket: ' . $e->getMessage());

            // Enviar respuesta de error en formato JSON
            echo json_encode([
                'success' => false,
                'activo' => false,
                'simulacion' => false,
                'message' => 'Error al verificar el estado: ' . $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_THROW_ON_ERROR);
        }

        // Asegurar que el script termine después de enviar la respuesta
        exit;
    }

    /**
     * Iniciar el servidor WebSocket vía AJAX
     * @return void Envía respuesta JSON
     */
    public function iniciarWebSocketAjax()
    {
        // Limpiar cualquier salida previa
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Establecer cabeceras para JSON
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        try {
            // Verificar primero si el servidor ya está activo
            $estaActivo = \App\Models\ArduinoSensorModel::verificarServidorWebSocket();

            if ($estaActivo) {
                echo json_encode([
                    'success' => true,
                    'message' => 'El servidor WebSocket ya está activo',
                    'timestamp' => date('Y-m-d H:i:s')
                ], JSON_THROW_ON_ERROR);
                exit;
            }

            // Iniciar el servidor WebSocket usando el modelo
            $resultado = \App\Models\ArduinoSensorModel::iniciarServidorWebSocket();

            // Preparar respuesta exitosa o de error
            $respuesta = [
                'success' => $resultado,
                'message' => $resultado ? 'Servidor WebSocket iniciado correctamente' : 'No se pudo iniciar el servidor WebSocket',
                'timestamp' => date('Y-m-d H:i:s')
            ];

            // Enviar respuesta JSON
            echo json_encode($respuesta, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            // Log del error
            error_log('Error al iniciar el servidor WebSocket: ' . $e->getMessage());

            // Enviar respuesta de error en formato JSON
            echo json_encode([
                'success' => false,
                'message' => 'Error al iniciar el servidor: ' . $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_THROW_ON_ERROR);
        }

        // Asegurar que el script termine después de enviar la respuesta
        exit;
    }

    /**
     * Detener el servidor WebSocket vía AJAX
     * @return void Envía respuesta JSON
     */
    public function detenerWebSocketAjax()
    {
        // Limpiar cualquier salida previa
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Establecer cabeceras para JSON
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        try {
            // Verificar primero si el servidor está activo
            $estaActivo = \App\Models\ArduinoSensorModel::verificarServidorWebSocket();

            if (!$estaActivo) {
                echo json_encode([
                    'success' => true,
                    'message' => 'El servidor WebSocket ya está detenido',
                    'timestamp' => date('Y-m-d H:i:s')
                ], JSON_THROW_ON_ERROR);
                exit;
            }

            // Detener el servidor WebSocket usando el modelo
            $resultado = \App\Models\ArduinoSensorModel::detenerServidorWebSocket();

            // Preparar respuesta exitosa o de error
            $respuesta = [
                'success' => $resultado,
                'message' => $resultado ? 'Servidor WebSocket detenido correctamente' : 'No se pudo detener el servidor WebSocket',
                'timestamp' => date('Y-m-d H:i:s')
            ];

            // Enviar respuesta JSON
            echo json_encode($respuesta, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            // Log del error
            error_log('Error al detener el servidor WebSocket: ' . $e->getMessage());

            // Enviar respuesta de error en formato JSON
            echo json_encode([
                'success' => false,
                'message' => 'Error al detener el servidor: ' . $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_THROW_ON_ERROR);
        }

        // Asegurar que el script termine después de enviar la respuesta
        exit;
    }

    /**
     * Recibe datos del WebSocket y los almacena en el modelo
     * Esta función es llamada desde JavaScript usando AJAX
     * 
     * @return void Envía respuesta JSON
     */
    public function setDatosWebSocket()
    {
        // Limpiar cualquier salida previa
        if (ob_get_length()) ob_clean();

        // Establecer cabeceras para JSON
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        try {
            // Obtener datos del POST
            $json = file_get_contents('php://input');
            $datos = json_decode($json, true);

            if (!$datos || !isset($datos['temperatura'])) {
                throw new Exception('Datos de temperatura no válidos');
            }

            // Verificar si los datos son simulados
            $simulado = isset($datos['simulated']) ? (bool)$datos['simulated'] : false;

            // Establecer los datos en el modelo
            \App\Models\ArduinoSensorModel::setUltimaLectura(
                floatval($datos['temperatura']),
                $simulado
            );

            echo json_encode([
                'success' => true,
                'message' => 'Datos recibidos correctamente'
            ]);
        } catch (Exception $e) {
            // Log del error
            error_log('Error al recibir datos del WebSocket: ' . $e->getMessage());

            echo json_encode([
                'success' => false,
                'message' => 'Error al procesar los datos: ' . $e->getMessage()
            ]);
        }
    }
}
