<?php
/**
 * Plugin Name: WooCommerce NIP Field
 * Author: Daniel Świderski
 * Author URI: https://8814.pl
 * Description: Adds an optional Polish NIP field to WooCommerce classic checkout.
 * Version: 1.0.4
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woocommerce-nip-field
 * Requires Plugins: woocommerce
 * WC requires at least: 3.0
 * WC tested up to: 9.0
 *
 * @package WooCommerce_NIP_Field
 */

defined( 'ABSPATH' ) || exit;

add_action( 'before_woocommerce_init', 'woocommerce_nip_declare_compatibility' );
add_action( 'plugins_loaded', 'woocommerce_nip_field_init' );

/**
 * Declares compatibility with WooCommerce feature flags.
 */
function woocommerce_nip_declare_compatibility() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
}

/**
 * Initializes the plugin after WooCommerce is available.
 */
function woocommerce_nip_field_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	add_filter( 'woocommerce_checkout_fields', 'woocommerce_nip_add_checkout_field' );
	add_filter( 'woocommerce_checkout_get_value', 'woocommerce_nip_get_checkout_field_value', 10, 2 );
	add_filter( 'woocommerce_billing_fields', 'woocommerce_nip_add_billing_address_field' );
	add_filter( 'woocommerce_my_account_my_address_formatted_address', 'woocommerce_nip_add_my_account_address_nip', 10, 3 );
	add_filter( 'woocommerce_order_formatted_billing_address', 'woocommerce_nip_add_order_billing_address_nip', 10, 2 );
	add_filter( 'woocommerce_order_get_formatted_billing_address', 'woocommerce_nip_add_formatted_order_billing_address_nip', 10, 3 );
	add_action( 'woocommerce_after_checkout_validation', 'woocommerce_nip_validate_checkout_field', 10, 2 );
	add_action( 'woocommerce_after_save_address_validation', 'woocommerce_nip_validate_billing_address_field', 10, 3 );
	add_action( 'woocommerce_checkout_create_order', 'woocommerce_nip_save_order_meta', 10, 2 );
	add_action( 'woocommerce_checkout_update_user_meta', 'woocommerce_nip_save_customer_billing_nip', 10, 2 );
	add_action( 'woocommerce_admin_order_data_after_billing_address', 'woocommerce_nip_display_admin_order_meta' );
	add_action( 'wp_enqueue_scripts', 'woocommerce_nip_enqueue_checkout_script' );
}

/**
 * Returns the NIP checkout/billing field definition.
 *
 * @return array
 */
function woocommerce_nip_field_definition() {
	return array(
		'type'              => 'text',
		'label'             => __( 'NIP', 'woocommerce-nip-field' ),
		'required'          => false,
		'priority'          => 31,
		'class'             => array( 'form-row-wide' ),
		'input_class'       => array( 'input-text' ),
		'custom_attributes' => array(
			'inputmode' => 'numeric',
			'maxlength' => '10',
			'pattern'   => '[0-9]*',
		),
	);
}

/**
 * Inserts the NIP field right after billing company, or appends it if company is absent.
 *
 * @param array $fields Billing fields keyed by field id.
 * @return array
 */
function woocommerce_nip_insert_field( $fields ) {
	$nip_field      = woocommerce_nip_field_definition();
	$billing_fields = array();

	foreach ( $fields as $key => $field ) {
		$billing_fields[ $key ] = $field;

		if ( 'billing_company' === $key ) {
			$billing_fields['billing_nip'] = $nip_field;
		}
	}

	if ( ! isset( $billing_fields['billing_nip'] ) ) {
		$billing_fields['billing_nip'] = $nip_field;
	}

	return $billing_fields;
}

/**
 * Adds the NIP field after billing company.
 *
 * @param array $fields Checkout fields.
 * @return array
 */
function woocommerce_nip_add_checkout_field( $fields ) {
	if ( ! isset( $fields['billing'] ) || ! is_array( $fields['billing'] ) ) {
		return $fields;
	}

	$fields['billing'] = woocommerce_nip_insert_field( $fields['billing'] );

	return $fields;
}

/**
 * Loads saved NIP into the checkout field for logged-in customers.
 *
 * @param mixed  $value Checkout field value.
 * @param string $input Checkout field key.
 * @return mixed
 */
function woocommerce_nip_get_checkout_field_value( $value, $input ) {
	if ( 'billing_nip' !== $input || '' !== (string) $value || ! is_user_logged_in() ) {
		return $value;
	}

	return get_user_meta( get_current_user_id(), 'billing_nip', true );
}

