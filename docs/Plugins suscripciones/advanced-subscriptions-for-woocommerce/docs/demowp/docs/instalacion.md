# Instalación de DemoWP

## Requisitos Previos

Antes de instalar DemoWP, asegúrate de que tu servidor cumple los siguientes requisitos:

### Servidor
- PHP 7.4 o superior
- MySQL 5.7+ o MariaDB 10.3+
- Extensiones PHP: `mysqli`, `json`, `mbstring`
- Memoria PHP: mínimo 128MB (recomendado 256MB)
- `max_execution_time`: mínimo 120 segundos

### WordPress
- WordPress 6.0 o superior
- Permalinks configurados (cualquier opción excepto "Plain")
- Usuario con rol de Administrador

### Permisos de Archivos
- Escritura en `wp-content/`
- Escritura en `wp-content/plugins/`
- Escritura en `wp-content/mu-plugins/`
- Capacidad de crear directorios en la raíz del sitio

### Base de Datos
- Permisos para crear tablas con prefijos personalizados
- O acceso para crear bases de datos adicionales (opcional)

## Instalación

### Método 1: Subida Manual

1. Descarga el archivo ZIP del plugin desde tu cuenta en [plugins.joseconti.com](https://plugins.joseconti.com)

2. Descomprime el archivo en tu ordenador

3. Sube la carpeta `demowp` a `/wp-content/plugins/` mediante FTP o el administrador de archivos de tu hosting

4. Accede a **Plugins > Plugins instalados** en el panel de WordPress

5. Busca "DemoWP" y haz clic en **Activar**

### Método 2: Subida desde WordPress

1. Descarga el archivo ZIP del plugin

2. Ve a **Plugins > Añadir nuevo** en el panel de WordPress

3. Haz clic en **Subir plugin**

4. Selecciona el archivo ZIP y haz clic en **Instalar ahora**

5. Una vez instalado, haz clic en **Activar plugin**

## Configuración Inicial

Tras la activación, el plugin:

1. Crea la tabla de seguimiento de demos (`wp_demowp_demos`)
2. Establece opciones por defecto:
   - Duración de demos: 1 hora
   - Endpoint: `/demo`
   - Máximo demos por IP: 3
3. Registra las reglas de reescritura

### Primer Paso: Activar Licencia

1. Ve a **DemoWP > Settings**
2. Introduce tu clave de licencia en el campo "License Key"
3. Guarda los cambios
4. Verifica que aparece "License is active"

### Segundo Paso: Configurar Endpoint

1. En **DemoWP > Settings**, configura la URL del endpoint
2. Por defecto es `demo`, lo que crea la URL `https://tusitio.com/demo`
3. Puedes cambiarlo a cualquier slug válido

### Tercer Paso: Verificar Permisos

Asegúrate de que el plugin puede:
- Crear directorios en la raíz del sitio
- Escribir en `wp-content/mu-plugins/`
- Crear tablas en la base de datos

## Verificación de la Instalación

Para verificar que todo funciona correctamente:

1. Ve a **DemoWP > Settings** y comprueba que no hay errores
2. Visita la URL del endpoint (ej: `https://tusitio.com/demo`)
3. Debería aparecer la página de creación de demo
4. Crea una demo de prueba
5. Verifica en **DemoWP > Active Demos** que aparece la nueva demo
6. Elimina la demo de prueba

## Solución de Problemas

### Error 404 en el Endpoint

1. Ve a **Ajustes > Enlaces permanentes**
2. Sin cambiar nada, haz clic en **Guardar cambios**
3. Esto regenera las reglas de reescritura

### Error al Crear Demo

Verifica:
- Permisos de escritura en el servidor
- Espacio en disco disponible
- Límites de PHP (`memory_limit`, `max_execution_time`)
- Logs de error en `wp-content/debug.log`

### Demo No Se Elimina Automáticamente

El plugin usa Action Scheduler para la limpieza. Verifica:
- Que el cron de WordPress funciona correctamente
- Que Action Scheduler está activo (incluido con el plugin)

## Desinstalación

Para desinstalar completamente el plugin:

1. Ve a **DemoWP > Active Demos**
2. Elimina todas las demos activas
3. Desactiva el plugin en **Plugins**
4. Elimina el plugin

El archivo `uninstall.php` se encarga de:
- Eliminar todas las tablas creadas
- Eliminar las opciones del plugin
- Limpiar los transients
- Eliminar demos huérfanas

## Actualización

Con una licencia activa, las actualizaciones aparecen automáticamente en **Dashboard > Actualizaciones**.

Para actualizar manualmente:
1. Descarga la nueva versión
2. Desactiva el plugin actual
3. Elimina la carpeta del plugin (las demos no se verán afectadas)
4. Instala la nueva versión
5. Activa el plugin
