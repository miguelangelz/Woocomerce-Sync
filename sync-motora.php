<?php
set_time_limit(1000);
date_default_timezone_set('America/Lima');

require_once(ABSPATH . 'wp-admin' . '/includes/image.php');
require_once(ABSPATH . 'wp-admin' . '/includes/file.php');
require_once(ABSPATH . 'wp-admin' . '/includes/media.php');
/*
  Plugin Name: Sync by La Motora
  Plugin URI:  https://bennyrock20.wordpress.com/
  Description: Sincroniza los productos con el almacén
  Version:     1.0.0
  Author:      LaMotora.com
  Author URI:  https://bennyrock20.wordpress.com/
  License:     GPL2
  License URI: https://www.gnu.org/licenses/gpl-2.0.html
  Text Domain: syncdbmotora
  Domain Path: /languages
 */

defined('ABSPATH') or die('No script kiddies please!');



$remote_db = null;

/*
 * Custom Cron
 */
function syncwarehouse_custom_cron_schedule($schedules)
{
    $schedules['every_eight_hours'] = array(
        'interval' => 28800, // Every 6 hours
        'display' => __('Every 8 hours'),
    );
    return $schedules;
}

add_filter('cron_schedules', 'syncwarehouse_custom_cron_schedule');
add_filter('woocommerce_get_sections_products', 'syncwarehouse_add_section');

function syncwarehouse_add_section($sections)
{
    $sections['syncwarehouse'] = __('Sincronizar con Almacén', 'syncdbmotora');
    return $sections;
}

/**
 * Add settings to the specific section we created before
**/

add_filter('woocommerce_get_settings_products', 'syncwarehouse_all_settings', 10, 2);

