# Scheduler API

Este documento ofrece una guía inicial de la Scheduler API usada por Advanced Subscriptions For WooCommerce. Su objetivo es centralizar la creación, cancelación y ejecución de acciones programadas.

## Conceptos clave

Antes de profundizar en los módulos conviene familiarizarse con la terminología que utiliza la API:

- **Acción programada**: tarea que se ejecutará en el futuro mediante Action Scheduler.
- **Hook**: nombre que identifica a la acción y que otros componentes pueden escuchar.
- **Grupo**: canal lógico dentro de Action Scheduler que permite aislar o filtrar acciones.
- **Timestamp**: marca temporal en segundos desde la época Unix que determina cuándo se ejecutará la acción.
- **Callback de desfase**: función que permite ajustar dinámicamente los timestamps antes de programarlos.

## Requisitos previos y arquitectura

La API se apoya en WooCommerce y Action Scheduler. Todos los módulos se cargan a través de `scheduler-api/scheduler.php`, que registra hooks, clases y utilidades auxiliares. Cada módulo se divide en archivos independientes para facilitar su reutilización desde otros plugins.

## Módulos

- **Core**: Funciones genéricas y wrappers de Action Scheduler.
- **Lifecycle**: Eventos relacionados con el ciclo de vida de la suscripción (expiraciones, fin de periodo de prueba, etc.).
- **Payments**: Renovaciones y reintentos de pago.
- **Notifications**: Avisos al cliente programados.
- **Background**: Procesos en segundo plano y utilidades comunes.

## Instalación e integración

Incluye el archivo `scheduler-api/scheduler.php` desde el plugin que quiera
aprovechar la API. Este bootstrap carga todos los módulos y registra los hooks
necesarios para reaccionar a cambios de fechas o estados en las suscripciones
mediante `ASWC_Scheduler_Subscription_Hooks`.

`ASWC_Scheduler_Subscription_Hooks` conecta los eventos de WooCommerce
Subscriptions –`woocommerce_subscription_date_updated`,
`woocommerce_subscription_date_deleted` y
`woocommerce_subscription_status_updated`– con los métodos correspondientes de
`ASWC_Scheduler_API`. Gracias a ello cualquier modificación en una suscripción
reprograma automáticamente las acciones afectadas sin depender de clases
_legacy_.

```php
// Archivo principal de tu plugin o librería externa.
require_once __DIR__ . '/scheduler-api/scheduler.php';

// Programar inmediatamente todos los eventos de una suscripción existente.
ASWC_Scheduler_API::schedule_all( $subscription_id );
```

Una vez cargada, los módulos pueden utilizarse directamente a través de los
métodos estáticos de `ASWC_Scheduler_API` o mediante las funciones helper con
prefijo `aswc_`.

### Flujo de trabajo general

1. Definir las fechas relevantes en la suscripción (pago, expiración, etc.).
2. Invocar los métodos `schedule_*` o `schedule_all()` para registrar las acciones.
3. Action Scheduler ejecutará cada hook cuando llegue su timestamp.
4. Los módulos de la API actualizan la suscripción o envían notificaciones según el hook.
5. Cualquier modificación posterior puede reprogramarse con `unschedule_*` y `reschedule_*`.

## Hooks disponibles

| Hook | Descripción |
| ---- | ----------- |
| `advanced_scheduled_subscription_payment` | Ejecuta el pago de renovación de una suscripción. |
| `advanced_scheduled_subscription_payment_retry` | Dispara un reintento de pago para una suscripción. |
| `advanced_scheduled_subscription_expiration` | Marca la suscripción como expirada. |
| `advanced_scheduled_subscription_end_of_prepaid_term` | Finaliza el periodo prepagado de la suscripción. |
| `advanced_scheduled_subscription_trial_end` | Finaliza el periodo de prueba de la suscripción. |
| `advanced_scheduled_subscription_customer_notification_*` | Envía la notificación correspondiente al cliente según el tipo (`renewal`, `expiration`, etc.). |

## Filtros disponibles

| Filtro | Descripción |
| ------ | ----------- |
| `advanced_subscriptions_woocommerce_scheduled_action_priority` | Permite modificar la prioridad usada al programar acciones. |
| `advanced_subscriptions_woocommerce_scheduled_action_args` | Ajusta los argumentos pasados a la acción programada. |
| `advanced_subscriptions_woocommerce_scheduled_action_hook` | Cambia el nombre del hook asociado a una acción. |
| `aswc_subscription_customer_notification_statuses` | Define los estados de suscripción que generan notificaciones. |
| `aswc_subscription_customer_notification_is_period_too_short` | Valida si el periodo es demasiado corto para notificar. |
| `aswc_subscription_customer_notification_time_offset` | Modifica el desfase temporal aplicado a las notificaciones. |
| `aswc_subscription_valid_customer_notification_types` | Filtra los tipos de notificación permitidos. |

## Opciones de configuración

La API consulta varias *options* de WordPress para permitir su personalización.
Si una opción no existe se utilizará el valor por defecto indicado en la
tabla siguiente:

| Opción WP | Descripción | Valor por defecto |
| --- | --- | --- |
| `advanced_subscriptions_woocommerce_scheduler_core_group` | Grupo de Action Scheduler usado por el núcleo. | `aswc_subscription_scheduled_event` |
| `advanced_subscriptions_woocommerce_scheduler_action_priority` | Prioridad asignada a las acciones programadas. | `1` |
| `advanced_subscriptions_woocommerce_scheduler_notifications_group` | Grupo de Action Scheduler para las notificaciones. | `aswc_customer_notifications` |
| `advanced_subscriptions_woocommerce_customer_notifications_offset` | Desfase aplicado a los avisos al cliente. | `{"number":3,"unit":"days"}` |
| `advanced_subscriptions_woocommerce_customer_notifications_enabled` | Interruptor global que habilita los avisos. | `yes` |
| `advanced_subscriptions_woocommerce_notification_settings_update_time` | Marca temporal de la última actualización de ajustes de notificación. | `0` |
| `advanced_subscriptions_woocommerce_scheduler_background_group` | Grupo usado para procesos en segundo plano. | `aswc_background_processes` |
| `advanced_subscriptions_woocommerce_failed_action_hooks` | Lista de hooks que supervisa el gestor de acciones fallidas. | `advanced_scheduled_subscription_trial_end`, `advanced_scheduled_subscription_payment`, `advanced_scheduled_subscription_payment_retry`, `advanced_scheduled_subscription_expiration`, `advanced_scheduled_subscription_end_of_prepaid_term` |
| `advanced_subscriptions_woocommerce_retry_intervals` | Intervalos en segundos para cada reintento de pago; la longitud define el número máximo de reintentos. | `[43200,43200,86400,172800,259200]` |

