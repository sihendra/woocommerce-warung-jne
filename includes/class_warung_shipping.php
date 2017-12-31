<?php

/**
 * Created by PhpStorm.
 * User: hendra
 * Date: 8/18/15
 * Time: 1:04 PM
 */
abstract class WC_Warung_Base extends WC_Shipping_Method
{

    private $shipping_rate_option_key;

    /**
     * Init your settings
     *
     * @access public
     * @return void
     */
    function init()
    {
        // Load the settings API
        $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
        $this->init_settings(); // This is part of the settings API. Loads settings you previously init.

        // Set shipping_rate key
        $this->shipping_rate_option_key = $this->plugin_id . $this->id . '_shipping_rate';

        // default upload handler
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
        // rate upload handler
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_upload_options'));
    }

    public function is_available($package)
    {
        if ('no' == $this->enabled) {
            return false;
        }

        // check min weight
        $min_weight = $this->settings['min_weight'];
        if ($this->get_total_weight() < $min_weight) return false;

        // check shipping class
        $shipping_classes = $this->settings['shipping_class'];
        if (empty($shipping_classes)) {
            return true; // available for all items
        }
        foreach ($package['contents'] as $item_id => $values) {
            if ($values['data']->needs_shipping()) {
                $found_class = $values['data']->get_shipping_class();

                if (!in_array($found_class, $shipping_classes)) {
                    return false;
                }
            }
        }

        return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', true, $package);
    }


    /**
     * calculate_shipping function.
     *
     * @access public
     * @param mixed $package
     * @return void
     */
    function calculate_shipping($package = array())
    {
        $woocommerce = wc();

        $this->shipping_total = 0;
        $this->shipping_tax = 0;

        $city = $woocommerce->customer->get_shipping_city();
        $cost = $this->get_cost($city);

        if ($cost == 0) {
            return false;
        }

        $total_weight = $this->get_total_weight();

        $cost = $cost * ceil($total_weight);

        $rate = array(
            'id' => $this->id,
            'label' => $this->title . ' (' . $total_weight . ' ' . ucwords(esc_attr(get_option('woocommerce_weight_unit'))) . ')',
            'cost' => $cost
        );

        $this->add_rate($rate);
    }

