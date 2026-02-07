# CORRECCIONES DE SEGURIDAD - FASE 1
## Advanced Subscriptions for WooCommerce

Este documento contiene todas las correcciones críticas de seguridad que deben aplicarse.

---

## ✅ CORRECCIÓN 1: aswc_cancel_recurring_payment() - IDOR Fix

**Archivo:** `includes/loader/admin/class-aswc-loaderadmin.php`
**Líneas:** 1548-1557
**Problema:** Cualquier usuario autenticado puede cancelar cualquier suscripción
**Severidad:** CRÍTICA

### Código ACTUAL (INSEGURO):
```php
public function aswc_cancel_recurring_payment() {

		check_ajax_referer( 'aswc_public_nonce', 'nonce' );

	$aswc_subscription_id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
	if ( $aswc_subscription_id ) {
		aswc_update_order_meta( $aswc_subscription_id, 'aswc_user_cancelled_recurring', 'yes' );
	}
		wp_die();
}
```

### Código CORREGIDO (SEGURO):
```php
public function aswc_cancel_recurring_payment() {

		check_ajax_referer( 'aswc_public_nonce', 'nonce' );

	$aswc_subscription_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

	if ( ! $aswc_subscription_id ) {
		wp_send_json_error( array( 'message' => __( 'Invalid subscription ID.', 'advanced-subscriptions-for-woocommerce' ) ) );
	}

	// SECURITY: Verify subscription exists.
	$subscription = wc_get_order( $aswc_subscription_id );
	if ( ! $subscription ) {
		wp_send_json_error( array( 'message' => __( 'Subscription not found.', 'advanced-subscriptions-for-woocommerce' ) ) );
	}

	// SECURITY: Verify ownership - user must own this subscription or be admin.
	$current_user_id = get_current_user_id();
	$subscription_customer_id = $subscription->get_customer_id();

	if ( $current_user_id !== $subscription_customer_id && ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission to cancel this subscription.', 'advanced-subscriptions-for-woocommerce' ) ) );
	}

	// Update subscription status.
	aswc_update_order_meta( $aswc_subscription_id, 'aswc_user_cancelled_recurring', 'yes' );

	// Log the action for audit trail.
	if ( class_exists( 'ASWC_Log' ) ) {
		ASWC_Log::log( sprintf( 'User %d cancelled recurring payment for subscription %d', $current_user_id, $aswc_subscription_id ) );
	}

	wp_send_json_success( array( 'message' => __( 'Recurring payment cancelled successfully.', 'advanced-subscriptions-for-woocommerce' ) ) );
}
```

**Cambios aplicados:**
1. ✅ Usar `absint()` en lugar de `sanitize_text_field()` para IDs
2. ✅ Validar que subscription_id no sea 0
3. ✅ Verificar que la suscripción exista
4. ✅ **CRÍTICO:** Verificar ownership antes de permitir cancelación
5. ✅ Usar `wp_send_json_success/error` en lugar de `wp_die()`
6. ✅ Agregar logging de auditoría

---

## ✅ CORRECCIÓN 2: aswc_show_parent_order_for_custom_manual_callback() - Information Disclosure Fix

**Archivo:** `includes/loader/admin/class-aswc-loaderadmin.php`
**Líneas:** 1850-1893
**Problema:** Cualquier usuario puede ver órdenes de cualquier cliente sin permisos de admin
**Severidad:** CRÍTICA

### Instrucciones:
1. Buscar la función `aswc_show_parent_order_for_custom_manual_callback()`
2. Agregar **DESPUÉS** de `check_ajax_referer`:

```php
// SECURITY: Only administrators can view customer orders.
if ( ! current_user_can( 'manage_woocommerce' ) ) {
	wp_send_json_error( array( 'message' => __( 'Permission denied.', 'advanced-subscriptions-for-woocommerce' ) ) );
}
```

3. Cambiar todas las instancias de:
   - `echo json_encode(...)` → `wp_send_json_success( array( 'html' => $html ) )`

4. Asegurar que el HTML está escapado:
   - `$order->get_id()` → `absint( $order->get_id() )`
   - En el HTML: `esc_attr()` para atributos, `esc_html()` para contenido

---

## ✅ CORRECCIÓN 3: aswc_update_subscription_items_callback() - Price Manipulation Fix

**Archivo:** `includes/loader/admin/class-aswc-loaderadmin.php`
**Líneas:** 2445-2596
**Problema:** Cualquier usuario puede modificar precios de suscripciones sin permisos
**Severidad:** CRÍTICA