/**
 * Adds the NIP field to customer billing address forms.
 *
 * @param array $fields Billing fields.
 * @return array
 */
function woocommerce_nip_add_billing_address_field( $fields ) {
	return woocommerce_nip_insert_field( $fields );
}

/**
 * Adds NIP to the formatted billing address displayed in My Account.
 *
 * @param array  $address      Formatted address data.
 * @param int    $customer_id  Customer ID.
 * @param string $address_type Address type.
 * @return array
 */
function woocommerce_nip_add_my_account_address_nip( $address, $customer_id, $address_type ) {
	if ( 'billing' !== $address_type ) {
		return $address;
	}

	$nip = trim( (string) get_user_meta( $customer_id, 'billing_nip', true ) );

	if ( '' === $nip ) {
		return $address;
	}

	$nip_line           = sprintf( __( 'NIP: %s', 'woocommerce-nip-field' ), esc_html( $nip ) );
	$address['company'] = empty( $address['company'] ) ? $nip_line : $address['company'] . "\n" . $nip_line;

	return $address;
}

/**
 * Adds NIP to the formatted billing address displayed for orders.
 *
 * @param array    $address Formatted address data.
 * @param WC_Order $order   Order object.
 * @return array
 */
function woocommerce_nip_add_order_billing_address_nip( $address, $order ) {
	$nip = $order->get_meta( '_billing_nip' );

	if ( '' === $nip ) {
		return $address;
	}

	$nip_line           = sprintf( __( 'NIP: %s', 'woocommerce-nip-field' ), esc_html( $nip ) );
	$address['company'] = empty( $address['company'] ) ? $nip_line : $address['company'] . "\n" . $nip_line;

	return $address;
}

/**
 * Adds NIP to the final formatted billing address if it is not already there.
 *
 * @param string   $address     Formatted billing address.
 * @param WC_Order $order       Order object.
 * @param array    $raw_address Raw billing address data.
 * @return string
 */
function woocommerce_nip_add_formatted_order_billing_address_nip( $address, $order, $raw_address ) {
	$nip = $order->get_meta( '_billing_nip' );

	if ( '' === $nip || false !== strpos( wp_strip_all_tags( $address ), $nip ) ) {
		return $address;
	}

	$nip_line = sprintf( __( 'NIP: %s', 'woocommerce-nip-field' ), $nip );

	return $address ? $address . '<br/>' . esc_html( $nip_line ) : esc_html( $nip_line );
}

/**
 * Validates NIP through WooCommerce's native checkout field validation flow.
 *
 * @param array    $data   Posted checkout data.
 * @param WP_Error $errors Validation errors.
 */
function woocommerce_nip_validate_checkout_field( $data, $errors ) {
	$nip = isset( $data['billing_nip'] ) ? trim( (string) $data['billing_nip'] ) : '';

	if ( '' === $nip ) {
		return;
	}

	$company = isset( $data['billing_company'] ) ? trim( (string) $data['billing_company'] ) : '';
	$errors_found = woocommerce_nip_get_validation_errors( $nip, $company );

	if ( in_array( 'company_required', $errors_found, true ) ) {
		$errors->add(
			'billing_company_required_with_nip',
			sprintf(
				/* translators: %s: checkout field label. */
				__( '<a href="#billing_company">%s jest wymagana przy podaniu NIP.</a>', 'woocommerce-nip-field' ),
				'<strong>' . esc_html__( 'Nazwa firmy', 'woocommerce-nip-field' ) . '</strong>'
			),
			array( 'id' => 'billing_company' )
		);
	}

	if ( in_array( 'invalid_nip', $errors_found, true ) ) {
		$errors->add(
			'billing_nip_validation',
			sprintf(
				/* translators: %s: checkout field label. */
				__( '<a href="#billing_nip">%s nie jest prawidłowym numerem.</a>', 'woocommerce-nip-field' ),
				'<strong>' . esc_html__( 'NIP', 'woocommerce-nip-field' ) . '</strong>'
			),
			array( 'id' => 'billing_nip' )
		);
	}

	if ( ! empty( $errors_found ) ) {
		woocommerce_nip_sort_checkout_errors( $errors );
	}
}

/**
 * Returns NIP validation errors shared by checkout and My Account billing address.
 *
 * @param string $nip     NIP value.
 * @param string $company Billing company value.
 * @return array
 */
