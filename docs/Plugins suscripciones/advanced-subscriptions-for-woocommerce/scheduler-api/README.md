# Plan de refactorización: Scheduler API

Este documento recoge todo el trabajo necesario para extraer en una librería independiente
toda la lógica de acciones programadas usada por Advanced Subscriptions For WooCommerce. Servirá como
referencia para futuras Pull Requests.

La documentación detallada de la API se encuentra en
[scheduler-api-documentation.md](scheduler-api-documentation.md).

## Objetivo

* Centralizar en `scheduler-api` la creación, cancelación y ejecución de acciones
  programadas (renovaciones, reintentos, cancelaciones, notificaciones, etc.).
* El archivo principal del plugin deberá cargar esta librería y **ninguna parte de la
  programación de acciones quedará fuera de ella**.

## Normas

* El directorio `scheduler-api` no debe contener código _legacy_, marcado como
  `deprecated` ni utilidades de migración (updaters). Cualquier aparición debe
  eliminarse inmediatamente.
* Las funciones esenciales deben depender únicamente de la API central, sin
  consultar clases externas de otros plugins.

## Estructura propuesta

```
scheduler-api/
│
├── core/                # Funciones genéricas de programación (envoltorio de Action Scheduler)
├── lifecycle/           # Acciones relativas al ciclo de vida de la suscripción
├── payments/            # Renovaciones y reintentos
├── notifications/       # Avisos al cliente
├── background/          # Procesos en segundo plano
└── README.md            # Este documento
```

## Código a migrar

### 1. Planificador central

| Archivo actual | Elementos a mover | Notas |
| -------------- | ---------------- | ----- |
| `includes/core/class-wcs-action-scheduler.php` | Clase `WCS_Action_Scheduler` con métodos `update_date`, `update_status`, `schedule_action`, `unschedule_actions`, `get_action_args`, `get_action_priority`… | Base de la API. Deberá convertirse en un servicio reutilizable dentro de `scheduler-api/core`.

### 2. Pagos y reintentos

| Archivo actual | Elementos a mover | Notas |
| -------------- | ---------------- | ----- |
| `includes/gateways/class-wc-subscriptions-payment-gateways.php` | Gancho `advanced_scheduled_subscription_payment` y funciones asociadas (`gateway_scheduled_subscription_payment`, `trigger_gateway_renewal_payment_hook`) | El agendado del pago deberá delegar en la API y las pasarelas sólo reaccionarán al hook ejecutado.
| `includes/payment-retry/class-wcs-retry-manager.php` | Programación del hook `advanced_scheduled_subscription_payment_retry`, método `maybe_retry_payment` y utilidades relacionadas (`maybe_apply_retry_rule`, `maybe_delete_payment_retry_date`…) | Refactorizar para que los reintentos se programen mediante la API.
| `includes/core/class-wcs-failed-scheduled-action-manager.php` | Manejo de errores de acciones programadas (`action_scheduler_failed_action`, etc.) | Integrar como parte del módulo de pagos para centralizar el registro de fallos.

### 3. Ciclo de vida de la suscripción

| Archivo actual | Elementos a mover | Notas |
| -------------- | ---------------- | ----- |
| `includes/core/class-wc-subscriptions-manager.php` | Callbacks para `advanced_scheduled_subscription_expiration`, `advanced_scheduled_subscription_end_of_prepaid_term`, `advanced_scheduled_subscription_trial_end`, así como la lógica de `prepare_renewal` | Todo el agendado y las funciones que se ejecutan deben residir en `scheduler-api/lifecycle`. La antigua `maybe_process_failed_renewal_for_repair` se eliminó por ser código de reparación legado.
| `includes/core/class-wcs-action-scheduler.php` | Llamadas a `unschedule_actions`/`schedule_action` al actualizar fechas/estados | Trasladar a la capa API y exponer funciones simples (`schedule_expiration`, `cancel_expiration`, etc.).

### 4. Notificaciones al cliente

| Archivo actual | Elementos a mover | Notas |
| -------------- | ---------------- | ----- |
| `includes/core/class-wcs-action-scheduler-customer-notifications.php` | Clase completa: cálculo de offsets, programación y cancelación de `advanced_scheduled_subscription_customer_notification_*` | Reubicar en `scheduler-api/notifications` con una interfaz pública (`schedule_notifications`, `unschedule_notifications`).
| `includes/core/class-wc-subscriptions-email-notifications.php` | Ganchos `advanced_scheduled_subscription_customer_notification_*` y método `send_notification` | El envío seguirá aquí, pero el registro de ganchos y programación se moverá a la API.

### 5. Procesos en segundo plano y utilidades

| Archivo actual | Elementos a mover | Notas |
| -------------- | ---------------- | ----- |
| `includes/core/abstracts/abstract-wcs-background-updater.php` y `abstract-wcs-background-repairer.php` | Métodos `schedule_background_updates`, `unschedule_background_updates` | Unificar en utilidades del API para que cualquier proceso en segundo plano use las mismas funciones.
| `includes/core/class-wcs-batch-processing-controller.php` | Programación del watchdog y procesado por lotes (`as_schedule_single_action`, `as_unschedule_all_actions`, etc.) | Extraer a utilidades de la API.
| `includes/core/privacy/class-wcs-privacy-background-updater.php` | Programación/ cancelación de anonimización (`as_schedule_single_action`, `as_unschedule_action`) | Reutilizar las funciones genéricas del API.


### 6. Prefijos

Todos los prefijos de funciones, clases, constantes, variables y archivos dentro de scheduler-api debe ser ASWC. Seguramente son ahora WCS, los que tengan este prefijo debe ser modificado a ASWC (sean mayusculas o minusculas). ASí mismo, todo lo que tenga advanced_subscriptions_woocommerce_* debe pasar a advanced_subscriptions_woocommerce_*

Cuidado que cuando se realicen estos cambios se debe impleemntar en el resto del pligin para no romper la consistencia de todo el plungin.

## Pasos de implementación

1. **Crear esqueleto de la librería** en `scheduler-api/` siguiendo la estructura propuesta.
2. **Mover `WCS_Action_Scheduler`** y adaptarlo como clase base `Scheduler` del API.
3. **Exponer funciones públicas** en la nueva librería (`schedule_payment`, `schedule_retry`, `schedule_expiration`, `schedule_notifications`, etc.).
4. **Refactorizar clases existentes** para que invoquen a la API en lugar de llamar directamente a Action Scheduler.
5. **Eliminar lógica duplicada** de los archivos originales una vez migrada.
6. **Actualizar el cargador del plugin** (`woocommerce-subscriptions.php` o clase bootstrap) para incluir la nueva librería.
7. **Añadir pruebas y documentación** a medida que se migra cada bloque.

8. **Control de empaquetado** añadir al archivo .gitattributes todo lo que no debe llegar a la versión final empaquetada para su liberación.

9. **Redactar una guía completa de la API** que detalle su funcionamiento, hooks, filtros y ejemplos de uso antes de crear la suite de pruebas. Esta documentación podrá dividirse en varios PRs si resulta demasiado extensa.

10. **Desarrollar la suite de pruebas PHPUnit** una vez finalizada la migración y la documentación.

## Seguimiento

En este documento se irán marcando las tareas completadas en futuros PRs.

