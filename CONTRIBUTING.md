# Guía de Contribución

## Instalación del proyecto

### Requisitos

- XAMPP
- GitHub Desktop (para clonar el repositorio y realizar commits de cambios)
- Acceso a un puerto COM para Arduino (opcional)

### Pasos de instalación

1. **Clonar el repositorio usando GitHub Desktop**

   - Abre GitHub Desktop
   - File > Clone repository
   - Ingresa la URL del repositorio o selecciónalo de la lista
   - Elige la ubicación local (ejemplo: `C:\GitHub\mvc-arduino-php`)
   - Haz clic en "Clone"

2. **Instalar dependencias**

   > Nota: Las dependencias de Composer se distribuirán en un archivo ZIP separado. No necesitas tener Composer instalado.

   - Descomprime el archivo `vendor.zip` proporcionado en la carpeta raíz del proyecto
   - Verifica que se haya creado correctamente la carpeta `vendor/` con todas las dependencias

3. **Configurar variables de entorno**

   ```
   cp .env.example .env
   ```

   - Edita el archivo `.env` con la configuración de tu entorno
   - Configura `ARDUINO_PORT` con el puerto COM de tu Arduino
   - Establece `ARDUINO_SIMULATE=true` si no tienes un Arduino

### Configuración con XAMPP

Para utilizar el proyecto con XAMPP, necesitas crear un enlace simbólico que conecte la ubicación de tu repositorio con el directorio `htdocs` de XAMPP:

**En Windows:**

1. Abre el Símbolo del sistema (CMD) como administrador
2. Ejecuta el siguiente comando, adaptando las rutas a tu configuración:

   ```
   mklink /D "C:\xampp\htdocs\mvc-arduino-php" "C:\GitHub\mvc-arduino-php"
   ```

   Donde:

   - `C:\xampp\htdocs\mvc-arduino-php` es la ruta donde quieres acceder desde XAMPP
   - `D:\GitHub\mvc-arduino-php` es la ubicación real del repositorio clonado

**En macOS:**

1. Abre Terminal
2. Ejecuta el siguiente comando, adaptando las rutas a tu configuración:

   ```
   ln -s "/Users/tuusuario/GitHub/mvc-arduino-php" "/Applications/XAMPP/xamppfiles/htdocs/mvc-arduino-php"
   ```

   Donde:

   - `/Users/tuusuario/GitHub/mvc-arduino-php` es la ubicación real del repositorio
   - `/Applications/XAMPP/xamppfiles/htdocs/mvc-arduino-php` es la ruta en XAMPP

3. Si tienes problemas de permisos, puedes necesitar usar `sudo` antes del comando

Una vez configurado el enlace simbólico, podrás acceder al proyecto desde:

```
http://localhost/mvc-arduino-php/
```

## Servidor WebSocket

El proyecto utiliza un servidor WebSocket para la comunicación en tiempo real con Arduino:

1. **Iniciar manualmente**

   ```
   php config/websocket_server.php
   ```

2. **Iniciar desde la interfaz web**

   - Accede a la página de administración en `index.php?option=arduino/webserver`
   - Utiliza el botón "Iniciar servidor WebSocket"

3. **Verificar estado**
   - El estado del servidor WebSocket se muestra en la página de administración
   - Los logs se guardan en `logs/websocket.log`

## Archivos que NO deben modificarse

Para mantener la integridad del sistema base, **NO modifiques** los siguientes archivos:

### Archivos de configuración base

- `config/bootstrap.config.php` - Inicialización del sistema
- `config/error_handler.config.php` - Manejo de errores
- `config/websocket_server.php` - Implementación del servidor WebSocket

### Controladores y modelos fundamentales

- `app/Controllers/PrincipalController.php` - Controlador principal
- `app/Controllers/ErrorController.php` - Manejo centralizado de errores
- `app/Models/EnlaceModel.php` - Enrutamiento y navegación
- `app/Models/ArduinoSensorModel.php` - Comunicación con Arduino

### Archivos del sistema

- `index.php` - Punto de entrada principal
- `.htaccess` - Configuración de Apache y manejo de URLs
- `composer.json` - Configuración de dependencias y autoloading PSR-4

### Scripts de utilidad

- `scripts/logs_debug.php`
- `scripts/test-api.php`
- `scripts/view_log.php`

### Actualizar archivos existentes

Si es **absolutamente necesario** modificar archivos existentes:

1. Mantén la compatibilidad con el código existente
2. Sigue los estándares de codificación establecidos

## Documentación de referencia

Consulta estos documentos para entender la arquitectura:

- `docs/ESTRUCTURA_BASE.md` - Organización general del proyecto
- `docs/MANEJO_ERRORES.md` - Sistema de manejo de errores
- `docs/INTEGRACION_MODULOS.md` - Cómo integrar módulos JavaScript
- `docs/WEBSOCKET_SERVER.md` - Funcionamiento del servidor WebSocket
- `docs/ESPECIFICACIONES.md` - Estándares y convenciones de codificación

## Convenciones para commits

Para mantener un historial de commits claro y útil, sigue estas convenciones:

1. **Escribe los mensajes en inglés**
2. **Usa el formato conventional commits**:
   ```
   type(scope): short description
   ```
   Donde:
   - `type`: feat, fix, docs, style, refactor, perf, test, chore
   - `scope`: módulo o componente afectado (arduino, websocket, database, etc.)
   - `description`: descripción concisa del cambio en tiempo presente

3. **Ejemplos de buenos commits**:
   - `feat(arduino): Add temperature threshold configuration`
   - `fix(websocket): Resolve connection timeout on Windows`
   - `docs(api): Update connection parameters documentation`
   - `refactor(sensors): Improve data processing efficiency`

4. **Mantén los commits atómicos** - Cada commit debe representar un cambio lógico único

## Tareas pendientes

Para ver las tareas pendientes de implementación, consulta el archivo [PENDING_TASKS.md](PENDING_TASKS.md).