function woocommerce_nip_get_validation_errors( $nip, $company ) {
	$errors = array();

	if ( '' === $nip ) {
		return $errors;
	}

	if ( '' === $company ) {
		$errors[] = 'company_required';
	}

	if ( ! woocommerce_nip_is_valid( $nip ) ) {
		$errors[] = 'invalid_nip';
	}

	return $errors;
}

/**
 * Validates NIP when a customer saves the billing address in My Account.
 *
 * @param int    $user_id      User ID.
 * @param string $load_address Address type.
 * @param array  $address      Address fields.
 */
function woocommerce_nip_validate_billing_address_field( $user_id, $load_address, $address ) {
	if ( 'billing' !== $load_address ) {
		return;
	}

	$nip = isset( $_POST['billing_nip'] ) ? trim( wc_clean( wp_unslash( $_POST['billing_nip'] ) ) ) : '';

	if ( '' === $nip ) {
		return;
	}

	$company = isset( $_POST['billing_company'] ) ? trim( wc_clean( wp_unslash( $_POST['billing_company'] ) ) ) : '';
	$errors  = woocommerce_nip_get_validation_errors( $nip, $company );

	if ( in_array( 'company_required', $errors, true ) ) {
		wc_add_notice( __( 'Nazwa firmy jest wymagana przy podaniu NIP.', 'woocommerce-nip-field' ), 'error' );
	}

	if ( in_array( 'invalid_nip', $errors, true ) ) {
		wc_add_notice( __( 'NIP nie jest prawidłowym numerem.', 'woocommerce-nip-field' ), 'error' );
	}
}

/**
 * Sorts checkout errors by field order so the NIP notice appears near its field.
 *
 * @param WP_Error $errors Validation errors.
 */
function woocommerce_nip_sort_checkout_errors( $errors ) {
	if ( ! isset( $errors->errors['billing_nip_validation'] ) && ! isset( $errors->errors['billing_company_required_with_nip'] ) ) {
		return;
	}

	$field_order = woocommerce_nip_get_checkout_field_order();

	if ( empty( $field_order ) ) {
		return;
	}

	$indexed_errors = array();

	foreach ( $errors->errors as $code => $messages ) {
		$data  = $errors->get_error_data( $code );
		$id    = is_array( $data ) && isset( $data['id'] ) ? $data['id'] : '';
		$order = isset( $field_order[ $id ] ) ? $field_order[ $id ] : PHP_INT_MAX;

		$indexed_errors[ $code ] = array(
			'messages' => $messages,
			'data'     => $data,
			'order'    => $order,
		);
	}

	uasort(
		$indexed_errors,
		static function ( $a, $b ) {
			return $a['order'] <=> $b['order'];
		}
	);

	$errors->errors     = wp_list_pluck( $indexed_errors, 'messages' );
	$errors->error_data = wp_list_pluck( $indexed_errors, 'data' );
}

/**
 * Returns checkout field ids sorted by WooCommerce priority.
 *
 * @return array
 */
function woocommerce_nip_get_checkout_field_order() {
	if ( ! function_exists( 'WC' ) || ! WC()->checkout() ) {
		return array();
	}

	$fields = WC()->checkout()->get_checkout_fields();
	$order  = array();
	$index  = 0;

	foreach ( $fields as $fieldset ) {
		uasort(
			$fieldset,
			static function ( $a, $b ) {
				return ( $a['priority'] ?? 0 ) <=> ( $b['priority'] ?? 0 );
			}
		);

		foreach ( $fieldset as $key => $field ) {
			$order[ $key ] = $index++;
		}
	}

	return $order;
}

/**
 * Checks whether a value is a valid Polish NIP.
 *
 * @param string $nip NIP value.
 * @return bool
 */
function woocommerce_nip_is_valid( $nip ) {
	if ( ! preg_match( '/^\d{10}$/', $nip ) ) {
		return false;
	}

	if ( preg_match( '/^(\d)\1{9}$/', $nip ) ) {
		return false;
	}

	$weights  = array( 6, 5, 7, 2, 3, 4, 5, 6, 7 );
	$checksum = 0;

	for ( $i = 0; $i < 9; $i++ ) {
		$checksum += (int) $nip[ $i ] * $weights[ $i ];
	}

	return ( $checksum % 11 ) === (int) $nip[9];
}

/**
 * Saves NIP in order meta.
 *
 * @param WC_Order $order Order object.
 * @param array    $data  Posted checkout data.
 */