- [x] Crear esqueleto inicial de `scheduler-api`.
    - [x] Migrar `WCS_Action_Scheduler` (en progreso: extraída lógica básica, resolución de hooks a `core/`, delegación de pagos y reintentos a `scheduler-api/payments` incluyendo actualizaciones de estado, delegación de eventos del ciclo de vida (`trial_end` y `end`) a `scheduler-api/lifecycle`, y encapsulación de la consulta de acciones programadas; se añadió utilidad `reschedule_action` en `core`, ahora optimizada para evitar reprograciones innecesarias, y se refactorizaron los planificadores para eliminar duplicación; `update_date` y `update_status` ahora usan `reschedule_action` para unificar el agendado genérico, además de aprovechar `schedule_all` de pagos y ciclo de vida para simplificar la activación de suscripciones. La API ahora expone `get_scheduled_events` para obtener una vista unificada de los pagos y eventos del ciclo de vida y `WCS_Action_Scheduler` delega en ella; se incorporó `last_scheduled_action` y `unschedule_actions` permite ignorar el grupo para facilitar migraciones, además de añadir `schedule_all` y `unschedule_all` en `ASWC_Scheduler_API` para gestionar pagos, ciclo de vida y notificaciones desde un único punto, **permitiendo además que `schedule_all` acepte un grupo personalizado para agendar los eventos conjuntamente**; se añadió `get_all_scheduled_events` para combinar pagos, ciclo de vida y notificaciones y `WCS_Action_Scheduler::get_scheduled_events` ahora lo utiliza, además de `get_all_last_scheduled_events` para reunir los eventos más recientes de estos módulos; la API incorpora `has_scheduled_events` para comprobar si existen pagos, eventos del ciclo de vida o notificaciones programadas, `unschedule_core_group` para vaciar por completo los eventos de suscripción y añade `unschedule_action` para eliminar una única acción programada; ahora también expone `schedule_action`, `enqueue_async_action`, `reschedule_action`, `next_scheduled_action`, `last_scheduled_action`, `get_scheduled_action`, `get_scheduled_actions` y `get_action_args` para manipular estas acciones desde un único punto, y ahora expone `get_action` para recuperar acciones por ID, con `WCS_Failed_Scheduled_Action_Manager` utilizando este wrapper para acceder a los metadatos de fallos sin instanciar el núcleo, y los helpers aceptan ahora un parámetro de grupo opcional para orientar o ignorar grupos al consultar o reprogramar acciones. Asimismo, `schedule_action` permite especificar un grupo personalizado al crear nuevas acciones, **`unschedule_core_group` ahora acepta un parámetro de grupo opcional para limpiar grupos personalizados**, y `WCS_Action_Scheduler` incorpora `get_last_scheduled_events` como alias de `get_all_last_scheduled_events` para acceder a los últimos pagos, eventos del ciclo de vida y notificaciones programados desde un único punto, **además de agregadores `get_scheduled_subscription_actions` y `get_all_scheduled_subscription_actions` junto con sus variantes `get_last_scheduled_subscription_actions` y `get_all_last_scheduled_subscription_actions`, y el módulo de pagos añade `get_scheduled_payment_actions` y `get_last_scheduled_payment_actions` para inspeccionar directamente estos objetos**). Se reemplazó la instanciación del planificador por `ASWC_Scheduler_Subscription_Hooks` y se eliminaron los shims `WCS_Action_Scheduler` y `WCS_Action_Scheduler_Customer_Notifications`.
    - La clase `WCS_Action_Scheduler` expone ahora wrappers `get_scheduled_subscription_actions`, `get_all_scheduled_subscription_actions`, `get_last_scheduled_subscription_actions` y `get_all_last_scheduled_subscription_actions` que delegan en la API central.
    - El grupo predeterminado del núcleo pasó a ser `aswc_subscription_scheduled_event` para unificar los prefijos y ahora puede recuperarse mediante `ASWC_Scheduler_Core::get_group()`.
    - Se eliminó la propiedad obsoleta `$action_hooks` de `WCS_Action_Scheduler` al delegar los mapeos de hooks en la API central.
   - [x] Migrar programación de pagos y reintentos (clase `ASWC_Scheduler_Payments` programa renovaciones y reintentos básicos, ahora también ofrece `unschedule_all` para cancelar pagos y reintentos en bloque y `schedule_all` para programarlos conjuntamente, además de permitir especificar un grupo personalizado al programar eventos y de exponer `get_scheduled_payment`, `get_scheduled_retry`, `get_scheduled_payments`, `has_scheduled_payment`, `has_scheduled_retry`, `has_scheduled_payments`, `last_scheduled_payment`, `last_scheduled_retry`, `get_last_scheduled_payments`, `get_scheduled_payment_action`, `get_scheduled_retry_action`, `get_last_scheduled_payment_action`, `get_last_scheduled_retry_action`, `get_scheduled_payment_actions`, `get_last_scheduled_payment_actions` y `get_scheduled_retry_actions` para inspeccionar o comprobar fácilmente los eventos agendados; `WCS_Retry_Manager` y `WCS_Action_Scheduler` ya usan la API para planificar y cancelar reintentos y ahora cargan la librería desde el punto de entrada centralizado; `WCS_Failed_Scheduled_Action_Manager` obtiene las acciones fallidas a través de la API; se añadieron pruebas para pagos manuales, reintentos basados en reglas y operaciones en bloque con `schedule_all`/`unschedule_all` en grupos personalizados y al pasar `false` como grupo para cubrir acciones en todos los grupos; **`schedule_payment`, `schedule_manual_payment` y `schedule_retry` ahora limpian acciones existentes en todos los grupos antes de programar nuevas para evitar duplicados** y se revisó la interacción con pasarelas externas que programan reintentos en grupos personalizados tras el procesamiento del pago.).
   - La API expone también helpers directos (`schedule_payment`, `unschedule_payment`, `schedule_retry`, `schedule_retry_after`, `schedule_retry_with_rule`, `unschedule_retry`, `get_scheduled_payment`, `get_scheduled_retry`, `get_scheduled_payment_action`, `get_scheduled_retry_action`, `get_last_scheduled_payment_action`, `get_last_scheduled_retry_action`, `get_scheduled_payment_actions`, `get_last_scheduled_payment_actions`, `get_scheduled_retry_actions`, `get_scheduled_payments`, `has_scheduled_payment`, `has_scheduled_retry`, `has_scheduled_payments`, `schedule_all_payments` y `unschedule_all_payments`) y las clases consumidoras utilizan ahora estos wrappers en lugar de instanciar el módulo de pagos.
   - Se añadieron funciones wrapper `aswc_schedule_payment`, `aswc_schedule_manual_payment`, `aswc_schedule_retry`, `aswc_schedule_retry_with_rule`, `aswc_schedule_retry_after`, `aswc_unschedule_payment`, `aswc_unschedule_retry`, `aswc_schedule_all_payments`, `aswc_unschedule_all_payments`, `aswc_get_scheduled_payment`, `aswc_get_scheduled_retry`, `aswc_get_scheduled_payments`, `aswc_get_last_scheduled_payments`, `aswc_get_scheduled_payment_action`, `aswc_get_scheduled_retry_action`, `aswc_get_scheduled_payment_actions`, `aswc_get_last_scheduled_payment_action`, `aswc_get_last_scheduled_retry_action`, `aswc_get_last_scheduled_payment_actions`, `aswc_has_scheduled_payment`, `aswc_has_scheduled_retry` y `aswc_has_scheduled_payments` para facilitar el acceso procedimental a la API.
   - Los helpers de pagos aceptan ahora un parámetro de grupo opcional para orientar o ignorar grupos al consultar, desprogramar **y programar** acciones.
   - Se documentó que pasar `false` como grupo permite buscar o borrar acciones sin limitarse a un grupo específico, cubriendo casos avanzados.
   - Se añadieron pruebas para `get_scheduled_payment_actions`, `get_last_scheduled_payment_actions` y `get_scheduled_retry_actions` que validan la recuperación de los objetos de acciones programadas.
   - Se añadieron pruebas que verifican la programación y cancelación de pagos y reintentos mediante `ASWC_Scheduler_Payments`.
   - Se añadieron pruebas para confirmar que el parámetro de grupo `false` permite gestionar pagos a través de todos los grupos.
   - Se añadieron pruebas que validan las operaciones cruzadas de reintentos utilizando `false` para abarcar todos los grupos.
   - Se añadió el helper `schedule_retry_after` con pruebas que verifican la programación de reintentos mediante un intervalo personalizado y su exposición a través de la API.
   - Se validó que `schedule_retry_after` no programe reintentos cuando el intervalo es menor o igual a 0, limpiando cualquier reintento existente.
   - Se añadió soporte para que `schedule_retry_with_rule` desprograme reintentos cuando la regla devuelva un intervalo menor o igual a 0, con pruebas que confirman este comportamiento también a través de la API.
   - Los métodos de programación aceptan ahora `false` para limpiar acciones en todos los grupos antes de reprogramar en el grupo por defecto, con pruebas que confirman este comportamiento.
   - Se añadieron pruebas que confirman que `schedule_payment` y `schedule_retry` eliminan acciones existentes de todos los grupos antes de programar nuevas.
   - Se añadieron pruebas y se documentó que `schedule_manual_payment` acepta `false` como grupo para limpiar acciones en todos los grupos antes de reprogramar en el grupo por defecto.
   - [x] Evitada la programación de pagos o reintentos cuando el timestamp es inválido (0 o vacío), desprogramando cualquier acción existente para el tipo correspondiente.
   - Las clases de reintentos (`WCS_Retry`, `WCS_Retry_Manager`, `WCS_Retry_Database_Store`, `WCS_Retry_Hybrid_Store`, `WCS_Retry_Post_Store`, `WCS_Retry_Email` y la plantilla `html-retries-table.php`) utilizan ahora los helpers de `ASWC_Scheduler_API` para acceder a los datos y estados de las reglas en lugar de llamar a los métodos del objeto de regla directamente.
   - Reemplazadas las llamadas directas a `ASWC_Scheduler_API::payments()` en `WC_Subscriptions_Payment_Gateways`, `WCS_Retry_Manager` y `WCS_Early_Renewal_Modal_Handler` por los wrappers públicos de la API (`gateway_scheduled_subscription_payment`, `trigger_gateway_renewal_payment_hook`, `has_scheduled_retry`, `unschedule_retry`, `schedule_retry_with_rule`).
       - Se eliminó la llamada residual a `ASWC_Scheduler_API::payments()` en `WC_Subscriptions_Payment_Gateways::init` y la inicialización del módulo se delegó a `WC_Subscriptions_Core_Plugin`.
   - [x] El manejador `gateway_scheduled_subscription_payment` ahora desprograma reintentos en todos los grupos antes de procesar un pago para evitar dobles cargos.
   - [x] Revisar la interacción con pasarelas externas que programen reintentos en grupos personalizados tras el procesamiento del pago, añadiendo una prueba que confirma que los reintentos agendados por las pasarelas permanecen activos.
   - Los metaboxes de administración delegan ahora en la API para procesar pagos y reintentos manuales y se añadió `has_gateway_renewal_payment_hook` para comprobar la existencia de hooks de pasarela sin usar `has_action` directamente.
 - [x] Migrar eventos del ciclo de vida de la suscripción (en progreso: `ASWC_Scheduler_Lifecycle` maneja fin de prueba y expiraciones básicas, ahora `schedule_all` también programa `end_of_prepaid_term` para suscripciones canceladas o pendientes y limpia los hooks opuestos; ofrece `unschedule_all` para limpiar eventos, expone `get_scheduled_trial_end`, `get_scheduled_expiration`, `get_scheduled_end_of_prepaid_term`, `has_scheduled_trial_end`, `has_scheduled_expiration`, `has_scheduled_end_of_prepaid_term`, `get_scheduled_events`/`has_scheduled_events`, **`last_scheduled_trial_end`, `last_scheduled_expiration`, `last_scheduled_end_of_prepaid_term` y `get_last_scheduled_events`** para inspeccionar también el historial de eventos, y se usa desde `WCS_Action_Scheduler` al activar suscripciones. La API central añade helpers directos `schedule_trial_end`, `unschedule_trial_end`, `schedule_expiration`, `unschedule_expiration`, `schedule_end_of_prepaid_term`, `unschedule_end_of_prepaid_term`, `get_scheduled_trial_end`, `get_scheduled_expiration`, `get_scheduled_end_of_prepaid_term`, `has_scheduled_trial_end`, `has_scheduled_expiration`, `has_scheduled_end_of_prepaid_term`, **`last_scheduled_trial_end`, `last_scheduled_expiration`, `last_scheduled_end_of_prepaid_term`, `get_last_scheduled_lifecycle_events`, `get_scheduled_trial_end_action`, `get_scheduled_expiration_action`, `get_scheduled_end_of_prepaid_term_action`, `get_last_scheduled_trial_end_action`, `get_last_scheduled_expiration_action`, `get_last_scheduled_end_of_prepaid_term_action`, `get_scheduled_lifecycle_actions` y `get_last_scheduled_lifecycle_actions`** y `has_scheduled_lifecycle_events` para gestionar estos eventos sin instanciar el módulo de ciclo de vida, y `WCS_Action_Scheduler` los utiliza; los callbacks `expire_subscription`, `subscription_end_of_prepaid_term` y `trigger_subscription_trial_ended_hook` se trasladaron a `ASWC_Scheduler_Lifecycle_Events` y `WC_Subscriptions_Manager` ahora delega en la API. Se eliminó la lógica de reparación `_wcs_repaired_2_0_2_needs_failed_payment` y su metadato asociado por tratarse de código legado. Los métodos de programación ahora desprograman automáticamente los eventos cuando se les pasa un timestamp vacío o no válido.).
   - La API también provee `schedule_all_lifecycle_events` y `unschedule_all_lifecycle_events` para gestionar en bloque los eventos del ciclo de vida, **y los helpers aceptan ahora un parámetro de grupo opcional para orientar o ignorar grupos al consultar, desprogramar y programar acciones.**
   - Se añadieron pruebas que validan que `unschedule_all` elimina los eventos programados en todos los grupos al pasar `false` como grupo.
    - [x] Migrar notificaciones al cliente.
        - `ASWC_Scheduler_Notifications` ahora expone `schedule_notification`, `schedule_notifications`, `schedule_all`, `unschedule_notification`, `unschedule_notifications`, `unschedule_all`, `get_scheduled_notification`, `get_scheduled_notifications`, `last_scheduled_notification`, `get_last_scheduled_notifications`, `get_scheduled_notification_action`, `get_last_scheduled_notification_action`, `get_scheduled_notification_actions`, `get_last_scheduled_notification_actions`, `has_scheduled_notification` y `has_scheduled_notifications`.
        - Los helpers de notificaciones aceptan ahora un parámetro de grupo opcional para orientar o ignorar grupos al consultar, desprogramar y programar acciones.
        - Se añadió una prueba para el filtro `aswc_subscription_customer_notification_statuses` que verifica que solo se programen avisos en los estados permitidos.
        - Se añadieron pruebas de los wrappers de la API para recuperar acciones programadas y sus últimas ejecuciones respetando grupos personalizados.
        - `ASWC_Scheduler_Notifications` expone `get_allowed_notification_statuses` y la API central ofrece su wrapper con pruebas correspondientes.
        - `get_allowed_notification_statuses` ahora sanea y elimina duplicados de los estados devueltos para garantizar resultados consistentes.
        - Se añadió wrapper procedimental `aswc_get_allowed_notification_statuses` que delega en `ASWC_Scheduler_API::get_allowed_notification_statuses`.
        - `ASWC_Scheduler_Notifications` expone `get_offset_option_name` y la API central ofrece su wrapper `get_notification_offset_option_name` para acceder al nombre de la opción de tiempo de notificación.
        - `ASWC_Scheduler_Notifications` expone `get_switch_option_name` y la API central ofrece su wrapper `get_notification_switch_option_name` para acceder al nombre de la opción que habilita globalmente las notificaciones.
        - `ASWC_Scheduler_Notifications` expone `get_settings_update_time_option_name` y la API central ofrece su wrapper `get_notification_settings_update_time_option_name` para acceder al nombre de la opción que almacena la última actualización de la configuración.
        - Los wrappers procedimentales para estos nombres de opción delegan ahora en `ASWC_Scheduler_API` en lugar de referenciar directamente a `ASWC_Scheduler_Notifications`.
        - `ASWC_Scheduler_Notifications` expone `get_option_prefix` y la API central ofrece su wrapper `get_notification_option_prefix` para construir nombres de opciones sin referenciar directamente `OPTION_PREFIX`, actualizando `WC_Subscriptions_Email_Notifications`.
          - `ASWC_Scheduler_Notifications` define internamente las claves de configuración y `notifications_globally_enabled` para eliminar la dependencia de `WC_Subscriptions_Email_Notifications`.
          - `register_email_hooks` ahora utiliza el wrapper `aswc_send_notification` en lugar de llamar directamente a `WC_Subscriptions_Email_Notifications::send_notification`.
          - Se añadieron pruebas para `aswc_send_notification` que verifican la delegación a `WC_Subscriptions_Email_Notifications::send_notification` cuando la clase está disponible.
          - Se añadieron pruebas para `get_switch_option_name` y su wrapper `get_notification_switch_option_name`.
          - `ASWC_Notifications_Batch_Processor`, `ASWC_Notifications_Debug_Tool_Processor`, `WC_Subscriptions_Email_Notifications`, `WC_Subscriptions_Tracker` y `WC_Subscriptions_Core_Plugin` usan ahora los helpers de la API para obtener nombres de opciones en lugar de referenciar directamente `OPTION_PREFIX`.
        - `ASWC_Scheduler_Notifications` expone `get_action_from_date_type` y la API central ofrece su wrapper `get_notification_hook_from_date_type` para convertir tipos de fecha en hooks, con pruebas correspondientes.
        - `WC_Subscriptions_Email_Notifications` y `ASWC_Notifications_Batch_Processor` utilizan ahora este wrapper en lugar de cadenas de hook codificadas.
        - Se añadieron pruebas para `schedule_notifications` y `unschedule_notifications` que verifican la programación y cancelación de múltiples avisos, incluyendo la delegación a grupos personalizados mediante los wrappers de la API.
        - Se añadió wrapper procedimental `aswc_convert_notification_offset_to_seconds` que delega en `ASWC_Scheduler_API::convert_offset_to_seconds` para convertir los offsets configurados a segundos, con pruebas correspondientes.
        - Se añadió wrapper procedimental `aswc_unschedule_notification_group` que delega en `ASWC_Scheduler_API::unschedule_notification_group` para limpiar el grupo de notificaciones desde código externo.
        - Se añadieron pruebas para `aswc_unschedule_notification_group` que verifican la delegación del grupo personalizado.
        - Se añadieron wrappers procedimentales para programar, desprogramar e inspeccionar notificaciones (`aswc_schedule_notification`, `aswc_schedule_notifications`, `aswc_schedule_all_notifications`, `aswc_unschedule_notification`, `aswc_unschedule_notifications`, `aswc_unschedule_all_notifications`, `aswc_get_scheduled_notification`, `aswc_get_scheduled_notifications`, `aswc_get_scheduled_notification_action`, `aswc_get_scheduled_notification_actions`, `aswc_last_scheduled_notification`, `aswc_get_last_scheduled_notifications`, `aswc_get_last_scheduled_notification_action`, `aswc_get_last_scheduled_notification_actions`, `aswc_has_scheduled_notification` y `aswc_has_scheduled_notifications`).
        - `ASWC_Notifications_Batch_Processor` y `ASWC_Notifications_Debug_Tool_Processor` utilizan ahora `aswc_notifications_globally_enabled()` en lugar de `WC_Subscriptions_Email_Notifications::notifications_globally_enabled`.
          - Estos procesadores también utilizan `ASWC_Scheduler_API::sanitize_subscription_status_key` para sanear los estados en lugar de `wcs_sanitize_subscription_status_key`.
            - `ASWC_Notifications_Batch_Processor` y `ASWC_Notifications_Debug_Tool_Processor` obtienen ahora las suscripciones mediante `ASWC_Scheduler_API::get_subscription` en lugar de llamar directamente a `wcs_get_subscription`.
            - Los identificadores internos de estos procesadores y la opción de estado del depurador utilizan ahora el prefijo `aswc_` para unificar el espacio de nombres.
            - `WC_Subscriptions_Email_Notifications` delega ahora en `ASWC_Scheduler_API` para obtener suscripciones, comprobar si un objeto es una suscripción y verificar si las notificaciones están habilitadas globalmente.
            - `WCS_Email_Customer_Notification` obtiene ahora la suscripción mediante `ASWC_Scheduler_API::get_subscription` en lugar de `wcs_get_subscription`.
            - `notifications_globally_enabled` usa la configuración predeterminada cuando no existe opción de offset y cuenta con pruebas que validan este comportamiento y la desactivación mediante el interruptor global.
            - Se eliminaron las propiedades estáticas heredadas `$offset_setting_string` y `$switch_setting_string` de `WC_Subscriptions_Email_Notifications` tras migrar los nombres de opciones a la API.
        - `ASWC_Scheduler_Notifications` expone `get_settings_update_time` y la API central ofrece su wrapper `get_notification_settings_update_time` junto al helper `aswc_get_notification_settings_update_time`; `ASWC_Notifications_Batch_Processor` usa ahora este wrapper en lugar de acceder directamente a la opción.
        - Renombrados `WCS_Notifications_Batch_Processor` y `WCS_Notifications_Debug_Tool_Processor` al prefijo `ASWC_` y se añadieron shims de compatibilidad.
        - Las acciones del administrador `wcs_customer_notification_*` se renombraron a `aswc_customer_notification_*` con ganchos heredados para mantener la compatibilidad y se añadió un aviso de deprecación al usar los antiguos ganchos.

   - [x] Migrar procesos en segundo plano (en progreso: `WCS_Background_Updater`, `WCS_Background_Repairer`, `WCS_Batch_Processing_Controller` y `WCS_Privacy_Background_Updater` ahora emplean la API central para planificar y cancelar acciones; **`WCS_Privacy_Background_Updater` utiliza los helpers de la API directamente sin mantener su propia instancia del programador**; el limpiado masivo de tareas se gestiona mediante `unschedule_group` disponible en el núcleo y `WCS_Batch_Processing_Controller` lo usa para limpiar procesos; `WCS_Report_Cache_Manager` utiliza el programador de fondo para actualizar cachés, `WCS_Cached_Data_Manager` programa el saneamiento de logs con la API y se añadió `has_scheduled_action` en el núcleo para simplificar comprobaciones de existencia, ya integrado en estos controladores; la API añade `unschedule_background_group` para eliminar de forma centralizada cualquier proceso en segundo plano **y permite especificar un grupo personalizado para limpiar acciones heredadas**; `WCS_Retry_Background_Migrator` renombró su hook a `aswc_retries_migration_hook` y aprovecha el logger inyectado para delegar en la API. `WCS_Cached_Data_Manager` ahora verifica acciones programadas antes de registrar nuevas limpiezas de log para evitar duplicados; se revisará si quedan otros controladores por adaptar).
      expone helpers directos (`schedule_background_action`, `reschedule_background_action`, `unschedule_background_action`, `has_scheduled_background_action`, `next_scheduled_background_action`, `last_scheduled_background_action`, `get_scheduled_background_action`, `get_scheduled_background_actions`, `get_last_scheduled_background_action`, `get_last_scheduled_background_actions`) que ahora aceptan un parámetro de grupo opcional para programar acciones en grupos personalizados y son utilizados por `WCS_Report_Cache_Manager`, `WCS_Cached_Data_Manager`;
      se añadieron wrappers procedimentales para estas utilidades —incluyendo `aswc_schedule_background_action` y `aswc_unschedule_background_group`— junto con pruebas que validan la delegación.
      se añadieron pruebas adicionales para `get_scheduled_background_action`, `get_scheduled_background_actions`, `get_last_scheduled_background_action` y `get_last_scheduled_background_actions` comprobando la delegación y el soporte de grupos personalizados.
      `WCS_Batch_Processing_Controller` deja de definir su propio grupo y reutiliza el del programador central, ahora consumiendo `ASWC_Scheduler_API` directamente para programar y limpiar sus acciones; el actualizador base ahora obtiene `ASWC_Scheduler_Background` en lugar del núcleo para que las herramientas y migraciones usen el grupo dedicado; las clases base de actualizaciones y reparaciones en segundo plano invocan directamente los helpers de la API eliminando la dependencia explícita del programador; pendiente adaptar otros controladores y utilidades).