## Grupos de acciones

Las acciones programadas se organizan en grupos de Action Scheduler que permiten
aislar eventos y facilitar su depuración. Por defecto el núcleo utiliza el
grupo `aswc_subscription_scheduled_event`, las notificaciones emplean
`aswc_customer_notifications` y los procesos en segundo plano
`aswc_background_processes`. Estos nombres pueden sobrescribirse mediante las
options de WordPress descritas en la tabla anterior. El parámetro `$group`
presente en la mayoría de los métodos permite redirigir una acción a un grupo
personalizado o pasar `false` para abarcar todos los grupos. El filtro
`advanced_subscriptions_woocommerce_scheduled_action_group` puede ajustar
dinámicamente el grupo por defecto.

## Métodos principales

La clase `ASWC_Scheduler_API` expone métodos estáticos agrupados por módulo.

### Orquestación

| Método | Descripción |
| ------ | ----------- |
| `schedule_all( $subscription, $offset_cb = null, $date_types = null, $group = null )` | Programa pagos, eventos de ciclo de vida y notificaciones en una sola llamada. |
| `unschedule_all( $subscription, $date_types = null, $group = null )` | Elimina todos los eventos asociados a la suscripción. |
| `update_date( $subscription, $date_type, $datetime )` | Actualiza una fecha de la suscripción y reprograma automáticamente los eventos relacionados. |
| `delete_date( $subscription, $date_type )` | Borra una fecha y desprograma los hooks vinculados. |
| `update_status( $subscription, $new_status, $old_status )` | Cambia el estado de la suscripción y limpia o reprograma eventos según corresponda. |
| `get_scheduled_subscription_actions( $subscription, $group = null )` | Devuelve los objetos de acciones programadas para pagos y ciclo de vida. |
| `get_scheduled_events( $subscription, $group = null )` | Recupera los timestamps de eventos de pagos y ciclo de vida. |
| `has_scheduled_events( $subscription, $date_types = null, $group = null )` | Comprueba si existen eventos pendientes, incluyendo notificaciones. |
| `get_last_scheduled_subscription_actions( $subscription, $group = null )` | Obtiene las últimas acciones de pagos y ciclo de vida. |
| `get_last_scheduled_events( $subscription, $group = null )` | Obtiene los últimos timestamps de eventos de pagos y ciclo de vida. |
| `get_all_scheduled_subscription_actions( $subscription, $date_types = null, $group = null )` | Combina acciones de pagos, ciclo y notificaciones. |
| `get_all_scheduled_events( $subscription, $date_types = null, $group = null )` | Combina todos los timestamps programados de los módulos. |
| `get_all_last_scheduled_subscription_actions( $subscription, $date_types = null, $group = null )` | Devuelve los objetos de las últimas acciones de todos los módulos. |
| `get_all_last_scheduled_events( $subscription, $date_types = null, $group = null )` | Devuelve los últimos timestamps registrados en todos los módulos. |

### Core

| Método | Descripción |
| ------ | ----------- |
| `schedule_action( $timestamp, $hook, $args = array(), $unique = false, $group = null )` | Programa una acción genérica. |
| `unschedule_action( $hook, $args = array(), $group = null )` | Cancela una acción programada. |
| `has_scheduled_action( $hook, $args = array(), $group = null )` | Comprueba si existe una acción para el hook indicado. |
| `get_scheduled_action( $hook, $args = array(), $group = null )` | Devuelve la primera acción programada que coincida. |
| `get_scheduled_actions( $hook, $args = array(), $group = null )` | Recupera todas las acciones programadas para un hook. |
| `last_scheduled_action( $hook, $args = array(), $group = null )` | Obtiene el timestamp de la última acción registrada. |
| `get_action( $action_id )` | Recupera una acción concreta mediante su ID. |
| `next_scheduled_action( $hook, $args = array(), $group = null )` | Obtiene la siguiente ejecución para un hook. |
| `schedule_recurring_action( $timestamp, $interval, $hook, $args = array(), $unique = false, $group = null )` | Programa una acción recurrente. |
| `schedule_cron_action( $timestamp, $schedule, $hook, $args = array(), $unique = false, $group = null )` | Programa una acción mediante una expresión CRON. |
| `schedule_unique_action( $timestamp, $hook, $args = array(), $group = null, $priority = 10 )` | Agenda una acción única evitando duplicados. |
| `enqueue_async_action( $hook, $args = array(), $group = null )` | Cola una acción asíncrona para ejecutarse lo antes posible. |
| `reschedule_action( $timestamp, $hook, $args = array(), $group = null )` | Reprograma una acción existente ajustando su fecha de ejecución. |
| `unschedule_actions( $hook = null, $args = array(), $group = null )` | Cancela todas las acciones que coincidan con los parámetros dados. |
| `unschedule_core_group( $group = null )` | Limpia todas las acciones del grupo principal. |
| `unschedule_notification_group( $group = null )` | Vacía el grupo dedicado a notificaciones. |
| `unschedule_all_groups( $groups = array() )` | Cancela las acciones de varios grupos a la vez. |
| `claim_actions( $claim_id, $limit, $before = null, $hooks = array(), $group = null )` | Reclama acciones pendientes para su procesamiento exclusivo. |
| `release_claim( $claim_id )` | Libera las acciones asociadas al claim. |
| `unclaim_action( $action_id )` | Desasocia un claim de una acción concreta. |
| `query_actions( $args = array() )` | Realiza consultas directas al almacén de acciones. |

Además, la API proporciona getters y setters para inspeccionar y modificar objetos de Action Scheduler, como `get_action_hook()`, `set_action_args()`, `get_action_schedule()`, `set_action_status()`, `get_action_meta()` o `delete_action_meta()`, así como utilidades para obtener información de los horarios (`get_schedule_timestamp()`, `get_schedule_next_timestamp()`, etc.).

### Payments

