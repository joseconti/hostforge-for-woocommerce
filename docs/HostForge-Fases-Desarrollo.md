# HOSTFORGE FOR WOOCOMMERCE — Fases de Desarrollo

**Plugin WordPress / WooCommerce — Desarrollo Modular por Fases**

Autor: José Conti | Versión 1.0 | Febrero 2026

---

## Resumen Ejecutivo

**HostForge for WooCommerce** es un plugin modular que transforma WooCommerce en una plataforma completa de gestión de hosting. Permite vender servicios de hosting (compartido, reseller, VPS, dedicado), dominios, certificados SSL y licencias de software, con aprovisionamiento automático en servidores cPanel y Plesk.

### Cifras clave

| Concepto | Valor |
|----------|-------|
| Total de fases | 8 |
| Total de tareas | ~115 |
| Fases críticas | 1, 2, 8 |
| Fases de prioridad alta | 3, 4 |
| Módulos independientes | 8 (Server Manager, Auto Provisioning, Support Desk, Domain Manager, Affiliates, Security, Notifications, Reports) |

### Arquitectura

- **Modular**: Cada módulo se activa/desactiva desde el admin sin afectar al resto.
- **PHP 8.0+** con PSR-4 autoloading, type hints y WordPress Coding Standards.
- **WooCommerce nativo**: Usa productos, pedidos, suscripciones y checkout de WC.
- **HPOS compatible**: Almacenamiento de alto rendimiento para pedidos.
- **Action Scheduler**: Todas las tareas programadas usan Action Scheduler (no WP-Cron).
- **Sin facturación**: No gestiona facturas ni impuestos — el usuario instala su propio plugin de facturación.

---

## Mapa de Dependencias entre Fases

```
FASE 1: Core y Fundamentos ─────────────────────────────────┐
  │                                                          │
  ├──→ FASE 2: Tipos de Producto WooCommerce                 │
  │       │                                                  │
  │       ├──→ FASE 4: Auto Provisioning ←── FASE 3          │
  │       │                                                  │
  │       └──→ FASE 6: Domain Manager                        │
  │                                                          │
  ├──→ FASE 3: Server Manager ──→ FASE 4                     │
  │                                                          │
  ├──→ FASE 5: Support Desk                                  │
  │                                                          │
  └──→ FASE 7: Módulos Adicionales                           │
                                                             │
FASE 8: Testing, Seguridad y Polish ←───── TODAS LAS FASES ─┘
```

### Orden de ejecución recomendado

1. **Fase 1** → Obligatoria primero (fundamentos del plugin)
2. **Fases 2 y 3** → Se pueden desarrollar en paralelo
3. **Fase 4** → Requiere Fases 2 y 3 completadas
4. **Fases 5 y 6** → Independientes, se pueden desarrollar en paralelo
5. **Fase 7** → Módulos complementarios
6. **Fase 8** → Revisión final de todo

---

## FASE 1 — Core y Fundamentos

**Prioridad**: CRÍTICA | **Dependencias**: Ninguna | **Tareas**: 18

> Sin esta fase, ningún módulo puede funcionar. Es la base de todo el plugin.

### Archivos clave

| Archivo | Propósito |
|---------|-----------|
| `hostforge-for-woocommerce.php` | Archivo principal del plugin |
| `uninstall.php` | Limpieza al desinstalar |
| `includes/class-hostforge.php` | Clase principal (singleton) |
| `includes/class-hf-autoloader.php` | Autoloader PSR-4 |
| `includes/class-hf-activator.php` | Lógica de activación |
| `includes/class-hf-deactivator.php` | Lógica de desactivación |
| `includes/class-hf-module-manager.php` | Gestor de módulos |
| `includes/class-hf-dependency-checker.php` | Verificador de dependencias |
| `includes/abstracts/abstract-hf-module.php` | Clase base de módulo |
| `includes/abstracts/abstract-hf-rest-controller.php` | Controlador REST base |
| `includes/traits/trait-hf-has-logs.php` | Trait de logging |
| `includes/traits/trait-hf-has-settings.php` | Trait de settings |

### Tareas

