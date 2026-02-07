<?php

/**
 * Bulk Action Settings.
 * 
 * @class SUMOSubs_Admin_Settings_Bulk_Action
 */
class SUMOSubs_Admin_Settings_Bulk_Action extends SUMOSubs_Abstract_Admin_Settings {

    /**
     * SUMOSubs_Admin_Settings_Bulk_Action constructor.
     */
    public function __construct() {
        $this->id            = 'bulk_action';
        $this->label         = __( 'Bulk Action', 'sumosubscriptions' );
        $this->custom_fields = array(
            'get_tab_note',
            'get_bulk_type_selector',
            'get_products_selector',
            'get_product_categories_selector',
            'get_product_status',
            'get_subscription_duration',
            'get_subscription_trial_status',
            'get_trial_type',
            'get_trial_fee',
            'get_trial_duration',
            'get_subscription_signup_status',
            'get_signup_fee',
            'get_recurring',
            'get_update_button_for_products',
            'get_deleted_product',
            'get_replace_product',
            'get_update_button_for_subscriptions',
        );
        $this->settings      = $this->get_settings();
        $this->init();

        add_action( 'sumosubscriptions_submit_' . $this->id, '__return_false' );
        add_action( 'sumosubscriptions_reset_' . $this->id, '__return_false' );
    }