- [x] Actualizar archivo principal del plugin para usar la API.
  - La API ahora se carga desde `woocommerce-subscriptions.php` usando un único punto de entrada en `scheduler-api/scheduler.php`.
    - Se eliminaron las inclusiones directas de `scheduler-api/scheduler.php` en clases y scripts; todos consumen la API desde el cargador central.
    - Se eliminaron las clases de compatibilidad `WCS_Action_Scheduler` y `WCS_Action_Scheduler_Customer_Notifications`.
    - Se verificó que `woocommerce-subscriptions.php` sea la **única** inclusión de la API.
  - Al desactivar el plugin se vacían todos los grupos de acciones programadas mediante la API usando el nuevo helper `unschedule_all_groups`.
- [x] Control de empaquetado
  - Se añadió `.gitattributes` dentro de `scheduler-api` para excluir sus pruebas y documentación del paquete y se ajustó el archivo raíz para ignorar este archivo. Queda revisar otras exclusiones cuando se añadan nuevos módulos.
  - Se añadieron `phpstan.neon` y `scheduled-actions.md` al `.gitattributes` para excluirlos del paquete final.

 - [x] Añadir pruebas automatizadas para la nueva API (en progreso: se añadieron pruebas básicas para `ASWC_Scheduler_Notifications::convert_offset_to_seconds`, su wrapper `aswc_convert_notification_offset_to_seconds` y ahora también pruebas para `schedule_notifications`, `unschedule_notifications`, `schedule_retry_after` y los wrappers `schedule_all_payments`/`unschedule_all_payments` con grupos personalizados y operaciones cruzadas, además de verificar que `get_scheduled_payment_actions` y `get_last_scheduled_payment_actions` respetan el parámetro de grupo, y se incorporaron pruebas para `schedule_manual_payment` con el grupo `false` tanto en la clase de pagos como a través de la API).
    - Se añadieron pruebas adicionales para `unschedule_all` en el módulo de ciclo de vida que verifican la limpieza de eventos en grupos personalizados.
    - Se añadieron pruebas de los helpers de la API para programar y cancelar pagos y eventos del ciclo de vida.
    - Se añadió una prueba para `ASWC_Scheduler_API::reschedule_action` que verifica la delegación del parámetro de grupo.
    - Se añadieron pruebas para `ASWC_Scheduler_Payments::schedule_all` y `unschedule_all` con grupos personalizados.
    - Se añadió una prueba que cubre operaciones de pagos con `group = false` para gestionar acciones en todos los grupos.
    - Se añadió una prueba que verifica que `schedule_retry_after` desprograma reintentos cuando el intervalo no es positivo.
    - Se añadieron pruebas para `schedule_manual_payment` utilizando `false` como grupo, tanto a nivel de clase como mediante los wrappers de la API.
    - Se añadieron pruebas que validan que `last_scheduled_payment` y `last_scheduled_retry` aceptan el parámetro de grupo y permiten obtener el último evento global al pasar `false`, tanto en la clase como mediante la API.
    - Se añadieron pruebas para programar pagos manuales y reintentos basados en reglas dentro de grupos personalizados.
    - Se añadieron pruebas que confirman que al programar pagos o reintentos con `group = false` se utiliza el grupo por defecto.
    - Se añadieron pruebas para `ASWC_Scheduler_Notifications::notifications_globally_enabled` y su wrapper en la API central, cubriendo el offset predeterminado y la desactivación mediante el interruptor global.
  - Se añadió una prueba para `ASWC_Scheduler_API::is_subscription_period_too_short`.
  - Se añadieron pruebas para `ASWC_Scheduler_API::unschedule_background_action` que verifican la delegación del parámetro de grupo y la limpieza global al pasar `false`.
  - Se añadieron pruebas de los agregadores `get_last_scheduled_subscription_actions` y `get_all_scheduled_subscription_actions` para comprobar la combinación de módulos y la propagación del grupo personalizado.
    - Se añadieron pruebas para `ASWC_Scheduler_Lifecycle_Events::prepare_renewal` y `ASWC_Scheduler_Lifecycle_Events::process_renewal`.
    - Se añadieron pruebas para los wrappers `schedule_all_payments` y `unschedule_all_payments` en la API central.
    - Se añadieron pruebas para `get_scheduled_payment_action` y `get_scheduled_retry_action` que validan la propagación del parámetro de grupo y la búsqueda global al pasar `false`.
    - Se añadieron pruebas que verifican que `schedule_payment`, `schedule_retry` y `schedule_manual_payment` desprograman eventos cuando el timestamp es menor o igual a cero, tanto en la clase de pagos como a través de la API.
    - Se añadieron pruebas para los wrappers de lectura `aswc_get_scheduled_payment_action`, `aswc_get_scheduled_retry_action`, `aswc_get_last_scheduled_payment_action`, `aswc_get_last_scheduled_retry_action`, `aswc_get_scheduled_payment_actions` y `aswc_get_last_scheduled_payment_actions`.
    - Se añadió una prueba que valida `ASWC_Scheduler_API::unschedule_all_payments` con `group = false` para limpiar acciones en todos los grupos.
    - **Pendiente:** ampliar la cobertura con escenarios de integraciones externas y posibles condiciones de carrera.