| ID | Tarea | Estado | Notas |
|----|-------|--------|-------|
| 1.1 | Archivo principal del plugin con constantes, cabeceras y verificación de dependencias | `DONE` | hostforge.php — Constantes, HPOS declaration, admin notices, action_links |
| 1.2 | Autoloader PSR-4 | `DONE` | class-hf-autoloader.php — HostForge\\ → includes/, modules → modules/{slug}/ |
| 1.3 | Clase principal singleton con init() | `DONE` | class-hostforge.php — instance(), helpers, textdomain, module manager |
| 1.4 | Module Manager (registrar, activar, desactivar, cargar) | `DONE` | class-hf-module-manager.php — AJAX toggles, dependencias, cascada |
| 1.5 | Clase abstracta de módulo | `DONE` | abstract-hf-module.php — get_id/name/desc/deps, init, activate, deactivate |
| 1.6 | Activador: tablas DB, capabilities, versión | `DONE` | class-hf-activator.php — hf_logs, hf_activity_log, 7 capabilities |
| 1.7 | Desactivador: des-programar acciones, flush reglas | `DONE` | class-hf-deactivator.php — as_unschedule_all_actions() 9 grupos |
| 1.8 | Desinstalador con borrado condicional de datos | `DONE` | uninstall.php — Solo si hf_delete_data_on_uninstall=yes |
| 1.9 | Sistema de logging: tabla, trait, visor admin | `DONE` | trait-hf-has-logs.php + class-hf-log-viewer.php + logs.php template |
| 1.10 | Settings: página General | `DONE` | class-hf-settings.php + settings.php template |
| 1.11 | Settings: pantalla de Módulos con toggles AJAX | `DONE` | modules.php template + admin.js AJAX |
| 1.12 | REST API base: namespace, health-check, controlador abstracto | `DONE` | abstract-hf-rest-controller.php + class-hf-rest-status-controller.php |
| 1.13 | Declaración de compatibilidad HPOS | `DONE` | En hostforge.php via before_woocommerce_init |
| 1.14 | Dependency checker | `DONE` | class-hf-dependency-checker.php — PHP, WP, WC, subs plugin |
| 1.15 | CSS/JS base admin con carga condicional | `DONE` | admin.css + admin.js — Solo en páginas hostforge |
| 1.16 | Página Dashboard con registro de widgets | `DONE` | dashboard.php template + hostforge_dashboard_widgets hook |
| 1.17 | Configuración de generación de archivo POT | `DONE` | Textdomain: hostforge, domain-path /languages |
| 1.18 | plugin_action_links con enlace a Settings | `DONE` | En hostforge.php — hostforge_plugin_action_links() |

---

## FASE 2 — Tipos de Producto WooCommerce para Hosting

**Prioridad**: CRÍTICA | **Dependencias**: Fase 1 | **Tareas**: 16

> Define los productos que se venden: hosting compartido, reseller, VPS, dedicado, dominios, SSL y licencias.

### Componentes principales

- **Capa de abstracción de suscripciones**: Adapta WooCommerce Subscriptions, YITH o Advanced Subs.
- **7 tipos de producto** personalizados con sus campos de admin y checkout.
- **Campos de checkout** personalizados compatibles con checkout clásico y de bloques.
- **Sistema de add-ons** para extras opcionales (IP dedicada, backup, SSL, almacenamiento).

### Tareas