### Instrucciones:
1. Buscar la función `aswc_update_subscription_items_callback()`
2. Agregar **DESPUÉS** de `check_ajax_referer`:

```php
// SECURITY: Only administrators can modify subscription items and prices.
if ( ! current_user_can( 'manage_woocommerce' ) ) {
	wp_send_json_error( array( 'message' => __( 'Permission denied.', 'advanced-subscriptions-for-woocommerce' ) ) );
}
```

3. Agregar validación de precios **ANTES** de `aswc_update_order_meta`:

```php
// SECURITY: Validate price is not negative and within reasonable bounds.
$subscription_price = floatval( $subscription_price );
if ( $subscription_price < 0 ) {
	wp_send_json_error( array( 'message' => __( 'Invalid price: cannot be negative.', 'advanced-subscriptions-for-woocommerce' ) ) );
}

// Optional: Add maximum price validation
$max_price = apply_filters( 'aswc_max_subscription_price', 999999 );
if ( $subscription_price > $max_price ) {
	wp_send_json_error( array( 'message' => __( 'Invalid price: exceeds maximum allowed.', 'advanced-subscriptions-for-woocommerce' ) ) );
}
```

---

## ✅ CORRECCIÓN 4: aswc_admin_cancel_susbcription() - Nonce Verification Fix

**Archivo:** `admin/class-aswc-admin.php`
**Líneas:** 644-668
**Problema:** Nonce no se verifica correctamente, solo se comprueba existencia
**Severidad:** CRÍTICA

### Código ACTUAL (INSEGURO):
```php
if ( isset( $_GET['aswc_subscription_status_admin'] ) && isset( $_GET['aswc_subscription_id'] ) && isset( $_GET['_wpnonce'] ) && ! empty( $_GET['_wpnonce'] ) ) {
	$aswc_status = sanitize_text_field( wp_unslash( $_GET['aswc_subscription_status_admin'] ) );
	$aswc_subscription_id = sanitize_text_field( wp_unslash( $_GET['aswc_subscription_id'] ) );
	if ( aswc_check_valid_subscription( $aswc_subscription_id ) ) {
		// Cancela suscripción SIN VERIFICAR EL NONCE
```

### Código CORREGIDO (SEGURO):
```php
if ( isset( $_GET['aswc_subscription_status_admin'] ) && isset( $_GET['aswc_subscription_id'] ) && isset( $_GET['_wpnonce'] ) && ! empty( $_GET['_wpnonce'] ) ) {

	// SECURITY: Verify capability first.
	if ( ! current_user_can( 'edit_shop_orders' ) ) {
		wp_die( esc_html__( 'You do not have permission to perform this action.', 'advanced-subscriptions-for-woocommerce' ), 403 );
	}

	$aswc_status = sanitize_text_field( wp_unslash( $_GET['aswc_subscription_status_admin'] ) );
	$aswc_subscription_id = absint( $_GET['aswc_subscription_id'] );

	// SECURITY: Verify nonce.
	$nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );
	if ( ! wp_verify_nonce( $nonce, 'aswc_cancel_subscription_' . $aswc_subscription_id ) ) {
		wp_die( esc_html__( 'Security check failed.', 'advanced-subscriptions-for-woocommerce' ), 403 );
	}

	if ( aswc_check_valid_subscription( $aswc_subscription_id ) ) {
```

**IMPORTANTE:** También hay que actualizar la generación del nonce en el enlace de cancelación:

**Archivo:** `admin/partials/class-aswc-admin-subscription-list.php`
**Líneas:** 75-82

Cambiar:
```php
$aswc_link = wp_nonce_url( $aswc_link, $subscription_id . $status );
```

Por:
```php
$aswc_link = wp_nonce_url( $aswc_link, 'aswc_cancel_subscription_' . $subscription_id );
```

---

## ✅ CORRECCIÓN 5: aswc_admin_reactivate_onhold_susbcription() - Same Security Issues

**Archivo:** `admin/class-aswc-admin.php`
**Líneas:** 1065-1082
**Problema:** Mismos problemas que la cancelación
**Severidad:** CRÍTICA

### Aplicar las mismas correcciones que CORRECCIÓN 4:
1. Agregar `current_user_can( 'edit_shop_orders' )`
2. Agregar `wp_verify_nonce()` con action específico
3. Usar `absint()` para el ID

---

## ✅ CORRECCIÓN 6: aswc_admin_pause_susbcription() - Missing Capability Check