    function init_form_fields()
    {

        $shipping_classes = wc()->shipping->get_shipping_classes();
        $shipping_class_opt = [];
        foreach ($shipping_classes as $shipping_class) {
            if (!isset($shipping_class->slug)) {
                continue;
            }
            $shipping_class_opt[$shipping_class->slug] = $shipping_class->name;
        }

        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Aktifkan/Non-aktifkan', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Aktifkan WooCommerce ' . $this->title, 'woocommerce'),
                'default' => 'yes',
            ),
            'title' => array(
                'title' => __('Judul', 'woocommerce'),
                'description' => __('Tambahkan judul untuk fitur pengiriman kamu.', 'woocommerce'),
                'desc_tip' => true,
                'type' => 'text',
                'default' => __($this->title, 'woocommerce'),
            ),
            'shipping_class' => array(
                'title' => __('Shipping Class', 'woocommerce'),
                'description' => __('Pilih shipping class, atau biarkan kosong jika berlaku untuk semua.', 'woocommerce'),
                'desc_tip' => true,
                'type' => 'multiselect',
                'options' => $shipping_class_opt
            ),
            'min_weight' => array(
                'title' => __('Berat Minimum', 'woocommerce'),
                'description' => __('Berat minimum, atau biarkan kosong jika berlaku untuk semua.', 'woocommerce'),
                'desc_tip' => true,
                'type' => 'number',
                'default' => 1,
                'custom_attributes' => array(
                    'step' => 'any',
                    'min' => '0'
                ),
            ),
            'default_weight' => array(
                'title' => __('Berat default', 'woocommerce'),
                'description' => __('Otomatis setting berat produk jika kamu tidak setting pada masing-masing produk.', 'woocommerce'),
                'desc_tip' => true,
                'type' => 'number',
                'custom_attributes' => array(
                    'step' => 'any',
                    'min' => '0'
                ),
                'placeholder' => '0.00',
                'default' => '1',
            ),
            'rates' => array(
                'type' => 'rates_upload',
            )
        );

    }

    public function generate_rates_upload_html()
    {
        ob_start();
        $jne_data = get_option($this->shipping_rate_option_key);
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc"><?php _e('Impor Kota', 'woojne') ?> <img class="help_tip"
                                                                                       data-tip="Masukan file csv untuk menginput data kota kamu."
                                                                                       src="<?php echo plugins_url('../images/help.png', __FILE__); ?>"
                                                                                       height="16" width="16"/></th>
            <td>
                <p><input type="file" name="<?php echo $this->plugin_id . $this->id . '_import_city'; ?>"
                          id="<?php echo $this->plugin_id . $this->id . '_import_city'; ?>" style="min-width:393px;"/>
                    <input name="save" class="button-primary help_tip" data-tip="Klik untuk melakukan upload data kota."
                           class="button-primary" type="submit" value="Upload Data Kota">
                    <a href="#"
                       class="button-primary help_tip"
                       data-tip="Silahkan lengkapi form checkout untuk mendapatkan data kota."
                       target="_blank">Download Data Kota</a>
                </p>
                <?php if (!empty($jne_data['cost_data'])) { ?>
                    <p style="background: #FFF;  border-left: 4px solid #FFF;  -webkit-box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);  box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);  margin: 5px 0 2px;  padding: 5px 12px;  border-color: #7AD03A;">
                        Anda telah melakukan upload data, dan jika Anda ingin <u>mengedit data kota</u>, Anda bisa
                        mengedit file csv terlebih dahulu.</p>
                <?php } else { ?>
                    <p style="background: #FFF;  border-left: 4px solid #FFF;  -webkit-box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);  box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);  margin: 5px 0 2px;  padding: 5px 12px;  border-color: #D54E21;">
                        Anda belum melakukan upload data kota.</p>
                <?php } ?>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    public function process_admin_upload_options()
    {
        $import_city_name = $this->plugin_id . $this->id . '_import_city';
        if ($_FILES[$import_city_name]['error'] == UPLOAD_ERR_NO_FILE) return false;

        $upload_path = $_FILES[$import_city_name]["tmp_name"];
        if (empty($upload_path)) return false;

        $ext = strtolower(pathinfo($_FILES[$import_city_name]['name'], PATHINFO_EXTENSION));
        if ($ext != 'csv') return false;

        $cities_cost = array();
        $city_counter = 0;
        $fd = fopen($upload_path, "r");
        while (!feof($fd)) {
            $buffer = fgetcsv($fd, filesize($upload_path));
            if (!empty($buffer[0])) {
                $buffer[1] = iconv('UTF-8', 'ISO-8859-15//TRANSLIT', $buffer[1]);

                $city_name = $buffer[0];
                $cities_cost[$city_name] = array('city' => $city_name, 'price' => $buffer[1]);
                $city_counter++;
            }
        }
        fclose($fd);

        $jne_options['cost_data'] = $cities_cost;
        $jne_options['city_count'] = $city_counter;

        update_option($this->shipping_rate_option_key, $jne_options);

    }

    /**
     * @param $city
     * @return mixed
     */
    public function get_cost($city)
    {
        $shipping_cities = get_option($this->shipping_rate_option_key);

        if (!isset($shipping_cities['cost_data'])) {
            return 0;
        }
        $shipping_price = $shipping_cities['cost_data'][$city];

        if (!isset($shipping_price['price'])) {
            return 0;
        }
        $cost = $shipping_price['price'];
        return $cost;
    }

    /**
     * @return float|int
     */
    private function get_total_weight()
    {
        $woocommerce = wc();
        $default_weight = isset($this->settings['default_weight'])?$this->settings['default_weight']:1;
        $total_weight = 0;

        if (sizeof($woocommerce->cart->cart_contents) > 0) {
            foreach ($woocommerce->cart->cart_contents as $cart_product) {
                $total_weight += $cart_product['data']->weight * $cart_product['quantity'];
            }
        }

        // round total weight
        $dec_point = ceil($total_weight) - $total_weight ? 1 - (ceil($total_weight) - $total_weight) : 0;
        if ($dec_point > 0 && (int)($dec_point * 10) > 3) {
            $total_weight = ceil($total_weight);
        } else {
            $total_weight = max($default_weight, floor($total_weight));
        }

        return $total_weight;
    }


}

class WC_Warung_JNE_Reguler extends WC_Warung_Base
{

    /**
     * Constructor for your shipping class
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        $this->id = 'warung_shipping_jne_reguler'; // Id for your shipping method. Should be uunique.
        $this->method_title = __('JNE Reguler');  // Title shown in admin
        $this->method_description = __('Pengiriman dengan JNE Reguler'); // Description shown in admin

        $this->enabled = "yes"; // This can be added as an setting but for this example its forced enabled
        $this->title = "JNE Reguler"; // This can be added as an setting but for this example its forced.

        $this->init();
    }

}

class WC_Warung_Tritama extends WC_Warung_Base
{

    /**
     * Constructor for your shipping class
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        $this->id = 'warung_shipping_tritama'; // Id for your shipping method. Should be uunique.
        $this->method_title = __('Tritama');  // Title shown in admin
        $this->method_description = __('Pengiriman dengan Tritama'); // Description shown in admin

        $this->enabled = "yes"; // This can be added as an setting but for this example its forced enabled
        $this->title = "Tritama"; // This can be added as an setting but for this example its forced.

        $this->init();
    }

}

class WC_Warung_Wahana extends WC_Warung_Base
{

    /**
     * Constructor for your shipping class
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        $this->id = 'warung_shipping_wahana'; // Id for your shipping method. Should be uunique.
        $this->method_title = __('Wahana');  // Title shown in admin
        $this->method_description = __('Pengiriman dengan Wahana'); // Description shown in admin

        $this->enabled = "yes"; // This can be added as an setting but for this example its forced enabled
        $this->title = "Wahana"; // This can be added as an setting but for this example its forced.

        $this->init();
    }

}
