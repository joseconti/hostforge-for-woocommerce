# HOSTFORGE FOR WOOCOMMERCE — Guia de Testing Paso a Paso

**Version 1.0.0 | Febrero 2026 | Documento Interno**

---

## TABLA DE CONTENIDOS

- [Requisitos Previos](#requisitos-previos)
- [1. Instalacion y Activacion](#1-instalacion-y-activacion)
- [2. Core y Fundamentos (Fase 1)](#2-core-y-fundamentos-fase-1)
- [3. Tipos de Producto WooCommerce (Fase 2)](#3-tipos-de-producto-woocommerce-fase-2)
- [4. Modulo Server Manager (Fase 3)](#4-modulo-server-manager-fase-3)
- [5. Modulo Auto Provisioning (Fase 4)](#5-modulo-auto-provisioning-fase-4)
- [6. Modulo Support Desk (Fase 5)](#6-modulo-support-desk-fase-5)
- [7. Modulo Domain Manager (Fase 6)](#7-modulo-domain-manager-fase-6)
- [8. Modulo Security (Fase 7)](#8-modulo-security-fase-7)
- [9. Modulo Notifications (Fase 7)](#9-modulo-notifications-fase-7)
- [10. Modulo Reports (Fase 7)](#10-modulo-reports-fase-7)
- [11. REST API](#11-rest-api)
- [12. Frontend — Mi Cuenta](#12-frontend--mi-cuenta)
- [13. Desinstalacion](#13-desinstalacion)
- [14. Compatibilidad](#14-compatibilidad)
- [15. Seguridad](#15-seguridad)
- [Checklist Rapido](#checklist-rapido)

---

## Requisitos Previos

### Entorno de Testing

- WordPress 6.0+ (recomendado 6.5+)
- WooCommerce 8.0+ (recomendado 9.0+)
- PHP 8.0+
- MySQL 5.7+ o MariaDB 10.3+
- Al menos un plugin de suscripciones instalado (WooCommerce Subscriptions, YITH, Advanced Subscriptions o SUMO)
- Action Scheduler (incluido con WooCommerce)
- Servidor cPanel/WHM o Plesk para pruebas reales (o usar credenciales de sandbox)
- Cuenta Namecheap con acceso a sandbox API (para testing de dominios)

### Herramientas Recomendadas

- **Query Monitor** — Para verificar queries SQL, hooks y rendimiento
- **WP Mail Logging** — Para verificar envio de emails
- **Postman o similar** — Para testing de REST API
- **WP-CLI** — Para operaciones de linea de comandos
- **Debug Log** — `WP_DEBUG` y `WP_DEBUG_LOG` activados en `wp-config.php`

### Configuracion de Debug

Anadir en `wp-config.php`:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG', true );
```

---

## 1. Instalacion y Activacion

### 1.1 Instalacion Limpia

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Subir el plugin a `wp-content/plugins/` | Carpeta `hostforge-for-woocommerce/` presente | [ ] |
| 2 | Ir a **Plugins > Plugins instalados** | El plugin aparece en la lista con nombre, version y descripcion | [ ] |
| 3 | Activar el plugin | Activacion sin errores, redireccion al dashboard | [ ] |
| 4 | Verificar que no hay errores PHP en `debug.log` | Log limpio | [ ] |
| 5 | Verificar que el menu **HostForge** aparece en el admin | Menu principal con submenus | [ ] |

### 1.2 Verificacion de Dependencias

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Desactivar WooCommerce y activar HostForge | Aviso de dependencia, plugin no carga funcionalidad | [ ] |
| 2 | Reactivar WooCommerce | Plugin funciona correctamente | [ ] |
| 3 | Probar con PHP < 8.0 (si es posible) | Aviso de version minima | [ ] |
| 4 | Probar con WordPress < 6.0 (si es posible) | Aviso de version minima | [ ] |

### 1.3 Verificacion de Tablas de Base de Datos

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Ejecutar `SHOW TABLES LIKE '%hf_%'` en phpMyAdmin | Se crean las tablas: `wp_hf_logs`, `wp_hf_activity_log`, `wp_hf_provisioning_queue`, `wp_hf_dns_records`, `wp_hf_tld_pricing`, `wp_hf_domain_queue`, `wp_hf_login_attempts`, `wp_hf_ip_blocks` | [ ] |
| 2 | Verificar estructura de cada tabla | Columnas correctas segun esquema | [ ] |
| 3 | Verificar que los indices existen | Indices creados en columnas clave | [ ] |

### 1.4 Verificacion de Capabilities

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Verificar que el rol **Administrator** tiene las capabilities | `manage_hostforge`, `manage_hostforge_servers`, `manage_hostforge_services`, `manage_hostforge_tickets`, `manage_hostforge_domains`, `manage_hostforge_settings`, `view_hostforge_reports` | [ ] |
| 2 | Crear un usuario **Editor** e intentar acceder a HostForge | Acceso denegado a todas las pantallas | [ ] |
| 3 | Crear un usuario **Shop Manager** y verificar acceso | Solo acceso si tiene capabilities asignadas | [ ] |

---

## 2. Core y Fundamentos (Fase 1)

### 2.1 Dashboard de HostForge

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Ir a **HostForge > Dashboard** | Pagina de dashboard carga sin errores | [ ] |
| 2 | Verificar cards de resumen | Muestran contadores de servicios, tickets, dominios, servidores | [ ] |
| 3 | Verificar widget de estado del sistema | Muestra version PHP, WP, WC, estado HPOS | [ ] |
| 4 | Verificar que los CSS/JS solo cargan en paginas de HostForge | Inspeccionar red del navegador en otras paginas admin | [ ] |

### 2.2 Configuracion General

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Ir a **HostForge > Settings** | Pagina de settings carga correctamente | [ ] |
| 2 | Modificar cada campo y guardar | Valores se guardan y persisten al recargar | [ ] |
| 3 | Verificar nonce en el formulario | Inspeccionar HTML: campo `_wpnonce` presente | [ ] |
| 4 | Verificar sanitizacion | Introducir `<script>alert('xss')</script>` en campos de texto — debe ser limpiado | [ ] |

### 2.3 Gestion de Modulos

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Ir a **HostForge > Settings > Modules** | Lista de modulos disponibles | [ ] |
| 2 | Desactivar el modulo **Server Manager** | El modulo se desactiva, su menu desaparece | [ ] |
| 3 | Verificar que las clases del modulo NO se cargan | `class_exists('HostForge\Modules\ServerManager\...')` retorna false | [ ] |
| 4 | Verificar que los endpoints REST del modulo NO se registran | GET `/wp-json/hostforge/v1/servers` retorna 404 | [ ] |
| 5 | Verificar que los Action Scheduler del modulo NO se programan | No hay tareas `hostforge_server_health_check` en el scheduler | [ ] |
| 6 | Reactivar el modulo | Todo vuelve a funcionar | [ ] |
| 7 | Desactivar **Auto Provisioning** que depende de Server Manager | Debe mostrar aviso de dependencia | [ ] |

### 2.4 Sistema de Logs

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Ir a **HostForge > Logs** | Visor de logs carga sin errores | [ ] |
| 2 | Realizar una accion que genere log (ej. test de conexion a servidor) | La entrada aparece en el visor | [ ] |
| 3 | Filtrar por modulo | Solo muestra logs del modulo seleccionado | [ ] |
| 4 | Filtrar por nivel (info, warning, error) | Filtrado funciona correctamente | [ ] |
| 5 | Verificar paginacion | Si hay >20 entradas, la paginacion funciona | [ ] |

### 2.5 Cifrado (HF_Encryption)

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Guardar credenciales de un servidor (API key) | El valor en la BD esta cifrado (no texto plano) | [ ] |
| 2 | Leer las credenciales desde la interfaz | Se muestran descifradas correctamente | [ ] |
| 3 | Copiar el valor cifrado de la BD e intentar usarlo directamente | No es utilizable sin descifrar | [ ] |

---

## 3. Tipos de Producto WooCommerce (Fase 2)

### 3.1 Registro de Tipos de Producto

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Ir a **Productos > Anadir nuevo** | Dropdown de tipo de producto muestra los 7 tipos de HostForge | [ ] |
| 2 | Verificar tipos: Shared Hosting, Reseller Hosting, VPS Server, Dedicated Server, Domain, SSL Certificate, Software License | Todos presentes en el selector | [ ] |

### 3.2 Producto: Shared Hosting

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Crear producto tipo **Shared Hosting** | Formulario carga sin errores | [ ] |
| 2 | Verificar tab **Hosting** en datos del producto | Tab visible con campos: Server Group, Package Name, Disk Space, Bandwidth, Email Accounts, Databases, Addon Domains, Subdomains | [ ] |
| 3 | Rellenar todos los campos y guardar | Datos se guardan correctamente | [ ] |
| 4 | Recargar el producto | Todos los valores persisten | [ ] |
| 5 | Verificar que el precio y SKU funcionan | Producto visible en el catalogo con precio | [ ] |
| 6 | Anadir al carrito desde la tienda | Se agrega sin errores | [ ] |

### 3.3 Producto: Reseller Hosting

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Crear producto tipo **Reseller Hosting** | Hereda campos de Shared + campos adicionales de reseller | [ ] |
| 2 | Verificar campos adicionales | Max Accounts, Overselling options | [ ] |
| 3 | Guardar y verificar persistencia | Datos correctos al recargar | [ ] |

### 3.4 Producto: VPS Server

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Crear producto tipo **VPS Server** | Tab VPS visible | [ ] |
| 2 | Verificar campos: OS Templates, CPU Cores, RAM (MB), Disk Space (GB), Bandwidth (GB), IP Addresses | Todos los campos presentes | [ ] |
| 3 | Guardar y verificar | Datos persisten correctamente | [ ] |

### 3.5 Producto: Dedicated Server

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Crear producto tipo **Dedicated Server** | Hereda campos de VPS + campos adicionales | [ ] |
| 2 | Verificar campos adicionales | Hardware specs, setup fee, etc. | [ ] |
| 3 | Guardar y verificar | Datos persisten correctamente | [ ] |

### 3.6 Producto: Domain

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Crear producto tipo **Domain** | Tab Domain visible | [ ] |
| 2 | Verificar campos: Supported TLDs, Registration Period | Campos presentes | [ ] |
| 3 | Guardar y verificar | Datos persisten correctamente | [ ] |

### 3.7 Producto: SSL Certificate

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Crear producto tipo **SSL Certificate** | Tab SSL visible | [ ] |
| 2 | Verificar campos: Validation Level (DV/OV/EV), Certificate Type, Duration | Campos presentes | [ ] |
| 3 | Guardar y verificar | Datos persisten correctamente | [ ] |

### 3.8 Producto: Software License

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Crear producto tipo **Software License** | Tab License visible | [ ] |
| 2 | Verificar campos: License Type, Max Activations, Duration | Campos presentes | [ ] |
| 3 | Guardar y verificar | Datos persisten correctamente | [ ] |

### 3.9 Add-ons de Producto

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Ir a la tab **Add-ons** en un producto hosting | Interfaz de add-ons visible | [ ] |
| 2 | Crear un add-on (ej. "Extra IP Address" con precio $5) | Add-on se guarda | [ ] |
| 3 | Ver el producto en el frontend | El add-on aparece como opcion seleccionable | [ ] |
| 4 | Agregar al carrito con add-on seleccionado | Precio actualizado incluye el add-on | [ ] |
| 5 | Completar una orden con add-on | Add-on registrado en el meta del pedido | [ ] |

### 3.10 Campos de Checkout

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Anadir un producto hosting al carrito | Campos adicionales de checkout aparecen | [ ] |
| 2 | Campo de dominio visible para hosting | Input para introducir dominio | [ ] |
| 3 | Dejar dominio vacio e intentar comprar | Error de validacion: dominio requerido | [ ] |
| 4 | Introducir dominio invalido (ej. "not a domain") | Error de validacion: formato invalido | [ ] |
| 5 | Introducir dominio valido y completar compra | Dominio guardado en meta del pedido | [ ] |
| 6 | Verificar campos para producto Domain | Opciones register/transfer/own visibles | [ ] |
| 7 | Seleccionar "transfer" y verificar campo EPP | Campo EPP code aparece | [ ] |

### 3.11 Order Meta Handler (HPOS)

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Completar una compra con producto hosting | Meta del pedido guardado via HPOS | [ ] |
| 2 | Ir al pedido en el admin | Meta de HostForge visible en la seccion de detalles | [ ] |
| 3 | Verificar con HPOS activado | Datos se leen correctamente con `$order->get_meta()` | [ ] |
| 4 | Verificar con HPOS desactivado (legacy) | Datos se leen correctamente con post meta | [ ] |

### 3.12 Adaptadores de Suscripcion

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Con WooCommerce Subscriptions activo, verificar deteccion | `HF_Subscription_Factory` retorna `HF_WCS_Adapter` | [ ] |
| 2 | Con YITH Subscription activo, verificar deteccion | Retorna `HF_YITH_Adapter` | [ ] |
| 3 | Con Advanced Subscriptions activo, verificar deteccion | Retorna `HF_Advanced_Subs_Adapter` | [ ] |
| 4 | Con SUMO Subscriptions activo, verificar deteccion | Retorna `HF_SUMO_Adapter` | [ ] |
| 5 | Sin ningun plugin de suscripcion | Factory retorna null, no hay error fatal | [ ] |
| 6 | Crear un producto suscripcion hosting y completar compra | Suscripcion creada correctamente | [ ] |

---

## 4. Modulo Server Manager (Fase 3)

### 4.1 Lista de Servidores

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Ir a **HostForge > Servers** | Lista de servidores (vacia inicialmente) | [ ] |
| 2 | Verificar paginacion | Funciona con >20 servidores | [ ] |
| 3 | Filtrar por grupo de servidores | Solo muestra servidores del grupo | [ ] |
| 4 | Filtrar por estado (active, inactive) | Filtro funciona | [ ] |
| 5 | Buscar por nombre | Busqueda funciona | [ ] |

### 4.2 Anadir Servidor cPanel/WHM

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Click en **Add Server** | Formulario de servidor carga | [ ] |
| 2 | Seleccionar tipo **cPanel/WHM** | Campos especificos de cPanel aparecen | [ ] |
| 3 | Rellenar: Nombre, Hostname, Puerto (2087), API Token | Campos aceptan los valores | [ ] |
| 4 | Asignar a un Server Group | Selector de grupo funciona (crear grupo si no existe) | [ ] |
| 5 | Guardar el servidor | Servidor creado sin errores | [ ] |
| 6 | Verificar que las credenciales estan cifradas en la BD | Consultar `post_meta` — valor cifrado | [ ] |

### 4.3 Anadir Servidor Plesk

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Seleccionar tipo **Plesk** | Campos especificos de Plesk aparecen | [ ] |
| 2 | Rellenar: Nombre, Hostname, Puerto (8443), API Key | Campos aceptan los valores | [ ] |
| 3 | Guardar el servidor | Servidor creado sin errores | [ ] |

### 4.4 Test de Conexion

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | En un servidor cPanel guardado, click **Test Connection** | AJAX request se envia | [ ] |
| 2 | Con credenciales correctas | Mensaje de exito: "Connection successful", muestra version WHM | [ ] |
| 3 | Con credenciales incorrectas | Mensaje de error descriptivo | [ ] |
| 4 | Con hostname inalcanzable | Timeout y mensaje de error | [ ] |
| 5 | Repetir test para Plesk | Mismos resultados esperados | [ ] |

### 4.5 Fetch Packages

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | En un servidor cPanel conectado, click **Fetch Packages** | AJAX request se envia | [ ] |
| 2 | Respuesta exitosa | Lista de paquetes hosting disponibles en el servidor | [ ] |
| 3 | Los paquetes se almacenan en el meta del servidor | Verificar en BD | [ ] |

### 4.6 Monitor de Servidor

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Click en un servidor para ver detalles | Pagina de monitor carga | [ ] |
| 2 | Verificar metricas: CPU, RAM, Disk, Accounts | Datos mostrados (o mensaje si no disponible) | [ ] |
| 3 | Verificar barras de progreso | Se renderizan correctamente | [ ] |
| 4 | Verificar grid de informacion | Hostname, IP, tipo, grupo, estado visible | [ ] |

### 4.7 Grupos de Servidores

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Crear un grupo "Production Shared" | Taxonomia `hf_server_group` creada | [ ] |
| 2 | Crear un grupo "VPS Pool" | Segundo grupo creado | [ ] |
| 3 | Asignar servidores a grupos | Servidores asociados correctamente | [ ] |
| 4 | Filtrar por grupo en la lista | Solo muestra servidores del grupo | [ ] |

### 4.8 Eliminar Servidor

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Click **Delete** en un servidor sin servicios | Confirmacion solicitada | [ ] |
| 2 | Confirmar eliminacion | Servidor movido a papelera | [ ] |
| 3 | Intentar eliminar servidor con servicios activos | Aviso: servidor tiene servicios asignados | [ ] |

### 4.9 Health Check Automatico

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Verificar en Action Scheduler que `hostforge_server_health_check` esta programado | Tarea presente, cada 15 minutos | [ ] |
| 2 | Esperar ejecucion o forzar con WP-CLI: `wp action-scheduler run` | Se ejecuta sin errores | [ ] |
| 3 | Verificar logs del health check | Entradas en el visor de logs | [ ] |

---

## 5. Modulo Auto Provisioning (Fase 4)

### 5.1 Configuracion de Automatizacion

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Ir a **HostForge > Services > Automation Settings** | Pagina de configuracion carga | [ ] |
| 2 | Activar/desactivar auto-provision on order complete | Toggle funciona y se guarda | [ ] |
| 3 | Configurar dias para auto-suspend | Valor se guarda | [ ] |
| 4 | Configurar dias para auto-terminate | Valor se guarda | [ ] |
| 5 | Guardar y recargar | Todos los valores persisten | [ ] |

### 5.2 Provision Automatico

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Crear un producto Shared Hosting con server group y package configurados | Producto listo para venta | [ ] |
| 2 | Realizar una compra como cliente | Pedido creado | [ ] |
| 3 | Marcar pedido como "Completed" | Hook `woocommerce_order_status_completed` se dispara | [ ] |
| 4 | Verificar que se crea un CPT `hf_service` | Servicio creado con estado "pending" | [ ] |
| 5 | Verificar que se encola la tarea en Action Scheduler | Tarea `hostforge_provision_service` presente | [ ] |
| 6 | Ejecutar Action Scheduler | Provisioning se ejecuta | [ ] |
| 7 | Con servidor real: verificar cuenta creada en cPanel/Plesk | Cuenta existe en el panel | [ ] |
| 8 | Verificar que el servicio cambia a estado "active" | Estado actualizado | [ ] |
| 9 | Verificar meta del servicio: username, password (cifrado), server, package | Datos correctos | [ ] |

### 5.3 Username Generator

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Provisionar un servicio para dominio "example.com" | Username generado de 8 caracteres basado en dominio | [ ] |
| 2 | Provisionar otro servicio para "example.org" | Username diferente (no duplicado) | [ ] |
| 3 | Provisionar para dominio con caracteres especiales | Username valido, sin caracteres especiales | [ ] |

### 5.4 Password Generator

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Verificar password generado en la provision | 12-32 caracteres, mezcla de mayusculas, minusculas, numeros, simbolos | [ ] |
| 2 | Password almacenado cifrado en la BD | No texto plano en `post_meta` | [ ] |

### 5.5 Server Selector

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Tener 2+ servidores en el mismo grupo | Servidores disponibles | [ ] |
| 2 | Provisionar un servicio | Se selecciona el servidor con menos cuentas | [ ] |
| 3 | Provisionar otro servicio | Balance de carga funciona | [ ] |
| 4 | Marcar un servidor como lleno (capacidad maxima) | Siguiente provision usa otro servidor | [ ] |

### 5.6 Suspension Automatica

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Tener un servicio activo con suscripcion | Servicio en estado "active" | [ ] |
| 2 | Poner la suscripcion en "on-hold" | Hook de suspension se dispara | [ ] |
| 3 | Verificar tarea en Action Scheduler | `hostforge_suspend_service` encolada | [ ] |
| 4 | Ejecutar Action Scheduler | Servicio cambia a "suspended" | [ ] |
| 5 | Con servidor real: verificar cuenta suspendida en panel | Cuenta suspendida | [ ] |

### 5.7 Reactivacion Automatica

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Reactivar la suscripcion del servicio suspendido | Hook de unsuspend se dispara | [ ] |
| 2 | Ejecutar Action Scheduler | Servicio cambia a "active" | [ ] |
| 3 | Con servidor real: verificar cuenta reactivada | Cuenta activa | [ ] |

### 5.8 Terminacion Automatica

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Cancelar la suscripcion | Hook de terminacion se dispara | [ ] |
| 2 | Ejecutar Action Scheduler | Servicio cambia a "terminated" | [ ] |
| 3 | Con servidor real: verificar cuenta eliminada | Cuenta terminada en panel | [ ] |

### 5.9 Cambio de Paquete

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Desde admin, cambiar el paquete de un servicio activo | Tarea `hostforge_change_package_service` encolada | [ ] |
| 2 | Ejecutar Action Scheduler | Paquete cambiado en el servidor | [ ] |
| 3 | Verificar meta actualizado | Nuevo paquete reflejado | [ ] |

### 5.10 Admin — Lista de Servicios

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Ir a **HostForge > Services** | Lista de servicios carga | [ ] |
| 2 | Verificar tabs de estado: Active, Pending, Suspended, Terminated | Tabs funcionan como filtro | [ ] |
| 3 | Verificar columnas: Service, Customer, Server, Package, Status, Created | Todas visibles | [ ] |
| 4 | Click en un servicio | Pagina de detalle carga | [ ] |
| 5 | Verificar acciones disponibles en detalle: Suspend, Unsuspend, Terminate, Change Package | Botones presentes segun estado | [ ] |

### 5.11 Provisioning Queue (Reintentos)

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Simular fallo de provision (servidor inalcanzable) | Tarea falla, se registra en `hf_provisioning_queue` | [ ] |
| 2 | Verificar que se reintenta automaticamente | Retry logic activo (segun configuracion) | [ ] |
| 3 | Verificar log del error | Entrada de error en el visor de logs | [ ] |

---

## 6. Modulo Support Desk (Fase 5)

### 6.1 Departamentos

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Ir a **HostForge > Support > Departments** | Pagina de departamentos carga | [ ] |
| 2 | Crear departamento "Technical Support" | Departamento creado | [ ] |
| 3 | Crear departamento "Billing" | Segundo departamento creado | [ ] |
| 4 | Editar un departamento | Cambios guardados | [ ] |
| 5 | Eliminar un departamento sin tickets | Eliminacion exitosa | [ ] |

### 6.2 Canned Responses

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Ir a **HostForge > Support > Canned Responses** | Pagina carga | [ ] |
| 2 | Crear respuesta enlatada "Welcome Message" | Respuesta creada con titulo y contenido | [ ] |
| 3 | Crear varias respuestas | Todas listadas | [ ] |
| 4 | Editar una respuesta | Cambios guardados | [ ] |
| 5 | Eliminar una respuesta | Eliminacion exitosa | [ ] |

### 6.3 Tickets — Admin

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Ir a **HostForge > Support > Tickets** | Lista de tickets carga | [ ] |
| 2 | Crear ticket desde admin (click **New Ticket**) | Formulario carga con campos: Subject, Customer, Department, Priority, Message | [ ] |
| 3 | Rellenar y enviar | Ticket creado con estado "open" | [ ] |
| 4 | Verificar filtros: por estado (open, answered, customer-reply, closed) | Filtros funcionan | [ ] |
| 5 | Verificar filtro por prioridad (low, medium, high, urgent) | Filtro funciona | [ ] |
| 6 | Verificar filtro por departamento | Filtro funciona | [ ] |
| 7 | Buscar tickets por asunto | Busqueda funciona | [ ] |

### 6.4 Tickets — Detalle y Respuestas

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Abrir un ticket existente | Pagina de detalle carga con historial de mensajes | [ ] |
| 2 | Escribir una respuesta de staff | Respuesta guardada, estado cambia a "answered" | [ ] |
| 3 | Insertar canned response | Texto de la respuesta enlatada se inserta en el editor | [ ] |
| 4 | Cambiar prioridad del ticket | Prioridad actualizada | [ ] |
| 5 | Cambiar departamento del ticket | Departamento actualizado | [ ] |
| 6 | Asignar ticket a un admin | Asignacion guardada | [ ] |
| 7 | Cerrar el ticket | Estado cambia a "closed" | [ ] |
| 8 | Reabrir un ticket cerrado | Estado cambia a "open" | [ ] |

### 6.5 Knowledge Base — Admin

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Ir a **HostForge > Support > Knowledge Base** | Lista de articulos KB carga | [ ] |
| 2 | Crear categoria KB "Getting Started" | Categoria creada | [ ] |
| 3 | Crear articulo KB con titulo, contenido, categoria | Articulo creado y publicado | [ ] |
| 4 | Editar articulo | Cambios guardados | [ ] |
| 5 | Crear varios articulos en distintas categorias | Todos listados correctamente | [ ] |

### 6.6 Auto-Close de Tickets

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Verificar tarea `hostforge_auto_close_tickets` en Action Scheduler | Tarea programada diariamente | [ ] |
| 2 | Tener un ticket "answered" sin actividad por mas dias que el umbral | Ticket presente | [ ] |
| 3 | Ejecutar la tarea auto-close | Ticket cambia a "closed" | [ ] |
| 4 | Verificar que se envia email de aviso previo | Email en el log de emails | [ ] |

### 6.7 IMAP Email Piping

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Configurar IMAP en settings del Support Desk | Campos: server, port, username, password, encryption | [ ] |
| 2 | Verificar tarea `hostforge_check_imap_email` en Action Scheduler | Programada cada 5 minutos | [ ] |
| 3 | Enviar email al buzon configurado | El email se procesa y crea/actualiza ticket | [ ] |

---

## 7. Modulo Domain Manager (Fase 6)

### 7.1 Configuracion de Registrar

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Ir a **HostForge > Domains > Registrar Settings** | Pagina de configuracion de registrars | [ ] |
| 2 | Seleccionar **Namecheap** como registrar | Campos de Namecheap aparecen | [ ] |
| 3 | Introducir API Key, Username, Client IP | Campos aceptan valores | [ ] |
| 4 | Activar modo Sandbox | Toggle funciona | [ ] |
| 5 | Guardar configuracion | Valores persisten (API Key cifrada) | [ ] |
| 6 | Test de conexion al registrar | Respuesta exitosa con sandbox | [ ] |

### 7.2 TLD Pricing

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Ir a **HostForge > Domains > TLD Pricing** | Tabla de precios por TLD | [ ] |
| 2 | Anadir TLD ".com" con precios: registro $12, transferencia $12, renovacion $14 | TLD anadido | [ ] |
| 3 | Anadir TLD ".net", ".org", ".es" | Todos anadidos | [ ] |
| 4 | Editar precio de un TLD | Cambio guardado | [ ] |
| 5 | Eliminar un TLD | Eliminacion exitosa | [ ] |
| 6 | Verificar datos en tabla `wp_hf_tld_pricing` | Registros correctos | [ ] |

### 7.3 Busqueda de Disponibilidad de Dominio

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Ir al frontend (checkout o widget de busqueda) | Campo de busqueda de dominio visible | [ ] |
| 2 | Buscar "testdomain123.com" | Resultado de disponibilidad mostrado | [ ] |
| 3 | Buscar dominio ocupado (ej. "google.com") | Muestra "no disponible" | [ ] |
| 4 | Buscar multiples TLDs simultaneamente | Resultados para cada TLD | [ ] |
| 5 | Verificar rate limiting: hacer 11 busquedas rapidas | La busqueda 11 es bloqueada (limite 10/min/IP) | [ ] |

### 7.4 Checkout de Dominio

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Anadir producto Domain al carrito | Producto anadido | [ ] |
| 2 | En checkout, seleccionar **Register new domain** | Campo de dominio deseado aparece | [ ] |
| 3 | Seleccionar **Transfer existing domain** | Campo de dominio + campo EPP code aparecen | [ ] |
| 4 | Seleccionar **Use existing domain** | Solo campo de dominio | [ ] |
| 5 | Completar compra con "Register" | Pedido creado con meta de dominio | [ ] |
| 6 | Verificar EPP code cifrado en meta del pedido (para transfer) | Valor cifrado en BD | [ ] |

### 7.5 Registro Automatico de Dominio

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Completar pedido con dominio a registrar | Hook post-order se dispara | [ ] |
| 2 | Verificar CPT `hf_domain` creado | Dominio con estado "pending" | [ ] |
| 3 | Verificar tarea `hostforge_register_domain` en Action Scheduler | Tarea encolada | [ ] |
| 4 | Ejecutar Action Scheduler (con sandbox Namecheap) | Dominio registrado via API | [ ] |
| 5 | Verificar estado del dominio cambia a "active" | Estado actualizado | [ ] |

### 7.6 Transferencia de Dominio

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Completar pedido con dominio a transferir (con EPP code) | Hook se dispara | [ ] |
| 2 | Verificar tarea `hostforge_transfer_domain` | Tarea encolada | [ ] |
| 3 | Ejecutar (sandbox) | Transferencia iniciada | [ ] |
| 4 | Verificar estado "transferring" | Estado correcto | [ ] |

### 7.7 Admin — Lista de Dominios

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Ir a **HostForge > Domains** | Lista de dominios carga | [ ] |
| 2 | Verificar tabs de estado: Active, Pending, Expired, Transferred | Tabs funcionan | [ ] |
| 3 | Verificar columnas: Domain, Customer, Registrar, Expiry, Status | Columnas visibles | [ ] |
| 4 | Buscar por nombre de dominio | Busqueda funciona | [ ] |
| 5 | Ordenar por columna (ej. fecha expiracion) | Ordenamiento funciona | [ ] |

### 7.8 Admin — Detalle de Dominio

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Click en un dominio para ver detalle | Pagina de detalle carga | [ ] |
| 2 | Verificar info: registrar, fechas, nameservers, estado de lock | Datos mostrados | [ ] |
| 3 | Sync con registrar | Datos actualizados desde API | [ ] |
| 4 | Renovar dominio manualmente | Tarea de renovacion encolada | [ ] |

### 7.9 DNS Records — Admin

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | En detalle de dominio, ir a seccion DNS | Tabla de registros DNS | [ ] |
| 2 | Anadir registro A: `@` -> `1.2.3.4` | Registro creado en tabla `wp_hf_dns_records` | [ ] |
| 3 | Anadir registro CNAME: `www` -> `example.com` | Registro creado | [ ] |
| 4 | Anadir registro MX: `@` -> `mail.example.com`, prioridad 10 | Registro creado | [ ] |
| 5 | Editar un registro | Cambio guardado | [ ] |
| 6 | Eliminar un registro | Registro eliminado | [ ] |

### 7.10 Expiry Check Automatico

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Verificar tarea `hostforge_check_domain_expiry` | Programada diariamente | [ ] |
| 2 | Tener un dominio con expiracion proxima (<30 dias) | Dominio existe | [ ] |
| 3 | Ejecutar la tarea | Email de recordatorio enviado | [ ] |

---

## 8. Modulo Security (Fase 7)

### 8.1 Configuracion de Seguridad

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Ir a **HostForge > Security > Settings** | Pagina de configuracion carga | [ ] |
| 2 | Configurar max login attempts (ej. 5) | Valor guardado | [ ] |
| 3 | Configurar duracion del bloqueo (ej. 30 minutos) | Valor guardado | [ ] |
| 4 | Activar/desactivar fraud detection | Toggle funciona | [ ] |
| 5 | Configurar CAPTCHA (Turnstile o reCAPTCHA) | Site key y secret key guardados | [ ] |

### 8.2 Brute Force Protection

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Intentar login con password incorrecta 5 veces (segun max configurado) | Cada intento registrado en `wp_hf_login_attempts` | [ ] |
| 2 | En el intento siguiente al maximo | IP bloqueada, mensaje de error mostrado | [ ] |
| 3 | Verificar registro en tabla `wp_hf_ip_blocks` | IP bloqueada con tipo "auto" y timestamp de expiracion | [ ] |
| 4 | Esperar que expire el bloqueo | Login permitido de nuevo | [ ] |
| 5 | Verificar en admin **Security > Login Attempts** | Intentos listados con IP, username, timestamp, resultado | [ ] |

### 8.3 IP Manager

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Ir a **HostForge > Security > IP Blocks** | Lista de IPs bloqueadas/permitidas | [ ] |
| 2 | Anadir IP a blocklist: `192.168.1.100` | IP bloqueada | [ ] |
| 3 | Anadir rango CIDR a blocklist: `10.0.0.0/24` | Rango bloqueado | [ ] |
| 4 | Intentar acceder desde IP bloqueada | Acceso denegado | [ ] |
| 5 | Anadir IP a allowlist | IP siempre permitida (incluso con brute force) | [ ] |
| 6 | Eliminar IP de blocklist | IP desbloqueada | [ ] |
| 7 | Verificar que allowlist tiene prioridad sobre blocklist | IP en ambas listas puede acceder | [ ] |

### 8.4 Fraud Detection

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Configurar paises bloqueados en fraud detection | Paises guardados | [ ] |
| 2 | Configurar emails bloqueados (ej. dominios desechables) | Patrones guardados | [ ] |
| 3 | Intentar checkout desde IP de pais bloqueado | Checkout rechazado con mensaje | [ ] |
| 4 | Intentar checkout con email de dominio bloqueado | Checkout rechazado | [ ] |
| 5 | Checkout normal sin restricciones | Completado sin problemas | [ ] |

### 8.5 CAPTCHA

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Activar CAPTCHA en login | Widget CAPTCHA aparece en formulario de login | [ ] |
| 2 | Activar CAPTCHA en registro | Widget aparece en formulario de registro | [ ] |
| 3 | Activar CAPTCHA en checkout | Widget aparece en checkout | [ ] |
| 4 | Activar CAPTCHA en tickets | Widget aparece en formulario de nuevo ticket | [ ] |
| 5 | Enviar formulario sin completar CAPTCHA | Error de validacion | [ ] |
| 6 | Completar CAPTCHA y enviar | Formulario procesado correctamente | [ ] |
| 7 | Probar con Cloudflare Turnstile | Widget Turnstile funciona | [ ] |
| 8 | Probar con Google reCAPTCHA | Widget reCAPTCHA funciona | [ ] |

### 8.6 Audit Log

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Ir a **HostForge > Security > Audit Log** | Lista de eventos de auditoria | [ ] |
| 2 | Realizar login exitoso | Evento registrado: "user_login" | [ ] |
| 3 | Realizar login fallido | Evento registrado: "user_login_failed" | [ ] |
| 4 | Activar/desactivar modulo | Evento registrado | [ ] |
| 5 | Crear/modificar servicio | Evento registrado | [ ] |
| 6 | Crear/responder ticket | Evento registrado | [ ] |
| 7 | Registrar/modificar dominio | Evento registrado | [ ] |
| 8 | Filtrar por tipo de evento | Filtro funciona | [ ] |
| 9 | Filtrar por usuario | Filtro funciona | [ ] |
| 10 | Verificar paginacion | Funciona con >20 entradas | [ ] |

### 8.7 Limpieza Automatica

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Verificar tarea `hostforge_cleanup_login_attempts` | Programada diariamente | [ ] |
| 2 | Verificar tarea `hostforge_cleanup_expired_blocks` | Programada diariamente | [ ] |
| 3 | Ejecutar tareas | Registros antiguos eliminados | [ ] |

---

## 9. Modulo Notifications (Fase 7)

### 9.1 Emails WooCommerce

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Ir a **WooCommerce > Settings > Emails** | 11 emails de HostForge listados | [ ] |
| 2 | Verificar email **Service Welcome** | Configuracion visible: activar/desactivar, asunto, destinatarios | [ ] |
| 3 | Verificar email **Service Suspended** | Configuracion visible | [ ] |
| 4 | Verificar email **Service Unsuspended** | Configuracion visible | [ ] |
| 5 | Verificar email **Service Terminated** | Configuracion visible | [ ] |
| 6 | Verificar email **Provision Failed** | Configuracion visible | [ ] |
| 7 | Verificar email **New Ticket (Staff)** | Configuracion visible | [ ] |
| 8 | Verificar email **Ticket Reply (Customer)** | Configuracion visible | [ ] |
| 9 | Verificar email **Ticket Reply (Staff)** | Configuracion visible | [ ] |
| 10 | Verificar email **Ticket Closed** | Configuracion visible | [ ] |
| 11 | Verificar email **Domain Registered** | Configuracion visible | [ ] |
| 12 | Verificar email **Domain Expiry Reminder** | Configuracion visible | [ ] |

### 9.2 Envio de Emails

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Provisionar un servicio | Email "Service Welcome" enviado al cliente | [ ] |
| 2 | Suspender un servicio | Email "Service Suspended" enviado | [ ] |
| 3 | Reactivar un servicio | Email "Service Unsuspended" enviado | [ ] |
| 4 | Terminar un servicio | Email "Service Terminated" enviado | [ ] |
| 5 | Fallo en provision | Email "Provision Failed" enviado al admin | [ ] |
| 6 | Cliente crea ticket | Email "New Ticket" enviado al staff | [ ] |
| 7 | Staff responde ticket | Email "Ticket Reply" enviado al cliente | [ ] |
| 8 | Cliente responde ticket | Email "Ticket Reply" enviado al staff | [ ] |
| 9 | Ticket cerrado | Email "Ticket Closed" enviado al cliente | [ ] |
| 10 | Dominio registrado | Email "Domain Registered" enviado al cliente | [ ] |
| 11 | Dominio proximo a expirar | Email "Domain Expiry Reminder" enviado | [ ] |

### 9.3 Merge Tags

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | En email de servicio, verificar merge tags: `{service_name}`, `{service_username}`, `{service_domain}`, `{server_hostname}` | Tags reemplazados con valores reales | [ ] |
| 2 | En email de ticket, verificar: `{ticket_id}`, `{ticket_subject}`, `{ticket_department}` | Tags reemplazados | [ ] |
| 3 | En email de dominio, verificar: `{domain_name}`, `{domain_expiry}`, `{registrar_name}` | Tags reemplazados | [ ] |

### 9.4 Plantillas de Email

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Verificar que las plantillas HTML existen en `templates/emails/` | 11 plantillas presentes | [ ] |
| 2 | Verificar que las plantillas plain text existen en `templates/emails/plain/` | Plantillas plain text presentes | [ ] |
| 3 | Copiar plantilla a `theme/hostforge/emails/` y modificar | La plantilla del tema tiene prioridad | [ ] |

---

## 10. Modulo Reports (Fase 7)

### 10.1 Dashboard de Reports

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Ir a **HostForge > Reports** | Dashboard de reportes carga | [ ] |
| 2 | Verificar cards de resumen: Revenue, MRR, Active Services, Open Tickets | Cards con datos | [ ] |
| 3 | Verificar grafico de revenue | Chart.js renderiza correctamente | [ ] |
| 4 | Verificar grafico de servicios | Barras por tipo de producto | [ ] |
| 5 | Verificar grafico de tickets | Distribucion por estado | [ ] |
| 6 | Verificar grafico de dominios | Datos de dominios | [ ] |
| 7 | Verificar grafico de clientes | Crecimiento de clientes | [ ] |
| 8 | Verificar tabla de servidores | Uso de recursos por servidor | [ ] |
| 9 | Cambiar rango de fechas | Graficos se actualizan | [ ] |

### 10.2 Exportacion CSV

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Click en **Export Revenue CSV** | Archivo CSV descargado | [ ] |
| 2 | Abrir CSV en Excel | Caracteres UTF-8 correctos (BOM presente), columnas separadas | [ ] |
| 3 | Exportar CSV de Services | Datos de servicios correctos | [ ] |
| 4 | Exportar CSV de Tickets | Datos de tickets correctos | [ ] |
| 5 | Exportar CSV de Domains | Datos de dominios correctos | [ ] |
| 6 | Exportar CSV de Customers | Datos de clientes correctos | [ ] |

---

## 11. REST API

### 11.1 Autenticacion

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | GET `/wp-json/hostforge/v1/status` sin autenticacion | Respuesta con info publica del plugin | [ ] |
| 2 | GET `/wp-json/hostforge/v1/servers` sin autenticacion | Error 401 Unauthorized | [ ] |
| 3 | GET con nonce de usuario admin | Respuesta 200 con datos | [ ] |
| 4 | GET con Application Password de admin | Respuesta 200 con datos | [ ] |
| 5 | GET con nonce de usuario sin capabilities | Error 403 Forbidden | [ ] |

### 11.2 Endpoint de Status

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | GET `/wp-json/hostforge/v1/status` | JSON con: version, php_version, wp_version, wc_version, hpos_enabled, active_modules | [ ] |

### 11.3 Endpoints de Servers

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | GET `/hostforge/v1/servers` | Lista de servidores en JSON | [ ] |
| 2 | GET `/hostforge/v1/servers/{id}` | Detalle de servidor | [ ] |
| 3 | POST `/hostforge/v1/servers/{id}/test` | Resultado de test de conexion | [ ] |
| 4 | GET `/hostforge/v1/servers/{id}/packages` | Lista de paquetes | [ ] |
| 5 | GET `/hostforge/v1/servers/{id}/stats` | Estadisticas del servidor | [ ] |
| 6 | GET con ID inexistente | Error 404 | [ ] |

### 11.4 Endpoints de Services

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | GET `/hostforge/v1/services` | Lista de servicios | [ ] |
| 2 | GET `/hostforge/v1/services?status=active` | Solo servicios activos | [ ] |
| 3 | GET `/hostforge/v1/services/{id}` | Detalle de servicio | [ ] |
| 4 | POST `/hostforge/v1/services/{id}/action` con `{"action": "suspend"}` | Servicio suspendido | [ ] |
| 5 | POST con accion invalida | Error de validacion | [ ] |

### 11.5 Endpoints de Tickets

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | GET `/hostforge/v1/tickets` | Lista de tickets | [ ] |
| 2 | POST `/hostforge/v1/tickets` con subject, message, department | Ticket creado, respuesta 201 | [ ] |
| 3 | GET `/hostforge/v1/tickets/{id}` | Detalle de ticket con historial | [ ] |
| 4 | POST `/hostforge/v1/tickets/{id}/replies` con message | Respuesta anadida | [ ] |
| 5 | PUT `/hostforge/v1/tickets/{id}/status` con `{"status": "closed"}` | Ticket cerrado | [ ] |
| 6 | DELETE `/hostforge/v1/tickets/{id}` | Ticket eliminado | [ ] |

### 11.6 Endpoints de Knowledge Base

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | GET `/hostforge/v1/kb` | Lista de articulos KB | [ ] |
| 2 | GET `/hostforge/v1/kb/{id}` | Detalle de articulo | [ ] |
| 3 | POST `/hostforge/v1/kb/{id}/vote` con `{"helpful": true}` | Voto registrado | [ ] |

### 11.7 Endpoints de Domains

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | GET `/hostforge/v1/domains` | Lista de dominios | [ ] |
| 2 | GET `/hostforge/v1/domains/{id}` | Detalle de dominio | [ ] |
| 3 | POST `/hostforge/v1/domains/{id}/sync` | Sincronizado con registrar | [ ] |
| 4 | POST `/hostforge/v1/domains/{id}/renew` | Renovacion encolada | [ ] |
| 5 | GET `/hostforge/v1/domains/{id}/dns` | Registros DNS del dominio | [ ] |
| 6 | POST `/hostforge/v1/domains/{id}/dns` con record data | Registro DNS creado | [ ] |
| 7 | PUT `/hostforge/v1/domains/{id}/dns` con record update | Registro actualizado | [ ] |
| 8 | DELETE `/hostforge/v1/domains/{id}/dns` con record ID | Registro eliminado | [ ] |
| 9 | GET `/hostforge/v1/domains/check?domain=test.com` | Resultado de disponibilidad | [ ] |

### 11.8 Endpoints de Security

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | GET `/hostforge/v1/security/ip-blocks` | Lista de IPs bloqueadas | [ ] |
| 2 | POST `/hostforge/v1/security/ip-blocks` con IP | IP bloqueada | [ ] |
| 3 | DELETE `/hostforge/v1/security/ip-blocks` con IP | IP desbloqueada | [ ] |
| 4 | GET `/hostforge/v1/security/login-attempts` | Log de intentos de login | [ ] |
| 5 | GET `/hostforge/v1/security/audit-log` | Log de auditoria | [ ] |

### 11.9 Endpoints de Reports

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | GET `/hostforge/v1/reports/revenue?period=30` | Datos de revenue ultimos 30 dias | [ ] |
| 2 | GET `/hostforge/v1/reports/customers` | Metricas de clientes | [ ] |
| 3 | GET `/hostforge/v1/reports/services` | Estadisticas de servicios | [ ] |
| 4 | GET `/hostforge/v1/reports/tickets` | Estadisticas de tickets | [ ] |
| 5 | GET `/hostforge/v1/reports/domains` | Estadisticas de dominios | [ ] |
| 6 | GET `/hostforge/v1/reports/servers` | Metricas de servidores | [ ] |

---

## 12. Frontend — Mi Cuenta

### 12.1 Hosting Services (Mi Cuenta)

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Login como cliente con servicios | Menu de navegacion incluye **Hosting Services** | [ ] |
| 2 | Click en **Hosting Services** | Lista de servicios del cliente | [ ] |
| 3 | Verificar columnas: Service, Domain, Server, Status | Datos correctos | [ ] |
| 4 | Click en un servicio | Pagina de detalle del servicio | [ ] |
| 5 | Verificar info: dominio, username, server, package, estado, fechas | Datos mostrados | [ ] |
| 6 | Click en **SSO Login** (si esta disponible) | Redireccion al panel de control del hosting | [ ] |
| 7 | Cambiar password del hosting | AJAX: password cambiado en el servidor | [ ] |
| 8 | Ver estadisticas de uso (disk, bandwidth) | Datos de uso mostrados | [ ] |
| 9 | Solicitar cancelacion | Solicitud enviada | [ ] |
| 10 | Solicitar upgrade | Opciones de upgrade mostradas | [ ] |

### 12.2 Support Tickets (Mi Cuenta)

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Login como cliente | Menu incluye **Support Tickets** | [ ] |
| 2 | Click en **Support Tickets** | Lista de tickets del cliente | [ ] |
| 3 | Click en **New Ticket** | Formulario: Subject, Department, Priority, Message | [ ] |
| 4 | Rellenar y enviar ticket | Ticket creado con estado "open" | [ ] |
| 5 | Ver ticket creado | Detalle con historial de mensajes | [ ] |
| 6 | Responder al ticket | Respuesta anadida, estado cambia a "customer-reply" | [ ] |
| 7 | Verificar que el cliente NO ve tickets de otros clientes | Solo sus propios tickets | [ ] |
| 8 | Adjuntar archivo al ticket (si soportado) | Archivo subido y asociado | [ ] |

### 12.3 Knowledge Base (Frontend)

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Acceder a la pagina de Knowledge Base | Archivo de categorias KB | [ ] |
| 2 | Click en una categoria | Lista de articulos de la categoria | [ ] |
| 3 | Click en un articulo | Contenido del articulo mostrado | [ ] |
| 4 | Votar articulo como util | Voto registrado, contador actualizado | [ ] |
| 5 | Votar articulo como no util | Voto registrado | [ ] |
| 6 | Verificar que KB es accesible sin login | Contenido publico visible | [ ] |

### 12.4 My Domains (Mi Cuenta)

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Login como cliente con dominios | Menu incluye **My Domains** | [ ] |
| 2 | Click en **My Domains** | Lista de dominios del cliente | [ ] |
| 3 | Verificar columnas: Domain, Registrar, Expiry, Status | Datos correctos | [ ] |
| 4 | Click en un dominio | Pagina de detalle del dominio | [ ] |
| 5 | Toggle **Auto-Renew** | AJAX: valor actualizado | [ ] |
| 6 | Editar nameservers | AJAX: nameservers actualizados | [ ] |
| 7 | Ver/editar registros DNS | Tabla de registros con CRUD | [ ] |
| 8 | Obtener EPP code | AJAX: EPP code mostrado | [ ] |
| 9 | Verificar que el cliente NO ve dominios de otros | Solo sus dominios | [ ] |

### 12.5 Template Overrides

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Copiar `templates/frontend/service-list.php` a `theme/hostforge/service-list.php` | Archivo copiado | [ ] |
| 2 | Modificar la plantilla del tema | Cambios visibles en el frontend | [ ] |
| 3 | Eliminar la plantilla del tema | Vuelve a usar la plantilla del plugin | [ ] |

---

## 13. Desinstalacion

### 13.1 Desactivacion del Plugin

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Desactivar el plugin desde Plugins | Plugin desactivado sin errores | [ ] |
| 2 | Verificar que las tareas de Action Scheduler se cancelan | No hay tareas pendientes de hostforge | [ ] |
| 3 | Verificar que las tablas de BD siguen existiendo | Tablas presentes (no se borran en desactivacion) | [ ] |
| 4 | Reactivar el plugin | Todo funciona como antes, datos intactos | [ ] |

### 13.2 Desinstalacion Completa (con borrado de datos)

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Activar opcion `hf_delete_data_on_uninstall` en settings | Opcion guardada | [ ] |
| 2 | Desactivar y eliminar el plugin | `uninstall.php` se ejecuta | [ ] |
| 3 | Verificar que las 6 tablas se eliminan | `wp_hf_logs`, `wp_hf_activity_log`, `wp_hf_provisioning_queue`, `wp_hf_dns_records`, `wp_hf_login_attempts`, `wp_hf_ip_blocks` eliminadas | [ ] |
| 4 | Verificar que todas las opciones `hf_*` se eliminan | No hay opciones en `wp_options` | [ ] |
| 5 | Verificar que los CPTs se eliminan | No hay posts de tipo `hf_server`, `hf_service`, `hf_ticket`, `hf_kb_article`, `hf_canned_response`, `hf_domain` | [ ] |
| 6 | Verificar que las taxonomias se eliminan | No hay terms de `hf_department`, `hf_kb_category`, `hf_server_group` | [ ] |
| 7 | Verificar que las capabilities se eliminan | Rol administrador no tiene `manage_hostforge*` | [ ] |
| 8 | Verificar que las tareas de Action Scheduler se eliminan | No hay tareas en los grupos de hostforge | [ ] |

### 13.3 Desinstalacion sin Borrado de Datos

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Sin activar `hf_delete_data_on_uninstall` | Opcion no marcada | [ ] |
| 2 | Desactivar y eliminar el plugin | `uninstall.php` se ejecuta | [ ] |
| 3 | Verificar que los datos se conservan | Tablas, CPTs, opciones siguen en BD | [ ] |
| 4 | Reinstalar el plugin | Todo funciona con los datos anteriores | [ ] |

---

## 14. Compatibilidad

### 14.1 HPOS (High-Performance Order Storage)

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Activar HPOS en WooCommerce > Settings > Advanced > Features | HPOS activado | [ ] |
| 2 | Crear pedido con producto hosting | Pedido creado correctamente | [ ] |
| 3 | Verificar meta del pedido en tabla HPOS | Datos correctos en `wp_wc_orders_meta` | [ ] |
| 4 | Desactivar HPOS (modo legacy) | Cambio sin errores | [ ] |
| 5 | Verificar que los pedidos anteriores siguen accesibles | Datos intactos | [ ] |
| 6 | Verificar declaracion de compatibilidad del plugin | `before_woocommerce_init` declara HPOS compatible | [ ] |

### 14.2 WooCommerce Blocks Checkout

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Activar checkout por bloques de WC | Checkout de bloques activo | [ ] |
| 2 | Anadir producto hosting al carrito | Producto anadido | [ ] |
| 3 | Ir al checkout | Campos adicionales de HostForge visibles | [ ] |
| 4 | Completar compra | Pedido creado con meta correcto | [ ] |

### 14.3 Temas

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Probar con tema **Storefront** (tema oficial WC) | Frontend renderiza correctamente | [ ] |
| 2 | Probar con tema **Twenty Twenty-Five** | Frontend renderiza correctamente | [ ] |
| 3 | Probar con tema de terceros popular (ej. Astra, GeneratePress) | Frontend renderiza correctamente | [ ] |
| 4 | Verificar que los estilos no conflictan con el tema | Sin CSS roto | [ ] |

### 14.4 Multisite

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Instalar en WordPress Multisite | Plugin se activa sin errores | [ ] |
| 2 | Activar por sitio (no network-wide) | Funciona independiente por sitio | [ ] |
| 3 | Verificar que las tablas usan el prefijo correcto | `wp_2_hf_logs` para sitio 2, etc. | [ ] |

### 14.5 Internacionalizacion (i18n)

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Verificar que existe `docs/hostforge.pot` | Archivo de traduccion presente | [ ] |
| 2 | Verificar que todos los strings usan `__()` / `esc_html__()` con textdomain `hostforge` | Strings traducibles | [ ] |
| 3 | Instalar traduccion (si existe) | Textos traducidos se muestran | [ ] |
| 4 | Cambiar idioma de WordPress | Interfaz cambia al idioma configurado | [ ] |

---

## 15. Seguridad

### 15.1 Nonces

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Inspeccionar todos los formularios admin | Campo `_wpnonce` presente | [ ] |
| 2 | Inspeccionar todas las llamadas AJAX | Nonce enviado y verificado | [ ] |
| 3 | Enviar formulario con nonce modificado | Solicitud rechazada (403) | [ ] |
| 4 | Enviar AJAX con nonce invalido | Solicitud rechazada | [ ] |

### 15.2 Capabilities

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Acceder a paginas admin como Editor (sin capabilities) | Acceso denegado | [ ] |
| 2 | Hacer AJAX requests como Editor | Solicitudes rechazadas | [ ] |
| 3 | Acceder a REST API como Subscriber | Error 403 en endpoints protegidos | [ ] |
| 4 | Verificar que `current_user_can()` se usa antes de cada accion | Code review | [ ] |

### 15.3 Sanitizacion de Input

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Enviar HTML en campos de texto | HTML strippeado/escapado | [ ] |
| 2 | Enviar SQL injection en campos: `'; DROP TABLE--` | Query segura via `$wpdb->prepare()` | [ ] |
| 3 | Enviar XSS en campos: `<script>alert(1)</script>` | Script no se ejecuta, HTML escapado | [ ] |
| 4 | Enviar path traversal en uploads: `../../etc/passwd` | Path rechazado/sanitizado | [ ] |

### 15.4 Escape de Output

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Crear servidor con nombre `<img src=x onerror=alert(1)>` | Nombre escapado en todas las vistas | [ ] |
| 2 | Crear ticket con asunto `<script>document.cookie</script>` | Asunto escapado | [ ] |
| 3 | Verificar que `esc_html()`, `esc_attr()`, `esc_url()` se usan en templates | Code review | [ ] |

### 15.5 CSRF Protection

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Crear pagina maliciosa con formulario que apunte a AJAX de HostForge | Formulario externo no funciona (nonce invalido) | [ ] |
| 2 | Intentar CSRF en cambio de estado de servicio | Accion rechazada | [ ] |

### 15.6 Cifrado de Datos Sensibles

| # | Paso | Resultado Esperado | OK |
|---|------|--------------------|----|
| 1 | Verificar API keys de servidores en BD | Cifradas con AES-256-CBC | [ ] |
| 2 | Verificar API key de registrar en BD | Cifrada | [ ] |
| 3 | Verificar passwords de servicios en BD | Cifrados | [ ] |
| 4 | Verificar EPP codes en BD | Cifrados | [ ] |
| 5 | Verificar CAPTCHA secret keys en BD | Cifradas | [ ] |

---

## Checklist Rapido

Resumen para verificacion rapida del estado general:

| Area | Tests | Superados | Estado |
|------|-------|-----------|--------|
| Instalacion y activacion | 14 | _ /14 | [ ] |
| Core y fundamentos | 18 | _ /18 | [ ] |
| Productos WooCommerce | 35 | _ /35 | [ ] |
| Server Manager | 28 | _ /28 | [ ] |
| Auto Provisioning | 30 | _ /30 | [ ] |
| Support Desk | 27 | _ /27 | [ ] |
| Domain Manager | 32 | _ /32 | [ ] |
| Security | 28 | _ /28 | [ ] |
| Notifications | 22 | _ /22 | [ ] |
| Reports | 15 | _ /15 | [ ] |
| REST API | 38 | _ /38 | [ ] |
| Frontend Mi Cuenta | 30 | _ /30 | [ ] |
| Desinstalacion | 15 | _ /15 | [ ] |
| Compatibilidad | 15 | _ /15 | [ ] |
| Seguridad | 18 | _ /18 | [ ] |
| **TOTAL** | **385** | _ /385 | [ ] |

---

## Notas Finales

### Entorno Recomendado para Testing Completo

1. **Servidor de staging** con WordPress + WooCommerce + WooCommerce Subscriptions
2. **Servidor cPanel/WHM de testing** (sandbox o VPS de pruebas)
3. **Cuenta Namecheap sandbox** para testing de dominios
4. **Claves Turnstile/reCAPTCHA de testing** de Cloudflare/Google
5. **Plugin WP Mail Logging** para verificar todos los emails
6. **Query Monitor** para verificar rendimiento y errores

### Orden de Testing Recomendado

1. Instalacion y activacion
2. Core y fundamentos
3. Tipos de producto (crear productos de prueba)
4. Server Manager (anadir servidor de pruebas)
5. Auto Provisioning (flujo completo de compra)
6. Support Desk (crear y gestionar tickets)
7. Domain Manager (flujo de registro de dominio)
8. Security (brute force, IP blocks, CAPTCHA)
9. Notifications (verificar todos los emails)
10. Reports (verificar datos y exportaciones)
11. REST API (Postman collection)
12. Frontend Mi Cuenta (perspectiva del cliente)
13. Seguridad (pentesting basico)
14. Compatibilidad (HPOS, temas, multisite)
15. Desinstalacion (ultimo paso)

### Reporte de Bugs

Para cada bug encontrado, documentar:

- **Modulo**: Cual modulo/fase afecta
- **Severidad**: Critical / High / Medium / Low
- **Pasos para reproducir**: Exactamente que se hizo
- **Resultado esperado**: Que deberia pasar
- **Resultado actual**: Que paso realmente
- **Capturas de pantalla**: Si aplica
- **Logs**: Entradas relevantes de debug.log y logs de HostForge