| Método | Descripción |
| ------ | ----------- |
| `schedule_payment( $subscription_id, $timestamp, $group = null )` | Programa un pago de renovación. |
| `schedule_manual_payment( $subscription_id, $timestamp, $group = null )` | Programa un pago manual de renovación. |
| `unschedule_payment( $subscription_id, $group = null )` | Cancela un pago programado. |
| `schedule_retry( $subscription_id, $timestamp, $group = null )` | Programa un reintento de pago. |
| `schedule_retry_with_rule( $subscription_id, $rule, $group = null )` | Calcula el intervalo usando la regla y programa el reintento; si la regla devuelve un intervalo menor o igual a 0 se desprograma cualquier reintento existente. |
| `schedule_retry_for_attempt( $subscription_id, $attempt, $group = null )` | Usa el índice de intento para aplicar los intervalos configurados y programa el reintento. |
| `unschedule_retry( $subscription_id, $group = null )` | Cancela un reintento de pago. |
| `get_scheduled_payment( $subscription_id, $group = null )` | Obtiene el timestamp del próximo pago. |
| `get_scheduled_retry( $subscription_id, $group = null )` | Obtiene el timestamp del próximo reintento. |
| `get_scheduled_payments( $subscription_id, $group = null )` | Devuelve todos los eventos de pago programados. |
| `has_scheduled_payment( $subscription_id, $group = null )` | Comprueba si hay un pago programado. |
| `has_scheduled_retry( $subscription_id, $group = null )` | Comprueba si hay un reintento programado. |
| `has_scheduled_payments( $subscription_id, $group = null )` | Comprueba si existe algún evento de pago. |
| `last_scheduled_payment( $subscription_id, $group = null )` | Recupera el último pago programado. |
| `last_scheduled_retry( $subscription_id, $group = null )` | Recupera el último reintento programado. |
| `get_last_scheduled_payments( $subscription_id, $group = null )` | Devuelve los últimos eventos de pago. |
| `get_scheduled_payment_action( $subscription_id, $group = null )` | Devuelve el objeto de acción del próximo pago. |
| `get_scheduled_retry_action( $subscription_id, $group = null )` | Devuelve el objeto de acción del próximo reintento. |
| `get_last_scheduled_payment_action( $subscription_id, $group = null )` | Devuelve el último objeto de acción de pago. |
| `get_last_scheduled_retry_action( $subscription_id, $group = null )` | Devuelve el último objeto de acción de reintento. |
| `get_scheduled_payment_actions( $subscription_id, $group = null )` | Devuelve los objetos de las acciones de pago y reintento programados. |
| `get_last_scheduled_payment_actions( $subscription_id, $group = null )` | Devuelve los últimos objetos de acciones de pago y reintento. |
| `get_scheduled_retry_actions( $subscription_id, $group = null )` | Devuelve todos los objetos de reintentos programados. |
| `get_retry_intervals()` | Devuelve la lista de intervalos en segundos aplicada a cada reintento. |
| `schedule_all_payments( $subscription_id, $group = null )` | Programa a la vez el pago y el reintento. |
| `unschedule_all_payments( $subscription_id, $group = null )` | Cancela todos los eventos de pago. |
| `gateway_scheduled_subscription_payment( $subscription_id, $deprecated = null )` | Hook de compatibilidad que ejecuta el pago con la pasarela correspondiente. |
| `trigger_gateway_renewal_payment_hook( $renewal_order )` | Dispara el hook de renovación para la pasarela del pedido. |
| `has_gateway_renewal_payment_hook( $gateway_id )` | Comprueba si una pasarela registra un hook de renovación. |

Los métodos de programación (`schedule_*`) aceptan un parámetro `$group` opcional para dirigir las acciones a un grupo
personalizado de Action Scheduler. Antes de programar una nueva acción se eliminan las existentes en todos los grupos para
evitar duplicados. Cuando se pasa `null`, las acciones se registran en el grupo por defecto de la API. Si se proporciona
`false`, además de limpiarse todos los grupos la acción resultante se asigna igualmente al grupo por defecto. El valor `false`
puede utilizarse también en los métodos de desprogramación (`unschedule_*`) para buscar o eliminar acciones sin limitarse a un
grupo concreto.

Los métodos de consulta (`get_*`, `has_*` y `last_*`) aceptan igualmente el parámetro `$group`. Pasar `false` permite inspeccionar acciones en todos los grupos, mientras que omitir el parámetro limita la búsqueda al grupo por defecto.

Para facilitar el acceso desde código procedimental, la API expone funciones helper equivalentes con el prefijo `aswc_`, como
`aswc_get_scheduled_payment_action()`, `aswc_get_scheduled_retry_action()`, `aswc_get_scheduled_payment_actions()`,
`aswc_get_last_scheduled_payment_action()`, `aswc_get_last_scheduled_retry_action()`, `aswc_get_last_scheduled_payment_actions()`,
`aswc_get_scheduled_retry_actions()`, `aswc_last_scheduled_payment()` y `aswc_last_scheduled_retry()`.

#### Casos avanzados de programación de pagos y reintentos

- Al ejecutarse un pago programado se eliminan automáticamente todos los reintentos pendientes en cualquier grupo para evitar duplicidades.
- Si la pasarela declara compatibilidad con pagos programados, si la suscripción es manual o si está en un estado finalizado, la ejecución del pago se omite y se eliminan los reintentos pendientes.
- Cuando no se encuentra el último pedido de renovación, el pedido ya está pagado o su total es cero, la API añade una nota a la suscripción y no dispara el hook de pago.
- Las funciones `schedule_retry_with_rule()` y `schedule_retry_after()` desprograman cualquier reintento existente cuando la regla o el intervalo proporcionan valores menores o iguales a cero.
- El parámetro `$group` permite tanto programar como desprogramar pagos y reintentos en grupos personalizados o atravesar todos los grupos usando `false`.

### Lifecycle

| Método | Descripción |
| ------ | ----------- |
| `schedule_trial_end( $subscription_id, $timestamp, $group = null )` | Programa el fin del periodo de prueba (usa `unschedule_trial_end()` si el timestamp es vacío o inválido). |
| `unschedule_trial_end( $subscription_id, $group = null )` | Cancela el fin de prueba programado. |
| `schedule_expiration( $subscription_id, $timestamp, $group = null )` | Programa la expiración de la suscripción (usa `unschedule_expiration()` si el timestamp es vacío o inválido). |
| `unschedule_expiration( $subscription_id, $group = null )` | Cancela la expiración programada. |
| `schedule_end_of_prepaid_term( $subscription_id, $timestamp, $group = null )` | Programa el fin del periodo prepagado (usa `unschedule_end_of_prepaid_term()` si el timestamp es vacío o inválido). |
| `unschedule_end_of_prepaid_term( $subscription_id, $group = null )` | Cancela el fin de periodo prepagado programado. |

