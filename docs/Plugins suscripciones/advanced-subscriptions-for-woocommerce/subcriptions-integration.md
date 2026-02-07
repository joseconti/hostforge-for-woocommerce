# Integración de métodos de pago en Advanced Subscriptions For WooCommerce

Este documento explica cómo los desarrolladores pueden añadir compatibilidad de sus métodos de pago con el plugin **Advanced Subscriptions For WooCommerce**.

## Declarar soporte del método de pago

Cuando el carrito contiene una suscripción el plugin solo permite los gateways compatibles. Los métodos de pago deben declarar soporte añadiendo la capacidad `subscriptions_jc` en la propiedad `$supports` del gateway:

```php
class Mi_Gateway extends WC_Payment_Gateway {
    public function __construct() {
        $this->supports = array( 'products', 'subscriptions_jc' );
    }
}
```

No es necesario aplicar filtros adicionales para declarar compatibilidad; basta con añadir la capacidad `subscriptions_jc` en el array `$supports` del gateway.

Si deseas mostrar tu plugin en la página de configuración del plugin puedes añadir tus datos mediante `aswc_jc_supported_data_payment_for_configuration`.

## Detección del resultado del pago

El cambio de estado de los pedidos se comunica mediante la acción `aswc_jc_order_status_changed` que recibe el identificador del pedido y el de la suscripción relacionada. Puedes usarla para comprobar si el pago se completó correctamente.

```php
add_action( 'aswc_jc_order_status_changed', function ( $order_id, $subscription_id ) {
    // Lógica personalizada tras el cambio de estado del pedido.
});
```

## Cobro de renovaciones

Cuando se genera un nuevo pedido de renovación se dispara el filtro
`aswc_jc_process_renewal_payment_{gateway}` donde *{gateway}* es el ID de tu
método de pago. Este filtro debe devolver `true` o `false` indicando si el cobro
se realizó correctamente.

```php
add_filter( 'aswc_jc_process_renewal_payment_mi_gateway', function ( $status, $order_id, $amount, $subscription_id, $order ) {
    // Ejecutar el cobro usando $order_id y $amount y devolver true/false.
    return $status;
}, 10, 5 );
```

El hook se ejecuta tras crear el pedido de renovación y antes de que el plugin realice más operaciones sobre él, por lo que es el momento ideal para procesar el pago.


## Resumen

1. Declara la compatibilidad de tu gateway añadiendo `subscriptions_jc` a `$supports`.
2. Escucha la acción `aswc_jc_order_status_changed` para saber si el pago fue correcto.
3. Implementa la lógica del cobro recurrente usando el filtro `aswc_jc_process_renewal_payment_{gateway}` y devuelve `true` o `false` según el resultado.

Con estos puntos cualquier desarrollador puede integrar su método de pago con Advanced Subscriptions For WooCommerce.