function syncwarehouse_all_settings($settings, $current_section)
{
   
    if ($current_section == 'syncwarehouse') {
        $settings_syncwarehouse = array();
        $settings_syncwarehouse[] = array('name' => __('Settings', 'syncwarehouse'), 'type' => 'title', 'desc' => __('The following options are used to configure Sync', 'text-domain'), 'id' => 'syncwarehouse_id');
        $settings_syncwarehouse[] = array(
            'name' => __('Enable', 'syncwarehouse'),
            'desc_tip' => __('Allow to run script to sync to warehouse', 'syncwarehouse'),
            'id' => 'syncwarehouse_active',
            'type' => 'checkbox',
            'css' => 'min-width:300px;',
            'desc' => __('Enable Plugin', 'syncwarehouse'),
        );

        $settings_syncwarehouse[] = array(
            'name' => __('Sync Products', 'syncwarehouse'),
            'desc_tip' => __('Allow to sync products', 'syncwarehouse'),
            'id' => 'syncwarehouse_sync_products',
            'type' => 'checkbox',
            'css' => 'min-width:300px;',
            'desc' => __('Enable Sync Products', 'syncwarehouse'),
        );

        $settings_syncwarehouse[] = array(
            'name' => __('Update Existing Products', 'syncwarehouse'),
            'desc_tip' => __('This take considerable time and server resources', 'syncwarehouse'),
            'id' => 'syncwarehoused_update_existing_products',
            'type' => 'checkbox',
            'css' => 'min-width:300px;',
            'desc' => __(' Update Existing products', 'syncwarehouse'),
        );


        $settings_syncwarehouse[] = array(
            'name' => __('Force to Update Images', 'syncwarehouse'),
            'desc_tip' => __('Force update images even if the product already has an associated image?', 'syncwarehouse'),
            'id' => 'syncwarehoused_update_images',
            'type' => 'checkbox',
            'css' => 'min-width:300px;',
            'desc' => __('Always Update Images', 'syncwarehouse'),
        );


       

        $settings_syncwarehouse[] = array(
            'name' => __('Sync Stock Products', 'syncwarehouse'),
            'desc_tip' => __('Allow to sync stocks', 'syncwarehouse'),
            'id' => 'syncwarehouse_update_stock_products',
            'type' => 'checkbox',
            'css' => 'min-width:300px;',
            'desc' => __('Enable Sync Stock', 'syncwarehouse'),
        );

        $settings_syncwarehouse[] = array(
            'name' => __('Debug', 'syncwarehouse'),
            'desc_tip' => __('Allow messages of debug', 'syncwarehouse'),
            'id' => 'syncwarehoused_debug',
            'type' => 'checkbox',
            'css' => 'min-width:300px;',
            'desc' => __('Enable Debug', 'syncwarehouse'),
        );
      
        $settings_syncwarehouse[] = array(
            'name' => __('Hora creación de nuevos productos', 'syncwarehouse'),
            'desc_tip' => __('Hora en la que se ejecutará el proceso creación de nuevos productos', 'syncwarehouse'),
            'id' => 'syncwarehoused_time_hour_create',
            'type' => 'time',
            'css' => 'min-width:200px;',
            'min' => '1',
            'max' => '24',
            'desc' => __('Hora Ej: 23:00', 'syncwarehouse'),
        );

        $settings_syncwarehouse[] = array(
            'name' => __('Hora de actualización de stock de productos', 'syncwarehouse'),
            'desc_tip' => __('Hora en la que se ejecutará el proceso de Actualización', 'syncwarehouse'),
            'id' => 'syncwarehoused_time_hour_update',
            'type' => 'time',
            'css' => 'min-width:200px;',
            'min' => '1',
            'max' => '24',
            'desc' => __('Hora Ej: 23:00', 'syncwarehouse'),
        );

        $settings_syncwarehouse[] = array(
            'name' => __('Remote Store Id', 'syncwarehouse'),
            'desc_tip' => __('Insert id of the remote Store', 'syncwarehouse'),
            'id' => 'syncwarehouse_remote_store_id',
            'type' => 'number',
            'css' => 'min-width:300px;',
            'desc' => __('Remote Store Id', 'syncwarehouse'),
        );


        $settings_syncwarehouse[] = array(
            'name' => __('Database Name', 'syncwarehouse'),
            'desc_tip' => __('Insert de database name', 'syncwarehouse'),
            'id' => 'syncwarehouse_sync_db_name',
            'type' => 'text',
            'css' => 'min-width:300px;',
            'desc' => __('External database', 'syncwarehouse'),
        );


        $settings_syncwarehouse[] = array(
            'name' => __('Database User', 'syncwarehouse'),
            'desc_tip' => __('Insert de database user', 'syncwarehouse'),
            'id' => 'syncwarehouse_sync_db_user',
            'type' => 'text',
            'css' => 'min-width:300px;',
            'desc' => __('External database', 'syncwarehouse'),
        );

        $settings_syncwarehouse[] = array(
            'name' => __('Database Password', 'syncwarehouse'),
            'desc_tip' => __('Insert de database password', 'syncwarehouse'),
            'id' => 'syncwarehouse_sync_db_password',
            'type' => 'password',
            'css' => 'min-width:300px;',
            'desc' => __('External database', 'syncwarehouse'),
        );

        $settings_syncwarehouse[] = array(
            'name' => __('Database Host', 'syncwarehouse'),
            'desc_tip' => __('Insert de database host', 'syncwarehouse'),
            'id' => 'syncwarehouse_sync_db_host',
            'type' => 'text',
            'css' => 'min-width:300px;',
            'desc' => __('External database', 'syncwarehouse'),
        );

        $settings_syncwarehouse[] = array(
            'name' => __('Url Image Products', 'syncwarehouse'),
            'desc_tip' => __('Insert de url of images', 'syncwarehouse'),
            'id' => 'syncwarehouse_sync_url_products',
            'type' => 'text',
            'css' => 'min-width:300px;',
            'desc' => __('Like https://domain.com/products', 'syncwarehouse'),
        );
        


        $settings_syncwarehouse[] = array('type' => 'sectionend', 'id' => 'syncwarehouse');

        $sync = get_option('syncwarehouse_active');
        
        
        
        $remote_store_id = intval(get_option('syncwarehouse_remote_store_id'));
        $time_hours_create = date_parse(get_option('syncwarehoused_time_hour_create'));
        $time_hours_update = date_parse(get_option('syncwarehoused_time_hour_update'));
        $hours_create = 0;
        $minutes_create = 0;
        $hours_update = 0;
        $minutes_update = 0;

        $syncwarehouse_sync_db_name = get_option('syncwarehouse_sync_db_name');

        if (empty($syncwarehouse_sync_db_name)) {
            external_database_info_admin_notice__success();
            add_action('admin_notices', 'external_database_info_admin_notice__success');
            return $settings_syncwarehouse;
        }
        $syncwarehouse_sync_db_user = get_option('syncwarehouse_sync_db_user');
        
        if (empty($syncwarehouse_sync_db_user)) {
            external_database_info_admin_notice__success();
            add_action('admin_notices', 'external_database_info_admin_notice__success');
            return $settings_syncwarehouse;
        }

        $syncwarehouse_sync_db_password = get_option('syncwarehouse_sync_db_password');

       

        $syncwarehouse_sync_db_host = get_option('syncwarehouse_sync_db_host');

        if (empty($syncwarehouse_sync_db_host)) {
            external_database_info_admin_notice__success();
            add_action('admin_notices', 'external_database_info_admin_notice__success');
            return $settings_syncwarehouse;
        }


        if ($time_hours_create["error_count"]) {
            remote_update_products_hours_invalid__error($time_hours_create["errors"][0]);
            add_action('admin_notices', 'remote_update_products_hours_invalid__error');
            return $settings_syncwarehouse;
        } else {
            $hours_create = ($time_hours_create["hour"]);
            $minutes_create = ($time_hours_create["minute"]);
        }

        if ($time_hours_update["error_count"]) {
            remote_update_products_hours_invalid__error($time_hours_update["errors"][0]);
            add_action('admin_notices', 'remote_update_products_hours_invalid__error');
            return $settings_syncwarehouse;
        } else {
            $hours_update = ($time_hours_update["hour"]);
            $minutes_update = ($time_hours_update["minute"]);
        }

        if (empty($remote_store_id)) {
            remote_id_admin_notice__success();
            add_action('admin_notices', 'remote_id_admin_notice__success');
            return $settings_syncwarehouse;
        }

        $current_date = new DateTime();

        if ($sync == 'yes') {
    	    //adding schedule create products
            if (!wp_next_scheduled('syncwarehouse_create_new_product_event')) {
                $current_date->setTime($hours_create, $minutes_create);
                $timestamp = $current_date->getTimestamp();
                syncwarehouse_write_log("wp_schedule_event -> syncwarehouse_create_new_product_event created! " . $hours_create . ":" . $minutes_create . " " . $timestamp);
                wp_schedule_event($timestamp, 'daily', 'syncwarehouse_create_new_product_event');
                add_action('admin_notices', 'create_products_event_admin_notice__success');
            }

            //adding schedule update stock products
            if (!wp_next_scheduled('syncwarehouse_update_stock_product_event')) {
                $current_date->setTime($hours_update, $minutes_update);
                $timestamp = $current_date->getTimestamp();
                syncwarehouse_write_log("wp_schedule_event -> syncwarehouse_update_stock_product_event created! " . $hours_update . ":" . $minutes_update . " " . $timestamp);
                wp_schedule_event($timestamp, 'every_eight_hours', 'syncwarehouse_update_stock_product_event');
                add_action('admin_notices', 'create_update_products_stock_event_admin_notice__success');
            }
        } else {

            //removing schedule create products

            if (wp_next_scheduled('syncwarehouse_create_new_product_event')) {
                syncwarehouse_write_log("wp_schedule_event -> syncwarehouse_create_new_product_event removed!");
                wp_clear_scheduled_hook('syncwarehouse_create_new_product_event');
                add_action('admin_notices', 'remove_products_event_admin_notice__success');
            }

            //removing schedule update products

            if (wp_next_scheduled('syncwarehouse_update_stock_product_event')) {
                syncwarehouse_write_log("wp_schedule_event -> syncwarehouse_update_stock_product_event removed!");
                wp_clear_scheduled_hook('syncwarehouse_update_stock_product_event');
                add_action('admin_notices', 'remote_update_products_stock_event_admin_notice__success');
            }
        }

        return $settings_syncwarehouse;
    } else {
        return $settings;
    }
}



