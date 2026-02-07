<?php
/**
 * Product subscription box step template.
 *
 * @since   4.0.0
 * @package YITH\Subscription
 * @var array $step_content The step content types available.
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

?>

<script type="text/template" id="tmpl-ywsbs-box-step">
	<div class="ywsbs-box-step" data-id="{{data.id}}">
		<div class="ywsbs-step-title">
			<span><?php echo esc_html_x( 'Step', '[Admin]Single box creation step options title', 'yith-woocommerce-subscription' ); ?></span>
			<div class="ywsbs-step-actions">
				<?php
					yith_plugin_fw_get_component(
						array(
							'type'   => 'action-button',
							'class'  => 'edit-step',
							'title'  => __( 'Edit', 'yith-woocommerce-subscription' ),
							'action' => 'edit',
							'icon'   => 'edit',
						)
					);
					?>

				<# if ( data.index > 1 ) { #>
				<?php
					yith_plugin_fw_get_component(
						array(
							'type'   => 'action-button',
							'class'  => 'delete-step',
							'title'  => __( 'Delete', 'yith-woocommerce-subscription' ),
							'action' => 'delete',
							'icon'   => 'trash',
						)
					);
					?>
				<# } #>

			</div>
		</div>
		<div class="ywsbs-step-settings" style="display:none;">

			<div class="ywsbs-product-metabox-field">
				<label for="_ywsbs_box_steps_content_{{data.id}}"><?php esc_html_e( 'In this step show', 'yith-woocommerce-subscription' ); ?></label>
				<div class="ywsbs-product-metabox-field-container">
					<div class="ywsbs-product-metabox-field-content">
						<select id="_ywsbs_box_steps_content_{{data.id}}" name="_ywsbs_box_steps[{{data.id}}][content]" data-value="{{data.content}}">
							<?php foreach ( $step_content as $key => $value ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="ywsbs-product-metabox-field-description">
						<?php esc_html_e( 'Choose which products you want to show in this step of the box creation.', 'yith-woocommerce-subscription' ); ?>
					</div>
				</div>
			</div>

			<div class="ywsbs-product-metabox-field" data-deps-on="_ywsbs_box_steps[{{data.id}}][content]" data-deps-val="specific_products">
				<label for="_ywsbs_box_steps_products_{{data.id}}"><?php esc_html_e( 'Products to show', 'yith-woocommerce-subscription' ); ?></label>
				<div class="ywsbs-product-metabox-field-container">
					<div class="ywsbs-product-metabox-field-content">
						<?php
						$args = array(
							'type'     => 'ajax-products',
							'id'       => '_ywsbs_box_steps_products_{{data.id}}',
							'name'     => '_ywsbs_box_steps[{{data.id}}][products]',
							'multiple' => true,
							'data'     => array(
								'product_type' => 'simple',
								'value'        => '{{data.products}}',
							),
							'value'    => '',
						);
						yith_plugin_fw_get_field( $args, true );
						?>
					</div>
					<div class="ywsbs-product-metabox-field-description">
						<?php esc_html_e( 'Select the products to show in this step.', 'yith-woocommerce-subscription' ); ?>
					</div>
				</div>
			</div>

			<div class="ywsbs-product-metabox-field" data-deps-on="_ywsbs_box_steps[{{data.id}}][content]" data-deps-val="specific_categories">
				<label for="_ywsbs_box_steps_categories_{{data.id}}"><?php esc_html_e( 'Categories to show', 'yith-woocommerce-subscription' ); ?></label>
				<div class="ywsbs-product-metabox-field-container">
					<div class="ywsbs-product-metabox-field-content">
						<?php
						$args = array(
							'type'     => 'ajax-terms',
							'id'       => '_ywsbs_box_steps_categories_{{data.id}}',
							'name'     => '_ywsbs_box_steps[{{data.id}}][categories]',
							'multiple' => true,
							'data'     => array(
								'taxonomy' => 'product_cat',
								'value'    => '{{data.categories}}',
							),
							'value'    => '',
						);
						yith_plugin_fw_get_field( $args, true );
						?>
					</div>
					<div class="ywsbs-product-metabox-field-description">
						<?php esc_html_e( 'Select the products of which categories to show in this step.', 'yith-woocommerce-subscription' ); ?>
					</div>
				</div>
			</div>

			<div class="ywsbs-product-metabox-field">
				<label for="_ywsbs_box_steps_enabled_threshold_{{data.id}}"><?php esc_html_e( 'Set min/max rules for product selection', 'yith-woocommerce-subscription' ); ?></label>
				<div class="ywsbs-product-metabox-field-container">
					<div class="ywsbs-product-metabox-field-content">
						<?php
						$args = array(
							'type'              => 'onoff',
							'id'                => '_ywsbs_box_steps_enabled_threshold_{{data.id}}',
							'name'              => '_ywsbs_box_steps[{{data.id}}][enabled_threshold]',
							'custom_attributes' => array( 'data-value' => '{{data.enabled_threshold}}' ),
						);
						yith_plugin_fw_get_field( $args, true );
						?>
					</div>
					<div class="ywsbs-product-metabox-field-description">
						<?php esc_html_e( 'Enable to set min/max rules for product selection.', 'yith-woocommerce-subscription' ); ?>
					</div>
				</div>
			</div>

			<div class="ywsbs-product-metabox-field" data-deps-on="_ywsbs_box_steps[{{data.id}}][enabled_threshold]" data-deps-val="yes">
				<label for="_ywsbs_box_steps_threshold_{{data.id}}"><?php esc_html_e( 'User can select', 'yith-woocommerce-subscription' ); ?></label>
				<div class="ywsbs-product-metabox-field-container">
					<div class="ywsbs-product-metabox-field-content">
						<span><?php esc_html_e( 'Min', 'yith-woocommerce-subscription' ); ?></span>
						<input type="number" class="ywsbs-short" name="_ywsbs_box_steps[{{data.id}}][threshold][min]" id="_ywsbs_box_steps_threshold_min_{{data.id}}" min="0" step="1" value="" data-value="{{data.threshold?.min}}"/>
						<span><?php esc_html_e( 'and max', 'yith-woocommerce-subscription' ); ?></span>
						<input type="number" class="ywsbs-short" name="_ywsbs_box_steps[{{data.id}}][threshold][max]" id="_ywsbs_box_steps_threshold_max_{{data.id}}" min="0" step="1" value="" data-value="{{data.threshold?.max}}"/>
					</div>
					<div class="ywsbs-product-metabox-field-content">
						<span><?php esc_html_e( 'Max', 'yith-woocommerce-subscription' ); ?></span>
						<input type="number" class="ywsbs-short" name="_ywsbs_box_steps[{{data.id}}][threshold][max_units]" id="_ywsbs_box_steps_threshold_units_{{data.id}}" min="0" step="1" value="" data-value="{{data.threshold?.max_units}}"/>
						<span><?php esc_html_e( 'units of the same product', 'yith-woocommerce-subscription' ); ?></span>
					</div>
					<div class="ywsbs-product-metabox-field-description">
						<?php esc_html_e( 'Choose the min/max number of products customers can add to the box.', 'yith-woocommerce-subscription' ); ?>
					</div>
				</div>
			</div>

			<div class="ywsbs-product-metabox-field">
				<label for="_ywsbs_box_steps_label_{{data.id}}"><?php esc_html_e( 'Step label', 'yith-woocommerce-subscription' ); ?></label>
				<div class="ywsbs-product-metabox-field-container">
					<div class="ywsbs-product-metabox-field-content">
						<input type="text" id="_ywsbs_box_steps_label_{{data.id}}" name="_ywsbs_box_steps[{{data.id}}][label]" value="{{data.label}}">
					</div>
					<div class="ywsbs-product-metabox-field-description">
						<?php esc_html_e( 'Set a label for this step.', 'yith-woocommerce-subscription' ); ?>
					</div>
				</div>
			</div>

			<div class="ywsbs-product-metabox-field">
				<label for="_ywsbs_box_steps_text_{{data.id}}"><?php esc_html_e( 'Step text', 'yith-woocommerce-subscription' ); ?></label>
				<div class="ywsbs-product-metabox-field-container">
					<div class="ywsbs-product-metabox-field-content">
						<div class="ywsbs_box_steps_text_editor_trigger">
							<iframe id="_ywsbs_box_steps_text_{{data.id}}_preview"></iframe>
							<input type="hidden" id="_ywsbs_box_steps_text_{{data.id}}" name="_ywsbs_box_steps[{{data.id}}][text]" data-value="{{data.text}}">
						</div>
					</div>
					<div class="ywsbs-product-metabox-field-description">
						<?php esc_html_e( 'Customize the text to show in this step, above products list.', 'yith-woocommerce-subscription' ); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</script>
<script type="text/template" id="tmpl-ywsbs-box-editor-field">
	<?php
	wp_editor(
		$value,
		'ywsbs_box_steps_text_editor',
		array(
			'wpautop'       => true, // Choose if you want to use wpautop.
			'media_buttons' => true, // Choose if showing media button(s).
			'textarea_name' => 'ywsbs_box_steps_text_editor', // Set the textarea name to something different, square brackets [] can be used here.
			'textarea_rows' => 10, // Set the number of rows.
			'tabindex'      => '',
			'editor_css'    => '', // Intended for extra styles for both visual and HTML editors buttons, needs to include the <style> tags, can use "scoped".
			'editor_class'  => '', // Add extra class(es) to the editor textarea.
			'teeny'         => false, // Output the minimal editor config used in Press This.
			'dfw'           => false, // Replace the default fullscreen with DFW (needs specific DOM elements and css).
			'tinymce'       => true, // Load TinyMCE, can be used to pass settings directly to TinyMCE using an array().
			'quicktags'     => true, // Load Quicktags, can be used to pass settings directly to Quicktags using an array().
		)
	);
	?>
</script>
