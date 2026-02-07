# Arquitectura de DemoWP

## Visión General

DemoWP está diseñado con una arquitectura modular que separa claramente las responsabilidades entre componentes. El plugin opera en dos modos distintos:

1. **Modo Template**: En la instalación principal de WordPress
2. **Modo Clone**: En las instalaciones de demo clonadas

## Estructura de Archivos

```
demowp/
├── demowp.php                 # Archivo principal del plugin
├── uninstall.php              # Limpieza al desinstalar
├── action-scheduler/          # Librería Action Scheduler
├── admin/
│   ├── class-demowp-admin-page.php    # Páginas de administración
│   ├── css/
│   │   └── demowp-admin.css           # Estilos del admin
│   ├── js/
│   │   └── demowp-admin.js            # Scripts del admin
│   └── views/
│       ├── settings-page.php          # Vista de configuración
│       ├── active-demos.php           # Vista de demos activas
│       └── statistics.php             # Vista de estadísticas
├── includes/
│   ├── class-demowp-loader.php        # Cargador principal
│   ├── class-demowp-cloner.php        # Lógica de clonación
│   ├── class-demowp-filesystem.php    # Operaciones de archivos
│   ├── class-demowp-database.php      # Operaciones de BD
│   ├── class-demowp-demo-tracker.php  # Seguimiento de demos
│   ├── class-demowp-cleanup.php       # Limpieza automática
│   ├── class-demowp-restrictions.php  # Restricciones de seguridad
│   ├── class-demowp-autologin.php     # Login automático
│   ├── class-demowp-maintenance.php   # Modo mantenimiento
│   ├── class-demowp-license.php       # Gestión de licencias
│   ├── class-demowp-utils.php         # Utilidades
│   ├── class-demowp-ajax.php          # Handlers AJAX
│   └── mu-plugin/
│       └── demowp-restrictions-loader.php  # MU-Plugin para clones
├── public/
│   └── class-demowp-public.php        # Frontend y endpoint
└── docs/
    └── ...                            # Documentación
```

## Flujo de Datos

### Creación de una Demo

```
Usuario visita /demo
        │
        ▼
┌─────────────────┐
│  DemoWP_Public  │  Detecta endpoint y muestra formulario
└────────┬────────┘
         │ Submit
         ▼
┌─────────────────┐
│   DemoWP_Ajax   │  Procesa solicitud AJAX
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  DemoWP_Cloner  │  Orquesta el proceso de clonación
└────────┬────────┘
         │
    ┌────┴────┐
    ▼         ▼
┌────────┐ ┌──────────┐
│Database│ │Filesystem│  Clonan datos y archivos
└────┬───┘ └────┬─────┘
     │          │
     └────┬─────┘
          ▼
┌─────────────────┐
│  Demo_Tracker   │  Registra la demo en BD
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Autologin     │  Genera token de acceso
└────────┬────────┘
         │
         ▼
   Redirect a demo
```

### Acceso a una Demo

```
Usuario accede con token
        │
        ▼
┌─────────────────────────┐
│ MU-Plugin Autologin     │  Valida token y hace login
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│ MU-Plugin Restrictions  │  Aplica restricciones
└────────────┬────────────┘
             │
             ▼
    Usuario en wp-admin
```

### Limpieza de Demos

```
Action Scheduler trigger
        │
        ▼
┌─────────────────┐
│ DemoWP_Cleanup  │  Identifica demos expiradas
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  DemoWP_Cloner  │  delete_demo()
└────────┬────────┘
         │
    ┌────┴────┐
    ▼         ▼
┌────────┐ ┌──────────┐
│Database│ │Filesystem│  Eliminan datos
└────────┘ └──────────┘
```

## Componentes Principales

### DemoWP_Loader

Punto de entrada que decide qué componentes cargar según el modo:

```php
if ( DEMOWP_IS_CLONE ) {
    // Modo Clone: solo restricciones y autologin
    $this->init_clone_mode();
} else {
    // Modo Template: todo el plugin
    $this->init_template_mode();
}
```

### DemoWP_Cloner

Orquestador principal de la clonación:

- Genera IDs únicos para clones
- Coordina `DemoWP_Database` y `DemoWP_Filesystem`
- Crea usuarios temporales
- Genera tokens de autologin
- Programa la limpieza

### DemoWP_Database

Gestiona todas las operaciones de base de datos:

- Clona tablas con nuevo prefijo
- Actualiza referencias internas (siteurl, home, etc.)
- Elimina tablas de demos

### DemoWP_Filesystem

Gestiona operaciones de archivos:

