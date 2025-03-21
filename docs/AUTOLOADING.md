# Sistema de Autoloading

## Descripción

El sistema de carga automática (autoloading) permite incluir clases PHP automáticamente cuando se necesitan, sin tener que usar `require` o `include` manualmente.

**IMPORTANTE**: Este proyecto utiliza **el estándar PSR-4** a través de Composer para el autoloading de clases. Se ha eliminado completamente cualquier sistema de autoloading personalizado que pudiera existir anteriormente.

## Implementación PSR-4

PSR-4 es un estándar de autoloading que mapea namespaces a directorios del sistema de archivos. Permite una estructura de código más organizada y modulable.

### Configuración en nuestro proyecto

En el archivo `composer.json`, tenemos la siguiente configuración para el autoloading:

```json
"autoload": {
    "psr-4": {
        "App\\": "app/"
    }
}
```

Esta configuración mapea el namespace `App\` al directorio `app/` del proyecto, lo que significa que:

- Una clase `App\Controllers\MenuController` se busca en `app/Controllers/MenuController.php`
- Una clase `App\Models\MenuModel` se busca en `app/Models/MenuModel.php`

### Ventajas del uso de PSR-4

- **Estructura organizada**: Mantiene una relación clara entre namespaces y directorios
- **Compatibilidad**: Facilita la integración con librerías y frameworks de terceros
- **Escalabilidad**: Permite añadir nuevas clases sin modificar la configuración de autoloading
- **Estándar de la industria**: PSR-4 es ampliamente adoptado en el ecosistema PHP

## Uso en el proyecto

### Cómo utilizar clases con namespace

Para utilizar una clase con namespace, se debe:

1. Importar la clase con `use`:

```php
use App\Models\MenuModel;
```

2. Instanciar o utilizar la clase:

```php
$menu = new MenuModel();
```

### Regenerar el autoloader

Después de añadir nuevas clases o cambiar la estructura de directorios, ejecuta:

```bash
composer dump-autoload -o
```

## Depuración

Si enfrentas problemas con el autoloading:

1. Verifica que la clase esté en el directorio correcto según su namespace
2. Confirma que el nombre del archivo coincida exactamente con el nombre de la clase (incluyendo mayúsculas/minúsculas)
3. Ejecuta `composer dump-autoload -o` para regenerar el autoloader
4. Revisa los registros de errores para mensajes específicos
