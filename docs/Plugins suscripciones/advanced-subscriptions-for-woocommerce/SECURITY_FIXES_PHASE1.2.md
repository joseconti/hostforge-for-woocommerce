# CORRECCIONES DE SEGURIDAD - FASE 1.2
## Advanced Subscriptions for WooCommerce

**Objetivo:** Prevenir race conditions y validar montos en pagos
**Prioridad:** ALTA
**Fecha:** 2026-01-06

---

## 🎯 ENFOQUE DE FASE 1.2

Después del análisis exhaustivo, la Fase 1.1 ya cubrió todas las vulnerabilidades de **ownership** en las operaciones de usuario.

La Fase 1.2 se enfoca en:
1. ✅ **Transactional Locks** - Prevenir race conditions en pagos
2. ✅ **Amount Validation** - Validar montos antes de cobrar
3. ✅ **Helper Functions** - Crear funciones reutilizables de seguridad

---

## ✅ CORRECCIÓN 1: Implementar Sistema de Locks Transaccionales

**Archivo:** `includes/aswc-common-functions.php` (nuevo código)
**Problema:** Múltiples procesos pueden procesar el mismo pago simultáneamente
**Severidad:** CRÍTICA

### Crear Helper Functions para Locks:

```php
/**
 * Acquire a transactional lock for subscription payment processing.
 *
 * @param int $subscription_id Subscription ID.
 * @param int $timeout         Lock timeout in seconds (default: 300 = 5 minutes).
 * @return bool True if lock acquired, false if already locked.
 */
function aswc_acquire_payment_lock( $subscription_id, $timeout = 300 ) {
	$lock_key = 'aswc_payment_lock_' . absint( $subscription_id );
	$lock_value = time() + $timeout;

	// Try to set the lock.
	$result = add_option( $lock_key, $lock_value, '', 'no' );

	if ( ! $result ) {
		// Lock exists, check if it's expired.
		$existing_lock = get_option( $lock_key, 0 );
		if ( $existing_lock < time() ) {
			// Lock expired, delete and retry.
			delete_option( $lock_key );
			$result = add_option( $lock_key, $lock_value, '', 'no' );
		}
	}

	if ( $result && class_exists( 'ASWC_Log' ) ) {
		ASWC_Log::log( sprintf( 'Payment lock acquired for subscription %d', $subscription_id ) );
	}

	return $result;
}

/**
 * Release a transactional lock for subscription payment processing.
 *
 * @param int $subscription_id Subscription ID.
 * @return bool True if lock released.
 */
function aswc_release_payment_lock( $subscription_id ) {
	$lock_key = 'aswc_payment_lock_' . absint( $subscription_id );
	$result = delete_option( $lock_key );

	if ( $result && class_exists( 'ASWC_Log' ) ) {
		ASWC_Log::log( sprintf( 'Payment lock released for subscription %d', $subscription_id ) );
	}

	return $result;
}

/**
 * Check if a payment lock exists for a subscription.
 *
 * @param int $subscription_id Subscription ID.
 * @return bool True if locked.
 */
function aswc_is_payment_locked( $subscription_id ) {
	$lock_key = 'aswc_payment_lock_' . absint( $subscription_id );
	$lock_value = get_option( $lock_key, 0 );

	// Check if lock exists and hasn't expired.
	return $lock_value > time();
}
```

---

## ✅ CORRECCIÓN 2: Aplicar Locks en Scheduler API

**Archivo:** `scheduler-api/payments/class-aswc-scheduler-payments.php`
**Función:** `gateway_scheduled_subscription_payment()`
**Líneas:** 92-466

### Modificación Requerida:

**AGREGAR AL INICIO DE LA FUNCIÓN** (después de línea 138):

```php
// SECURITY: Acquire payment lock to prevent race conditions.
$subscription_id_for_lock = method_exists( $subscription, 'get_id' ) ? $subscription->get_id() : 0;
if ( ! aswc_acquire_payment_lock( $subscription_id_for_lock ) ) {
	if ( class_exists( 'ASWC_Log' ) ) {
		ASWC_Log::log( sprintf( '[gateway_scheduled_subscription_payment] Payment already being processed for subscription %d - aborting to prevent race condition', $subscription_id_for_lock ) );
	}
	return; // Another process is already handling this payment.
}
```

