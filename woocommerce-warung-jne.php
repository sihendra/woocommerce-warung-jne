<?php
/*
* Plugin Name: Woocommerce Warung JNE 
* Plugin URI:  http://URI_Of_Page_Describing_Plugin_and_Updates
* Description: This describes my plugin in a short sentence
* Version:     1.0
* Author:      Hendra Setiawan
* Author URI:  http://warungsprei.com
* License:     GPL2
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
* Domain Path: /languages
* Text Domain: woocommerce-warung-jne
*
*/

/**
 * Check if WooCommerce is active
// **/
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    // Show require woocommerce
    return;
}

// init warung jne
function warung_jne_init() {

    class WC_Warung_JNE extends WC_Shipping_Method {

        /**
         * Constructor for your shipping class
         *
         * @access public
         * @return void
         */
        public function __construct() {
            $this->id                 = 'wc_warung_jne'; // Id for your shipping method. Should be uunique.
            $this->method_title       = __( 'JNE' );  // Title shown in admin
            $this->method_description = __( 'Pengiriman dengan JNE' ); // Description shown in admin

            $this->enabled            = "yes"; // This can be added as an setting but for this example its forced enabled
            $this->title              = "JNE"; // This can be added as an setting but for this example its forced.

            $this->init();
        }

        /**
         * Init your settings
         *
         * @access public
         * @return void
         */
        function init() {
            // Load the settings API
            $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
            $this->init_settings(); // This is part of the settings API. Loads settings you previously init.

            // Save settings in admin if you have any defined
            add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        /**
         * calculate_shipping function.
         *
         * @access public
         * @param mixed $package
         * @return void
         */
        public function calculate_shipping( $package ) {
            $rate = array(
                'id' => $this->id,
                'label' => $this->title,
                'cost' => '10.99',
                'calc_tax' => 'per_item'
            );

            // Register the rate
            $this->add_rate( $rate );
        }

        function init_form_fields() {
            global $woocommerce;

            $this->form_fields = array(
                'enabled' => array(
                    'title'         => __( 'Aktifkan/Non-aktifkan', 'woocommerce' ),
                    'type'          => 'checkbox',
                    'label'         => __( 'Aktifkan WooCommerce JNE Shipping', 'woocommerce' ),
                    'default'       => 'yes',
                ),
                'title' => array(
                    'title'         => __( 'Judul', 'woocommerce' ),
                    'description' 	=> __( 'Tambahkan judul untuk fitur pengiriman kamu.', 'woocommerce' ),
                    'desc_tip'	=> true,
                    'type'          => 'text',
                    'default'       => __( 'JNE Shipping', 'woocommerce' ),
                ),
                'jne_weight' => array(
                    'title'         => __( 'Berat default', 'woocommerce' ),
                    'description' 	=> __( 'Otomatis setting berat produk jika kamu tidak setting pada masing-masing produk.', 'woocommerce' ),
                    'desc_tip'	=> true,
                    'type'          => 'number',
                    'default'       => __( '0.25', 'woocommerce' ),
                    'custom_attributes' => array(
                        'step'	=> 'any',
                        'min'	=> '0'
                    ),
                    'placeholder'	=> '0.00',
                    'default'		=> '1',
                ),
                'jne_service' => array(
                    'type'          => 'jne_service',
                ),
                'jne_import' => array(
                    'type'          => 'jne_import',
                ),
                'jne_free_shipping' => array(
                    'type'          => 'jne_free_shipping',
                ),
                'free_shipping_city' => array(
                    'type'          => 'free_shipping_city',
                ),
                'jne_credit' => array(
                    'type'          => 'jne_credit',
                ),
            );

        }


    }
}
add_action( 'woocommerce_shipping_init', 'warung_jne_init' );


// register shipping method
function add_warung_jne_shipping_method( $methods ) {
    $methods[] = 'WC_Warung_JNE'; return $methods;
}
add_filter('woocommerce_shipping_methods', 'add_warung_jne_shipping_method' );

