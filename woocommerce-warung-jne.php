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
            $this->id                 = 'warung_jne'; // Id for your shipping method. Should be uunique.
            $this->method_title       = __( 'JNE' );  // Title shown in admin
            $this->method_description = __( 'Pengiriman dengan JNE' ); // Description shown in admin

            $this->enabled            = "yes"; // This can be added as an setting but for this example its forced enabled
            $this->title              = "JNE"; // This can be added as an setting but for this example its forced.

            $this->option_name_shipping_data = "woocommerce_warung_jne_shipping";

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
            // save file upload
            add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_jne_options' ) );
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
                'jne_import' => array(
                    'type'          => 'jne_import',
                )
            );

        }

        public function generate_jne_import_html()
        {
            ob_start();
            $jne_data = get_option( $this->option_name_shipping_data );
            ?>
            <tr valign="top">
                <th scope="row" class="titledesc"><?php _e('Impor Kota', 'woojne') ?> <img class="help_tip" data-tip="Masukan file csv untuk menginput data kota kamu." src="<?php echo plugins_url( 'images/help.png', __FILE__ ); ?>" height="16" width="16" /></th>
                <td>
                    <p><input type="file" name="woocommerce_warung_jne_import_city" id="woocommerce_warung_jne_import_city" style="min-width:393px;" /> <input name="save" class="button-primary help_tip" data-tip="Klik untuk melakukan upload data kota." class="button-primary" type="submit" value="Upload Data Kota"> <a href="http://www.agenwebsite.com/?add-to-cart=5318" class="button-primary help_tip" data-tip="Silahkan lengkapi form checkout untuk mendapatkan data kota." target="_blank">Download Data Kota</a>
                    </p>
                    <?php if(!empty($jne_data['cost_data'])) { ?>
                        <p style="background: #FFF;  border-left: 4px solid #FFF;  -webkit-box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);  box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);  margin: 5px 0 2px;  padding: 5px 12px;  border-color: #7AD03A;">Anda telah melakukan upload data, dan jika Anda ingin <u>mengedit data kota</u>, Anda bisa mengedit file csv terlebih dahulu.</p>
                    <?php } else { ?>
                        <p style="background: #FFF;  border-left: 4px solid #FFF;  -webkit-box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);  box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);  margin: 5px 0 2px;  padding: 5px 12px;  border-color: #D54E21;">Anda belum melakukan upload data kota.</p>
                    <?php } ?>
                </td>
            </tr>
            <?php
            return ob_get_clean();
        }

        public function process_admin_jne_options()
        {
            if($_FILES['woocommerce_warung_jne_import_city']['error'] != UPLOAD_ERR_NO_FILE) {
                $cities_cost = array();
                $upload_path = $_FILES["woocommerce_warung_jne_import_city"]["tmp_name"];
                $ext = strtolower(end(explode('.', $_FILES['woocommerce_warung_jne_import_city']['name'])));

                if(!empty($upload_path) and $ext == 'csv') {
                    $fd = fopen ($upload_path, "r");
                    $city_counter = 0;
                    while (!feof ($fd)) {
                        $buffer = fgetcsv($fd, filesize( $upload_path ) );
                        if(!empty($buffer[0])){
                            $buffer[0] = $buffer[0];
                            $buffer[1] = iconv( 'UTF-8', 'ISO-8859-15//TRANSLIT',$buffer[1]);

                            $city_name = $buffer[0];
                            $cities_cost[$city_name] = array( 'city' => $city_name, 'price' => $buffer[1] );
                            $city_counter++;
                        }
                    }
                    fclose ($fd);

                    $jne_options['cost_data'] = $cities_cost;
                    $jne_options['city_count'] = $city_counter;

                    update_option( $this->option_name, $jne_options );
                }
            }
        }


    }
}
add_action( 'woocommerce_shipping_init', 'warung_jne_init' );


// register shipping method
function add_warung_jne_shipping_method( $methods ) {
    $methods[] = 'WC_Warung_JNE'; return $methods;
}
add_filter('woocommerce_shipping_methods', 'add_warung_jne_shipping_method' );

