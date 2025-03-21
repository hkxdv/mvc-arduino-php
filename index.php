<?php

/**
 * index.php
 * Punto de entrada principal de la aplicación
 * 
 * Este archivo inicia la aplicación MVC cargando la configuración
 * y ejecutando el flujo de control principal usando PSR-4.
 */

// Cargar el bootstrap de la aplicación
require_once "config/bootstrap.config.php";

// Manejar errores HTTP
manejarErroresHttp();

// Iniciar la aplicación
iniciarAplicacion();