| ID | Tarea | Estado | Notas |
|----|-------|--------|-------|
| 2.1 | Interfaz del adaptador de suscripciones y factory | `DONE` | HF_Subscription_Factory auto-detecta WCS, YITH, Advanced Subs |
| 2.2 | Adaptador para WooCommerce Subscriptions | `DONE` | HF_WCS_Adapter — create, cancel, suspend, reactivate, status hooks |
| 2.3 | Adaptador para YITH Subscriptions | `DONE` | HF_YITH_Adapter — mismos métodos, normalización de estados |
| 2.4 | Adaptador para Advanced Subscriptions | `DONE` | HF_Advanced_Subs_Adapter — mismos métodos |
| 2.5 | WC_Product_HF_Shared_Hosting con todos los campos | `DONE` | Server group, plan, disco, bandwidth, emails, DBs, subdominios, setup fee, trial |
| 2.6 | WC_Product_HF_Reseller_Hosting | `DONE` | Extiende Shared + max_accounts, aggregate limits, reseller_plan, WHM access |
| 2.7 | WC_Product_HF_VPS_Server | `DONE` | CPU, RAM, disco, tipo disco, IPs, OS choices, hostname, root password |
| 2.8 | WC_Product_HF_Dedicated_Server | `DONE` | Extiende VPS + processor, RAID, uplink, datacenter, IPMI |
| 2.9 | WC_Product_HF_Domain con búsqueda de disponibilidad | `DONE` | TLDs, registrar, registration years, auto-renew, transfer, ID protection |
| 2.10 | WC_Product_HF_SSL_Certificate | `DONE` | Tipo DV/OV/EV/Wildcard, brand, validity, SAN, CSR, warranty |
| 2.11 | WC_Product_HF_Software_License | `DONE` | License type/provider, server IP, auto-generate key, max activations |
| 2.12 | Pestañas de datos de producto admin para cada tipo | `DONE` | 5 paneles: Hosting, Server, Domain, SSL, License con todos los campos |
| 2.13 | Campos de checkout personalizados (clásico + bloques) | `DONE` | HF_Checkout_Fields — dominio, hostname, OS, password, CSR, IP |
| 2.14 | Validación de checkout por tipo de producto | `DONE` | Validación domain, hostname, IPv4 server-side |
| 2.15 | Guardar meta de producto en pedido (HPOS) | `DONE` | HF_Order_Meta_Handler — line item meta + order meta display |
| 2.16 | Sistema de add-ons de producto | `DONE` | HF_Product_Addons — admin config, frontend display, cart price, order save |

---

## FASE 3 — Módulo Server Manager

**Prioridad**: ALTA | **Dependencias**: Fase 1 | **Tareas**: 20 | **ID Módulo**: `server-manager`

> Gestiona servidores cPanel y Plesk: conexiones, cuentas, paquetes y monitorización.

### Componentes principales

- **CPT `hf_server`**: Almacena datos de cada servidor con credenciales encriptadas.
- **Interfaz Panel Provider**: Contrato unificado para cPanel y Plesk.
- **cPanel/WHM Provider**: API WHM v1 (puerto 2087, HTTPS).
- **Plesk Provider**: API XML (primaria) + REST API (complemento), puerto 8443.
- **Admin screens**: Lista de servidores, formulario add/edit, test de conexión, monitorización.

### Tareas

| ID | Tarea | Estado | Notas |
|----|-------|--------|-------|
| 3.1 | CPT hf_server con meta | `DONE` | class-hf-server-manager-module.php — register_post_type, 18 meta keys |
| 3.2 | Utilidad de encriptación de credenciales | `DONE` | HF_Encryption (Phase 1) — AES-256-CBC con wp_salt('auth') |
| 3.3 | Interfaz HF_Panel_Provider | `DONE` | interface-hf-panel-provider.php (Phase 1) — 11 métodos |
| 3.4 | cPanel provider: crear, suspender, reactivar, terminar | `DONE` | class-hf-cpanel-provider.php — WHM API v1 via wp_remote_request |
| 3.5 | cPanel provider: password, cambiar paquete, listar paquetes | `DONE` | passwd, changepackage, listpkgs con cache 15min |
| 3.6 | cPanel provider: SSO | `DONE` | create_user_session para acceso cPanel |
| 3.7 | Plesk provider: cliente XML API | `DONE` | class-hf-plesk-provider.php — xml_request() port 8443, X-API-Key/Basic |
| 3.8 | Plesk provider: operaciones webspace | `DONE` | webspace>add (con customer), webspace>del, webspace>set (suspend/unsuspend) |
| 3.9 | Plesk provider: planes de servicio, clientes | `DONE` | service-plan>get, customer>add/get |
| 3.10 | Plesk provider: REST API (servidor, DNS) | `DONE` | rest_request() — GET /api/v2/server, domains, dns |
| 3.11 | Plesk provider: SSO | `DONE` | server>create_session + rsession_init URL |
| 3.12 | Admin: Lista de servidores | `DONE` | class-hf-server-list-table.php — WP_List_Table con filtros grupo/estado |
| 3.13 | Admin: Formulario Add/Edit servidor | `DONE` | server-form.php — grid 2 columnas, todos los campos |
| 3.14 | Admin: Test Connection AJAX | `DONE` | ajax_test_connection — soporta servidores guardados y sin guardar |
| 3.15 | Admin: Fetch Packages AJAX | `DONE` | ajax_fetch_packages — force refresh cache |
| 3.16 | Admin: Server groups | `DONE` | Taxonomía hf_server_group + filtro en lista + selector en form |
| 3.17 | Admin: Monitor de estado | `DONE` | server-monitor.php — info, stats, paquetes |
| 3.18 | Action Scheduler: health check cada 5 min | `DONE` | hostforge_server_health_check recurrente, grupo hostforge-server-manager |
| 3.19 | Dashboard widget: estado de servidores | `DONE` | render_dashboard_widget — total/online/errors |
| 3.20 | REST API: endpoints de servidor | `DONE` | class-hf-rest-server-controller.php — list, get, test, packages, stats |

