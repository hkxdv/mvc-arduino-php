# Sistema MVC para Arduino con WebSockets en PHP

Sistema desarrollado en PHP con arquitectura MVC para monitoreo y control de Arduino en tiempo real utilizando WebSockets.

<div align="center">
  <img src="https://img.shields.io/badge/-PHP-000000?style=for-the-badge&logo=php&labelColor=282c34"/>
  <img src="https://img.shields.io/badge/-PostgreSQL-000000?style=for-the-badge&logo=postgresql&labelColor=282c34"/>
  <img src="https://img.shields.io/badge/-XAMPP-000000?style=for-the-badge&logo=xampp&labelColor=282c34"/>
  <img src="https://img.shields.io/badge/-Composer-000000?style=for-the-badge&logo=composer&labelColor=282c34"/>
  <img src="https://img.shields.io/badge/-WebSockets-000000?style=for-the-badge&logo=socket.io&labelColor=282c34"/>
  <img src="https://img.shields.io/badge/-Arduino-000000?style=for-the-badge&logo=arduino&labelColor=282c34"/>
</div>

> [!IMPORTANT]
> Para una documentación detallada, consulta la carpeta `/docs`. Se recomienda empezar por [ESPECIFICACIONES.md](/docs/ESPECIFICACIONES.md)

## Descripción

Aplicación MVC para monitoreo de sensores Arduino en tiempo real usando WebSockets. Permite visualización de datos sin recargar la página y ofrece comunicación bidireccional entre Arduino y múltiples clientes web.

## Documentación

Consulta los siguientes documentos para más detalles:

- [ESPECIFICACIONES.md](/docs/ESPECIFICACIONES.md) - Documento principal con todas las especificaciones
- [WEBSOCKET_SERVER.md](/docs/WEBSOCKET_SERVER.md) - Información sobre el servidor WebSocket
- [ESTRUCTURA_BASE.md](/docs/ESTRUCTURA_BASE.md) - Estructura del proyecto MVC
- [AUTOLOADING.md](/docs/AUTOLOADING.md) - Sistema de autoloading PSR-4
- [MANEJO_ERRORES.md](/docs/MANEJO_ERRORES.md) - Sistema de manejo de errores
- [INTEGRACION_MODULOS.md](/docs/INTEGRACION_MODULOS.md) - Integración entre módulos

## Inicio Rápido

```bash
# Configuración inicial
cp .env.example .env
composer install

# Iniciar el servidor WebSocket
php config/websocket_server.php
```

> [!TIP]
> Para desarrollo sin Arduino, establece `ARDUINO_SIMULATE=true` en tu archivo `.env`

## 🥷 Autor

<a href="https://github.com/hk4u-dxv">
  <img src="https://img.shields.io/badge/-hk4u--dxv-000000?style=for-the-badge&logo=github&labelColor=282c34"/>
</a>

<div align="center">
  <p>Sistema MVC para monitoreo de Arduino en tiempo real con WebSockets.</p>
</div>
