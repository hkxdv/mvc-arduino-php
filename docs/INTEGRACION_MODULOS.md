# Guía de Integración de Módulos JavaScript ES6

**Última actualización:** 19/03/2025

Este documento explica el proceso para integrar nuevos módulos JavaScript ES6 en tu aplicación utilizando el sistema de carga dinámica de módulos implementado en la estructura base MVC. Este sistema permite cargar módulos específicos según la página actual, optimizando el rendimiento y facilitando una arquitectura modular.

## Módulos Base JavaScript Fundamentales

El sistema se basa en tres módulos JavaScript fundamentales que gestionan la carga dinámica:

1. **app.js**: Punto de entrada principal que inicializa la aplicación.

   - Configura el evento `DOMContentLoaded` para comenzar la inicialización
   - Llama a `inicializarCargador()` para activar la carga dinámica de módulos
   - Proporciona funciones básicas como `mostrarNotificacionError()`

2. **cargador.js**: Núcleo del sistema de carga dinámica.

   - Detecta automáticamente la página actual mediante diferentes estrategias:
     - Atributo data-page en el body
     - Parámetro "option" en la URL
     - Nombre del archivo PHP
   - Mantiene un mapeo de qué módulos cargar para cada página mediante `MODULOS_POR_PAGINA`
   - Carga módulos comunes que se necesitan en todas las páginas
   - Importa dinámicamente los módulos JavaScript y llama a su método `init()`

3. **cache.js**: Sistema de caché para optimizar el rendimiento.
   - Implementa un sistema de almacenamiento temporal en memoria
   - Permite reducir llamadas repetidas al servidor para los mismos datos
   - Ofrece funciones para gestionar tiempo de vida (TTL) de los datos en caché

Estos módulos base son **esenciales** si deseas aprovechar el sistema de carga dinámica de módulos JavaScript. Proporcionan la infraestructura necesaria para que los módulos específicos de cada página se carguen automáticamente.

## Arquitectura de Módulos

El sistema sigue una arquitectura MVC (Modelo-Vista-Controlador) con carga dinámica de módulos JavaScript según la página actual:

```txt
/
├── app/
│   ├── Controllers/   # Controladores (PSR-4: App\Controllers)
│   ├── Models/        # Modelos (PSR-4: App\Models)
│   └── Views/
│       └── modules/   # Módulos de vista PHP
└── assets/
    └── js/
        ├── modules/   # Módulos JavaScript ES6
        │   ├── app.js       # Punto de entrada principal
        │   ├── cargador.js  # Sistema de carga dinámica
        │   ├── cache.js     # Sistema de caché
        │   └── ejemplo.js   # Ejemplo de módulo específico
        └── utils/     # Utilidades JavaScript
```

## Flujo de Carga de Páginas y Módulos

1. El usuario accede a una URL (ej: `index.php?option=recetas`)
2. El controlador principal (`PrincipalController.php`) procesa el parámetro `option`
3. Se carga la vista PHP correspondiente (ej: `app/Views/modules/recetas.php`)
4. El template principal (`plantilla_base.php`) incluye el script `app.js` como módulo ES6:
   ```html
   <script type="module" src="assets/js/modules/app.js"></script>
   ```
5. `app.js` espera a que el DOM esté listo y luego inicializa el cargador de módulos
6. `cargador.js` detecta que la página actual es "recetas" mediante el parámetro URL
7. El cargador consulta la configuración `MODULOS_POR_PAGINA` para ver qué módulos cargar
8. Se cargan dinámicamente los módulos correspondientes mediante `import()`
9. Se llama automáticamente a la función `init()` de cada módulo cargado

## Sistema de Carga Dinámica de Módulos JavaScript

El sistema utiliza la función `import()` de ES6 para cargar dinámicamente los módulos:

```javascript
async function cargarModulo(nombreModulo) {
  const rutaCompleta = `../modules/${nombreModulo}`;
  const modulo = await import(rutaCompleta);

  // Inicializar automáticamente el módulo
  if (typeof modulo.init === "function") {
    modulo.init();
  }

  return modulo;
}
```

Esta implementación permite:

- **Carga bajo demanda**: Solo se cargan los módulos necesarios para cada página
- **Inicialización automática**: Los módulos se inicializan al cargarse si tienen función `init()`
- **Gestión de dependencias**: Cada módulo puede importar sus propias dependencias

