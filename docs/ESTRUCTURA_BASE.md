# Estructura Base del Proyecto MVC en PHP

Este documento detalla la estructura base MVC desarrollada para proyectos PHP, explicando cada directorio y componente esencial. **Esta es una estructura base**, diseñada para ser una plantilla inicial que puedes adaptar a tus necesidades específicas.

> [!NOTE]
> Esta documentación describe la estructura MVC fundamental. Para más detalles sobre la implementación, consulta [ESPECIFICACIONES.md](./ESPECIFICACIONES.md).

## Estructura de Carpetas

```
/
├── app/                     # Núcleo de la aplicación (MVC)
│   ├── Controllers/         # Controladores de la aplicación (namespace App\Controllers)
│   ├── Models/              # Modelos para la lógica de negocio (namespace App\Models)
│   │   └── sql/             # Scripts SQL para la creación de esquemas
│   └── Views/               # Vistas para la presentación
│       ├── components/      # Componentes reutilizables
│       ├── modules/         # Módulos principales de la aplicación
│       ├── errors/          # Módulos para páginas de error
│       └── templates/       # Plantillas base
├── assets/                  # Recursos estáticos
│   ├── css/                 # Hojas de estilo CSS
│   ├── js/                  # Scripts de JavaScript
│   │   ├── modules/         # Módulos JavaScript
│   │   └── utils/           # Utilidades JavaScript
│   └── img/                 # Imágenes y recursos gráficos
├── config/                  # Archivos de configuración
│   ├── bootstrap.config.php     # Inicialización de la aplicación
│   ├── routes.config.php        # Configuración de rutas
│   ├── error_handler.config.php # Manejo de errores
│   └── alias.config.php         # Sistema de alias para rutas
├── docs/                    # Documentación del proyecto
├── logs/                    # Registros de errores y eventos
├── vendor/                  # Dependencias (gestionadas por Composer)
├── .env.example             # Plantilla para variables de entorno
├── .gitignore               # Archivos a ignorar en control de versiones
├── .htaccess                # Configuración de Apache y manejo de errores HTTP
├── composer.json            # Definición de dependencias y configuración PSR-4
└── index.php                # Punto de entrada principal
```

> **Nota importante**: Los componentes relacionados con la documentación son opcionales y solo sirven para mostrar información sobre la estructura base. Puedes eliminarlos en tus proyectos si no los necesitas.

## Componentes Esenciales

### 1. Archivos de Configuración Clave

Los siguientes archivos en la carpeta `config/` son fundamentales para la estructura MVC:

- **bootstrap.config.php**: Inicializa la aplicación, carga dependencias y configuraciones. **Obligatorio** para el arranque correcto del sistema.
- **error_handler.config.php**: Proporciona manejo centralizado de errores y excepciones. **Obligatorio** para la gestión adecuada de errores.
- **routes.config.php**: Define rutas estáticas de la aplicación. *Recomendado* para facilitar la navegación.
- **alias.config.php**: Implementa sistema de alias para simplificar rutas. *Recomendado* para mejor mantenimiento del código.

Si se desea modificar estos archivos, primero es esencial comprender su funcionamiento para no romper la estructura base.

### 2. Estructura MVC con PSR-4

#### Controladores Fundamentales

- **PrincipalController.php**: Controlador base que maneja el flujo principal (App\Controllers\PrincipalController). **Obligatorio** como punto de entrada de la lógica de control.
- **ErrorController.php**: Gestiona los errores de la aplicación (App\Controllers\ErrorController). **Obligatorio** para mostrar errores de forma adecuada.
- Otros controladores específicos para cada módulo.

#### Modelos Esenciales

- **ConexionModel.php**: Gestiona la conexión a la base de datos (App\Models\ConexionModel). **Obligatorio** para interactuar con la base de datos.
- **EnlaceModel.php**: Gestiona el enrutamiento dinámico (App\Models\EnlaceModel). **Obligatorio** para la navegación entre páginas.
- Modelos específicos para entidades del sistema, todos con namespace App\Models.

#### Vistas

- **templates/plantilla_base.php**: Estructura HTML principal.
- **templates/plantilla_error.php**: Plantilla específica para páginas de error.
- **modules/**: Contenido específico de cada sección.
- **components/**: Elementos reutilizables (menús, cabeceras, etc.).
- **errors/**: Vistas para diferentes tipos de errores.

### 3. Assets (Recursos Estáticos)

Organizados por tipo para facilitar su mantenimiento:

- **css/**: Estilos de la aplicación.
- **js/**: Scripts del lado del cliente.
- **img/**: Imágenes y elementos gráficos.

### 4. Autoloading PSR-4

El proyecto utiliza **exclusivamente el estándar PSR-4** a través de Composer para la carga automática de clases:

```json
"autoload": {
    "psr-4": {
        "App\\": "app/"
    }
}
```

Esto mapea el namespace `App\` al directorio `app/` del proyecto, lo que significa que:
- Una clase `App\Controllers\MenuController` se busca en `app/Controllers/MenuController.php`
- Una clase `App\Models\MenuModel` se busca en `app/Models/MenuModel.php`

### 5. Archivos Raíz

- **index.php**: Punto de entrada único que inicia la aplicación. **Obligatorio** como puerta de entrada al sistema.
- **.htaccess**: Configuración de Apache y manejo de URLs amigables.
- **composer.json**: Gestión de dependencias y configuración PSR-4.
- **.env.example**: Plantilla para configuración de variables de entorno.

## Flujo de Ejecución

1. El usuario solicita una URL.
2. **index.php** carga el bootstrap de la aplicación.
3. **bootstrap.config.php** inicializa componentes esenciales y carga el autoloader de Composer.
4. El controlador apropiado maneja la solicitud, normalmente comenzando con **PrincipalController**.
5. El controlador consulta al modelo correspondiente según sea necesario.
6. Se carga la vista correspondiente dentro de la plantilla base.
7. El sistema de JavaScript carga los módulos JS necesarios para esa vista.

## Modificación de Archivos Fundamentales

Si necesitas modificar alguno de los archivos fundamentales mencionados anteriormente (PrincipalController, ErrorController, ConexionModel, EnlaceModel, o los archivos de configuración), es **esencial** que primero comprendas su funcionamiento y las implicaciones de los cambios que planeas realizar. Estos archivos son cruciales para mantener la integridad base de esta estructura MVC.