**Archivo:** `includes/loader/admin/class-aswc-loaderadmin.php`
**Líneas:** 1040-1060
**Problema:** Verifica nonce pero no capabilities
**Severidad:** CRÍTICA

### Agregar ANTES del `if ( wp_verify_nonce... )`:
```php
// SECURITY: Only admins can pause subscriptions.
if ( ! current_user_can( 'edit_shop_orders' ) ) {
	wp_die( esc_html__( 'You do not have permission to pause subscriptions.', 'advanced-subscriptions-for-woocommerce' ), 403 );
}
```

---

## ✅ CORRECCIÓN 7: aswc_create_manually_recurring() - Missing Capability Check

**Archivo:** `includes/loader/admin/class-aswc-loaderadmin.php`
**Líneas:** 1015-1025
**Problema:** Verifica nonce pero no capabilities
**Severidad:** CRÍTICA

### Agregar ANTES del `if ( wp_verify_nonce... )`:
```php
// SECURITY: Only admins can create manual recurring orders.
if ( ! current_user_can( 'edit_shop_orders' ) ) {
	wp_die( esc_html__( 'You do not have permission to create recurring orders.', 'advanced-subscriptions-for-woocommerce' ), 403 );
}
```

---

## ✅ CORRECCIÓN 8: aswc_export_csv_report() - Missing Capability and Nonce

**Archivo:** `includes/loader/admin/class-aswc-loaderadmin.php`
**Líneas:** 1090-1093
**Problema:** NO verifica nonce NI capabilities
**Severidad:** CRÍTICA

### Código CORREGIDO:
```php
public function aswc_export_csv_report() {

	// SECURITY: Verify capability first.
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'You do not have permission to export subscription data.', 'advanced-subscriptions-for-woocommerce' ), 403 );
	}

	// SECURITY: Verify nonce.
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'aswc_export_csv' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'advanced-subscriptions-for-woocommerce' ), 403 );
	}

	if ( isset( $_GET['aswc_csv_export'] ) && ! empty( $_GET['aswc_csv_export'] ) ) {
		$aswc_export_csv = sanitize_text_field( wp_unslash( $_GET['aswc_csv_export'] ) );
		if ( 'aswc_csv_report' === $aswc_export_csv ) {
			// ... resto del código de exportación ...
```

**IMPORTANTE:** También hay que agregar el nonce al enlace de exportación donde se genera el link.

---

## 📋 RESUMEN DE CORRECCIONES FASE 1.1

| # | Función | Archivo | Líneas | Corrección |
|---|---------|---------|--------|------------|
| 1 | `aswc_cancel_recurring_payment()` | loader/admin/class-aswc-loaderadmin.php | 1548-1557 | ✅ Ownership validation |
| 2 | `aswc_show_parent_order_for_custom_manual_callback()` | loader/admin/class-aswc-loaderadmin.php | 1850-1893 | ✅ Capability check |
| 3 | `aswc_update_subscription_items_callback()` | loader/admin/class-aswc-loaderadmin.php | 2445-2596 | ✅ Capability + price validation |
| 4 | `aswc_admin_cancel_susbcription()` | admin/class-aswc-admin.php | 644-668 | ✅ Nonce verification |
| 5 | `aswc_admin_reactivate_onhold_susbcription()` | admin/class-aswc-admin.php | 1065-1082 | ✅ Nonce verification |
| 6 | `aswc_admin_pause_susbcription()` | loader/admin/class-aswc-loaderadmin.php | 1040-1060 | ✅ Capability check |
| 7 | `aswc_create_manually_recurring()` | loader/admin/class-aswc-loaderadmin.php | 1015-1025 | ✅ Capability check |
| 8 | `aswc_export_csv_report()` | loader/admin/class-aswc-loaderadmin.php | 1090-1093 | ✅ Capability + nonce |

---

## 🔒 VALIDACIÓN POST-CORRECCIÓN

Después de aplicar estas correcciones, verificar:

1. ✅ Los usuarios regulares NO pueden cancelar suscripciones de otros
2. ✅ Los usuarios regulares NO pueden ver órdenes de otros clientes
3. ✅ Los usuarios regulares NO pueden modificar precios
4. ✅ Todas las operaciones administrativas requieren `manage_woocommerce` o `edit_shop_orders`
5. ✅ Todos los nonces se verifican correctamente
6. ✅ Se registran logs de las operaciones críticas

---

**Estado:** FASE 1.1 COMPLETA ✅
**Próximo paso:** FASE 1.2 - Agregar validación de ownership en operaciones del Scheduler API