add_action('syncwarehouse_create_new_product_event', 'syncwarehouse_create_new_product');
add_action('syncwarehouse_update_stock_product_event', 'syncwarehouse_update_stock_products');



/**
 * Realiza el proceso de agregar nuevos productos o actualizar los existentes
 */
function syncwarehouse_create_new_product()
{
    
    if(get_option('syncwarehouse_sync_products') =="yes"){
    
        syncwarehouse_write_log("starting syncwarehouse_create_new_product...");

        $remote_db = mysqli_connect(get_option('syncwarehouse_sync_db_host'), get_option('syncwarehouse_sync_db_user'), get_option('syncwarehouse_sync_db_password'), get_option('syncwarehouse_sync_db_name'));

        if (!$remote_db) {
            syncwarehouse_write_log("Database error!");
            return;
        }
    
        $result = getRemoteProducts($remote_db);
        $total = $result->num_rows;
        $cont=1;

        while ($product=mysqli_fetch_object($result)) {

            syncwarehouse_write_log("Creating Product ". $cont . "/" . $total );
            
            $cont++;
            
            $sku = $product->pro_codigo;
            $name = $product->pro_desclarga;
            $iva = $product->pro_iva; 
            $regular_price = floatval($product->pre_valor);
            $description = $product->pro_desclarga;
            $short_description = $product->pro_desclarga;        
            $slug = sanitize_title($name);
            $stock = 0;
            $weight = $product->pro_pesokg;
            $length = null;
            $width = null;
            $height = null;
            $status = $product->pro_estado == "OK" ? "publish" : "pending" ;
            $categories = array(
                $product->cls_descripcion
            );
            //Get Categories id by Names
            $categories_ids = syncwarehouse_product_categories($categories);
            $default_image_url = $sync = get_option('syncwarehouse_sync_url_products') ."/". $sku . ".jpg";
            $image_gallery_urls = array(
            );
            $tags = array(
                $product->pro_descripcion,
            );
            //Get Tags ids by Names
            //$tags_ids = syncwarehouse_product_tags($tags);

            $attributes = array();
            $result2 = getProductAttributes($product->pro_codigo,$remote_db);
            while ($product_attribute =mysqli_fetch_object($result2)) {
                $attribute_value = array($product_attribute->atr_valor);
                $attribute_name = $product_attribute->atr_nombre;
                $new_row = array(
                    'name'=>$attribute_name,
                    "value" => $attribute_value
                );
                array_push($attributes, $new_row );
            }

            $getters_and_setters = array(
                'name' => $name,
                'slug' => $slug,
                'catalog_visibility' => 'visible',
                'featured' => false,
                'description' => $description,
                'short_description' => $short_description,
                'sku' => $sku,
                'regular_price' => $regular_price,
                //'sale_price' => $sale_price,
                //'date_on_sale_from' => '1475798400',
                //'date_on_sale_to' => '1477267200',
                //'total_sales' => 20,
                'tax_status' => ( $iva == "S" ? "taxable" : "none" ),
                'tax_class' => 'standard',
                'manage_stock' => true,
                'stock_quantity' => $stock,
                'stock_status' => ( $stock > 0 ? "instock" : "outofstock" ),
                'backorders' => 'no', //yes, no, notify
                'sold_individually' => false,
                'weight' => $weight,
                'length' => $length,
                'width' => $width,
                'height' => $height,
                //'upsell_ids' => array(2, 3),
                //'cross_sell_ids' => array(4, 5),
                'parent_id' => 0,
                'reviews_allowed' => true,
                'default_attributes' => array(),
                //'purchase_note' => 'A note',
                //'menu_order' => 2,
                //'gallery_image_ids' => array(),
                //'download_expiry' => -1,
                //'download_limit' => 5,
                'category_ids' => $categories_ids,
                //'tag_ids' => $tags_ids,
                'attributes' => $attributes,
                'status' => $status 
            );
            
            $price_array = array();

            $results_prices = getPricesbyProductCode($sku, $remote_db);

            while ($product_price =mysqli_fetch_object($results_prices)){
                $price_value = floatval($product_price->pre_valor);
                $role_code = $product_price->pre_codigo;

                $role = "";

                switch ($role_code) {
                    case 1:
                        $role = "";
                        break;
                    case 2:
                        $role = "mayoristas";
                        break;
                    case 9:
                        $role = "distribuidores";
                        break;
                }
                if ($role != "" && $price_value > 0) {
                    $price_array[$role] = array(
                        "regular_price" => $price_value,
                        "selling_price" => "",
                    );
                }
            }
            syncwarehouse_save_product($getters_and_setters, $price_array, $attributes, $default_image_url, $image_gallery_urls);
            if($cont > 100){
                //break;
            }
        }

        mysqli_close($remote_db);

        syncwarehouse_write_log("finished syncwarehouse_create_new_product...");
    }else{
        syncwarehouse_write_log("syncwarehouse_sync_new_products is not enabled...");
    }

    
}


