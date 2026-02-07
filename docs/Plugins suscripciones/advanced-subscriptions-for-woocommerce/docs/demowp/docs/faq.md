# Preguntas Frecuentes (FAQ)

## General

### ¿Qué es DemoWP?

DemoWP es un plugin de WordPress que permite crear copias temporales de tu sitio (demos) para que los usuarios puedan probar plugins, temas o funcionalidades sin afectar al sitio principal.

### ¿Para qué sirve?

- **Desarrolladores de plugins/temas**: Ofrecer demos en vivo a potenciales compradores
- **Agencias**: Mostrar sitios de demostración a clientes
- **Formadores**: Crear entornos de práctica para alumnos
- **Soporte técnico**: Reproducir problemas en entornos aislados

### ¿Es seguro?

Sí. Cada demo está completamente aislada:
- Base de datos separada (tablas con prefijo único)
- Archivos en directorio propio
- Restricciones que impiden acciones peligrosas
- Limpieza automática

---

## Instalación y Configuración

### ¿Qué requisitos tiene?

- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+
- WordPress 6.0+
- Permisos de escritura en wp-content

### El endpoint devuelve error 404

1. Ve a **Ajustes > Enlaces permanentes**
2. Sin cambiar nada, guarda los cambios
3. Esto regenera las reglas de reescritura

### ¿Puedo cambiar la URL del endpoint?

Sí. En **DemoWP > Settings**, modifica el campo "Endpoint URL" y guarda. Las reglas se actualizan automáticamente.

---

## Demos

### ¿Cuánto tarda en crearse una demo?

Depende del tamaño del sitio (base de datos y archivos). Normalmente:
- Sitio pequeño: 5-15 segundos
- Sitio mediano: 15-30 segundos
- Sitio grande: 30-60 segundos

### ¿Cuántas demos puedo tener activas?

No hay límite global. El límite es por IP del usuario (configurable, por defecto 3).

### ¿Los usuarios pueden instalar plugins en las demos?

No. Las demos tienen restricciones que bloquean:
- Instalar/eliminar plugins
- Instalar/eliminar temas
- Editar archivos
- Actualizar WordPress

Sí pueden:
- Activar/desactivar plugins existentes
- Cambiar entre temas instalados
- Modificar configuraciones

### ¿Qué pasa cuando expira una demo?

1. La demo se marca como expirada
2. Action Scheduler programa su eliminación
3. Se eliminan las tablas de BD y archivos
4. El registro se elimina del tracker

### ¿Los usuarios pueden guardar su trabajo?

No de forma persistente. Las demos son temporales. Si necesitan exportar contenido, deben hacerlo antes de que expire.

### ¿Puedo eliminar una demo manualmente?

Sí. En **DemoWP > Active Demos**, cada demo tiene un botón "Delete". También hay un botón "Delete All Demos" para limpiar todo.

---

## Rendimiento

### ¿Afecta al rendimiento de mi sitio?

- **Durante la creación**: Hay carga de CPU/IO durante unos segundos
- **Con demos activas**: Impacto mínimo (tablas y archivos separados)
- **Servidor de licencias caído**: No afecta (timeout de 5 segundos)

### ¿Cuánto espacio ocupa cada demo?

Aproximadamente el mismo que tu sitio actual:
- Tamaño de la BD × 1
- Tamaño de wp-content × 1 (aproximado)

El plugin excluye directorios como cache para reducir tamaño.

### ¿Puedo limitar el uso de recursos?

- **Límite por IP**: Reduce demos simultáneas por usuario
- **Duración corta**: Las demos se limpian antes
- **Cron más frecuente**: Limpieza más rápida

---

## Licencia

### ¿Necesito licencia para usar el plugin?

El plugin funciona sin licencia, pero no recibirás actualizaciones automáticas.

### ¿Cuántos sitios cubre una licencia?

Depende del plan adquirido. Consulta las opciones en [plugins.joseconti.com](https://plugins.joseconti.com).

### ¿Puedo mover la licencia a otro sitio?

Sí. Desactiva el plugin en el sitio actual y activa la licencia en el nuevo.

### Mi licencia dice "not valid"

Posibles causas:
- Error tipográfico en la clave
- Licencia expirada
- Límite de activaciones alcanzado

Solución: Verifica en tu cuenta o contacta soporte.

---

## Modo Mantenimiento

### ¿Qué es el modo mantenimiento?

Permite mostrar una página de mantenimiento a los visitantes mientras tú (como admin) puedes navegar normalmente.

### ¿Afecta a las demos?

No. Las demos existentes siguen accesibles y se pueden crear nuevas.

### ¿Qué ven los visitantes?

Una página simple con:
- Título: "Site Under Maintenance"
- Tu mensaje personalizado (si lo configuraste)
- HTTP Status 503

---

## Técnico

### ¿Cómo funciona el aislamiento de BD?

Cada demo usa tablas con prefijo único:
- Sitio principal: `wp_posts`, `wp_options`...
- Demo: `demo_a1b2c3_posts`, `demo_a1b2c3_options`...

### ¿Qué es el MU-Plugin?

Un "Must Use Plugin" que se carga automáticamente. DemoWP instala uno en cada demo para:
- Aplicar restricciones de seguridad
- Manejar el login automático
- No puede ser desactivado por el usuario

### ¿Por qué el plugin DemoWP no está en las demos?

No es necesario y evita confusiones. Las demos solo necesitan el MU-Plugin para funcionar.

### ¿Cómo se detecta si es una demo?

El plugin crea un archivo `.demowp-clone` en la raíz de cada demo. Si existe, WordPress carga solo los componentes necesarios.

---

## Solución de Problemas

### Las demos no se eliminan automáticamente

1. Verifica que el cron de WordPress funciona
2. Comprueba Action Scheduler: **Herramientas > Scheduled Actions**
3. Revisa los logs en `wp-content/debug.log`

### Error al crear demo

Comprueba:
- Permisos de escritura en el servidor
- Espacio en disco suficiente
- Límites de PHP (`memory_limit`, `max_execution_time`)
- El log de errores de WordPress

### Los usuarios ven restricciones que no deberían ver

Asegúrate de que:
- El MU-Plugin está instalado en la demo
- El archivo `.demowp-clone` existe
- La constante `DEMOWP_IS_CLONE` es `true`

### Problemas de login automático

El token de autologin:
- Expira en 5 minutos
- Es de un solo uso
- Requiere que el clone_id coincida

Si falla, el usuario puede usar las credenciales mostradas.

---

## Contacto y Soporte

### ¿Dónde reporto bugs?

- GitHub: [Issues](https://github.com/joseconti/demowp/issues)
- Web: [plugins.joseconti.com](https://plugins.joseconti.com)

### ¿Hay documentación para desarrolladores?

Sí. Consulta [arquitectura.md](arquitectura.md) para detalles técnicos, hooks disponibles y estructura del código.