| `schedule_all_lifecycle_events( $subscription_id, $group = null )` | Programa simultáneamente todos los eventos del ciclo de vida. |
| `unschedule_all_lifecycle_events( $subscription_id, $group = null )` | Elimina todos los eventos del ciclo de vida de la suscripción. |
| `get_scheduled_trial_end( $subscription_id, $group = null )` | Recupera el timestamp del fin de prueba programado. |
| `get_scheduled_expiration( $subscription_id, $group = null )` | Obtiene la fecha de expiración programada. |
| `get_scheduled_end_of_prepaid_term( $subscription_id, $group = null )` | Devuelve el fin de periodo prepagado programado. |
| `get_scheduled_lifecycle_actions( $subscription_id, $group = null )` | Devuelve los objetos de las acciones de ciclo de vida. |
| `last_scheduled_trial_end( $subscription_id, $group = null )` | Devuelve el último fin de prueba registrado. |
| `last_scheduled_expiration( $subscription_id, $group = null )` | Devuelve la última expiración registrada. |
| `last_scheduled_end_of_prepaid_term( $subscription_id, $group = null )` | Devuelve el último fin de periodo prepagado. |
| `get_last_scheduled_lifecycle_actions( $subscription_id, $group = null )` | Recupera los objetos de las últimas acciones de ciclo de vida. |
| `has_scheduled_trial_end( $subscription_id, $group = null )` | Comprueba si hay un fin de prueba programado. |
| `has_scheduled_expiration( $subscription_id, $group = null )` | Comprueba si existe una expiración programada. |
| `has_scheduled_end_of_prepaid_term( $subscription_id, $group = null )` | Comprueba si hay un fin de periodo prepagado programado. |
| `has_scheduled_lifecycle_events( $subscription_id, $group = null )` | Comprueba si existe cualquier evento de ciclo de vida. |

Estas funciones eliminan automáticamente cualquier evento cuando se les pasa un timestamp vacío o menor o igual a cero, evitando que se programen hooks huérfanos.

### Notifications

| Método | Descripción |
| ------ | ----------- |
| `schedule_notification( $subscription_id, $timestamp, $type, $group = null )` | Programa una notificación al cliente. |
| `schedule_all_notifications( $subscription_id, $offset_cb = null, $types = null, $group = null )` | Programa todas las notificaciones permitidas. |
| `unschedule_notification( $subscription_id, $type, $group = null )` | Cancela una notificación específica. |
| `schedule_notifications( $subscription_id, $notifications, $group = null )` | Programa múltiples notificaciones a la vez. |
| `unschedule_notifications( $subscription_id, $types, $group = null )` | Elimina varias notificaciones según su tipo. |
| `unschedule_all_notifications( $subscription_id, $types = null, $exceptions = array(), $group = null )` | Limpia todas las notificaciones, con posibilidad de excluir algunas. |
| `get_scheduled_notifications( $subscription_id, $types = null, $group = null )` | Recupera los timestamps de notificaciones programadas. |
| `get_scheduled_notification( $subscription_id, $type, $group = null )` | Obtiene el timestamp de una notificación concreta. |
| `get_scheduled_notification_action( $subscription_id, $type, $group = null )` | Devuelve el objeto de acción de una notificación. |
| `get_scheduled_notification_actions( $subscription_id, $types = null, $group = null )` | Devuelve los objetos de varias notificaciones. |
| `get_last_scheduled_notification_action( $subscription_id, $type, $group = null )` | Recupera el último objeto de acción registrado para una notificación. |
| `last_scheduled_notification( $subscription_id, $type, $group = null )` | Devuelve el último timestamp registrado para una notificación. |
| `get_last_scheduled_notifications( $subscription_id, $types = null, $group = null )` | Devuelve los últimos timestamps de varias notificaciones. |
| `get_last_scheduled_notification_actions( $subscription_id, $types = null, $group = null )` | Devuelve los objetos de las últimas acciones de notificación. |
| `has_scheduled_notifications( $subscription_id, $types = null, $group = null )` | Comprueba si existen notificaciones programadas. |
| `has_scheduled_notification( $subscription_id, $type, $group = null )` | Comprueba si existe una notificación de un tipo concreto. |

### Background

| Método | Descripción |
| ------ | ----------- |
| `schedule_background_action( $timestamp, $hook, $args = array(), $unique = false, $group = null )` | Programa un proceso en segundo plano. |
| `reschedule_background_action( $timestamp, $hook, $args = array(), $group = null )` | Reprograma una acción ya existente. |
| `unschedule_background_action( $hook, $args = array(), $group = null )` | Cancela un proceso en segundo plano. |
| `unschedule_background_group( $group = null )` | Cancela todas las acciones de un grupo. |
| `has_scheduled_background_action( $hook, $args = array(), $group = null )` | Comprueba si existe una acción en segundo plano programada. |
| `next_scheduled_background_action( $hook, $args = array(), $group = null )` | Obtiene la siguiente ejecución prevista. |
| `last_scheduled_background_action( $hook, $args = array(), $group = null )` | Devuelve el timestamp de la última acción programada. |
| `get_scheduled_background_action( $hook, $args = array(), $group = null )` | Recupera la primera acción que coincide con el hook. |
| `get_last_scheduled_background_action( $hook, $args = array(), $group = null )` | Recupera la última acción programada para un hook. |
| `get_scheduled_background_actions( $hook, $args = array(), $group = null )` | Devuelve todas las acciones que coinciden con el hook. |
| `get_last_scheduled_background_actions( $hook, $args = array(), $group = null )` | Devuelve las últimas acciones registradas para un hook. |

### Utilidades

