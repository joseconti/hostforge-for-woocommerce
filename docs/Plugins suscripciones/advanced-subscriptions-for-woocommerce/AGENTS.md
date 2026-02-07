# AGENTS.md

## 🧠 Propósito

Este documento establece las directrices que **Codex** debe seguir al desarrollar cualquier funcionalidad, clase, bloque o plugin para José Conti. Todo lo desarrollado debe cumplir **estrictamente** con los estándares y buenas prácticas de **WordPress**, **WooCommerce**, y las preferencias específicas de desarrollo del proyecto.

---

## 🎯 Objetivos generales

1. **Seguridad y buenas prácticas ante todo.**
2. Código que pase **PHP CodeSniffer (PHPCS)** sin errores ni warnings usando la **WordPress Coding Standards**.
3. Estructura clara, organizada y modular.
4. Soporte completo para **checkout por bloques y shortcode** en WooCommerce si aplica.
5. El código debe estar **internacionalizado (i18n)** con dominio de texto configurable.
6. Todos los nombres, etiquetas, clases y estructuras deben ser **personalizados y únicos** para evitar conflictos o relación con otros plugins.
7. Ningún archivo debe terminar sin **una línea en blanco**.

---

## 🧱 Estructura general esperada

Todos los plugins o funcionalidades deben tener una estructura clara como esta:

plugin-name/
├── assets/
│   ├── js/
│   └── css/
├── includes/
│   ├── class-nombre-clase.php
│   └── …
├── templates/
├── languages/
├── uninstall.php
├── plugin-name.php  ← archivo principal del plugin

- Las clases deben guardarse en archivos llamados `class-nombre-clase.php`.
- Deben usar `namespace` si es un proyecto moderno, o `class_exists()` si es legado.
- Cada clase debe tener su función `get_instance()` si aplica el patrón singleton.
- No debe haber lógica directa en el archivo principal. Todo debe encapsularse.

---

## ✍️ Requisitos de codificación

### 📌 Funciones

- Todas las funciones deben tener docblocks al estilo PHPDoc.
- Los comentarios deben estar **en inglés**.
- Usar siempre estilo **Yoda** para condicionales.
- Los datos de entrada deben validarse con:
  - `isset()`
  - `! empty()`
  - `sanitize_text_field()`, `esc_html()`, `esc_url_raw()`, etc., según corresponda.
- Todos los formularios deben usar `wp_nonce_field()` y verificar el nonce en el `$_POST`.

### 📌 Hooks

- Las funciones deben registrarse con `add_action()` o `add_filter()` **en métodos dedicados**, nunca inline.
- No debe haber duplicidad de acciones ni nombres genéricos.

### 📌 Roles y capacidades

Si se registran roles o capabilities:
- Usar `add_role()` y `add_cap()` correctamente.
- Asegurarse de no crear conflictos con otros plugins.
- Respetar la separación entre permisos de lectura, edición, administración, etc.

---

## 🧪 Validaciones obligatorias

Antes de entregar o finalizar una tarea, el código debe pasar:

### ✅ Validadores obligatorios

1. **PHP CodeSniffer (PHPCS)** con las reglas:
   - `WordPress-Core`
   - `WordPress-Extra`
   - `WordPress-Docs`
   - `WordPress-DB`
2. **Eslint** (si se usa React/JS), con los presets de Gutenberg si aplica.
3. **Pruebas funcionales** si hay integración con WooCommerce:
   - Añadir al carrito.
   - Checkout (bloques y shortcode).
   - Notificaciones, errores, redirecciones.
4. **Traducción** con `__()` o `_e()` usando el dominio de texto indicado.

---

## 🌐 Internacionalización

- Todo debe estar preparado para traducción.
- No debe haber texto plano en el frontend o backend sin estar envuelto en `__()` o `_e()`.
- Se debe usar `load_plugin_textdomain()` correctamente.

---

## 📁 Nombres

- Los nombres de funciones, clases y archivos deben comenzar por el prefijo del plugin o funcionalidad.
  - Ejemplo: `subscriptions_jc_create_order()`, `class-subscriptions-jc-order-manager.php`