---

## FASE 4 — Módulo Auto Provisioning

**Prioridad**: ALTA | **Dependencias**: Fases 2 y 3 | **Tareas**: 24 | **ID Módulo**: `auto-provisioning`

> Automatiza el ciclo de vida completo de un servicio de hosting: desde la compra hasta la terminación.

### Componentes principales

- **CPT `hf_service`**: Representa un servicio activo vinculado a pedido, suscripción, servidor y usuario.
- **Motor de provisioning**: Escucha hooks de WooCommerce para crear servicios automáticamente.
- **Cola de provisioning**: Tabla con reintentos y backoff exponencial.
- **Automatización**: Auto-suspender, auto-terminar y auto-reactivar servicios.
- **Frontend My Account**: El cliente ve sus servicios, accede via SSO, cambia password, solicita cancelación.

### Tareas

| ID | Tarea | Estado | Notas |
|----|-------|--------|-------|
| 4.1 | CPT hf_service con meta | `DONE` | class-hf-auto-provisioning-module.php — CPT hf_service, 17 meta keys |
| 4.2 | Motor de provisioning: hooks de pedidos WC | `DONE` | HF_Provisioning_Engine — woocommerce_order_status_completed/processing |
| 4.3 | Motor de provisioning: hooks de suscripciones | `DONE` | Subscription hooks via adapter: expired→suspend, active→unsuspend, cancelled |
| 4.4 | Generación de username con unicidad | `DONE` | HF_Username_Generator — 8 chars from domain, uniqueness check |
| 4.5 | Generación de password | `DONE` | HF_Password_Generator — 12-32 chars, uppercase+lowercase+digits+special |
| 4.6 | Auto-selección de servidor | `DONE` | HF_Server_Selector — fewest accounts in group, capacity check |
| 4.7 | Tabla de cola de provisioning | `DONE` | hf_provisioning_queue table via dbDelta on module activate |
| 4.8 | Action Scheduler: callback de provisioning | `DONE` | hostforge_provision_service async action |
| 4.9 | Lógica de reintentos con backoff | `DONE` | 5min × attempt exponential backoff, max 3 retries |
| 4.10 | Tarea de auto-suspensión | `DONE` | Every 6h, suspend active services with expired sub > grace days |
| 4.11 | Tarea de auto-terminación | `DONE` | Every 24h, terminate suspended > X days |
| 4.12 | Auto-reactivación al pago | `DONE` | on_renewal_payment → unsuspend immediately |
| 4.13 | Admin: Lista de servicios (todos/pending/suspended/cancelaciones) | `DONE` | HF_Service_List_Table — status tabs, filters, views |
| 4.14 | Admin: Detalle del servicio con acciones manuales | `DONE` | service-detail.php — info cards, queue history, manual actions |
| 4.15 | Admin: Settings de automatización | `DONE` | automation-settings.php — grace days, password length, provision trigger |
| 4.16 | Frontend: endpoint hosting-services | `DONE` | HF_Service_Frontend — hosting-services endpoint, rewrite rules |
| 4.17 | Frontend: template lista de servicios | `DONE` | templates/frontend/service-list.php — responsive table |
| 4.18 | Frontend: detalle del servicio (SSO, password, uso) | `DONE` | templates/frontend/service-detail.php — SSO, password, usage, upgrade |
| 4.19 | Frontend: solicitud de cancelación | `DONE` | AJAX cancel request → admin reviews → process or deny |
| 4.20 | Frontend: solicitud de upgrade/downgrade | `DONE` | AJAX upgrade request → change_package via Action Scheduler |
| 4.21 | Email: Bienvenida con credenciales | `DONE` | templates/emails/service-welcome.php — credentials table |
| 4.22 | Email: Servicio suspendido | `DONE` | templates/emails/service-suspended.php — reason, reactivation info |
| 4.23 | Email: Servicio terminado | `DONE` | templates/emails/service-terminated.php — data removal notice |
| 4.24 | Dashboard widget: conteo de servicios | `DONE` | Dashboard widget — active/pending/suspended counts |