    /**
     * Get settings array.
     *
     * @return array
     */
    public function get_settings() {
        global $current_section;

        /**
         * Get the admin settings.
         * 
         * @since 1.0
         */
        return apply_filters( 'sumosubscriptions_get_' . $this->id . '_settings', array(
            array(
                'type' => $this->get_custom_field_type( 'get_tab_note' ),
            ),
            array(
                'name' => __( 'Subscription Product Settings Bulk Update', 'sumosubscriptions' ),
                'type' => 'title',
                'id'   => 'bulk_update_section',
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_bulk_type_selector' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_products_selector' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_product_categories_selector' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_product_status' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_subscription_duration' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_subscription_trial_status' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_trial_type' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_trial_fee' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_trial_duration' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_subscription_signup_status' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_signup_fee' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_recurring' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_update_button_for_products' ),
            ),
            array( 'type' => 'sectionend', 'id' => 'bulk_update_section' ),
            array(
                'name' => __( 'Troubleshoot - Deleted Product Replacement', 'sumosubscriptions' ),
                'type' => 'title',
                'id'   => $this->id . '_deleted_product_replacement_settings',
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_tab_description_for_deleted_product_replacement' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_deleted_product' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_replace_product' ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_update_button_for_subscriptions' ),
            ),
            array( 'type' => 'sectionend', 'id' => $this->id . '_deleted_product_replacement_settings' ),
                ) );
    }

    /**
     * Custom type field.
     */
    public function get_tab_note() {
        ?>
        <tr>
            <?php echo wp_kses_post( __( 'Using bulk update, you can enable/disable/modify subscription values in edit product page for all/multiple products at once. <br>Note: If you are bulk updating large number of products, then it may take some time to update.', 'sumosubscriptions' ) ); ?>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_bulk_type_selector() {
        ?>
        <tr>
            <th>
                <?php esc_html_e( 'Select Products/Categories', 'sumosubscriptions' ); ?>
            </th>
            <td>
                <select class="sumosubs-select" name="selected_bulk_type" id="selected_bulk_type">
                    <option value="all-products" <?php selected( 'all-products' === get_option( 'bulk_sumosubs_selected_bulk_type', 'all-products' ), true ); ?>><?php esc_html_e( 'All products', 'sumosubscriptions' ); ?></option>
                    <option value="selected-products" <?php selected( 'selected-products' === get_option( 'bulk_sumosubs_selected_bulk_type', 'all-products' ), true ); ?>><?php esc_html_e( 'Selected products', 'sumosubscriptions' ); ?></option>
                    <option value="selected-categories" <?php selected( 'selected-categories' === get_option( 'bulk_sumosubs_selected_bulk_type', 'all-products' ), true ); ?>><?php esc_html_e( 'Selected categories', 'sumosubscriptions' ); ?></option>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_products_selector() {
        sumosubs_wc_search_field( array(
            'class'       => 'wc-product-search',
            'id'          => 'selected_products',
            'type'        => 'product',
            'action'      => 'woocommerce_json_search_products_and_variations',
            'title'       => __( 'Select Product(s)', 'sumosubscriptions' ),
            'placeholder' => __( 'Search for a product&hellip;', 'sumosubscriptions' ),
            'options'     => get_option( 'bulk_sumosubs_selected_products', array() ),
        ) );
    }

    /**
     * Custom type field.
     */
    public function get_product_categories_selector() {
        ?>
        <tr>
            <th>
                <?php esc_html_e( 'Select Categories', 'sumosubscriptions' ); ?>
            </th>
            <td>                
                <select name="selected_product_categories[]" class="wc-enhanced-select" id="selected_product_categories" multiple="multiple">
                    <?php
                    $option_value = get_option( 'bulk_sumosubs_selected_product_categories', array() );

                    foreach ( sumosubs_get_product_categories() as $key => $val ) {
                        ?>
                        <option value="<?php echo esc_attr( $key ); ?>"
                        <?php
                        if ( is_array( $option_value ) ) {
                            selected( in_array( ( string ) $key, $option_value, true ), true );
                        } else {
                            selected( $option_value, ( string ) $key );
                        }
                        ?>
                                >
                                    <?php echo esc_html( $val ); ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_product_status() {
        ?>
        <tr class="bulk-fields-wrapper">
            <th>
                <?php esc_html_e( 'SUMO Subscriptions', 'sumosubscriptions' ); ?>
            </th>
            <td>
                <select class="sumosubs-select" name="sumo_susbcription_status" id="subscription_status">
                    <option value="2" <?php selected( '2' === get_option( 'bulk_sumo_susbcription_status' ), true ); ?>><?php esc_html_e( 'Disable', 'sumosubscriptions' ); ?></option>
                    <option value="1" <?php selected( '1' === get_option( 'bulk_sumo_susbcription_status' ), true ); ?>><?php esc_html_e( 'Enable', 'sumosubscriptions' ); ?></option>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_subscription_duration() {
        ?>
        <tr class="bulk-fields-wrapper">
            <th>
                <?php esc_html_e( 'Renewal frequency', 'sumosubscriptions' ); ?>
            </th>
            <td class="subscription_duration_wrap">
                <span class="sumosubs-duration-every"><?php esc_html_e( 'every ', 'sumosubscriptions' ); ?></span>
                <select class="sumosubs-duration-interval" name="sumo_susbcription_period_value" id="subscription_period_value">
                    <?php foreach ( sumo_get_subscription_duration_options( get_option( 'bulk_sumo_susbcription_period', 'D' ), false ) as $value => $label ) { ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( get_option( 'bulk_sumo_susbcription_period_value' ) == $value, true ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php } ?>
                </select>
                <select class="sumosubs-duration-period" name="sumo_susbcription_period" id="subscription_period">
                    <?php foreach ( sumosubs_get_duration_period_selector() as $period => $label ) : ?>
                        <option value="<?php echo esc_attr( $period ); ?>" <?php selected( get_option( 'bulk_sumo_susbcription_period' ) == $period, true ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_subscription_trial_status() {
        ?>
        <tr class="bulk-fields-wrapper">
            <th>
                <?php esc_html_e( 'Trial', 'sumosubscriptions' ); ?>
            </th>
            <td>
                <select class="sumosubs-select" name="sumo_susbcription_trial_enable_disable" id="subscription_trial_status">
                    <option value="2" <?php selected( '2' === get_option( 'bulk_sumo_susbcription_trial_enable_disable' ), true ); ?>><?php esc_html_e( 'Disable', 'sumosubscriptions' ); ?></option>
                    <option value="1" <?php selected( '1' === get_option( 'bulk_sumo_susbcription_trial_enable_disable' ), true ); ?>><?php esc_html_e( 'Forced trial', 'sumosubscriptions' ); ?></option>
                    <option value="3" <?php selected( '3' === get_option( 'bulk_sumo_susbcription_trial_enable_disable' ), true ); ?>><?php esc_html_e( 'Optional trial', 'sumosubscriptions' ); ?></option>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_trial_type() {
        ?>
        <tr class="bulk-fields-wrapper">
            <th>
                <?php esc_html_e( 'Trial type', 'sumosubscriptions' ); ?>
            </th>
            <td>
                <select class="sumosubs-select" name="sumo_susbcription_fee_type_selector" id="subscription_trial_type">
                    <option value="free" <?php selected( 'free' === get_option( 'bulk_sumo_susbcription_fee_type_selector' ), true ); ?>><?php esc_html_e( 'Free trial', 'sumosubscriptions' ); ?></option>
                    <option value="paid" <?php selected( 'paid' === get_option( 'bulk_sumo_susbcription_fee_type_selector' ), true ); ?>><?php esc_html_e( 'Paid trial', 'sumosubscriptions' ); ?></option>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_trial_fee() {
        ?>
        <tr class="bulk-fields-wrapper">
            <th>
                <?php echo wp_kses_post( __( 'Trial fee', 'sumosubscriptions' ) . '(' . get_woocommerce_currency_symbol() . ')' ); ?>
            </th>
            <td>
                <input class="sumosubs-input-price" name="sumo_trial_price" id="subscription_trial_price" placeholder="<?php esc_attr_e( 'Enter the trial fee', 'sumosubscriptions' ); ?>" type="text" value="<?php echo esc_attr( get_option( 'bulk_sumo_trial_price' ) ); ?>"/>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_trial_duration() {
        ?>
        <tr class="bulk-fields-wrapper">
            <th>
                <?php esc_html_e( 'Trial duration', 'sumosubscriptions' ); ?>
            </th>
            <td class="subscription_trial_duration_wrap">
                <select class="sumosubs-trial-duration-interval" name="sumo_trial_period_value" id="subscription_trial_period_value">
                    <?php foreach ( sumo_get_subscription_duration_options( get_option( 'bulk_sumo_trial_period', 'D' ), false ) as $value => $label ) { ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( get_option( 'bulk_sumo_trial_period_value' ) == $value, true ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php } ?>
                </select>
                <select class="sumosubs-trial-duration-period" name="sumo_trial_period" id="subscription_trial_period">
                    <?php foreach ( sumosubs_get_duration_period_selector() as $period => $label ) : ?>
                        <option value="<?php echo esc_attr( $period ); ?>" <?php selected( get_option( 'bulk_sumo_trial_period' ) == $period, true ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>                    
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_subscription_signup_status() {
        ?>
        <tr class="bulk-fields-wrapper">
            <th>
                <?php esc_html_e( 'Charge sign up fee', 'sumosubscriptions' ); ?>
            </th>
            <td>
                <select class="sumosubs-select" name="sumo_susbcription_signusumoee_enable_disable" id="subscription_signup_fee_status">
                    <option value="2" <?php selected( '2' === get_option( 'bulk_sumo_susbcription_signusumoee_enable_disable' ), true ); ?>><?php esc_html_e( 'Disable', 'sumosubscriptions' ); ?></option>
                    <option value="1" <?php selected( '1' === get_option( 'bulk_sumo_susbcription_signusumoee_enable_disable' ), true ); ?>><?php esc_html_e( 'Forced sign up', 'sumosubscriptions' ); ?></option>
                    <option value="3" <?php selected( '3' === get_option( 'bulk_sumo_susbcription_signusumoee_enable_disable' ), true ); ?>><?php esc_html_e( 'Optional sign up', 'sumosubscriptions' ); ?></option>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_signup_fee() {
        ?>
        <tr class="bulk-fields-wrapper">
            <th>
                <?php echo wp_kses_post( __( 'Sign up fee', 'sumosubscriptions' ) . '(' . get_woocommerce_currency_symbol() . ')' ); ?>
            </th>
            <td>
                <input class="sumosubs-input-price" name="sumo_signup_price" id="subscription_signup_fee" placeholder="<?php esc_attr_e( 'Enter the Sign up fee', 'sumosubscriptions' ); ?>" type="text" value="<?php echo esc_attr( get_option( 'bulk_sumo_signup_price' ) ); ?>"/>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_recurring() {
        ?>
        <tr class="bulk-fields-wrapper">
            <th>
                <?php esc_html_e( 'Number of installments', 'sumosubscriptions' ); ?>
            </th>
            <td>
                <select class="sumosubs-select" name="sumo_recurring_period_value" id="subscription_recurring_cycle">
                    <?php foreach ( sumo_get_subscription_recurring_options() as $value => $label ) { ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( get_option( 'bulk_sumo_recurring_period_value' ) == $value, true ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php } ?>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_update_button_for_products() {
        ?>
        <tr class="bulk-update-products">
            <td>
                <input type="button" id="bulk_update_products" class="button-primary" value="<?php esc_html_e( 'Save and Update', 'sumosubscriptions' ); ?>" />
                <span class="spinner"></span>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_tab_description_for_deleted_product_replacement() {
        ?>
        <p>
            <?php echo esc_html_e( "If product linked with an ongoing subscription was deleted, then future renewals won't work properly. In that case, replace the deleted product with a new product using the below options.", 'sumosubscriptions' ); ?>
        </p>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_deleted_product() {
        ?>
        <tr class="bulk-update-subscriptions-field-row">
            <th>
                <?php esc_html_e( 'Deleted Product ID', 'sumosubscriptions' ); ?>
            </th>
            <td>
                <input name="_deleted_product" type="number" min="0" value="" />
                <p class="description"><?php esc_html_e( 'This can be found in "SUMO Subscriptions > Subscriptions" under "Product Name" column of the respective Subscription.', 'sumosubscriptions' ); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_replace_product() {
        ?>
        <tr class="bulk-update-subscriptions-field-row">
            <th>
                <?php esc_html_e( 'Product to be Replaced', 'sumosubscriptions' ); ?>
            </th>
            <td>
                <?php
                sumosubs_wc_search_field( array(
                    'class'       => 'wc-product-search',
                    'id'          => '_replace_product',
                    'type'        => 'product',
                    'multiple'    => false,
                    'action'      => 'woocommerce_json_search_products_and_variations',
                    'placeholder' => __( 'Search for a product&hellip;', 'sumosubscriptions' ),
                ) );
                ?>
                <p class="description"><?php esc_html_e( 'This product will be replaced with the deleted product.', 'sumosubscriptions' ); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_update_button_for_subscriptions() {
        ?>
        <tr class="bulk-update-subscriptions">
            <td>
                <input type="button" id="bulk_update_subscriptions" class="button-primary" value="<?php esc_html_e( 'Update', 'sumosubscriptions' ); ?>" />
                <span class="spinner"></span>
            </td>
        </tr>
        <?php
    }

}

return new SUMOSubs_Admin_Settings_Bulk_Action();
