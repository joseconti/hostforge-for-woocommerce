# DemoWP - Documentación

Plugin de WordPress para crear instalaciones de demostración temporales (sandbox) que permiten a los usuarios probar plugins y temas de forma segura.

## Índice

1. [Descripción General](#descripción-general)
2. [Requisitos](#requisitos)
3. [Instalación](instalacion.md)
4. [Configuración](configuracion.md)
5. [Uso](#uso)
6. [Arquitectura](arquitectura.md)
7. [Licencia](licencia.md)
8. [FAQ](faq.md)

## Descripción General

DemoWP permite a los desarrolladores y vendedores de plugins/temas ofrecer demostraciones en vivo de sus productos. Cuando un usuario visita la URL del endpoint de demo, se crea automáticamente una copia completa del sitio WordPress (clon) con:

- Base de datos independiente (usando prefijos de tabla únicos)
- Directorio de archivos separado
- Usuario administrador temporal
- Login automático
- Restricciones de seguridad (no se pueden instalar/eliminar plugins o temas)
- Limpieza automática tras expiración

### Características Principales

- **Clonación completa**: Copia la base de datos y archivos del sitio plantilla
- **Aislamiento**: Cada demo es independiente y no afecta al sitio principal
- **Seguridad**: Restricciones para evitar abusos (no instalación de plugins/temas)
- **Auto-limpieza**: Las demos se eliminan automáticamente tras expirar
- **Límite por IP**: Control de demos simultáneas por dirección IP
- **Modo mantenimiento**: Posibilidad de poner el sitio principal en mantenimiento
- **Licencias**: Sistema de actualizaciones automáticas con licencia

## Requisitos

### Requisitos del Servidor

- PHP 7.4 o superior
- MySQL 5.7 o superior / MariaDB 10.3 o superior
- WordPress 6.0 o superior
- Permisos de escritura en `wp-content`
- Capacidad de crear bases de datos o tablas con prefijos personalizados

### Requisitos de WordPress

- Acceso de administrador
- Permalinks habilitados (no usar "Plain")
- Action Scheduler (incluido con el plugin)

## Uso

### Para Administradores

1. **Configurar el plugin**: Accede a DemoWP > Settings en el panel de administración
2. **Establecer duración**: Define cuánto tiempo durarán las demos (30 min - 24 horas)
3. **Configurar límites**: Establece el máximo de demos por IP
4. **Personalizar mensajes**: Añade mensajes de bienvenida personalizados

### Para Usuarios

1. Visitar la URL del endpoint (ej: `https://tusitio.com/demo`)
2. Completar el captcha (si está habilitado)
3. Esperar la creación del clon (unos segundos)
4. Ser redirigido automáticamente al panel de administración
5. Explorar y probar el sitio durante el tiempo asignado

### Panel de Administración

El plugin añade un menú "DemoWP" con las siguientes secciones:

- **Settings**: Configuración general del plugin
- **Active Demos**: Lista de demos activas con opciones para eliminarlas
- **Statistics**: Estadísticas de uso

## Soporte

Para soporte técnico o reportar problemas:

- GitHub: [Issues](https://github.com/developer/demowp/issues)
- Web: [plugins.joseconti.com](https://plugins.joseconti.com)

## Licencia

DemoWP es software premium. Se requiere una licencia válida para recibir actualizaciones automáticas.

- [Comprar licencia](https://plugins.joseconti.com)
- [Activar licencia](licencia.md)
