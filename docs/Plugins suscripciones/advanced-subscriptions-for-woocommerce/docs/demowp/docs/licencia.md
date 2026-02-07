# Sistema de Licencias de DemoWP

## Descripción General

DemoWP utiliza un sistema de licencias para:

- Verificar la compra legítima del plugin
- Habilitar actualizaciones automáticas
- Proporcionar soporte técnico

## Obtener una Licencia

1. Visita [plugins.joseconti.com](https://plugins.joseconti.com)
2. Selecciona el plan de DemoWP
3. Completa la compra
4. Recibirás tu clave de licencia por email

## Activar la Licencia

### Paso 1: Acceder a la Configuración

1. Ve al panel de administración de WordPress
2. Navega a **DemoWP > Settings**
3. Localiza la sección "License" al principio

### Paso 2: Introducir la Clave

1. Introduce tu clave de licencia en el campo "License Key"
2. Haz clic en **Guardar cambios**

### Paso 3: Verificar Activación

Tras guardar, verás uno de estos estados:

| Estado | Significado |
|--------|-------------|
| ✅ **License is active** | Licencia válida y activada |
| ❌ **License is not valid** | La licencia no es válida o ha expirado |

## Actualizaciones Automáticas

Con una licencia activa:

1. WordPress detectará nuevas versiones automáticamente
2. Aparecerán en **Dashboard > Actualizaciones**
3. Podrás actualizar con un clic

Sin licencia activa:

- No recibirás notificaciones de actualizaciones
- Deberás actualizar manualmente descargando desde tu cuenta

## Gestión de la Licencia

### Mover Licencia a Otro Sitio

Si necesitas usar la licencia en otro sitio:

1. Desactiva el plugin en el sitio actual
2. Activa la licencia en el nuevo sitio

Nota: Cada licencia tiene un límite de activaciones simultáneas según el plan adquirido.

### Renovar Licencia

Antes de que expire tu licencia:

1. Accede a tu cuenta en [plugins.joseconti.com](https://plugins.joseconti.com)
2. Renueva tu suscripción
3. La licencia se actualizará automáticamente

### Desactivar Licencia

Para desactivar la licencia:

1. Borra el contenido del campo "License Key"
2. Guarda los cambios

## Solución de Problemas

### "License is not valid"

Posibles causas:
- La clave está mal escrita (copia y pega para evitar errores)
- La licencia ha expirado
- Se ha alcanzado el límite de activaciones
- Problemas de conexión con el servidor de licencias

Solución:
1. Verifica que la clave es correcta
2. Comprueba el estado de tu licencia en tu cuenta
3. Contacta con soporte si el problema persiste

### No Aparecen Actualizaciones

1. Ve a **Dashboard > Actualizaciones**
2. Haz clic en "Comprobar de nuevo"
3. Verifica que la licencia está activa
4. Espera unos minutos y vuelve a comprobar

### Error de Conexión

Si el servidor de licencias no responde:
- El plugin seguirá funcionando normalmente
- Las actualizaciones automáticas no estarán disponibles temporalmente
- No ralentizará tu sitio (timeout de 5 segundos)

## Información Técnica

### Datos Almacenados

| Option | Descripción |
|--------|-------------|
| `demowp_lic_license_key` | Tu clave de licencia |
| `demowp_lic_license_status` | Estado: 'valid' o vacío |
| `demowp_lic_license_salt` | Token de verificación |

### API de Licencias

El plugin se comunica con:
- **URL**: `https://plugins.joseconti.com`
- **Endpoints**:
  - `/wc-api/lm-license-api/` (activación)
  - `/wc-api/upgrade-api/` (actualizaciones)

### Datos Enviados

Al activar o verificar la licencia se envía:
- Clave de licencia
- URL del sitio
- Versión del plugin
- Nombre del producto

No se envía información personal ni contenido del sitio.

## Política de Licencias

### Uso Permitido

- Una licencia por sitio de producción
- Uso en sitios de desarrollo/staging (sin límite)
- Transferencia a otro sitio (desactivando el anterior)

### Uso No Permitido

- Compartir la clave de licencia
- Redistribuir el plugin
- Usar en sitios de terceros sin autorización

## Soporte

Para problemas con licencias:

- **Email**: Usa el formulario de contacto en tu cuenta
- **Web**: [plugins.joseconti.com](https://plugins.joseconti.com)

Incluye en tu solicitud:
- Tu clave de licencia (o los últimos 4 caracteres)
- URL del sitio
- Descripción del problema
- Capturas de pantalla si es relevante