- [x] Configurar PHPUnit e integración continua para ejecutar las pruebas en los Pull Requests.
  - El flujo `tests.yml` ejecuta PHPUnit en PHP 8.2, 8.3 y 8.4 en cada push y pull request.

  - [x] Encapsular dependencias externas para que `scheduler-api` no dependa de funciones ajenas a WordPress o WooCommerce. Cualquier llamada externa debe implementarse dentro de la librería y cumplir esta misma regla al encapsularse.
    - [x] `wcs_get_subscription` reemplazado por el wrapper `aswc_get_subscription` dentro de `scheduler-api/lifecycle`, que ahora resuelve las suscripciones mediante `wc_get_order` cuando la función original no está disponible
    - [x] Eliminados los wrappers `aswc_get_subscription_from_key` y `aswc_get_subscription_id_from_key` junto con sus métodos públicos
    - [x] Los wrappers de suscripciones se definen de forma condicional para evitar colisiones con funciones externas
    - [x] `WC_Subscriptions_Email_Notifications::send_notification` encapsulado como `aswc_send_notification` y usado en los ganchos de notificaciones
    - [x] `aswc_send_notification` expuesto mediante `ASWC_Scheduler_API::send_notification`
    - [x] `aswc_should_send_notification` delega en `ASWC_Scheduler_API::notifications_globally_enabled` para determinar si debe enviarse una notificación, sin depender de clases externas
    - [x] `wc_get_payment_gateway_by_order` encapsulado como `aswc_get_payment_gateway_by_order` y expuesto mediante `ASWC_Scheduler_API::get_payment_gateway_by_order`, con pruebas y verificación automática de dependencias.
    - [x] Actualizadas las clases del plugin para utilizar `ASWC_Scheduler_API::get_payment_gateway_by_order` en lugar de `wc_get_payment_gateway_by_order`.
    - [x] `wcs_get_subscription_date_types` encapsulado como `aswc_get_subscription_date_types` y usado por la API y el programador de notificaciones
    - [x] `wc_get_order` encapsulado como `aswc_get_order` y expuesto mediante `ASWC_Scheduler_API::get_order`, actualizando `ASWC_Scheduler_Failed_Action_Manager` para utilizarlo
    - [x] `wcs_get_subscription_statuses` encapsulado como `aswc_get_subscription_statuses` y usado por `get_allowed_notification_statuses` para validar estados
    - [x] Método `ASWC_Scheduler_API::get_subscription_status_names` expuesto para obtener los nombres de estado, reemplazando el uso directo de `wcs_get_subscription_statuses` en `WC_API_Subscriptions`, `WC_REST_Subscriptions_Controller`, `WC_Subscriptions_Tracker`, `WCS_Related_Order_Store_Cached_CPT` y `WCS_Orders_Table_Subscription_Data_Store`
    - [x] `wcs_sanitize_subscription_status_key` encapsulado como `aswc_sanitize_subscription_status_key` y utilizado por el programador de notificaciones
    - [x] Métodos `ASWC_Scheduler_API::get_subscription_date_types` y `ASWC_Scheduler_API::get_subscription_statuses` expuestos junto a pruebas que confirman el fallback de los wrappers `aswc_get_subscription_*`
    - [x] Método `ASWC_Scheduler_API::get_subscription` expuesto junto a pruebas que confirman el fallback de su wrapper
    - [x] `wcs_is_subscription` encapsulado como `aswc_is_subscription` y expuesto como `ASWC_Scheduler_API::is_subscription`
    - [x] `wcs_get_objects_property` encapsulado como `aswc_get_objects_property` y expuesto como `ASWC_Scheduler_API::get_objects_property`
    - [x] `wcs_get_subscriptions_for_order`, `wcs_get_subscription_ids_for_order`, `wcs_get_canonical_product_id` y `wcs_get_order_item` encapsulados como `aswc_get_subscriptions_for_order`, `aswc_get_subscription_ids_for_order`, `aswc_get_canonical_product_id` y `aswc_get_order_item` y expuestos en la API central
        - [x] `WC_Subscriptions_Manager` usa ahora `ASWC_Scheduler_API::get_subscriptions_for_order`
        - [x] Las comprobaciones de pedidos (`wcs_order_contains_renewal`, `wcs_order_contains_resubscribe`, `wcs_order_contains_switch` y la limpieza de `WC_Subscriptions_Order`) dependen de `ASWC_Scheduler_API::get_subscriptions_for_order`
    - [x] `wcs_get_subscriptions_for_renewal_order` encapsulado como `aswc_get_subscriptions_for_renewal_order` y expuesto en la API central
    - [x] `wcs_create_renewal_order` encapsulado como `aswc_create_renewal_order` y expuesto en la API central
    - [x] `ActionScheduler_Versions::latest_version` y `ActionScheduler_Store::instance` encapsulados como `aswc_get_latest_action_scheduler_version` y `aswc_get_action_scheduler_store` en `scheduler-api/core`, con acceso desde la API central
    - [x] `ActionScheduler_Store::STATUS_PENDING` encapsulado como `aswc_get_action_scheduler_pending_status` y expuesto en la API central
    - [x] `ActionScheduler_Store::STATUS_COMPLETE`, `STATUS_FAILED`, `STATUS_RUNNING` y `STATUS_CANCELED` encapsulados como `aswc_get_action_scheduler_complete_status`, `aswc_get_action_scheduler_failed_status`, `aswc_get_action_scheduler_running_status` y `aswc_get_action_scheduler_canceled_status`, y expuestos en la API central
    - [x] `ActionScheduler_Store::fetch_action` encapsulado como `aswc_get_action_scheduler_action` y utilizado por el núcleo
    - [x] `ActionScheduler_Logger::instance` encapsulado como `aswc_action_scheduler_log` y expuesto en la API central mediante `ASWC_Scheduler_API::log_action`
    - [x] `WCS_Admin_Notice` encapsulado como `aswc_create_admin_notice` para mostrar avisos sin depender de clases externas
    - [x] `WC_Subscriptions_Plugin::get_plugin_directory` encapsulado como `aswc_get_plugin_directory` y expuesto en la API central
    - [x] Añadida prueba automática que prohíbe instanciar `WC_Subscription` o llamar a sus métodos estáticos directamente dentro de `scheduler-api`
