<?php
/*
* Plugin Name: Woocommerce Warung JNE 
* Plugin URI:  http://URI_Of_Page_Describing_Plugin_and_Updates
* Description: Plugins woocommerce untuk ekspedisi JNE, Wahana, Tritama.
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
    include_once 'includes/class_warung_shipping.php';

    // frontend
    add_filter('woocommerce_shipping_calculator_enable_city', '__return_true');

    // -- custom city select
    function billing_jne_free_modified_address_fields($address_fields){
        global $woocommerce;

        $selectedCity = $woocommerce->customer->get_shipping_city();

        $form = 'form-row-wide';
        if($form == 'form-row-wide') $clear = true; else $clear = false;

        $address_fields['billing']['billing_city'] = array(
            'type'		=> 'select',
            'label'		=> __('City','woocommerce'),
            'placeholder'	=> 'City',
            'required'	=> true,
            'class'		=> array($form, 'update_totals_on_change'),
            'clear'		=> $clear,
            'defaults'	=> array(
                '' => __( 'Select an option', 'woocommerce' ),
            ),
            'options'       => !empty($selectedCity)?array($selectedCity=>$selectedCity):array(''=>'Isi Kota/Kecamatan')
        );
        $address_fields['shipping']['shipping_city'] = array(
            'type'          => 'select',
            'label'         => __('City','woocommerce'),
            'placeholder'       => 'City',
            'required'      => true,
            'class'         => array($form, 'update_totals_on_change'),
            'clear'         => $clear,
            'defaults'		 => array(
                '' => __( 'Select an option', 'woocommerce' ),
            ),
            'options'       => !empty($selectedCity)?array($selectedCity=>$selectedCity):array(''=>'Isi Kota/Kecamatan')
        );
        return $address_fields;
    }
    add_filter('woocommerce_checkout_fields', 'billing_jne_free_modified_address_fields', 1, 10);



}
add_action( 'woocommerce_shipping_init', 'warung_jne_init' , 0);


// register shipping method
function add_warung_jne_shipping_method( $methods ) {
    $methods[] = 'WC_Warung_JNE_Reguler';
    $methods[] = 'WC_Warung_JNE_Yes';
    $methods[] = 'WC_Warung_JNE_Oke';
    $methods[] = 'WC_Warung_Tritama';
    $methods[] = 'WC_Warung_Wahana';

    return $methods;
}
add_filter('woocommerce_shipping_methods', 'add_warung_jne_shipping_method' );


// enqueue plugin scripts
function warung_shipping_enqueue_script() {

    if ( is_page() ) {

        // init select2 in city select box
        wp_enqueue_script('woocommerce-warung-jne', plugin_dir_url(__FILE__) . 'js/woocommerce-warung-shipping.js', array( 'jquery' ), false, true);
        wp_localize_script( 'woocommerce-warung-jne', 'ajax_object',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' ) )
        );

        // deregister prev select2 v3.5
        wp_dequeue_style( 'select2' );
        wp_deregister_style( 'select2' );
        wp_dequeue_script( 'select2');
        wp_deregister_script('select2');

        // register select2 v4
        wp_enqueue_style('select2v4', plugin_dir_url(__FILE__) . 'css/select2.min.css');
        wp_enqueue_script('select2v4', plugin_dir_url(__FILE__) . 'js/select2.full.min.js', array( 'jquery' ), false, true);
    }

}
add_action( 'wp_enqueue_scripts', 'warung_shipping_enqueue_script', 100 );

// -- city ajax search
function warung_shipping_ajax_action_callback() {
    // query
    $q = strtoupper($_POST['q']);
    $limit = 35;
    
    // optional params
    $isCalculatorMode = $_POST['m'] == 'calc';
    
    // Use JNE AS DEFAULT
    $shipping_cities = get_option( 'woocommerce_warung_shipping_jne_reguler_shipping_rate' );
    if(isset($shipping_cities) && isset($shipping_cities['cost_data'])) {asort($shipping_cities['cost_data']);}
    $new_states = array();
    if (!empty($q)) {
        if(is_array($shipping_cities['cost_data']) && count($shipping_cities['cost_data']) > 0){
            foreach($shipping_cities['cost_data'] as $key => $city){
                if (strpos(strtoupper($city['city']), $q) !== false) {
                    $new_states[] = (object)array("text"=>$city['city'],"id"=>$city['city']);
                    if (count($new_states) >= $limit) break;
                }
            }
        }
    }

    if ($isCalculatorMode) {
        $weight = isset($_POST['weight'])?$_POST['weight']:1;
        if (!is_numeric($weight)) {
            $weight = 1;
        }

        // loop through all shipping methods
        global $woocommerce;
        $woocommerce->shipping->load_shipping_methods();

        foreach($new_states as &$cities) {
            foreach ($woocommerce->shipping->shipping_methods as $sm) {
                if ($sm->enabled) {
                    if (method_exists($sm, 'get_cost')) {
                        $costPerKg = $sm->get_cost($cities->text);
                        $cost = $costPerKg * $weight;
                        $cities->cost[] = array('name'=> $sm->title, 'price'=> $cost, 'pricePerKg'=>$costPerKg);
                    } else {
                        //error_log('no get_cost() in ' . $sm->title);
                    }
                }
            }

        } unset($cities);
    }

    echo json_encode((object)array("results"=>$new_states));

    header('Content-Type: application/json');
    wp_die(); // this is required to terminate immediately and return a proper response
}
add_action( 'wp_ajax_ongkir', 'warung_shipping_ajax_action_callback' );
add_action( 'wp_ajax_nopriv_ongkir', 'warung_shipping_ajax_action_callback' );


// widget
function woocommerce_warung_shipping_calculator_shortcode( $atts, $content = null ) {

    $a = shortcode_atts( array(
        'url'       => '#',
        'target'    => '_self',
    ), $atts );

    // Buffer our contents
    ob_start();
    ?>
    <form class="woocommerce_warung_shipping_calculator_form">
        <p class="form-row">
            <label><strong>Kecamatan/Kota </strong><abbr class="required">*</abbr></label>
            <select class="woocommerce_warung_shipping_calculator_city"><option>-- Ketik Kota/Kecamatan --</option></select>
        </p>
        <p>
            <label><strong>Berat (Kg)</strong><abbr class="required">*</abbr></label>
            <input type="text" class="woocommerce_warung_shipping_calculator_weight" name="weight" placeholder="Masukan berat dalam Kg" value="1">
        </p>
        <button class="button" type="submit">Hitung</button>
    </form>
    <?php
    // Return buffered contents
    return ob_get_clean();

}
add_shortcode( 'warung_shipping_calculator', 'woocommerce_warung_shipping_calculator_shortcode' );