---

## FASE 5 — Módulo Support Desk

**Prioridad**: MEDIA | **Dependencias**: Fase 1 | **Tareas**: 23 | **ID Módulo**: `support-desk`

> Sistema completo de tickets de soporte con base de conocimiento, respuestas predefinidas y email piping.

### Componentes principales

- **CPT `hf_ticket`**: Tickets con prioridad, estado, departamento y asignación.
- **Respuestas via WP Comments**: `comment_type` = `hf_ticket_reply`, soporte para notas privadas.
- **Base de conocimiento**: CPT `hf_kb_article` con categorías y votación de utilidad.
- **Respuestas predefinidas**: CPT `hf_canned_response` con merge tags.
- **Email piping**: Opcional, via IMAP cada 5 minutos.

### Tareas

| ID | Tarea | Estado | Notas |
|----|-------|--------|-------|
| 5.1 | CPT hf_ticket con meta y taxonomía de departamento | `PENDING` | priority, status, assigned_to, related_service |
| 5.2 | Sistema de respuestas via WP comments | `PENDING` | comment_type = hf_ticket_reply |
| 5.3 | Notas privadas | `PENDING` | Meta _hf_is_private_note |
| 5.4 | Adjuntos en tickets | `PENDING` | Meta _hf_attachments |
| 5.5 | CPT hf_kb_article con taxonomía | `PENDING` | hf_kb_category |
| 5.6 | Votación de utilidad KB (AJAX) | `PENDING` | helpful_yes / helpful_no counters |
| 5.7 | CPT hf_canned_response | `PENDING` | Respuestas predefinidas con merge tags |
| 5.8 | Auto-cierre de tickets inactivos (Action Scheduler) | `PENDING` | Diario, cerrar > X días inactivos, email aviso 24h antes |
| 5.9 | Email piping: verificación IMAP | `PENDING` | Cada 5min via Action Scheduler |
| 5.10 | Sugerencias de KB al crear ticket | `PENDING` | Búsqueda AJAX mientras escribe |
| 5.11 | Admin: Lista de tickets con filtros | `PENDING` | WP_List_Table: estado, prioridad, departamento |
| 5.12 | Admin: Detalle del ticket (respuestas, notas, sidebar) | `PENDING` | Thread de conversación con acciones laterales |
| 5.13 | Admin: Inserción de respuestas predefinidas | `PENDING` | Selector con merge tags |
| 5.14 | Admin: Gestión de departamentos | `PENDING` | CRUD taxonomía hf_department |
| 5.15 | Admin: Gestión de KB | `PENDING` | CRUD artículos y categorías |
| 5.16 | Frontend: endpoint support-tickets | `PENDING` | WooCommerce My Account |
| 5.17 | Frontend: Formulario nuevo ticket | `PENDING` | Con sugerencias KB |
| 5.18 | Frontend: Detalle del ticket con respuestas | `PENDING` | Thread de conversación del cliente |
| 5.19 | Frontend: Página pública KB | `PENDING` | Categorías y búsqueda |
| 5.20 | Email: Nuevo ticket (al staff) | `PENDING` | Notificación al equipo |
| 5.21 | Email: Notificaciones de respuesta | `PENDING` | Staff→cliente y cliente→staff |
| 5.22 | Email: Ticket cerrado | `PENDING` | Notificación al cliente |
| 5.23 | Dashboard widget: tickets abiertos | `PENDING` | Widget en el dashboard de HostForge |