- Copia directorios (wp-content, uploads)
- Excluye el plugin DemoWP de los clones
- Copia el MU-Plugin a los clones
- Genera wp-config.php para clones
- Crea archivo marcador `.demowp-clone`
- Elimina directorios de demos

### DemoWP_Demo_Tracker

Tabla de seguimiento de demos:

```sql
CREATE TABLE wp_demowp_demos (
    id              BIGINT UNSIGNED AUTO_INCREMENT,
    clone_id        VARCHAR(32) NOT NULL,
    db_prefix       VARCHAR(20) NOT NULL,
    clone_path      VARCHAR(255) NOT NULL,
    username        VARCHAR(60) NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    ip_address      VARCHAR(45),
    created_at      DATETIME NOT NULL,
    expires_at      DATETIME NOT NULL,
    status          VARCHAR(20) DEFAULT 'active',
    PRIMARY KEY (id),
    UNIQUE KEY clone_id (clone_id)
);
```

### MU-Plugin (demowp-restrictions-loader.php)

Plugin obligatorio que se instala en `wp-content/mu-plugins/` de cada clon:

- **No puede desactivarse**: Los MU-Plugins siempre se cargan
- **Autosuficiente**: No depende del plugin principal
- **Contiene**:
  - `DemoWP_Clone_Restrictions`: Aplica restricciones de seguridad
  - `DemoWP_Clone_Autologin`: Procesa tokens de login

## Sistema de Licencias

### Flujo de Activación

```
Usuario introduce licencia
        │
        ▼
┌─────────────────┐
│ DemoWP_License  │  activate_license()
└────────┬────────┘
         │
         ▼
┌─────────────────────────┐
│ API plugins.joseconti.com│
└────────────┬────────────┘
         │
         ▼
┌─────────────────┐
│ Guardar estado  │  license_status = 'valid'
└─────────────────┘
```

### Flujo de Actualizaciones

```
WordPress check updates
        │
        ▼
┌─────────────────┐
│ check_update()  │  ¿Licencia válida?
└────────┬────────┘
         │ Sí
         ▼
┌─────────────────────────┐
│ API plugins.joseconti.com│  ¿Nueva versión?
└────────────┬────────────┘
         │ Sí
         ▼
┌─────────────────┐
│ Añadir a        │  transient update_plugins
│ actualizaciones │
└─────────────────┘
```

## Seguridad

### Aislamiento de Demos

Cada demo está completamente aislada:

1. **Base de datos**: Tablas con prefijo único (`demo_abc123_`)
2. **Archivos**: Directorio propio (`/clone_id/`)
3. **Sesiones**: Cookies únicas por dominio/path

### Restricciones Implementadas

Las restricciones se aplican mediante:

1. **Filtro de capacidades**: `user_has_cap` bloquea `install_plugins`, etc.
2. **Bloqueo de páginas**: `admin_init` bloquea acceso a páginas específicas
3. **Bloqueo AJAX**: Intercepta acciones AJAX peligrosas
4. **Ocultación de menús**: `admin_menu` oculta opciones
5. **CSS**: Oculta elementos visuales restantes

### Tokens de Autologin

- Generados con `DemoWP_Utils::generate_random_string(64)`
- Almacenados como transients (5 minutos de vida)
- Un solo uso (se eliminan tras validar)
- Verifican que el clone_id coincide

## Hooks Disponibles

### Acciones

```php
// Antes de crear una demo
do_action( 'demowp_before_create_demo', $clone_id );

// Después de crear una demo
do_action( 'demowp_after_create_demo', $clone_id, $demo_data );

// Antes de eliminar una demo
do_action( 'demowp_before_delete_demo', $clone_id );

// Después de eliminar una demo
do_action( 'demowp_after_delete_demo', $clone_id );
```

### Filtros

```php
// Modificar datos del clon antes de crear
$clone_data = apply_filters( 'demowp_clone_data', $clone_data );

// Modificar tablas a clonar
$tables = apply_filters( 'demowp_tables_to_clone', $tables );

// Modificar directorios a excluir
$exclude = apply_filters( 'demowp_exclude_directories', $exclude );
```

## Rendimiento

### Optimizaciones

1. **Timeout corto en API**: 5 segundos para no bloquear si el servidor de licencias está caído
2. **Transients**: Caché de respuestas de API (24 horas)
3. **Action Scheduler**: Tareas pesadas en segundo plano
4. **Limpieza batch**: Elimina demos en lotes para evitar timeouts

### Consideraciones

- La clonación es intensiva en I/O - depende del tamaño del sitio
- Las demos grandes consumen espacio en disco
- Muchas demos simultáneas pueden afectar rendimiento de BD