| Método | Descripción |
| ------ | ----------- |
| `get_logger()` | Devuelve la instancia de `WC_Logger_Interface` si está disponible. |
| `get_edit_post_link( $post_id )` | Obtiene el enlace de edición de un contenido dado. |
| `get_plugin_directory( $path = '' )` | Devuelve la ruta al directorio del plugin, con un subpath opcional. |
| `get_subscription_date_types()` | Lista los tipos de fecha manejados por las suscripciones. |
| `get_subscription_statuses()` | Devuelve los estados registrados de las suscripciones. |
| `get_subscription_status_names()` | Devuelve los nombres legibles de los estados de suscripción. |
| `get_subscription_ended_statuses()` | Devuelve los estados considerados finales para evitar eventos futuros. |
| `date_to_time( $datetime )` | Convierte valores de fecha a timestamp. |
| `get_subscription( $subscription_id )` | Recupera una instancia de `WC_Subscription`. |
| `get_subscriptions( $args = array() )` | Consulta suscripciones mediante parámetros de búsqueda. |
| `get_subscriptions_for_order( $order, $args = array() )` | Obtiene las suscripciones vinculadas a un pedido. |
| `get_subscription_ids_for_order( $order, $types = array( 'any' ) )` | Devuelve los IDs de suscripción asociados a un pedido. |

Además, existen helpers adicionales para generar cadenas de periodos (`get_subscription_period_strings()`, `get_subscription_ranges()`, `get_available_time_periods()`, etc.) y funciones de compatibilidad con WooCommerce (`is_woocommerce_pre()`, `is_custom_order_tables_usage_enabled()`, ...).

### Helpers procedimentales `aswc_`

Todos los métodos públicos cuentan con funciones equivalentes con el prefijo
`aswc_` para facilitar su uso desde código procedimental. Por ejemplo,
`aswc_schedule_action()`, `aswc_unschedule_action()`,
`aswc_schedule_payment()`, `aswc_unschedule_retry()`,
`aswc_schedule_notification()` o `aswc_schedule_background_action()` delegan en
los métodos estáticos de `ASWC_Scheduler_API` manteniendo la misma firma de
parámetros.

### Gestor de acciones programadas fallidas

La API puede inicializar un gestor que monitoriza los errores al ejecutar acciones programadas y muestra avisos en el escritorio de WordPress.

| Método | Descripción |
| ------ | ----------- |
| `init_failed_action_manager( ?WC_Logger_Interface $logger = null )` | Activa el gestor y registra los fallos utilizando el logger proporcionado. |

Los detalles de cada fallo se almacenan en la opción `advanced_subscriptions_woocommerce_failed_scheduled_actions` y se registran en el canal de log `failed-scheduled-actions` para facilitar la depuración.

## Ejemplos

```php
// Programar un pago de renovación
ASWC_Scheduler_API::schedule_payment( $subscription_id, $timestamp );

// Programar un reintento de pago en un grupo personalizado
ASWC_Scheduler_API::schedule_retry( $subscription_id, $timestamp + DAY_IN_SECONDS, 'my_group' );

// Cancelar todas las acciones programadas de la suscripción
ASWC_Scheduler_API::unschedule_all( $subscription_id );

// Programar una notificación de expiración
ASWC_Scheduler_API::schedule_notification( $subscription_id, $timestamp, 'expiration' );

// Activar el gestor de acciones fallidas con un logger personalizado
ASWC_Scheduler_API::init_failed_action_manager( wc_get_logger() );
```

## Casos de uso avanzados

```php
// Reprogramar un proceso en segundo plano existente
ASWC_Scheduler_API::reschedule_background_action(
    $new_timestamp,
    'aswc_example_batch_process',
    array( 'batch_id' => 123 )
);

// Consultar la última acción en segundo plano planificada
$action = ASWC_Scheduler_API::get_last_scheduled_background_action(
    'aswc_example_batch_process',
    array( 'batch_id' => 123 )
);

// Ajustar la prioridad de una acción programada
add_filter(
    'advanced_subscriptions_woocommerce_scheduled_action_priority',
    function( $priority, $hook ) {
        return 'advanced_scheduled_subscription_payment' === $hook ? 5 : $priority;
    },
    10,
    2
);
```

```php
// Obtener utilidades del plugin
$plugin_dir = ASWC_Scheduler_API::get_plugin_directory();
$logger     = ASWC_Scheduler_API::get_logger();
if ( $logger ) {
    $logger->info( 'Proceso finalizado.' );
}
```

## Recomendaciones de migración

- Sustituir cualquier uso directo de Action Scheduler o clases con prefijo `WCS_` por sus equivalentes `ASWC_`.
- Cargar el archivo `scheduler-api/scheduler.php` desde el punto de entrada del plugin y delegar toda la programación en `ASWC_Scheduler_API`.
- Emplear los nuevos prefijos `advanced_scheduled_subscription_*` y `advanced_subscriptions_woocommerce_*` para hooks y opciones.
- Evitar llamadas directas a métodos de `ActionScheduler_Action` o `ActionScheduler_Store`; los wrappers del API ya los exponen de forma segura.

## Escenarios de depuración

- Consultar los eventos pendientes mediante métodos como `ASWC_Scheduler_API::get_scheduled_payment()` o `get_scheduled_lifecycle_actions()`.
- Revisar las acciones fallidas con el gestor `ASWC_Scheduler_Failed_Action_Manager` y el log `aswc_scheduler`.
- Verificar que los filtros de la API no estén alterando inesperadamente el hook o los argumentos de la acción.
- Utilizar WP‑CLI (`wp action-scheduler list` y `wp action-scheduler cancel <hook>`) para inspeccionar o limpiar acciones según sea necesario.
## Compatibilidad y extensibilidad

- Los hooks heredados con prefijo `woocommerce_scheduled_subscription_*` siguen disparándose para mantener la compatibilidad con extensiones antiguas.
- El filtro `advanced_subscriptions_woocommerce_scheduled_action_group` permite redirigir dinámicamente las acciones a grupos personalizados.
- Los desarrolladores pueden inyectar su propio registrador implementando `WC_Logger_Interface` y pasando la instancia a `ASWC_Scheduler_API::set_logger()`.

## Integraciones externas y condiciones de carrera