function woocommerce_nip_save_order_meta( $order, $data ) {
	if ( empty( $data['billing_nip'] ) ) {
		return;
	}

	$nip = trim( (string) $data['billing_nip'] );

	if ( woocommerce_nip_is_valid( $nip ) ) {
		$order->update_meta_data( '_billing_nip', $nip );
	}
}

/**
 * Saves NIP to the customer's billing profile after checkout.
 *
 * @param int   $customer_id Customer ID.
 * @param array $data        Posted checkout data.
 */
function woocommerce_nip_save_customer_billing_nip( $customer_id, $data ) {
	if ( ! $customer_id || ! is_array( $data ) || ! isset( $data['billing_nip'] ) ) {
		return;
	}

	$nip = trim( (string) $data['billing_nip'] );

	if ( '' === $nip ) {
		delete_user_meta( $customer_id, 'billing_nip' );
		return;
	}

	if ( woocommerce_nip_is_valid( $nip ) ) {
		update_user_meta( $customer_id, 'billing_nip', $nip );
	}
}

/**
 * Displays NIP in admin order billing data.
 *
 * @param WC_Order $order Order object.
 */
function woocommerce_nip_display_admin_order_meta( $order ) {
	$nip = $order->get_meta( '_billing_nip' );

	if ( '' === $nip ) {
		return;
	}

	echo '<p><strong>' . esc_html__( 'NIP', 'woocommerce-nip-field' ) . ':</strong> ' . esc_html( $nip ) . '</p>';
}

/**
 * Adds checkout-only helpers for NIP and prefilled billing fields.
 */
function woocommerce_nip_enqueue_checkout_script() {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
		return;
	}

	wp_register_script( 'woocommerce-nip-field', '', array(), '1.0.0', true );
	wp_enqueue_script( 'woocommerce-nip-field' );
	wp_add_inline_script(
		'woocommerce-nip-field',
		"(function(){var field=document.getElementById('billing_nip');if(!field){return;}function clearError(id){var wrap=document.getElementById(id+'_field');if(wrap){wrap.classList.remove('woocommerce-invalid','woocommerce-invalid-required-field');}}function markError(id){var wrap=document.getElementById(id+'_field');if(document.querySelector('.woocommerce-error [data-id=\"'+id+'\"],.woocommerce-error li[data-id=\"'+id+'\"]')&&wrap){wrap.classList.remove('woocommerce-validated');wrap.classList.add('woocommerce-invalid','woocommerce-invalid-required-field');}}field.addEventListener('input',function(){this.value=this.value.replace(/\\D/g,'').slice(0,10);clearError('billing_nip');});var company=document.getElementById('billing_company');if(company){company.addEventListener('input',function(){clearError('billing_company');});}if(window.jQuery){window.jQuery(document.body).on('checkout_error updated_checkout',function(){markError('billing_nip');markError('billing_company');});}}());"
	);
	wp_add_inline_script(
		'woocommerce-nip-field',
		"(function(){var saved={};function selector(){return '.woocommerce-checkout [name^=\"billing_\"],.woocommerce-checkout [name^=\"shipping_\"]';}function initialValue(field){if(field.type==='checkbox'||field.type==='radio'||field.type==='hidden'){return '';}if(field.tagName==='SELECT'){var option=field.querySelector('option[selected]');return option?option.value:'';}return field.getAttribute('value')||'';}function remember(){document.querySelectorAll(selector()).forEach(function(field){if(saved[field.name]!==undefined){return;}var value=initialValue(field);if(value){saved[field.name]=value;}});}function restore(){document.querySelectorAll(selector()).forEach(function(field){var value=saved[field.name];if(!value||field.dataset.woocommerceNipTouched==='1'||field.value){return;}field.value=value;field.dispatchEvent(new Event(field.tagName==='SELECT'?'change':'input',{bubbles:true}));});}function markTouched(event){var field=event.target;if(event.isTrusted&&field&&field.name&&(field.name.indexOf('billing_')===0||field.name.indexOf('shipping_')===0)){field.dataset.woocommerceNipTouched='1';}}document.addEventListener('input',markTouched,true);document.addEventListener('change',markTouched,true);remember();restore();setTimeout(restore,50);setTimeout(restore,250);setTimeout(restore,750);if(window.jQuery){window.jQuery(document.body).on('updated_checkout',function(){remember();restore();setTimeout(restore,50);});}}());"
	);
}