---

## FASE 6 — Módulo Domain Manager

**Prioridad**: MEDIA | **Dependencias**: Fases 1 y 2 | **Tareas**: 18 | **ID Módulo**: `domain-manager`

> Gestión de dominios: registro, transferencia, renovación, DNS y WHOIS a través de registradores.

### Componentes principales

- **Interfaz HF_Registrar**: Contrato para cualquier registrador (OpenProvider, Namecheap, etc.).
- **CPT `hf_domain`**: Datos del dominio, registrador, fechas, auto-renovación.
- **Tabla hf_dns_records**: Registros DNS (A, AAAA, CNAME, MX, TXT, NS, SRV, CAA).
- **Integración checkout**: Búsqueda AJAX de disponibilidad, opciones registrar/transferir/usar propio.
- **Auto-renovación**: Tarea diaria que crea pedidos WC para dominios próximos a expirar.

### Tareas

| ID | Tarea | Estado | Notas |
|----|-------|--------|-------|
| 6.1 | Interfaz HF_Registrar | `PENDING` | check_availability, register, transfer, renew, DNS, WHOIS, nameservers |
| 6.2 | CPT hf_domain con meta | `PENDING` | domain_name, registrar, expiry_date, auto_renew, lock_status |
| 6.3 | Tabla de registros DNS | `PENDING` | hf_dns_records: type, name, value, ttl, priority |
| 6.4 | Primera implementación de registrar | `PENDING` | OpenProvider o Namecheap |
| 6.5 | Búsqueda de disponibilidad de dominio (AJAX) | `PENDING` | check_availability + check_availability_bulk |
| 6.6 | Checkout: widget de búsqueda de dominio | `PENDING` | Integrado en el checkout |
| 6.7 | Checkout: flujo registrar/transferir/usar propio | `PENDING` | Tres opciones con campos específicos |
| 6.8 | Auto-registro al completar pedido | `PENDING` | Hook woocommerce_order_status_completed |
| 6.9 | Admin: Tabla de precios TLD | `PENDING` | Precios por extensión |
| 6.10 | Admin: Configuración de registrar | `PENDING` | Credenciales y settings del registrador |
| 6.11 | Admin: Lista de dominios | `PENDING` | WP_List_Table |
| 6.12 | Admin: Detalle del dominio con DNS | `PENDING` | Gestión completa DNS |
| 6.13 | Frontend: endpoint domains | `PENDING` | WooCommerce My Account |
| 6.14 | Frontend: Detalle del dominio | `PENDING` | DNS, nameservers, WHOIS, lock, EPP, auto-renew |
| 6.15 | Action Scheduler: verificación expiración, auto-renovación | `PENDING` | Diaria: crear pedido WC o enviar email aviso |
| 6.16 | Email: Dominio registrado | `PENDING` | Confirmación al cliente |
| 6.17 | Email: Aviso de expiración de dominio | `PENDING` | Recordatorio al cliente |
| 6.18 | Dashboard widget: resumen de dominios | `PENDING` | Widget en el dashboard de HostForge |

---

## FASE 7 — Módulos Adicionales

**Prioridad**: NORMAL | **Dependencias**: Fases 1-6 | **Tareas**: 19

> Cuatro módulos complementarios, cada uno activable de forma independiente.

---

### 7A — Módulo Affiliates (`affiliates`)

Sistema de afiliados con comisiones, referidos y pagos.

**Tablas DB**: `hf_affiliates`, `hf_commissions`, `hf_referrals`

| ID | Tarea | Estado | Notas |
|----|-------|--------|-------|
| 7.1 | Tablas DB de afiliados | `PENDING` | affiliates, commissions, referrals |
| 7.2 | Tracking de referidos (cookie 30 días) | `PENDING` | Cookie con referral_code |
| 7.3 | Cálculo de comisiones | `PENDING` | Porcentaje/fijo, one-time/recurrente, override por producto |
| 7.4 | Admin: pantallas de afiliados | `PENDING` | Lista, comisiones, pagos, settings |
| 7.5 | Frontend: panel My Account de afiliados | `PENDING` | Stats, enlaces, historial, solicitud de pago |