- Los plugins externos deben programar acciones en grupos personalizados para evitar interferir con las tareas internas del plugin.
- La API ofrece operaciones idempotentes como `reschedule_action()` para reducir duplicados cuando múltiples procesos ajustan un mismo evento.
- Al limpiar un grupo específico mediante `unschedule_group()`, las acciones de otros grupos permanecen intactas, permitiendo que cada integración gestione su propia cola.
- En entornos con alta concurrencia se recomienda verificar la existencia de acciones con `has_scheduled_action()` antes de registrar nuevas tareas.
- Los fallos de red o excepciones lanzadas por proveedores externos al programar o reprogramar acciones se capturan y registran mediante el logger disponible, devolviendo `0` cuando no es posible programar la tarea.
- En despliegues con colas distribuidas (por ejemplo, Redis o RabbitMQ), cada nodo debe utilizar grupos independientes; si un nodo falla al reprogramar, la última reprogramación válida prevalece y el error queda registrado para su análisis.
- Cuando varios nodos comparten una cola distribuida real, las reprogramaciones realizadas por cualquier nodo son visibles para el resto y la última marca registrada es la que se ejecutará.
- Las limpiezas globales (`unschedule_group( false )`) eliminan las acciones de todos los nodos, por lo que deben coordinarse cuidadosamente en entornos distribuidos.
- Se recomienda que cada integración consulte `next_scheduled_action()` tras reprogramar para confirmar que la cola compartida refleja el cambio más reciente.

## Consideraciones sobre pagos y estados

- Cuando la orden de renovación ya se encuentra pagada y su total es mayor que cero, la API evita ejecutar un nuevo cobro y añade una nota a la suscripción indicando que se omitió el proceso por estar liquidada.
- Las órdenes de renovación con total igual a cero se omiten silenciosamente sin añadir notas ni intentar un nuevo cobro.
- Antes de ejecutar un pago programado, la API elimina cualquier reintento pendiente —incluidos los asignados a grupos personalizados— para evitar intentos duplicados.
- Las pasarelas pueden programar nuevos reintentos en grupos personalizados dentro del hook de pago; la limpieza previa garantiza que no se eliminen los reintentos recién registrados.
- Las suscripciones cuyo estado esté incluido en `ASWC_Scheduler_API::get_subscription_ended_statuses()` —incluyendo estados personalizados añadidos mediante filtros— no dispararán pagos ni reintentos programados.
- Cualquier reintento pendiente se desprograma automáticamente en estos casos para mantener la coherencia del sistema.

## Preguntas frecuentes

**¿Cómo programo múltiples acciones de una sola vez?**  
Utiliza helpers como `schedule_all_payments()` o `schedule_all_notifications()` que encapsulan la lógica necesaria.

**¿Puedo seguir usando Action Scheduler directamente?**  
Técnicamente sí, pero se recomienda utilizar la API para aprovechar las validaciones y mantener la consistencia de prefijos.

**¿Qué ocurre si desactivo el plugin y quedan acciones pendientes?**  
Las acciones permanecerán en la cola y sólo se ejecutarán cuando el plugin vuelva a activarse. Puedes depurarlas con WP‑CLI o `ASWC_Scheduler_API::unschedule_all()`.
## Referencia detallada de la API

A continuación se resumen los componentes disponibles y las funciones más
utilizadas de cada módulo. Todas las llamadas pueden ejecutarse mediante los
métodos estáticos de `ASWC_Scheduler_API` o a través de sus equivalentes con
prefijo `aswc_`.

### Núcleo

Envuelve a Action Scheduler y ofrece utilidades genéricas:

- `schedule_action()` / `schedule_recurring_action()` /
  `schedule_cron_action()` para programar eventos únicos, recurrentes o con
  sintaxis de WP‑Cron.
- `unschedule_action()` y `unschedule_group()` para cancelar eventos.
- `get_scheduled_action()` y `get_scheduled_actions()` para inspeccionar la
  cola de acciones.
- `claim_actions()` y `release_claim()` para gestionar lotes de acciones en
  procesos personalizados.
- `next_scheduled_action()` y `last_scheduled_action()` para recuperar el
  próximo o último timestamp registrado.

Estas utilidades son el fundamento de todos los módulos y pueden usarse de forma
independiente en integraciones avanzadas.

### Pagos

Gestiona los cobros de renovación y reintentos:

- `schedule_payment()` programa el próximo pago automático o manual.
- `schedule_retry()` y `schedule_retry_after()` registran reintentos de pago
  explícitos. `schedule_retry_with_rule()` acepta un objeto de regla con el
  intervalo definido por la pasarela.
- `unschedule_payment()` y `unschedule_retry()` limpian cualquier acción
  pendiente; `schedule_all_payments()` orquesta ambos.
- `get_scheduled_payment()` / `get_scheduled_retry()` devuelven el timestamp
  actual, mientras que `last_scheduled_payment()` y `last_scheduled_retry()`
  permiten auditar reprogramaciones anteriores.
- `has_scheduled_payment()` y `has_scheduled_retry()` comprueban la existencia de
  eventos pendientes.

Ejemplo rápido:

```php
// Programar un pago manual dentro de una hora
$time = time() + HOUR_IN_SECONDS;
ASWC_Scheduler_API::schedule_manual_payment( $subscription, $time );

// Programar un reintento 30 minutos después
ASWC_Scheduler_API::schedule_retry_after( $subscription, 30 * MINUTE_IN_SECONDS );
```

### Ciclo de vida

Controla eventos como fin de prueba, expiración y fin del periodo
prepagado:

- `schedule_trial_end()`, `schedule_expiration()` y
  `schedule_end_of_prepaid_term()` añaden los eventos correspondientes.
- `unschedule_*()` elimina los ganchos cuando dejan de ser necesarios.
- `get_scheduled_events()` y `get_last_scheduled_events()` exponen los
  timestamps actuales y los últimos registrados.
- `has_scheduled_events()` facilita comprobar si queda algún evento pendiente.

### Notificaciones

Encargado de avisar al cliente con antelación configurable:

- `schedule_notification()` programa un aviso individual; `schedule_all()`
  calcula automáticamente qué notificaciones son válidas según las fechas de la
  suscripción y el desfase definido.
- `unschedule_notification()`/`unschedule_all_notifications()` limpian los
  avisos.
- `get_scheduled_notifications()` y
  `get_last_scheduled_notifications()` permiten auditar los envíos.
- `notifications_globally_enabled()` comprueba el interruptor global y
  `get_time_offset()` devuelve el desfase en segundos aplicable a cada tipo.

Las opciones utilizadas por este módulo se almacenan con el prefijo
`advanced_subscriptions_woocommerce` y pueden consultarse mediante
`get_notification_offset_option_name()` o
`get_notification_switch_option_name()`.

### Procesos en segundo plano

El módulo **Background** permite reutilizar la infraestructura de la API para
cualquier tarea que no esté vinculada a una suscripción concreta:

- `schedule_background_action()` y `reschedule_background_action()` programan
  trabajos en el grupo `aswc_background_processes`.