/**
 * Realiza el proceso de actualizacion de stock de los productos por store id
 */
function syncwarehouse_update_stock_products()
{
    if(get_option('syncwarehouse_update_stock_products') =="yes"){
        syncwarehouse_write_log("running syncwarehouse_update_stock_products...");
        $remote_store_id = get_option('syncwarehouse_remote_store_id');

        if (!empty($remote_store_id)) {

            $remote_db = mysqli_connect(get_option('syncwarehouse_sync_db_host'), get_option('syncwarehouse_sync_db_user'), get_option('syncwarehouse_sync_db_password'), get_option('syncwarehouse_sync_db_name'));

            $result = getProductsStockByStoreId($remote_store_id, $remote_db);
            $total = $result->num_rows;
            $cont=1;

            syncwarehouse_write_log($total . " products to update stocks!");

            while ($product_stock=mysqli_fetch_object($result)) {

                syncwarehouse_write_log("Updating Product ". $cont++ ." of ".$total);
                
                $stock = intval($product_stock->slp_actual);
                $sku = $product_stock->pro_codigo;
                $product_id = wc_get_product_id_by_sku($sku);
                $product = new WC_Product();

                if ($product_id) {
                    
                    $product = new WC_Product($product_id);
                    
                    $stock_status = "outofstock";
                    
                    if ($stock > 0) {
                        $stock_status = "instock";
                    } else {
                        $stock_status = "outofstock";
                    }

                    if ($product->get_stock_quantity() != $stock || $product->get_stock_status() != $stock_status) {
                        wc_update_product_stock($product, $stock, 'set');
                        $product->set_stock_status($stock_status);
                        $product->save();
                        wc_recount_after_stock_change($product->get_id());
                        syncwarehouse_write_log("Product stock  with sku ". $sku ." updated to ". $stock ." items!...".$product->get_stock_status());
                    }else{
                        syncwarehouse_write_log("Local product ". $sku ." stock is equal to remote stock ". $product->get_stock_quantity() ." == ". $stock ."");
                    }
                } else {
                    syncwarehouse_write_log("Error updating product, product with sku ". $sku ." not found!...");
                    continue;
                }

                if($cont > 10){
                    //break;
                }
            }
            
            mysqli_close($remote_db);

        } else {
            syncwarehouse_write_log("Error, Remote Store Id is empty!");
        }
        syncwarehouse_write_log("finished syncwarehouse_update_stock_products...");
    }else{
        syncwarehouse_write_log("syncwarehouse_update_stock_products is not enabled...");
    }
}