- Evitar cualquier uso de `woo`, `woocommerce`, `gf`, `gravityforms` en nombres internos si el plugin es personalizado.

---

## 🚫 WooCommerce Subscriptions

- Este plugin no debe depender de **WooCommerce Subscriptions** ni utilizar sus hooks, filtros, clases o funciones.
- Toda la documentación, comentarios y mensajes deben referenciar al plugin exclusivamente como **"Advanced Subscriptions for WooCommerce"**; evita mencionar nombres legados como "WooCommerce Subscriptions" o "WooCommerce Subscriptions Pro".
- Todos los hooks, filtros, clases y funciones personalizados deben usar el prefijo `aswc_`.

---

## 📚 Base de conocimiento obligatoria

- Mantén el archivo `Docuements.md` como la fuente de conocimiento del proyecto, documentando funciones internas, helpers, acciones, filtros y ejemplos de uso con parámetros y valores de retorno.
- Cada vez que se cree o modifique funcionalidad que afecte helpers, acciones o filtros, actualiza `Docuements.md` en la misma tarea.

---

## 🛡️ Seguridad

- Todos los accesos a `$_POST`, `$_GET`, `$_REQUEST` y `$_COOKIE` deben estar validados y saneados.
- No se debe asumir que un dato está presente sin validarlo.
- Siempre usar `check_admin_referer()` o verificación de nonce.
- Evitar exponer endpoints sin autenticación si no es estrictamente necesario.

---

## 🛡️ Acciones programadas

- Nunca usar `wp_schedule_event()` directamente.  
- Usar siempre **Action Scheduler** de WooCommerce si WooCommerce está presente.
- Si **el plugin no depende directamente de WooCommerce**, se debe instalar manualmente la librería **[Action Scheduler](https://actionscheduler.org/usage/)** como dependencia.  
- Esta librería debe incluirse correctamente como subpaquete o usando Composer (`composer require woocommerce/action-scheduler`) y cargarse de forma condicional.
- Usar nombres únicos y manejables para los hooks programados (`subscriptions_jc_scheduled_cleanup`, por ejemplo).
- Las acciones deben registrarse con funciones bien documentadas.

---

## 🧪 Testing mínimo requerido

### Manual

- Verificar que todos los formularios y entradas se guardan correctamente.
- Navegación clara y sin errores en el admin.
- Compatibilidad con navegadores modernos (Chrome, Firefox, Safari).
- Soporte en pantallas móviles si aplica.

### Automatizado (opcional pero deseado)

- Pruebas con PHPUnit si es posible.
- Comprobación de errores en el log de WP.
- Testear con WP_DEBUG y WP_DEBUG_LOG activos.

---

## 💬 Soporte a bloques de WooCommerce

Si el código afecta al checkout:

- Debe funcionar tanto en el checkout tradicional (shortcode) como en el nuevo **Checkout Block**.
- Debe usarse `ExperimentalOrderMeta` si hay que guardar datos en el pedido desde bloques.
- Debe registrarse el bloque con `registerCheckoutBlock()` en JS.
- Toda funcionalidad debe estar encapsulada en JS y comunicarse correctamente con la Store `wc/store`.

---

## ✅ Entregables esperados

- Archivos bien organizados y completos.
- Código limpio y comentado.
- Plugin funcional, sin errores fatales ni warnings.
- Que pase todos los validadores mencionados.
- Compatible con las versiones actuales de WP y WooCommerce.

---

## ❌ Qué evitar siempre

- Código inline en archivos raíz.
- Duplicación de funciones o lógica.
- Uso de funciones obsoletas (`create_function`, `mysql_*`, etc.).
- Texto sin traducir.
- Archivos sin `<?php` al inicio o sin línea en blanco final.

---

## 📎 Resumen final

> Si no estás 100% seguro de que el código cumple con todos los puntos anteriores, no lo entregues ni hagas pull request. Todo el código debe poder ser usado en producción sin tener que "arreglarlo después". Cualquier desviación debe ser previamente acordada con José Conti.