- `has_scheduled_background_action()`,
  `next_scheduled_background_action()` y
  `get_scheduled_background_actions()` ofrecen utilidades de inspección.
- `unschedule_background_action()` o `unschedule_background_group()` eliminan
  las acciones pendientes.

### Gestor de acciones fallidas

`ASWC_Scheduler_Failed_Action_Manager` monitoriza las acciones que terminan en
error y registra información detallada en el log `failed-scheduled-actions`. Al
invocar `ASWC_Scheduler_API::init_failed_action_manager( $logger )` se habilita un
aviso en el panel de administración cuando se detectan fallos recurrentes.

### Helpers y utilidades

El directorio `core/aswc-core-functions.php` expone funciones auxiliares que
simplifican tareas habituales:

- `aswc_get_subscription_date_types()` devuelve los tipos de fecha soportados.
- `aswc_get_subscription_statuses()` y `aswc_get_subscription_ended_statuses()`
  permiten validar estados.
- `aswc_date_to_time()` convierte fechas MySQL a timestamps.
- `aswc_get_action_scheduler_store()` y sus wrappers (`aswc_save_action()`,
  `aswc_cancel_action()`, etc.) ofrecen un acceso seguro a la capa de datos de
  Action Scheduler.

## Glosario de hooks y grupos

- Grupo principal de suscripciones: `aswc_subscription_scheduled_event`.
- Grupo de notificaciones: `aswc_customer_notifications`.
- Grupo de procesos en segundo plano: `aswc_background_processes`.
- Los ganchos internos siguen el patrón `advanced_scheduled_*` y todos los
  filtros utilizan el prefijo `advanced_subscriptions_woocommerce_*` para evitar
  colisiones con otras extensiones.

Esta referencia pretende servir como mapa rápido de la API. Para obtener detalles
concretos revisa los archivos de cada módulo, donde encontrarás las firmas y la
lógica exacta de cada helper.

## Referencia detallada de la API

La documentación que sigue amplía la referencia rápida anterior y describe con
mayor precisión cada clase pública, los métodos expuestos y los parámetros
aceptados. Todas las funciones tienen equivalentes con el prefijo `aswc_`
(`ASWC_Scheduler_API::schedule_all()` → `aswc_schedule_all()`) para quien
prefiera un estilo procedimental.

### ASWC_Scheduler_API

Punto de entrada que orquesta todos los módulos. Sus utilidades son estáticas y
permiten trabajar con suscripciones sin instanciar clases manualmente.

| Método | Parámetros | Descripción |
| ------ | ---------- | ----------- |
| `schedule_all( $subscription, $notification_offset_cb = null, $notification_date_types = null, $group = null )` | `WC_Subscription` $subscription,<br>`callable` $notification_offset_cb,<br>`array` $notification_date_types,<br>`string|null` $group | Registra pagos, eventos de ciclo de vida y notificaciones en bloque. |
| `unschedule_all( $subscription, $notification_date_types = null, $group = null )` | `WC_Subscription` $subscription,<br>`array` $notification_date_types,<br>`string|null` $group | Elimina cualquier acción pendiente asociada a la suscripción. |
| `update_date( $subscription, $date_type, $datetime )` | `WC_Subscription` $subscription,<br>`string` $date_type,<br>`string` $datetime | Actualiza la fecha indicada y reprograma las acciones dependientes. |
| `delete_date( $subscription, $date_type )` | `WC_Subscription` $subscription,<br>`string` $date_type | Borra la fecha y desprograma los eventos relacionados. |
| `update_status( $subscription, $new_status, $old_status )` | `WC_Subscription` $subscription,<br>`string` $new_status,<br>`string` $old_status | Gestiona los cambios de estado y limpia las acciones incompatibles. |
| `get_scheduled_subscription_actions( $subscription, $group = null )` | `WC_Subscription` $subscription,<br>`string|bool|null` $group | Devuelve los objetos Action Scheduler para pagos y ciclo de vida. |
| `get_all_scheduled_subscription_actions( $subscription, $date_types = null, $group = null )` | `WC_Subscription` $subscription,<br>`array|null` $date_types,<br>`string|bool|null` $group | Combina acciones de pagos, ciclo y notificaciones. |
| `get_all_scheduled_events( $subscription, $date_types = null, $group = null )` | `WC_Subscription` $subscription,<br>`array|null` $date_types,<br>`string|bool|null` $group | Igual que el anterior pero devolviendo únicamente *timestamps*. |
| `has_scheduled_events( $subscription, $date_types = null, $group = null )` | `WC_Subscription` $subscription,<br>`array|null` $date_types,<br>`string|bool|null` $group | Comprueba si la suscripción tiene acciones pendientes. |

### Núcleo (`ASWC_Scheduler_Core`)

Abstrae Action Scheduler y centraliza la gestión de grupos, prioridades y
consultas. Se utiliza tanto de forma directa como a través de los módulos
especializados.

| Método | Descripción |
| ------ | ----------- |
| `schedule_action( $timestamp, $hook, $args = array(), $unique = false, $group = null )` | Programa una acción única. |
| `schedule_recurring_action( $timestamp, $interval, $hook, $args = array(), $unique = false, $group = null )` | Programa acciones recurrentes con intervalo fijo. |
| `schedule_cron_action( $timestamp, $cron, $hook, $args = array(), $unique = false, $group = null )` | Usa una expresión WP‑Cron para planificar repeticiones complejas. |
| `schedule_unique_action( $timestamp, $hook, $args = array(), $group = null, $priority = 10 )` | Igual que `schedule_action()` pero evitando duplicados. |
| `enqueue_async_action( $hook, $args = array(), $group = null )` | Inserta una tarea asincrónica que se ejecuta lo antes posible. |
| `reschedule_action( $timestamp, $hook, $args = array(), $group = null )` | Desprograma la acción existente y la vuelve a planificar. |
| `unschedule_action( $hook, $args, $group = null )` / `unschedule_actions( $hook, $args, $group = null )` | Elimina una o todas las acciones que coincidan con los argumentos. |
| `unschedule_group( $group = null )` | Borra todas las acciones de un grupo concreto. |
| `next_scheduled_action( $hook, $args = array(), $group = null )` / `last_scheduled_action( $hook, $args = array(), $group = null )` | Obtienen el próximo o último *timestamp* registrado. |
| `has_scheduled_action( $hook, $args = array(), $group = null )` | Confirma si existe una acción pendiente para ese hook. |
| `get_scheduled_action()` / `get_scheduled_actions()` | Recuperan uno o varios objetos `ActionScheduler_Action`. |
| `get_action( $id )`, `save_action( $action, $date )`, `delete_action( $id )` | Acceso de bajo nivel al almacén de datos de Action Scheduler. |
| Métodos como `claim_actions()`, `release_claim()`, `get_schedule_timestamp()` o `get_schedule_next_timestamp()` | Facilitan trabajar con *queues* distribuidas y objetos de programación. |