- [x] `wc_get_logger` encapsulado como `aswc_get_logger` y expuesto como `ASWC_Scheduler_API::get_logger`
- [x] Instancias directas de `WC_Logger` y llamadas a `wc_get_logger()` reemplazadas por `ASWC_Scheduler_API::get_logger` en los componentes restantes
- [x] `wc_get_container` y `DataSynchronizer` encapsulados como `aswc_get_orders_data_synchronizer`, usado por `aswc_is_custom_order_tables_data_sync_enabled` y cubierto por pruebas de dependencias externas
    - [x] Reemplazar el uso del helper legado `wcs_get_objects_property` por la API central en las clases del plugin
        - [x] `WCS_Retry_Manager` actualizado para usar `ASWC_Scheduler_API::get_objects_property`
        - [x] Resto de clases del plugin actualizadas para usar `ASWC_Scheduler_API::get_objects_property`
  - [x] Funciones globales de Action Scheduler (`as_schedule_single_action`, `as_schedule_recurring_action`, `as_schedule_cron_action`, `as_schedule_unique_action`, `as_enqueue_async_action`, `as_unschedule_all_actions`, `as_unschedule_action`, `as_next_scheduled_action`, `as_has_scheduled_action`, `as_get_scheduled_actions`) encapsuladas como wrappers `aswc_*` y utilizadas por el núcleo
      - [x] `wcs_date_to_time` encapsulado como `aswc_date_to_time` y expuesto en la API central mediante `ASWC_Scheduler_API::date_to_time`
        - [x] `wcs_get_subscription_period_strings` y `wcs_get_subscription_trial_period_strings` encapsulados como `aswc_get_subscription_period_strings` y `aswc_get_subscription_trial_period_strings`, con wrappers en la API central
        - [x] `wcs_get_subscription_ranges` encapsulado como `aswc_get_subscription_ranges` y expuesto en la API central
        - [x] `wcs_get_subscription_period_interval_strings` encapsulado como `aswc_get_subscription_period_interval_strings` y expuesto en la API central
        - [x] `wcs_get_available_time_periods` encapsulado como `aswc_get_available_time_periods` y expuesto en la API central
        - [x] `wcs_get_subscription_trial_lengths` encapsulado como `aswc_get_subscription_trial_lengths` y expuesto en la API central
        - [x] `wcs_append_numeral_suffix` encapsulado como `aswc_append_numeral_suffix`
        - [x] `wcs-formatting-functions.php` utiliza ahora `ASWC_Scheduler_API::get_subscription_period_strings`, `get_subscription_ranges` y `get_subscription_trial_period_strings`
        - [x] Reemplazadas referencias a `wcs_get_subscription_period_strings`, `wcs_get_subscription_period_interval_strings` y `wcs_get_subscription_ranges` en componentes legacy (informes, API, administración, productos y plantillas) para usar la API central
        - [x] Eliminado `aswc_remove_gateway_scheduled_payment_hook` y su wrapper en la API para evitar dependencias con pasarelas obsoletas
        - [x] Revisar y encapsular otras funciones externas restantes
            - Se añadió una prueba que comprueba que `scheduler-api` no llame directamente a funciones `wcs_*`, métodos `WC_Subscriptions_*` ni métodos estáticos de `WC_Subscriptions::`.
            - La prueba se amplió para detectar referencias directas a clases o funciones con prefijo `WCS_`.
            - La prueba se amplió para detectar llamadas directas a funciones `as_*` y a clases `ActionScheduler_*`.
            - Se encapsuló `ActionScheduler_Store::fetch_action` mediante `aswc_get_action_scheduler_action` y se añadieron pruebas para su wrapper.
            - Se ampliaron los wrappers de `ActionScheduler_Versions`, `ActionScheduler_Store` y `ActionScheduler_Logger` para admitir sus equivalentes con espacios de nombres, y la prueba de dependencias externas detecta ahora referencias directas a `\ActionScheduler\`.
            - Se encapsularon `ActionScheduler_Action::get_hook` y `ActionScheduler_Action::get_args` mediante `aswc_get_action_hook` y `aswc_get_action_args`, con wrappers públicos `get_action_hook` y `get_action_args_from_action` en la API central.
            - Se encapsuló `ActionScheduler_Action::get_schedule` mediante `aswc_get_action_schedule` y se expuso como wrapper público `get_action_schedule` en la API central.
            - Se encapsuló `ActionScheduler_Schedule::get_date` mediante `aswc_get_schedule_timestamp` y se expuso como `get_schedule_timestamp` en la API central.
            - Se encapsuló `ActionScheduler_Action::get_status` mediante `aswc_get_action_status` y se expuso como wrapper público `get_action_status` en la API central.
            - Se encapsularon `ActionScheduler_Action::get_group` y `ActionScheduler_Action::get_id` mediante `aswc_get_action_group` y `aswc_get_action_id`, con wrappers públicos `get_action_group` y `get_action_id` en la API central.
            - La prueba de dependencias externas detecta ahora llamadas directas a `->get_hook`, `->get_args` y `->get_group` para garantizar el uso de los wrappers.
            - Se añadió una comprobación para detectar llamadas directas a `->get_id` en variables de acciones, obligando al uso de `get_action_id`.
            - `WCS_Failed_Scheduled_Action_Manager` usa ahora `get_action_hook` y `get_action_args_from_action` en lugar de llamar directamente a los métodos del objeto.
            - `WCS_Failed_Scheduled_Action_Manager` registra también el número de intentos y el claim ID mediante `get_action_attempts_from_action` y `get_action_claim_id_from_action`.
            - Se añadió una comprobación para detectar llamadas directas a `schedule->get_date`, obligando al uso de `get_schedule_timestamp`.
            - Se encapsuló `ActionScheduler_Action::get_priority` mediante `aswc_get_action_priority` y se expuso como `get_action_priority_from_action` en la API central, añadiendo una comprobación para detectar llamadas directas a `->get_priority`.
            - Se encapsularon `ActionScheduler_Action::get_attempts` y `ActionScheduler_Action::get_claim_id` mediante `aswc_get_action_attempts` y `aswc_get_action_claim_id`, con wrappers públicos `get_action_attempts_from_action` y `get_action_claim_id_from_action` en la API central, y la prueba de dependencias externas detecta ahora llamadas directas a `->get_attempts` y `->get_claim_id`.
            - Se encapsuló `ActionScheduler_Schedule::get_date_gmt` mediante `aswc_get_schedule_gmt_timestamp` y se añadió una comprobación para detectar llamadas directas a `->get_date_gmt`.
            - Se encapsuló `ActionScheduler_Schedule::next` mediante `aswc_get_schedule_next_timestamp` y se añadió una comprobación para detectar llamadas directas a `->next`.
            - Se encapsuló `ActionScheduler_Schedule::get_recurrence` mediante `aswc_get_schedule_recurrence` y se añadió una comprobación para detectar llamadas directas a `->get_recurrence`.
            - Se encapsuló `ActionScheduler_Action::is_finished` mediante `aswc_is_action_finished` y se añadió una comprobación para detectar llamadas directas a `->is_finished`.
            - Se encapsuló `WCS_Retry_Rule::get_retry_interval` mediante `aswc_get_retry_interval_from_rule` y se añadió una comprobación para detectar llamadas directas a `->get_retry_interval`.
            - Se encapsularon `WCS_Retry_Rule::get_raw_data` y `WCS_Retry_Rule::get_status_to_apply` mediante `aswc_get_retry_rule_raw_data` y `aswc_get_retry_rule_status_to_apply`, con comprobaciones para evitar llamadas directas a estos métodos.
            - Se encapsularon `WCS_Retry_Rule::has_email_template` y `WCS_Retry_Rule::get_email_template` mediante `aswc_retry_rule_has_email_template` y `aswc_get_retry_rule_email_template`, añadiendo comprobaciones para detectar llamadas directas a estos métodos.
            - Se encapsuló `wcs_is_woocommerce_pre` como `aswc_is_woocommerce_pre` y se expuso en la API central mediante `ASWC_Scheduler_API::is_woocommerce_pre`, actualizando `WCS_Failed_Scheduled_Action_Manager`, `WCS_Report_Cache_Manager`, las plantillas afectadas y reemplazando las referencias restantes en el plugin para utilizarla.
            - Se encapsuló `ActionScheduler_Action::get_meta` mediante `aswc_get_action_meta` y se añadió una comprobación para detectar llamadas directas a `->get_meta`.
            - Se encapsularon `ActionScheduler_Action::save_meta` y `ActionScheduler_Action::delete_meta` mediante `aswc_set_action_meta` y `aswc_delete_action_meta`, añadiendo comprobaciones para detectar llamadas directas a `->save_meta` y `->delete_meta`.
            - Se encapsuló `ActionScheduler_Schedule::is_recurring` mediante `aswc_is_schedule_recurring` y se añadió una comprobación para detectar llamadas directas a `->is_recurring`.
            - Se encapsuló `ActionScheduler_Action::get_post_id` mediante `aswc_get_action_post_id` y se expuso como `get_action_post_id` en la API central, añadiendo una comprobación para detectar llamadas directas a `->get_post_id`.
            - Se encapsuló `ActionScheduler_Action::get_user_id` mediante `aswc_get_action_user_id` y se expuso como `get_action_user_id` en la API central, añadiendo una comprobación para detectar llamadas directas a `->get_user_id`.
            - Se encapsularon `ActionScheduler_Action::set_hook`, `set_args`, `set_schedule`, `set_group`, `set_status`, `set_priority`, `set_attempts`, `set_claim_id`, `set_post_id` y `set_user_id` mediante funciones `aswc_set_action_*`, añadiendo una comprobación para detectar llamadas directas a estos métodos.
            - Se encapsularon `ActionScheduler_Store::save_action`, `cancel_action` y `delete_action` mediante `aswc_save_action`, `aswc_cancel_action` y `aswc_delete_action`, añadiendo una comprobación para detectar llamadas directas a estos métodos.
            - Se encapsularon `ActionScheduler_Store::mark_complete`, `mark_failure`, `claim_actions` y `release_claim` mediante `aswc_mark_action_complete`, `aswc_mark_action_failed`, `aswc_claim_actions` y `aswc_release_claim`, con métodos públicos en la API central y pruebas que validan su delegación.
            - Se encapsularon `ActionScheduler_Store::unclaim_action` y `query_actions` mediante `aswc_unclaim_action` y `aswc_query_actions`, con métodos públicos en la API central y pruebas que validan su delegación.
            - Se añadieron funciones helper `aswc_get_time_offset`, `aswc_subtract_time_offset`, `aswc_get_valid_notifications` y `aswc_is_subscription_period_too_short` para exponer las utilidades de notificaciones.
            - `WCS_Scheduler::set_date_types_to_schedule` ahora usa `ASWC_Scheduler_API::get_subscription_date_types()` para evitar depender del helper legado `wcs_get_subscription_date_types()`.
            - Se encapsuló `wcs_get_subscription_item_grouping_key` mediante `aswc_get_subscription_item_grouping_key` y se expuso como `get_subscription_item_grouping_key` en la API central.
- [x] `WC_Subscriptions_Email_Notifications::set_notification_settings_update_time` utiliza ahora `aswc_create_admin_notice` en lugar de instanciar `WCS_Admin_Notice` directamente.
- [x] Sustituidas las instancias restantes de `WCS_Admin_Notice` por `aswc_create_admin_notice` en el resto del plugin.
- [x] Reemplazadas las llamadas directas a `wcs_is_custom_order_tables_usage_enabled()` y `wcs_is_custom_order_tables_data_sync_enabled()` por sus equivalentes en la API central en los componentes restantes.
- [x] Encapsulado `wcs_get_subscription_ended_statuses` como `aswc_get_subscription_ended_statuses` y expuesto en la API central.
    - [x] `WC_Subscriptions_Payment_Gateways` y `WCS_My_Account_Auto_Renew_Toggle` utilizan ahora `ASWC_Scheduler_API::get_subscription` y `get_subscription_ended_statuses`.
    - [x] Las clases de renovación anticipada obtienen ahora las suscripciones mediante `ASWC_Scheduler_API::get_subscription`.
    - [x] `WC_Subscriptions_Manager` usa `ASWC_Scheduler_API::get_subscription_ended_statuses` en lugar de `wcs_get_subscription_ended_statuses`.
    - [x] Actualizar el resto de clases que aún llaman directamente a `wcs_get_subscription` o `wcs_get_subscription_ended_statuses`.
        - [x] `WCS_Admin_Meta_Boxes` obtiene ahora las suscripciones mediante `ASWC_Scheduler_API::get_subscription` y comprueba los estados finales con `get_subscription_ended_statuses`.
        - [x] `WCS_Limited_Recurring_Coupon_Manager` usa `ASWC_Scheduler_API::get_subscription` al cambiar el método de pago.
        - [x] `WC_Subscription` y los manejadores asociados (`WC_Subscriptions_Order`, `WC_Subscriptions_Renewal_Order`, `WCS_Privacy_Background_Updater`, `WCS_PayPal_Reference_Transaction_IPN_Handler` y la función `wcs_get_subscription_in_deprecated_structure`) dependen ahora de `ASWC_Scheduler_API::get_subscription_ended_statuses`.
        - [x] `WC_REST_Subscriptions_Controller` y controladores REST v1/v2 usan ahora `ASWC_Scheduler_API` para obtener suscripciones y helpers relacionados.
        - [x] `WCS_Object_Data_Cache_Manager` obtiene ahora las suscripciones mediante `ASWC_Scheduler_API::get_subscription`.
        - [x] `WCS_PayPal_Standard_Request` utiliza `ASWC_Scheduler_API` para recuperar suscripciones desde pedidos y reintentos.
        - [x] `WCS_Change_Payment_Method_Admin` obtiene ahora las suscripciones mediante `ASWC_Scheduler_API::get_subscription`.
        - [x] `WCS_Cached_Data_Manager` usa `ASWC_Scheduler_API::get_subscription` al purgar la caché de usuarios.
        - [x] `WCS_Limiter` utiliza `ASWC_Scheduler_API::get_subscription` para comprobar resuscripciones.
        - [x] `WCS_Download_Handler` delega en `ASWC_Scheduler_API` para recuperar suscripciones y listas de pedidos.
        - [x] `WCS_User_Change_Status_Handler` y `WC_Subscriptions_Frontend_Scripts` obtienen las suscripciones a través de la API central.
        - [x] `WCS_Query` usa `ASWC_Scheduler_API::get_subscription` al gestionar endpoints.
        - [x] `WCS_Meta_Box_Schedule` y `WCS_Meta_Box_Subscription_Data` usan ahora `ASWC_Scheduler_API::get_subscription`.
        - [x] `WCS_Meta_Box_Related_Orders` obtiene la suscripción mediante `ASWC_Scheduler_API::get_subscription`.
        - [x] `WC_Subscriptions_Addresses` recurre a `ASWC_Scheduler_API::get_subscription` al manipular direcciones.
        - [x] Las funciones de pedido en `wcs-order-functions.php` utilizan `ASWC_Scheduler_API::get_subscription`.
        - [x] `WCS_Payment_Tokens` usa `ASWC_Scheduler_API::get_subscription` para obtener las suscripciones del usuario.
        - [x] `wcs_user_functions` delega en `ASWC_Scheduler_API::get_subscription`.
        - [x] `WC_Subscriptions_Admin` recupera las suscripciones mediante `ASWC_Scheduler_API::get_subscription`.
        - [x] `WCS_Admin_Post_Types` obtiene ahora las suscripciones y estados mediante `ASWC_Scheduler_API::get_subscription` y `get_subscription_statuses`.
        - [x] `wcs_trial_has_passed` utiliza `ASWC_Scheduler_API::get_subscription` en lugar de llamar directamente a `wcs_get_subscription`.
        - [x] `WCS_Meta_Box_Subscription_Data` usa `ASWC_Scheduler_API::get_subscription_statuses` para listar estados válidos.
        - [x] `wcs-switch-functions.php` obtiene las suscripciones mediante `ASWC_Scheduler_API::get_subscription`.
        - [x] `wcs-resubscribe-functions.php` obtiene las suscripciones mediante `ASWC_Scheduler_API::get_subscription`.
       - [x] `wcs-renewal-functions.php`, `wcs-resubscribe-functions.php` y `wcs-switch-functions.php` usan `ASWC_Scheduler_API::get_subscriptions_for_order`.
       - [x] `WC_Subscriptions_Order` accede a las suscripciones mediante `ASWC_Scheduler_API::get_subscription`.
       - [x] `WCS_Cart_Renewal` obtiene la suscripción mediante `ASWC_Scheduler_API::get_subscription`.
       - [x] `WCS_Cart_Resubscribe` obtiene la suscripción mediante `ASWC_Scheduler_API::get_subscription`.
       - [x] `WCS_Cart_Initial_Payment` delega en `ASWC_Scheduler_API::get_subscriptions_for_order`.
       - [x] `WCS_Switch_Totals_Calculator` y `WCS_Cart_Switch` utilizan la API central para recuperar suscripciones e ítems de pedido.
       - [x] `WCS_Remove_Item` obtiene la suscripción mediante `ASWC_Scheduler_API::get_subscription`.
       - [x] `WCS_Privacy_Background_Updater` obtiene las suscripciones mediante `ASWC_Scheduler_API::get_subscriptions` y `get_subscription`.
       - [x] `WCS_Privacy` utiliza ahora `ASWC_Scheduler_API::get_subscription`.
       - [x] `WCS_Template_Loader` obtiene las suscripciones mediante `ASWC_Scheduler_API::get_subscription`.
       - [x] `WC_API_Subscriptions` obtiene ahora las suscripciones mediante `ASWC_Scheduler_API::get_subscription`.
       - [x] `WCS_PayPal` y controladores estándar utilizan `ASWC_Scheduler_API::get_subscription`.
       - [x] `WCS_Related_Order_Store_Cached_CPT` obtiene las suscripciones mediante `ASWC_Scheduler_API::get_subscription`.
       - [x] Confirmado que no quedan referencias directas a `wcs_get_subscription` ni `wcs_get_subscription_ended_statuses`.
       - [x] Revisar las demás clases que siguen utilizando `wcs_get_subscription`.
        - [x] `WC_Subscription::get_related_orders_query` usa ahora `ASWC_Scheduler_API::get_subscription`.
        - [x] `WC_Subscriptions_Change_Payment_Gateway` delega en `ASWC_Scheduler_API::get_subscription` en sus funciones obsoletas.
- [x] Centralizar la ejecución de pagos programados en la API.
    - [x] `gateway_scheduled_subscription_payment` y `trigger_gateway_renewal_payment_hook` movidos a `ASWC_Scheduler_Payments` y envueltos en `WC_Subscriptions_Payment_Gateways` para compatibilidad.
- [x] Migrar manejo de fallos de acciones programadas.
    - [x] `WCS_Failed_Scheduled_Action_Manager` movido a `scheduler-api/payments` como `ASWC_Scheduler_Failed_Action_Manager`, con prefijo interno para opciones y expuesto mediante `ASWC_Scheduler_API::init_failed_action_manager`.
    - [x] `wcs_get_edit_post_link` encapsulado como `aswc_get_edit_post_link` y expuesto en la API central.
    - [x] Prefijos de la notificación de error de acciones programadas actualizados a `aswc_`.
    - [x] Prefijo de opciones actualizado a `advanced_subscriptions_woocommerce` para las acciones fallidas.
    - [x] `ASWC_Scheduler_Failed_Action_Manager` utiliza la instancia de logger inyectada en lugar de llamar directamente a `wc_get_logger`.
     - [x] Eliminada la función de reparación heredada y su meta clave en `ASWC_Scheduler_Lifecycle_Events`.
- [x] Control de empaquetado.
    - [x] Excluir `scheduler-api/README.md` y `scheduler-api/tests` del paquete mediante `.gitattributes`.
    - [x] Revisar otros archivos y directorios a excluir.
    - [x] Consolidar reglas específicas en `scheduler-api/.gitattributes` para simplificar el archivo raíz.
- [x] Renombrar hooks con el prefijo antiguo a `advanced_scheduled_subscription_*` en todo el plugin.
    - [x] Añadida prueba automatizada para asegurar que no quedan ganchos `woocommerce_scheduled_subscription_*` y se usa el nuevo prefijo.
- [x] Actualizados los ganchos y la opción de `WCS_Batch_Processing_Controller` al prefijo `advanced_subscriptions_woocommerce`, manteniendo compatibilidad con los nombres antiguos.
- [x] Añadida prueba automatizada para garantizar que no queden prefijos de opción `woocommerce_subscriptions` en `scheduler-api`.
- [x] Añadida prueba automatizada para asegurar que no quedan clases, funciones o constantes con prefijo `WCS` en `scheduler-api`.
- [x] Eliminada la lógica de reparación `_wcs_repaired_2_0_2_needs_failed_payment` y la prueba `LifecycleEventsRepairFlagTest` para mantener `scheduler-api` libre de código legacy, deprecated y updaters.
- [x] Deshabilitada la ejecución de pruebas PHPUnit en GitHub Actions eliminando el flujo de trabajo `tests.yml`.
- [x] Reemplazadas las llamadas directas a los métodos de programación en segundo plano por los wrappers `aswc_*` en el resto del plugin.
- [x] Redactar una documentación exhaustiva de la API —incluyendo hooks, filtros y ejemplos de uso— antes de crear la suite de pruebas PHPUnit. Esta guía podrá dividirse en múltiples PRs.
    - [x] La guía se amplió con secciones por módulo, tablas de métodos y documentación de hooks y filtros adicionales.
    - [x] Añadidos casos de uso avanzados y descripción de los procesos en segundo plano.
    - [x] Añadidas recomendaciones de migración y escenarios de depuración.
    - [x] Documentadas consideraciones de compatibilidad y una sección de preguntas frecuentes.
- [x] Ejecutar la suite de pruebas PHPUnit una vez completada la migración del código.
    - [x] Se instaló `phpunit` mediante Composer y se ejecutaron 254 pruebas (0 advertencias y 0 deprecations).
    - [x] Investigar y resolver las advertencias y deprecations reportadas por la suite de pruebas.
        - [x] Corregido el warning provocado por una expresión regular inválida en `ExternalDependenciesTest`.
        - [x] Investigar y resolver las deprecations reportadas por PHPUnit.
            - [x] El script `composer test` ahora muestra las deprecations y evita la caché de resultados para facilitar su análisis.
            - [x] Identificar y corregir la fuente de las deprecations restantes.

- [x] Completar cobertura avanzada para la lógica de programación de pagos y reintentos.
    - [x] Incluir escenarios adicionales como órdenes ya pagadas y estados personalizados en las pruebas.
    - [x] Añadidas pruebas para órdenes de renovación con total cero.
- [x] Documentar el uso del gestor de acciones programadas fallidas en la guía de la API.
- [x] Añadir pruebas específicas para el gestor de acciones fallidas y su notificación de administrador.
- [x] Ampliar cobertura con escenarios de integraciones externas y posibles condiciones de carrera.
    - [x] Añadidas pruebas básicas que validan la coexistencia de acciones externas al limpiar el grupo principal.
    - [x] Añadida prueba que comprueba la idempotencia de `reschedule_action` frente a acciones simultáneas.
    - [x] Añadidas pruebas que eliminan acciones externas al limpiar todos los grupos y simulan reprogramaciones concurrentes.
    - [x] Añadidas pruebas que validan comportamientos en entornos con múltiples procesos concurrentes y plugins externos complejos.
    - [x] Prueba que confirma que las acciones externas en el grupo principal se eliminan al limpiar dicho grupo.
    - [x] Prueba que al desprogramar un grupo externo no se afectan las acciones del grupo principal ni de otros plugins.
    - [x] Prueba que garantiza que, en un grupo compartido por varios plugins externos, la última reprogramación prevalece.
    - [x] Añadida prueba que simula servicios externos con colas distribuidas y limpiezas globales.
    - [x] Añadida prueba que combina fallos de red y reprogramaciones concurrentes en colas distribuidas.
    - [x] Añadida prueba que valida una limpieza global tras un fallo de red y la posterior reprogramación exitosa en otro nodo.
    - [x] Probar integración con colas distribuidas reales y manejo de fallos de red.
        - [x] Manejar y registrar fallos de red al programar o reprogramar acciones mediante pruebas unitarias.
        - [x] Validar la integración con colas distribuidas reales coordinando múltiples nodos.

