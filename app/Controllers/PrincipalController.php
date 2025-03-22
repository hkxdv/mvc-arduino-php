<?php

/**
 * PrincipalController.php
 * Controlador principal que maneja las interacciones entre modelos y vistas
 * Versión con namespace PSR-4
 */

namespace App\Controllers;

use App\Models\EnlaceModel;
use Exception;

/**
 * Clase para gestionar el comportamiento principal de la aplicación
 * 
 * @package App\Controllers
 */
class PrincipalController
{
    /**
     * Carga la plantilla principal de la aplicación
     * 
     * @return void
     */
    public static function plantillaBase(): void
    {
        // Solo cargar la plantilla base si no estamos en una página de error
        if (!defined('ES_PAGINA_ERROR')) {
            include dirname(__DIR__) . "/Views/templates/plantilla_base.php";
        }
    }

    /**
     * Maneja la navegación entre las diferentes secciones
     * Carga los módulos correspondientes según la opción seleccionada
     * 
     * @return void
     */
    public static function cargaModulos(): void
    {
        // Si ya estamos en una página de error, no hacer nada
        if (defined('ES_PAGINA_ERROR')) {
            return;
        }

        try {
            // Obtener la opción de navegación
            $enlace = filter_input(INPUT_GET, 'option', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'principal';

            // Si es la página principal, redirigir a Arduino
            if ($enlace === 'principal') {
                $enlace = 'arduino';
            }

            // Intentar ejecutar un controlador o cargar un módulo
            if (self::intentarEjecutarControlador($enlace) || self::intentarCargarModulo($enlace)) {
                return; // Si se ejecutó con éxito, terminamos
            }

            // Si llegamos aquí, no se encontró el controlador ni el módulo
            ErrorController::errorGenerico(404, 'Página no encontrada', 'La ruta solicitada no existe');
        } catch (Exception $e) {
            ErrorController::errorGenerico(500, 'Error interno del servidor', $e->getMessage());
        }
    }

    /**
     * Intenta ejecutar un controlador si el enlace contiene una acción
     * 
     * @param string $enlace Enlace a procesar
     * @return bool True si se ejecutó un controlador, false en caso contrario
     */
    private static function intentarEjecutarControlador(string $enlace): bool
    {
        // Depuración
        error_log("[DEBUG] Intentando ejecutar controlador para: " . $enlace);

        // Si no hay separador de acción, verificar si es un controlador con método index
        if (strpos($enlace, '/') === false) {
            $controladorNombre = ucfirst($enlace) . 'Controller';
            $controladorClase = "\\App\\Controllers\\{$controladorNombre}";

            // Verificar si existe la clase y el método index
            if (class_exists($controladorClase) && method_exists($controladorClase, 'index')) {
                error_log("[DEBUG] Ejecutando controlador {$controladorClase}->index()");
                $controlador = new $controladorClase();
                $controlador->index();
                return true;
            }

            return false;
        }

        $partes = explode('/', $enlace);
        if (count($partes) < 2) {
            return false;
        }

        // Construir nombre de controlador
        $controladorNombre = ucfirst($partes[0]) . 'Controller';
        $controladorClase = "\\App\\Controllers\\{$controladorNombre}";
        $metodoNombre = $partes[1];

        // Verificar si existe la clase
        if (!class_exists($controladorClase)) {
            error_log("[DEBUG] Clase de controlador no encontrada: " . $controladorClase);
            return false;
        }

        // Verificar si existe el método
        if (!method_exists($controladorClase, $metodoNombre)) {
            error_log("[DEBUG] Método no encontrado en controlador: {$controladorClase}->{$metodoNombre}()");
            return false;
        }

        // Ejecutar controlador->método
        error_log("[DEBUG] Ejecutando controlador: {$controladorClase}->{$metodoNombre}()");
        $controlador = new $controladorClase();
        $controlador->$metodoNombre();

        return true;
    }

    /**
     * Intenta cargar un módulo desde la tabla de enlaces
     * 
     * @param string $enlace Enlace a buscar
     * @return bool True si se cargó un módulo, false en caso contrario
     */
    private static function intentarCargarModulo(string $enlace): bool
    {
        // Buscar ruta del módulo
        $respuesta = EnlaceModel::rutaModulo($enlace);
        if ($respuesta === null) {
            return false;
        }

        // Construir ruta completa según si comienza con 'app/' o no
        $rutaCompleta = self::construirRutaCompleta($respuesta);

        // Verificar si el archivo existe
        if (!file_exists($rutaCompleta)) {
            return false;
        }

        // Incluir el módulo
        include $rutaCompleta;
        return true;
    }

    /**
     * Construye la ruta completa para un módulo
     * 
     * @param string $ruta Ruta relativa del módulo
     * @return string Ruta completa del archivo
     */
    private static function construirRutaCompleta(string $ruta): string
    {
        if (strpos($ruta, 'app/') === 0) {
            return dirname(dirname(__DIR__)) . '/' . $ruta;
        }

        return dirname(__DIR__) . '/' . $ruta;
    }

    /**
     * Extrae los parámetros GET excluyendo 'option'
     * 
     * @return array Arreglo de parámetros
     */
    private static function extraerParametrosGet(): array
    {
        $parametros = [];

        if (!empty($_GET)) {
            $parametrosGet = $_GET;
            unset($parametrosGet['option']);

            foreach ($parametrosGet as $value) {
                $parametros[] = $value;
            }
        }

        return $parametros;
    }
}