---

### 7B — Módulo Security (`security`)

Protección contra fuerza bruta, fraude y spam.

**Tablas DB**: `hf_login_attempts`, `hf_ip_blocks`

| ID | Tarea | Estado | Notas |
|----|-------|--------|-------|
| 7.6 | Anti brute-force | `PENDING` | Bloqueo tras X intentos |
| 7.7 | Allowlist/blocklist de IPs | `PENDING` | Gestión manual de IPs |
| 7.8 | Hooks de detección de fraude | `PENDING` | IP, país, email en checkout |
| 7.9 | Turnstile/reCAPTCHA | `PENDING` | En formularios |
| 7.10 | Audit log | `PENDING` | Tabla hf_activity_log |

---

### 7C — Módulo Notifications (`notifications`)

Emails transaccionales como subclases de WC_Email.

| ID | Tarea | Estado | Notas |
|----|-------|--------|-------|
| 7.11 | Todas las subclases WC_Email | `PENDING` | 11 emails: welcome, suspended, terminated, tickets, domains, etc. |
| 7.12 | Sistema de merge tags | `PENDING` | {customer_name}, {service_domain}, {ticket_id}, etc. |
| 7.13 | Templates de email (HTML + plain) | `PENDING` | En templates/emails/, overrideable via theme |
| 7.14 | Admin: settings de activar/desactivar emails | `PENDING` | WooCommerce > Settings > Emails |

---

### 7D — Módulo Reports (`reports`)

Informes y gráficos con Chart.js.

| ID | Tarea | Estado | Notas |
|----|-------|--------|-------|
| 7.15 | Dashboard con Chart.js | `PENDING` | Gráficos interactivos AJAX |
| 7.16 | Informes de ingresos (MRR, mensual) | `PENDING` | Revenue recurrente y total |
| 7.17 | Informes de servicios y soporte | `PENDING` | Por tipo, estado, métricas de tickets |
| 7.18 | Exportación CSV | `PENDING` | Descargar datos |
| 7.19 | REST endpoints para datos de gráficos | `PENDING` | API para alimentar Chart.js |

---

## FASE 8 — Testing, Seguridad y Polish Final

**Prioridad**: CRÍTICA | **Dependencias**: Todas las fases | **Tareas**: 20

> Revisión completa de seguridad, rendimiento, compatibilidad y calidad de código.

### 8A — Auditoría de Seguridad

| ID | Tarea | Estado | Notas |
|----|-------|--------|-------|
| 8.1 | Escapar todo el output | `PENDING` | esc_html(), esc_attr(), esc_url(), wp_kses() |
| 8.2 | Sanitizar todo el input | `PENDING` | sanitize_text_field(), absint(), sanitize_email() |
| 8.3 | Nonces en todos los formularios | `PENDING` | wp_nonce_field() + wp_verify_nonce() |
| 8.4 | Verificación de capabilities | `PENDING` | current_user_can() antes de cada acción |
| 8.5 | $wpdb->prepare() en todas las queries | `PENDING` | Sin excepciones |
| 8.6 | defined('ABSPATH') en todos los archivos | `PENDING` | Línea 1 de cada archivo PHP |
| 8.7 | REST permission callbacks | `PENDING` | permission_callback en cada endpoint |

### 8B — Rendimiento

| ID | Tarea | Estado | Notas |
|----|-------|--------|-------|
| 8.8 | Sin N+1 queries, caching con transients | `PENDING` | Transient caching para APIs externas |
| 8.9 | Carga condicional de assets | `PENDING` | CSS/JS solo donde se necesitan |
| 8.10 | Índices DB en tablas custom | `PENDING` | En todas las columnas de búsqueda |

### 8C — Compatibilidad