**AGREGAR AL FINAL DE LA FUNCIÓN** (antes de return/exit):

```php
// SECURITY: Always release the lock, even if payment fails.
aswc_release_payment_lock( $subscription_id_for_lock );
```

**IMPORTANTE:** Asegurar que el lock se libera en TODOS los return statements de la función.

---

## ✅ CORRECCIÓN 3: Validación de Monto Antes de Cobrar

**Archivo:** `includes/aswc-common-functions.php` (nuevo código)
**Problema:** No se valida que el monto a cobrar sea correcto
**Severidad:** ALTA

### Crear Helper Function para Validación:

```php
/**
 * Validate subscription payment amount before processing.
 *
 * @param int   $subscription_id Subscription ID.
 * @param float $amount_to_charge Amount about to be charged.
 * @return array Array with 'valid' boolean and 'message' string.
 */
function aswc_validate_payment_amount( $subscription_id, $amount_to_charge ) {
	$subscription = wc_get_order( $subscription_id );

	if ( ! $subscription ) {
		return array(
			'valid'   => false,
			'message' => 'Subscription not found',
		);
	}

	// Get expected amount from subscription meta.
	$expected_amount = floatval( aswc_get_meta_data( $subscription_id, 'aswc_recurring_total', true ) );

	// Allow small floating point differences (1 cent).
	$difference = abs( $amount_to_charge - $expected_amount );
	$tolerance = 0.01;

	if ( $difference > $tolerance ) {
		// Amount mismatch - log and reject.
		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( sprintf(
				'SECURITY WARNING: Payment amount mismatch for subscription %d. Expected: %s, Attempted: %s',
				$subscription_id,
				wc_price( $expected_amount ),
				wc_price( $amount_to_charge )
			) );
		}

		return array(
			'valid'   => false,
			'message' => sprintf(
				'Payment amount mismatch. Expected %s but got %s',
				wc_price( $expected_amount ),
				wc_price( $amount_to_charge )
			),
		);
	}

	// Validate amount is not negative.
	if ( $amount_to_charge < 0 ) {
		return array(
			'valid'   => false,
			'message' => 'Payment amount cannot be negative',
		);
	}

	// Validate amount doesn't exceed reasonable maximum.
	$max_amount = apply_filters( 'aswc_max_payment_amount', 999999 );
	if ( $amount_to_charge > $max_amount ) {
		return array(
			'valid'   => false,
			'message' => sprintf( 'Payment amount exceeds maximum allowed: %s', wc_price( $max_amount ) ),
		);
	}

	return array(
		'valid'   => true,
		'message' => 'Amount validated successfully',
	);
}
```

---

## ✅ CORRECCIÓN 4: Aplicar Validación de Monto en Scheduler

**Archivo:** `scheduler-api/payments/class-aswc-scheduler-payments.php`
**Función:** `gateway_scheduled_subscription_payment()`

### Modificación Requerida:

**AGREGAR ANTES DE PROCESAR EL PAGO** (buscar donde se obtiene el total del renewal order):

```php
// SECURITY: Validate payment amount before charging.
$renewal_order_total = $latest_renewal_order->get_total();
$validation = aswc_validate_payment_amount( $subscription->get_id(), $renewal_order_total );

if ( ! $validation['valid'] ) {
	if ( class_exists( 'ASWC_Log' ) ) {
		ASWC_Log::log( sprintf(
			'[gateway_scheduled_subscription_payment] Payment validation failed for subscription %d: %s',
			$subscription->get_id(),
			$validation['message']
		) );
	}

	// Add order note about validation failure.
	$latest_renewal_order->add_order_note(
		sprintf(
			__( 'Payment failed validation: %s', 'advanced-subscriptions-for-woocommerce' ),
			$validation['message']
		)
	);

	// Release lock and abort.
	aswc_release_payment_lock( $subscription->get_id() );
	return;
}
```

---

## ✅ CORRECCIÓN 5: Helper Function para Ownership Validation (Reutilizable)

**Archivo:** `includes/aswc-common-functions.php` (nuevo código)
**Objetivo:** Crear función reutilizable para futuras validaciones

