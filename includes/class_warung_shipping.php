<?php
/**
 * Created by PhpStorm.
 * User: hendra
 * Date: 8/18/15
 * Time: 1:04 PM
 */

class WC_Warung_Base extends WC_Shipping_Method {

    /**
     * Constructor for your shipping class
     *
     * @access public
     * @return void
     */
    public function __construct() {
        $this->id                 = 'warung_shipping'; // Id for your shipping method. Should be uunique.
        $this->method_title       = __( 'Warung Shipping' );  // Title shown in admin
        $this->method_description = __( 'Pengiriman pilihan Warung' ); // Description shown in admin

        $this->enabled            = "yes"; // This can be added as an setting but for this example its forced enabled
        $this->title              = "Warung Shipping"; // This can be added as an setting but for this example its forced.

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

        // Load external setting
        $this->option_name_shipping_data = $this->plugin_id.$this->id.'_shipping_data';

        // Save settings in admin if you have any defined
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
        // save file upload
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_upload_options' ) );
    }

    /**
     * calculate_shipping function.
     *
     * @access public
     * @param mixed $package
     * @return void
     */
    function calculate_shipping( $package = array() ) {
        global $woocommerce;

        $this->shipping_total  = 0;
        $this->shipping_tax    = 0;

        $cost = 0;
        $total_weight = 0;
        $shipping_cities = '';

        $city = $woocommerce->customer->get_shipping_city();

        $shipping_cities = get_option( $this->option_name_shipping_data );

        $shipping_price = $shipping_cities['cost_data'][$city];

        $cost = $shipping_price['price'];

        if($cost == 0) {
            return false;
        }

        if (sizeof($woocommerce->cart->cart_contents)>0){
            foreach($woocommerce->cart->cart_contents as $cart_product){
                $total_weight += $cart_product['data']->weight * $cart_product['quantity'];
                if(!$total_weight) { $total_weight = 1; }
            }
        }

        if(!is_int($total_weight)) {
            $jne_weight = explode('.',$total_weight);
            if($jne_weight[1] <= 3 && $jne_weight[1] != "") {
                $total_weight = ceil($total_weight) - 1;
                if($total_weight == 0) {
                    $total_weight = 1;
                }
            } else {
                $total_weight = ceil($total_weight);
            }
        }

        $cost = $cost * ceil($total_weight);

        $rate = array(
            'id'        => $this->id,
            'label'     => $this->title .' ('.$total_weight. ' ' .ucwords(esc_attr( get_option('woocommerce_weight_unit' ) )).')',
            'cost'      => $cost
        );

        $this->add_rate($rate);
    }

    function init_form_fields() {

        $this->form_fields = array(
            'enabled' => array(
                'title'         => __( 'Aktifkan/Non-aktifkan', 'woocommerce' ),
                'type'          => 'checkbox',
                'label'         => __( 'Aktifkan WooCommerce '.$this->title, 'woocommerce' ),
                'default'       => 'yes',
            ),
            'title' => array(
                'title'         => __( 'Judul', 'woocommerce' ),
                'description' 	=> __( 'Tambahkan judul untuk fitur pengiriman kamu.', 'woocommerce' ),
                'desc_tip'	=> true,
                'type'          => 'text',
                'default'       => __( $this->title, 'woocommerce' ),
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
            <th scope="row" class="titledesc"><?php _e('Impor Kota', 'woojne') ?> <img class="help_tip" data-tip="Masukan file csv untuk menginput data kota kamu." src="<?php echo plugins_url( '../images/help.png', __FILE__ ); ?>" height="16" width="16" /></th>
            <td>
                <p><input type="file" name="<?php echo $this->plugin_id.$this->id.'_import_city';?>" id="<?php echo $this->plugin_id.$this->id.'_import_city';?>" style="min-width:393px;" /> <input name="save" class="button-primary help_tip" data-tip="Klik untuk melakukan upload data kota." class="button-primary" type="submit" value="Upload Data Kota"> <a href="http://www.agenwebsite.com/?add-to-cart=5318" class="button-primary help_tip" data-tip="Silahkan lengkapi form checkout untuk mendapatkan data kota." target="_blank">Download Data Kota</a>
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

    public function process_admin_upload_options()
    {
        $import_city_name = $this->plugin_id.$this->id.'_import_city';
        if($_FILES[$import_city_name]['error'] != UPLOAD_ERR_NO_FILE) {
            $cities_cost = array();
            $upload_path = $_FILES[$import_city_name]["tmp_name"];
            $ext = strtolower(end(explode('.', $_FILES[$import_city_name]['name'])));

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

                update_option( $this->option_name_shipping_data, $jne_options );
            }
        }
    }



}

class WC_Warung_JNE_Reguler extends WC_Warung_Base {

    /**
     * Constructor for your shipping class
     *
     * @access public
     * @return void
     */
    public function __construct() {
        $this->id                 = 'warung_shipping_jne_reguler'; // Id for your shipping method. Should be uunique.
        $this->method_title       = __( 'JNE Reguler' );  // Title shown in admin
        $this->method_description = __( 'Pengiriman dengan JNE Reguler' ); // Description shown in admin

        $this->enabled            = "yes"; // This can be added as an setting but for this example its forced enabled
        $this->title              = "JNE Reguler"; // This can be added as an setting but for this example its forced.

        $this->init();
    }

}

class WC_Warung_Tritama extends WC_Warung_Base {

    /**
     * Constructor for your shipping class
     *
     * @access public
     * @return void
     */
    public function __construct() {
        $this->id                 = 'warung_shipping_tritama'; // Id for your shipping method. Should be uunique.
        $this->method_title       = __( 'Tritama' );  // Title shown in admin
        $this->method_description = __( 'Pengiriman dengan Tritama' ); // Description shown in admin

        $this->enabled            = "yes"; // This can be added as an setting but for this example its forced enabled
        $this->title              = "Tritama"; // This can be added as an setting but for this example its forced.

        $this->init();
    }

}

class WC_Warung_Wahana extends WC_Warung_Base {

    /**
     * Constructor for your shipping class
     *
     * @access public
     * @return void
     */
    public function __construct() {
        $this->id                 = 'warung_shipping_wahana'; // Id for your shipping method. Should be uunique.
        $this->method_title       = __( 'Wahana' );  // Title shown in admin
        $this->method_description = __( 'Pengiriman dengan Wahana' ); // Description shown in admin

        $this->enabled            = "yes"; // This can be added as an setting but for this example its forced enabled
        $this->title              = "Wahana"; // This can be added as an setting but for this example its forced.

        $this->init();
    }

}