| ID | Tarea | Estado | Notas |
|----|-------|--------|-------|
| 8.11 | PHPCS zero errores | `PENDING` | WordPress-Extra ruleset |
| 8.12 | Test: PHP 8.0, 8.1, 8.2, 8.3 | `PENDING` | Compatibilidad multi-versión |
| 8.13 | Test: HPOS habilitado | `PENDING` | High Performance Order Storage |
| 8.14 | Test: Checkout de bloques | `PENDING` | WooCommerce Blocks |
| 8.15 | Test: Los 3 adaptadores de suscripciones | `PENDING` | WCS, YITH, Advanced Subs |

### 8D — Documentación y Calidad

| ID | Tarea | Estado | Notas |
|----|-------|--------|-------|
| 8.16 | PHPDoc completo | `PENDING` | Todas las clases y métodos |
| 8.17 | README.md + CHANGELOG.md | `PENDING` | Documentación del proyecto |
| 8.18 | Documento de referencia de hooks | `PENDING` | Todos los actions y filters |
| 8.19 | Guía de desarrollo | `PENDING` | Cómo crear providers, registrars, adapters custom |
| 8.20 | Generar hostforge.pot | `PENDING` | Archivo de traducciones |

---

## Resumen de Progreso

| Fase | Descripción | Prioridad | Tareas | Completadas | Progreso |
|------|-------------|-----------|--------|-------------|----------|
| 1 | Core y Fundamentos | CRÍTICA | 18 | 18 | 100% |
| 2 | Tipos de Producto WooCommerce | CRÍTICA | 16 | 16 | 100% |
| 3 | Server Manager (cPanel/Plesk) | ALTA | 20 | 20 | 100% |
| 4 | Auto Provisioning | ALTA | 24 | 24 | 100% |
| 5 | Support Desk (Tickets + KB) | MEDIA | 23 | 0 | 0% |
| 6 | Domain Manager | MEDIA | 18 | 0 | 0% |
| 7 | Módulos Adicionales | NORMAL | 19 | 0 | 0% |
| 8 | Testing, Seguridad y Polish | CRÍTICA | 20 | 0 | 0% |
| **TOTAL** | | | **158** | **78** | **49%** |

---

## Reglas Fundamentales de Desarrollo

| # | Regla | Detalle |
|---|-------|---------|
| 1 | WordPress Coding Standards | PHPCS con ruleset `WordPress-Extra` |
| 2 | Seguridad WordPress | Escapar output, sanitizar input, nonces, capabilities |
| 3 | Prefijo | `hf_`, `hostforge_` o namespace `HostForge\\` |
| 4 | PHP 8.0+ | Type hints, return types obligatorios |
| 5 | Strings en inglés | `__()`, `_e()`, textdomain `hostforge` |
| 6 | Sin jQuery | Vanilla JS o Alpine.js |
| 7 | Assets condicionales | CSS/JS solo en páginas necesarias |
| 8 | Action Scheduler | NUNCA usar WP-Cron |
| 9 | HPOS | `$order->get_meta()` / `$order->update_meta_data()` |
| 10 | Blocks Checkout | Compatible con checkout de bloques |
| 11 | Sin facturación | No facturas, no impuestos, no PDFs |

---

## Esquema de Base de Datos por Módulo

### Core (siempre se crean)
- `{prefix}hf_logs` — Registros del sistema (module, level, message, context)
- `{prefix}hf_activity_log` — Log de actividad (user_id, action, object_type, ip_address)

### Auto Provisioning
- `{prefix}hf_provisioning_queue` — Cola de aprovisionamiento (service_id, action, status, attempts)

### Domain Manager
- `{prefix}hf_dns_records` — Registros DNS (domain_id, type, name, value, ttl, priority)

### Affiliates
- `{prefix}hf_affiliates` — Afiliados (user_id, status, commission_type/rate, referral_code)
- `{prefix}hf_commissions` — Comisiones (affiliate_id, order_id, amount, status)
- `{prefix}hf_referrals` — Referidos (affiliate_id, visitor_ip, referral_code, converted_user_id)

### Security
- `{prefix}hf_login_attempts` — Intentos de login (ip_address, username, status)
- `{prefix}hf_ip_blocks` — IPs bloqueadas (ip_address, reason, expires_at)

---

*Documento generado a partir del plan maestro de desarrollo — HostForge for WooCommerce — Febrero 2026*