### Pagos (`ASWC_Scheduler_Payments`)

Controla pagos automáticos, manuales y reintentos.

| Método | Descripción |
| ------ | ----------- |
| `schedule_payment()` / `schedule_manual_payment()` | Programan la próxima renovación, sea automática o manual. |
| `schedule_retry()`, `schedule_retry_after()`, `schedule_retry_with_rule()` | Registran reintentos inmediatos, tras un intervalo o siguiendo la regla de la pasarela. |
| `unschedule_payment()` / `unschedule_retry()` / `unschedule_all()` | Limpian los eventos asociados. |
| `get_scheduled_payment()` / `get_scheduled_retry()` | Devuelven los *timestamps* de pago y reintento. |
| `get_scheduled_payment_action()` / `get_scheduled_retry_action()` | Recuperan los objetos `ActionScheduler_Action`. |
| `get_scheduled_payment_actions()` / `get_scheduled_retry_actions()` | Listan todas las acciones existentes. |
| `last_scheduled_payment()` / `last_scheduled_retry()` | Permiten auditar cuándo se planificó por última vez cada evento. |
| `has_scheduled_payment()` / `has_scheduled_retry()` | Verifican si hay acciones pendientes. |

### Ciclo de vida (`ASWC_Scheduler_Lifecycle`)

Coordina eventos que definen el estado de la suscripción.

| Método | Descripción |
| ------ | ----------- |
| `schedule_trial_end()` / `unschedule_trial_end()` | Gestionan el final del periodo de prueba. |
| `schedule_expiration()` / `unschedule_expiration()` | Marcan la suscripción como expirada. |
| `schedule_end_of_prepaid_term()` / `unschedule_end_of_prepaid_term()` | Controlan el fin del periodo prepagado. |
| `schedule_all()` / `unschedule_all()` | Orquestan todos los eventos anteriores de una sola vez. |
| `get_scheduled_events()` / `get_last_scheduled_events()` | Exponen los *timestamps* actuales y pasados. |
| `has_scheduled_events()` | Indica si queda algún evento de ciclo de vida por ejecutar. |

### Notificaciones (`ASWC_Scheduler_Notifications`)

Programa avisos al cliente con una antelación configurable.

| Método | Descripción |
| ------ | ----------- |
| `schedule_notification()` / `unschedule_notification()` | Trabajan con un único aviso para un tipo de fecha. |
| `schedule_all()` / `unschedule_all_notifications()` | Calculan qué avisos son válidos y los registran en bloque. |
| `get_scheduled_notifications()` / `get_last_scheduled_notifications()` | Devuelven los *timestamps* de los avisos. |
| `notifications_globally_enabled()` | Comprueba el interruptor global que habilita las notificaciones. |
| `get_time_offset()` / `convert_offset_to_seconds()` | Calculan el desfase temporal utilizado al programar avisos. |
| Métodos estáticos `get_offset_option_name()`, `get_switch_option_name()` y `get_settings_update_time()` | Permiten leer y escribir la configuración almacenada en la base de datos. |

### Procesos en segundo plano (`ASWC_Scheduler_Background`)

Extiende el núcleo para tareas no asociadas a una suscripción.

| Método | Descripción |
| ------ | ----------- |
| `schedule_background_action()` / `reschedule_background_action()` | Programan trabajos en el grupo `aswc_background_processes`. |
| `unschedule_background_action()` / `unschedule_background_group()` | Eliminan acciones pendientes de dicho grupo. |
| `has_scheduled_background_action()` / `next_scheduled_background_action()` | Consultan el estado de una tarea concreta. |
| `get_scheduled_background_actions()` / `get_last_scheduled_actions()` | Recuperan todos los objetos `ActionScheduler_Action` registrados. |

### Gestor de acciones fallidas

`ASWC_Scheduler_Failed_Action_Manager` supervisa acciones que terminan en error
y registra los detalles en el *log* `failed-scheduled-actions`. Al invocar
`ASWC_Scheduler_API::init_failed_action_manager( $logger )` se habilita el
monitor y, cuando detecta fallos recurrentes, muestra un aviso en el
administrador con enlaces para investigar o ignorar el problema.

### Hooks y filtros relevantes

Además de los hooks listados en la sección anterior, la API expone filtros que
permiten personalizar su comportamiento:

- `advanced_subscriptions_woocommerce_scheduled_action_priority` ajusta la
  prioridad de las acciones programadas.
- `advanced_subscriptions_woocommerce_scheduled_action_args` modifica los
  argumentos enviados a Action Scheduler.
- `advanced_subscriptions_woocommerce_scheduled_action_hook` permite cambiar el
  nombre del hook usado para un tipo de fecha.
- `advanced_subscriptions_woocommerce_scheduled_action_group` altera el grupo
  por defecto donde se almacenan las acciones.
- `aswc_subscription_customer_notification_*` controla qué estados
  disparan avisos, si el periodo es demasiado corto y el desfase aplicado a
  cada notificación.

### Ejemplo completo de integración

```php
require_once __DIR__ . '/scheduler-api/scheduler.php';

// Obtener una suscripción de WooCommerce.
$subscription = wcs_get_subscription( $subscription_id );

// Programar todos los eventos utilizando un desfase personalizado para las notificaciones.
ASWC_Scheduler_API::schedule_all(
    $subscription,
    function( $type ) {
        // Notificar 5 días antes del próximo pago y 2 días antes del resto.
        return 'next_payment' === $type ? 5 * DAY_IN_SECONDS : 2 * DAY_IN_SECONDS;
    }
);

// Consultar cuándo se ejecutará el próximo pago.
$timestamp = ASWC_Scheduler_API::payments()->get_scheduled_payment( $subscription );
```

Con esta información cualquier extensión puede aprovechar la Scheduler API para
programar, consultar o cancelar acciones de forma consistente y sin depender de
la implementación interna del plugin.