/**
 * Get Remote products from extreral db
 **/
function getRemoteProducts($remote_db)
{
    return  mysqli_query($remote_db, 'SELECT * FROM `Producto` inner JOIN Clasificacion inner JOIN Precio on Producto.cls_codigo = Clasificacion.cls_codigo and Producto.pro_codigo = Precio.pro_codigo where Precio.pre_codigo= 1');
    
}

/**
 * Get Attributes by Product Id
 * return OBECT
 */
function getProductAttributes($product_id,$remote_db)
{
    return mysqli_query($remote_db, "SELECT * FROM Atributo JOIN Atributo_Producto ON Atributo.atr_codigo= Atributo_Producto.atr_codigo where Atributo_Producto.pro_codigo =" . $product_id);
}

/**
 * Get Remote products prices from extreral db
 **/
function getPricesbyProductCode($remote_product_code, $remote_db)
{
    return  mysqli_query($remote_db, "SELECT * FROM `Precio` where Precio.pro_codigo=" . $remote_product_code);
}

/**
 * Get Products Stock from extreral db by Store Id
 **/
function getProductsStockByStoreId($remote_store_id, $remote_db)
{
    return mysqli_query($remote_db, "SELECT * FROM `Saldo_Producto` WHERE emp_codigo =" . $remote_store_id);
}

/**
 * Save a Product and return code success or error code
 * @param type $getters_and_setters
 * @return type String code
 */