## Configuración de Módulos por Página

El archivo `cargador.js` contiene un mapeo que define qué módulos cargar para cada página:

```javascript
const MODULOS_POR_PAGINA = {
  recetas: ["recetas.js", "ingredientes.js"],
  menu: ["menu.js"],
  ejemplo: ["ejemplo.js"],
  // Añade aquí tus páginas y módulos
};
```

También existe un array para módulos comunes que se cargan en todas las páginas:

```javascript
const MODULOS_COMUNES = ["comun.js"];
```

## Cómo Agregar un Nuevo Módulo

### 1. Crear un Nuevo Módulo de Vista PHP

Crear un archivo en `app/Views/modules/`, por ejemplo `nuevo_modulo.php`:

```php
<div class="card mb-4">
    <div class="card-header">
        <h2>Nuevo Módulo</h2>
    </div>
    <div class="card-body">
        <!-- Contenido del módulo -->
        <div id="nuevo-modulo-container">
            <!-- Este contenedor será manipulado por el JavaScript -->
        </div>
    </div>
</div>
```

### 2. Crear el Módulo JavaScript Correspondiente

Crear un archivo en `assets/js/modules/`, por ejemplo `nuevo_modulo.js`:

```javascript
/**
 * Módulo para gestionar la nueva funcionalidad
 * @module nuevo_modulo
 */

// Importar otros módulos si es necesario
import appCache from "./cache.js";

/**
 * Inicializa el módulo
 * Esta función se llamará automáticamente por el cargador
 */
export function init() {
  console.log("Inicializando nuevo módulo...");

  // Inicializar componentes
  const container = document.getElementById("nuevo-modulo-container");
  if (!container) return;

  // Cargar datos o inicializar eventos
  container.innerHTML = "<p>Módulo inicializado correctamente</p>";
}

/**
 * Maneja la acción del botón
 */
function manejarAccion() {
  console.log("Acción ejecutada");
  // Implementación
}
```

### 3. Registrar el Módulo en el Cargador

Modificar `cargador.js` para incluir el nuevo módulo:

```javascript
const MODULOS_POR_PAGINA = {
  // Módulos existentes...
  nuevo_modulo: ["nuevo_modulo.js"],
};
```

### 4. Actualizar el Controlador (si es necesario)

Si se requiere una nueva ruta, actualizar el controlador para manejar la nueva opción.

## Uso Avanzado del Sistema de Caché

El módulo `cache.js` proporciona un sistema de caché en memoria que puede utilizarse para optimizar el rendimiento de las operaciones:

```javascript
import appCache from "./cache.js";

// Guardar datos en caché
appCache.set("mi_clave", datos, 600); // TTL de 10 minutos

// Verificar si existe en caché
if (appCache.has("mi_clave")) {
  // Obtener datos de la caché
  const datosCached = appCache.get("mi_clave");

  // Verificar tiempo restante
  const segundosRestantes = appCache.ttlRemaining("mi_clave");
  console.log(`La caché expira en ${segundosRestantes} segundos`);
}

// Limpiar una entrada específica
appCache.clear("mi_clave");

// Limpiar toda la caché
appCache.clear();
```

## Mejores Prácticas

1. **Naming convention**:

   - Controladores: `NombreController.php` (PSR-4)
   - Modelos: `NombreModel.php` (PSR-4)
   - Módulos PHP: `nombre_modulo.php`
   - Módulos JS: `nombre_modulo.js`
   - Utilidades JS: `nombre_utilidad.js`

2. **Estructura de módulos JavaScript**:

   - Exportar una función `init()` que inicialice el módulo
   - Mantener funciones auxiliares como privadas dentro del módulo
   - Utilizar el sistema de caché para datos frecuentemente utilizados
   - Documentar cada función con JSDoc

3. **Separación de responsabilidades**:

   - PHP: renderizado de la estructura HTML y datos iniciales
   - JavaScript: interactividad, validaciones y actualización dinámica

4. **Modularidad**:
   - Cada módulo debe tener una responsabilidad única y bien definida
   - Evitar dependencias circulares entre módulos
   - Preferir composición sobre herencia para reutilizar funcionalidad