```php
/**
 * Verify that current user owns the subscription.
 *
 * @param int  $subscription_id Subscription ID.
 * @param bool $allow_admin     Whether to allow admin users (default: true).
 * @return array Array with 'valid' boolean, 'message' string, and 'user_id' int.
 */
function aswc_verify_subscription_ownership( $subscription_id, $allow_admin = true ) {
	$current_user_id = get_current_user_id();

	if ( ! $current_user_id ) {
		return array(
			'valid'   => false,
			'message' => __( 'You must be logged in to access subscriptions.', 'advanced-subscriptions-for-woocommerce' ),
			'user_id' => 0,
		);
	}

	// Get subscription.
	$subscription = wc_get_order( $subscription_id );

	if ( ! $subscription ) {
		return array(
			'valid'   => false,
			'message' => __( 'Subscription not found.', 'advanced-subscriptions-for-woocommerce' ),
			'user_id' => $current_user_id,
		);
	}

	// Get subscription owner.
	$subscription_customer_id = $subscription->get_customer_id();

	// Check if user owns subscription.
	if ( $current_user_id === $subscription_customer_id ) {
		return array(
			'valid'   => true,
			'message' => 'User owns subscription',
			'user_id' => $current_user_id,
		);
	}

	// Check if admin (if allowed).
	if ( $allow_admin && current_user_can( 'manage_woocommerce' ) ) {
		return array(
			'valid'   => true,
			'message' => 'Admin user - access granted',
			'user_id' => $current_user_id,
		);
	}

	// Access denied.
	return array(
		'valid'   => false,
		'message' => __( 'You do not have permission to access this subscription.', 'advanced-subscriptions-for-woocommerce' ),
		'user_id' => $current_user_id,
	);
}
```

---

## 📋 RESUMEN DE CORRECCIONES FASE 1.2

| # | Corrección | Archivo | Tipo |
|---|------------|---------|------|
| 1 | Sistema de locks transaccionales | `includes/aswc-common-functions.php` | Helper Functions (NEW) |
| 2 | Aplicar locks en Scheduler | `scheduler-api/payments/class-aswc-scheduler-payments.php` | Modificación |
| 3 | Validación de montos | `includes/aswc-common-functions.php` | Helper Function (NEW) |
| 4 | Aplicar validación en Scheduler | `scheduler-api/payments/class-aswc-scheduler-payments.php` | Modificación |
| 5 | Helper ownership validation | `includes/aswc-common-functions.php` | Helper Function (NEW) |

---

## 🔒 IMPACTO DE SEGURIDAD

### Problemas Prevenidos:

✅ **Race Conditions:** Múltiples procesos no pueden cobrar el mismo pago simultáneamente
✅ **Double Charging:** Sistema de locks previene cobros duplicados
✅ **Amount Tampering:** Validación asegura que el monto sea el correcto
✅ **Negative Amounts:** Validación previene montos negativos
✅ **Excessive Amounts:** Límite máximo configurable
✅ **Code Reusability:** Funciones helper para uso futuro

---

## 🧪 TESTING REQUERIDO

### Test 1: Race Condition Prevention
```bash
# Simular múltiples procesos intentando cobrar simultáneamente
# Esperar: Solo uno debe procesar, otros deben abortar con log
```

### Test 2: Lock Expiration
```bash
# Adquirir lock y dejarlo sin liberar
# Esperar: Lock expira después del timeout (5 minutos)
# Nuevo proceso debe poder adquirir el lock
```

### Test 3: Amount Validation
```bash
# Intentar procesar pago con monto diferente al esperado
# Esperar: Pago rechazado con mensaje de error
```

### Test 4: Lock Release on Error
```bash
# Forzar error durante procesamiento de pago
# Esperar: Lock se libera correctamente
```

---

## ⚠️ CONSIDERACIONES IMPORTANTES

1. **Locks deben liberarse SIEMPRE:** Incluso si hay errores, usar try-finally o asegurar release en todos los return statements

2. **Timeout del Lock:** 5 minutos es adecuado para pagos normales, pero puede ajustarse si hay problemas

3. **Amount Tolerance:** Diferencia de 1 centavo permitida para compensar redondeos de floating point

4. **Backward Compatibility:** Las funciones helper son nuevas, no rompen código existente

5. **Performance:** Los locks usan `add_option/delete_option` que es más rápido que transients para esta finalidad

---

**Estado:** Listo para implementar
**Archivos a Modificar:** 2
**Funciones a Crear:** 5
**Prioridad:** ALTA
**Tiempo Estimado:** 1-2 horas