function syncwarehouse_save_product($getters_and_setters, $price_array, $attributes, $default_image_url = "", $image_gallery_urls = array())
{
    try {

        $product_id = wc_get_product_id_by_sku($getters_and_setters["sku"]);
        $product = new WC_Product();
        
        if ($product_id) {
            if(get_option('syncwarehoused_update_existing_products') == "no"){
                if($getters_and_setters["status"] != $product->get_status()){
                    syncwarehouse_write_log("The product need to be updated because it change of status");
                    $product = new WC_Product($product_id);
                    $product->set_status($getters_and_setters["status"]);
                    $product->save();
                    return false;
                }else{
                    syncwarehouse_write_log("The product already exists and you dont select to update existing products");
                    return false;
                }
               
            }else{
                $action = "updated";
                $product = new WC_Product($product_id);
            }
        } else {
            $action = "created";
        }
        foreach ($getters_and_setters as $function => $value) {
            $product->{"set_{$function}"}($value);
        }
        //upload images product default

        if ($default_image_url != "") {
            $allowed = false;

            if(empty($product->get_image_id())){
                $allowed = true;
            }else if (get_option('syncwarehoused_update_images') == "yes" ){
                $allowed = true;
            }

            if($allowed == true){
                $image_id = syncwarehouse_upload_images_by_url_and_return_id($default_image_url, $product->get_id());
                if ($image_id > 0) {
                    $product->set_image_id($image_id);
                }
            }else{
                syncwarehouse_write_log("The product already has an image and is not forced");
            }
        }

        //upload images gallery

        $image_gallery_urls_id = array();

        foreach ($image_gallery_urls as $url) {
            if ($url) {
                $gallery_id = syncwarehouse_upload_images_by_url_and_return_id($url, $product->get_id());
                if ($gallery_id > 0) {
                    array_push($image_gallery_urls_id, $gallery_id);
                }
            }
        }

        if ($image_gallery_urls_id) {
            $product->set_gallery_image_ids($image_gallery_urls_id);
        }
        //add term
        $atts = array();

        foreach ($attributes as $attribute) {

            //Get the taxonomy ID
            $name = $attribute["name"];
            $options= $attribute["value"];
            $sanitize_name = wc_sanitize_taxonomy_name($name);
            $taxonomy_id = wc_attribute_taxonomy_id_by_name('pa_' . $sanitize_name );

           // syncwarehouse_write_log($getters_and_setters["sku"]. " product_attribute is array: ". json_encode($options));
            //Create the Product Attribute object
            $product_attribute = new WC_Product_Attribute();
            $product_attribute->set_id($taxonomy_id);
            $product_attribute->set_name($name);
            $product_attribute->set_options($options);
            $product_attribute->set_visible(true);
           // syncwarehouse_write_log("product_attribute: ".print_r($product_attribute,true));
            $atts['ds_' . $sanitize_name] = $product_attribute;

        }
        //syncwarehouse_write_log("Attributtes: ".print_r($atts,true));
        $product->set_attributes($atts);
        //wp_set_object_terms( $product->get_id(), '3 Business Days', 'pa_brand' , false);
        $product->save();
        if (function_exists('wc_rbp_update_role_based_price')) {
            if (!empty($price_array)) {
                wc_rbp_update_role_based_price($product->get_id(), $price_array);
                syncwarehouse_write_log($product->get_id()." Array Prices Updated!");
            } else {
                syncwarehouse_write_log("Array prices es empty!");
            }
        } else {
            syncwarehouse_write_log("Plugin Array Prices dont found!");
        }
        syncwarehouse_write_log("Product: " .$product->get_name()." " .$action . " with status  " . $product->get_status() ."!");
        return true;
    } catch (WC_Data_Exception $e) {
         syncwarehouse_write_log($e);
        return false;

    }
}

/*function syncwarehouse_get_attributes_in_line($name, $option)
{
    $attributes = array();
    $attribute = new WC_Product_Attribute();
      //$attribute->set_id(0);
    $attribute->set_name("demo");
    $attribute->set_options(array("1", "3"));
      //$attribute->set_position(0);
    $attribute->set_visible(true);
    $attribute->set_variation(true);
      //$attribute->save();

//attributes['test-attribute'] = $attribute;
    return $attribute;
}*/

/**
 * Crea una categoria, recibe un array strings con los nombres de las categorias y devuelve un array de los ids de cada una de las categorias creadas
 * @since 3.0.0
 */
function syncwarehouse_product_categories($categories)
{
    $array_categories_id = array();
    foreach ($categories as $category) {
        $category_inserted = get_term_by('name', $category, 'product_cat', ARRAY_A);
        if (!$category_inserted['term_id']) {
            $category_inserted = wp_insert_term($category, 'product_cat');
        }
        $array_categories_id[] = $category_inserted['term_id'];
    }
    return $array_categories_id;
}

