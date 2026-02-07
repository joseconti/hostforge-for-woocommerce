# Configuración de DemoWP

## Acceso a la Configuración

La configuración del plugin se encuentra en **DemoWP > Settings** en el menú de administración de WordPress.

## Secciones de Configuración

### 1. Licencia

El primer bloque de configuración es la licencia del plugin.

| Campo | Descripción |
|-------|-------------|
| **License Key** | Tu clave de licencia para recibir actualizaciones automáticas |

**Estados de la licencia:**
- **License is active** (verde): Licencia válida, recibirás actualizaciones
- **License is not valid** (rojo): Licencia inválida o expirada
- Sin mensaje: No se ha introducido ninguna licencia

### 2. Demo Endpoint

Configuración de la URL donde los usuarios pueden crear demos.

| Campo | Descripción | Valor por defecto |
|-------|-------------|-------------------|
| **Endpoint URL** | Slug de la URL para crear demos | `demo` |

**Ejemplo:** Si tu sitio es `https://ejemplo.com` y el endpoint es `demo`, la URL será `https://ejemplo.com/demo`

**Recomendaciones:**
- Usa slugs cortos y descriptivos
- Evita caracteres especiales
- Si cambias el endpoint, WordPress regenerará las reglas de reescritura automáticamente

### 3. Demo Settings

Configuración del comportamiento de las demos.

| Campo | Descripción | Opciones |
|-------|-------------|----------|
| **Demo Lifetime** | Tiempo de vida de cada demo | 30 min, 1h, 2h, 4h, 8h, 24h |
| **Max Demos per IP** | Máximo de demos simultáneas por IP | 1-10 |

**Consideraciones:**

- **Demo Lifetime**: Tras este tiempo, la demo se marca para eliminación. La limpieza real ocurre mediante Action Scheduler, generalmente en pocos minutos.

- **Max Demos per IP**: Evita abusos limitando las demos por dirección IP. Si un usuario alcanza el límite, deberá esperar a que expire una demo existente.

### 4. Customization

Personalización de mensajes mostrados a los usuarios.

| Campo | Descripción |
|-------|-------------|
| **Welcome Message** | Mensaje personalizado en el aviso de bienvenida del admin de demos |

El mensaje de bienvenida aparece en el panel de administración de cada demo junto con:
- Información sobre el modo demo
- Tiempo restante
- Aviso sobre restricciones

### 5. Maintenance Mode

Control del modo mantenimiento para el sitio principal.

| Campo | Descripción |
|-------|-------------|
| **Enable Maintenance** | Activa/desactiva el modo mantenimiento |
| **Maintenance Message** | Mensaje personalizado para visitantes |

**Comportamiento del modo mantenimiento:**
- Los **administradores** pueden navegar normalmente
- Los **visitantes** ven una página de mantenimiento (HTTP 503)
- El **endpoint de demos** sigue funcionando
- Las **demos existentes** siguen accesibles

## Configuración Recomendada

### Para Demos de Plugins/Temas

```
Endpoint: demo
Lifetime: 1-2 horas
Max per IP: 2-3
```

### Para Ferias/Eventos

```
Endpoint: prueba
Lifetime: 30 minutos
Max per IP: 1
```

### Para Formación/Cursos

```
Endpoint: practica
Lifetime: 4-8 horas
Max per IP: 1
```

## Opciones Avanzadas (Base de Datos)

Estas opciones se almacenan en `wp_options` y pueden modificarse directamente si es necesario:

| Option Name | Descripción | Tipo |
|-------------|-------------|------|
| `demowp_endpoint_slug` | Slug del endpoint | string |
| `demowp_demo_lifetime` | Duración en segundos | integer |
| `demowp_max_concurrent_demos` | Máximo por IP | integer |
| `demowp_welcome_message` | Mensaje de bienvenida | string |
| `demowp_maintenance_mode` | Modo mantenimiento activo | boolean |
| `demowp_maintenance_message` | Mensaje de mantenimiento | string |
| `demowp_lic_license_key` | Clave de licencia | string |
| `demowp_lic_license_status` | Estado de licencia | string |

## Restricciones en Demos

Las demos tienen restricciones de seguridad que **no son configurables**:

### Bloqueado
- Instalar plugins
- Instalar temas
- Eliminar plugins
- Eliminar temas
- Editar archivos de plugins/temas
- Actualizar WordPress core
- Actualizar plugins/temas

### Permitido
- Activar/desactivar plugins existentes
- Cambiar entre temas instalados
- Modificar configuraciones
- Crear/editar contenido
- Gestionar usuarios (dentro de la demo)

## Verificar Configuración

Para verificar que la configuración es correcta:

1. **Test del endpoint**: Visita la URL del endpoint y verifica que carga
2. **Test de creación**: Crea una demo de prueba
3. **Test de restricciones**: En la demo, intenta instalar un plugin (debe bloquearse)
4. **Test de limpieza**: Espera a que expire o elimina manualmente

## Solución de Problemas de Configuración

### El endpoint devuelve 404

1. Ve a **Ajustes > Enlaces permanentes**
2. Guarda sin cambiar nada (regenera reglas)
3. Verifica que no hay conflicto con otros plugins

### Las demos no expiran

1. Verifica que Action Scheduler funciona: **Herramientas > Scheduled Actions**
2. Comprueba que el cron de WordPress está activo
3. Revisa los logs por errores

### Modo mantenimiento no funciona

1. Verifica que guardaste los cambios
2. Prueba en una ventana de incógnito (sin sesión de admin)
3. Limpia cachés si usas plugins de caché