/**
 * Busca el id de la imagen en base al nombre del archivo, en caso de no existir lo sube al servidor
 * @global type $wpdb
 * @param type $image_url
 * @param type $product_id
 * @return type
 */
function syncwarehouse_upload_images_by_url_and_return_id($image_url, $product_id)
{
    global $wpdb;

    $filePath = syncwarehouse_filePath($image_url);

    $title_ = $filePath["basename"];

    $title = sanitize_title(preg_replace('/\\.[^.\\s]{3,4}$/', '', $title_));

    $attachments = $wpdb->get_results("SELECT ID FROM $wpdb->posts WHERE post_title = '$title' AND post_type = 'attachment' ", OBJECT);

    if ($attachments) {
        syncwarehouse_write_log("Image already exists with ID: " . $attachments[0]->ID);
        return $attachments[0]->ID;
    } else {
    
        $image_id = media_sideload_image($image_url, $product_id, $title, 'src');

        if (is_wp_error($image_id)) {
            syncwarehouse_write_log("Error getting image from : " .   $image_url.  ' -> '. print_r($image_id->get_error_message(),true));
            return null;
        } else {
            if (isset($image_id[0])) {
                syncwarehouse_write_log("Image Id Inserted: " .  print_r($image_id,true));
                return $image_id[0];
            } else {
                syncwarehouse_write_log("Error uploading image #2: " . $image_url .  print_r($image_id,true));
                return null;
            }
        }
    }
}

/**
 * Obtiene el nombre del archivo en base a una url
 * @param type $filePath
 * @return type
 */
function syncwarehouse_filePath($filePath)
{
    $fileParts = pathinfo($filePath);

    if (!isset($fileParts['filename'])) {
        $fileParts['filename'] = substr($fileParts['basename'], 0, strrpos($fileParts['basename'], '.'));
    }

    return $fileParts;
}


/**
 * Tags
 * @since 3.0.0
 */
function syncwarehouse_product_tags($tags)
{

    $array_tags_id = array();

    foreach ($tags as $tag) {

        $tag_inserted = get_term_by('name', $tag, 'product_tag', ARRAY_A);

        if (!$tag_inserted['term_id']) {
            $tag_inserted = wp_insert_term($tag, 'product_tag');
        }

        $array_tags_id[] = $tag_inserted['term_id'];
    }

    return $array_tags_id;
}

/**
 * Para depuración escribe mensajes
 * @param type $message
 */
function syncwarehouse_write_log($message)
{
    $debug = get_option('syncwarehoused_debug');
    $store_name = get_bloginfo("name");
    $message = $store_name . " -> " . $message;

    if ($debug == "yes") {
        write_log($message);
        $file_path= dirname(__FILE__) ."/logs/sync_".date("m_d_Y").".log";
        $myfile = fopen($file_path, "a") or die("Unable to open file!");
        fwrite($myfile, print_r($message, true) . "\n");
        fclose($myfile);
    }
}
/****
 Writes logs in worpdress log
 **/
if (!function_exists('write_log')) {
    function write_log($log)
    {      
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }

           
    }
} 


function remote_id_admin_notice__success()
{
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php _e('Please insert the remote store id !', 'sync-text-domain'); ?></p>
    </div>
    <?php

}

function external_database_info_admin_notice__success()
{
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php _e('Please insert the external database info!', 'sync-text-domain'); ?></p>
    </div>
    <?php

}

function create_products_event_admin_notice__success()
{
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e('Create products cron schedule created!', 'sync-text-domain'); ?></p>
    </div>
    <?php

}

function remove_products_event_admin_notice__success()
{
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e('Create products cron schedule created!', 'sync-text-domain'); ?></p>
    </div>
    <?php

}


function create_update_products_stock_event_admin_notice__success()
{
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e('Create update stock cron schedule created!', 'sync-text-domain'); ?></p>
    </div>
    <?php

}

function remote_update_products_stock_event_admin_notice__success()
{
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e('Remove update stock cron schedule created!', 'sync-text-domain'); ?></p>
    </div>
    <?php

}

function remote_update_products_hours_invalid__error($error = 'La hora de ejecución del proceso es incorrecta 1-24!')
{
    ?>
    <div class="notice notice-error">
        <p><?php _e($error, 'sync-text-domain'); ?></p>
    </div>
    <?php

}
