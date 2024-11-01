<?php

/*
Plugin Name: Ultimate Downloadable Products for WooCommerce
Plugin URI: https://wordpress.org/plugins/ultimate-downloadable-products-for-woocommerce
Description: Ultimate Downloadable Products for WooCommerce plugin enables to display downloadable files on a product page.
Version: 1.3.1
WC requires at least: 5.5.0
WC tested up to: 8.0.3
Author: ethereumicoio
Author URI: https://ethereumico.io
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: ultimate-downloadable-products-for-woocommerce
Domain Path: /languages
*/
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
use  UltimateDownloadableProducts\Service\ImageManipulation as ImageManipulationService ;
use  wapmorgan\UnifiedArchive\UnifiedArchive ;
// Explicitly globalize to support bootstrapped WordPress
global 
    $ULTIMATE_DOWNLOADABLE_PRODUCTS_plugin_basename,
    $ULTIMATE_DOWNLOADABLE_PRODUCTS_options,
    $ULTIMATE_DOWNLOADABLE_PRODUCTS_plugin_dir,
    $ULTIMATE_DOWNLOADABLE_PRODUCTS_plugin_url_path,
    $ULTIMATE_DOWNLOADABLE_PRODUCTS_product
;
if ( !function_exists( 'ULTIMATE_DOWNLOADABLE_PRODUCTS_deactivate' ) ) {
    function ULTIMATE_DOWNLOADABLE_PRODUCTS_deactivate()
    {
        if ( !current_user_can( 'activate_plugins' ) ) {
            return;
        }
        deactivate_plugins( plugin_basename( __FILE__ ) );
    }

}

if ( version_compare( phpversion(), '7.0', '<' ) ) {
    add_action( 'admin_init', 'ULTIMATE_DOWNLOADABLE_PRODUCTS_deactivate' );
    add_action( 'admin_notices', 'ULTIMATE_DOWNLOADABLE_PRODUCTS_admin_notice' );
    function ULTIMATE_DOWNLOADABLE_PRODUCTS_admin_notice()
    {
        if ( !current_user_can( 'activate_plugins' ) ) {
            return;
        }
        echo  '<div class="error"><p><strong>Ultimate Downloadable Products for WooCommerce</strong> requires PHP version 7.0 or above.</p></div>' ;
        if ( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
    }

} else {
    /**
     * Check if WooCommerce is active
     * https://wordpress.stackexchange.com/a/193908/137915
     **/
    
    if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        add_action( 'admin_init', 'ULTIMATE_DOWNLOADABLE_PRODUCTS_deactivate' );
        add_action( 'admin_notices', 'ULTIMATE_DOWNLOADABLE_PRODUCTS_admin_notice_woocommerce' );
        function ULTIMATE_DOWNLOADABLE_PRODUCTS_admin_notice_woocommerce()
        {
            if ( !current_user_can( 'activate_plugins' ) ) {
                return;
            }
            echo  '<div class="error"><p><strong>Ultimate Downloadable Products for WooCommerce</strong> requires WooCommerce plugin to be installed and activated.</p></div>' ;
            if ( isset( $_GET['activate'] ) ) {
                unset( $_GET['activate'] );
            }
        }
    
    } else {
        
        if ( function_exists( 'ultimate_downloadable_products_for_woocommerce_freemius_init' ) ) {
            ultimate_downloadable_products_for_woocommerce_freemius_init()->set_basename( false, __FILE__ );
        } else {
            // Create a helper function for easy SDK access.
            function ultimate_downloadable_products_for_woocommerce_freemius_init()
            {
                global  $ultimate_downloadable_products_for_woocommerce_freemius_init ;
                
                if ( !isset( $ultimate_downloadable_products_for_woocommerce_freemius_init ) ) {
                    // Activate multisite network integration.
                    if ( !defined( 'WP_FS__PRODUCT_10631_MULTISITE' ) ) {
                        define( 'WP_FS__PRODUCT_10631_MULTISITE', true );
                    }
                    // Include Freemius SDK.
                    require_once dirname( __FILE__ ) . '/vendor/freemius/wordpress-sdk/start.php';
                    $ultimate_downloadable_products_for_woocommerce_freemius_init = fs_dynamic_init( array(
                        'id'              => '10631',
                        'slug'            => 'ultimate-downloadable-products-for-woocommerce',
                        'type'            => 'plugin',
                        'public_key'      => 'pk_b8f344a34fdcab548f324dd5275cd',
                        'is_premium'      => false,
                        'has_addons'      => false,
                        'has_paid_plans'  => true,
                        'trial'           => array(
                        'days'               => 7,
                        'is_require_payment' => true,
                    ),
                        'has_affiliation' => 'all',
                        'menu'            => array(
                        'slug'   => 'ultimate-downloadable-products-for-woocommerce',
                        'parent' => array(
                        'slug' => 'options-general.php',
                    ),
                    ),
                        'is_live'         => true,
                    ) );
                }
                
                return $ultimate_downloadable_products_for_woocommerce_freemius_init;
            }
            
            // Init Freemius.
            ultimate_downloadable_products_for_woocommerce_freemius_init();
            // Signal that SDK was initiated.
            do_action( 'ultimate_downloadable_products_for_woocommerce_freemius_init_loaded' );
            // ... Your plugin's main file logic ...
            $ULTIMATE_DOWNLOADABLE_PRODUCTS_plugin_basename = plugin_basename( dirname( __FILE__ ) );
            $ULTIMATE_DOWNLOADABLE_PRODUCTS_plugin_dir = untrailingslashit( plugin_dir_path( __FILE__ ) );
            $plugin_url_path = untrailingslashit( plugin_dir_url( __FILE__ ) );
            // HTTPS?
            $ULTIMATE_DOWNLOADABLE_PRODUCTS_plugin_url_path = ( is_ssl() ? str_replace( 'http:', 'https:', $plugin_url_path ) : $plugin_url_path );
            // Set plugin options
            $ULTIMATE_DOWNLOADABLE_PRODUCTS_options = get_option( 'ultimate-downloadable-products-for-woocommerce_options', array() );
            require $ULTIMATE_DOWNLOADABLE_PRODUCTS_plugin_dir . '/vendor/autoload.php';
            function ULTIMATE_DOWNLOADABLE_PRODUCTS_list_shortcode( $attributes )
            {
                $attributes = shortcode_atts( array(), $attributes, 'ultimate-downloadable-list' );
                // $gaslimit = !empty($attributes['gaslimit']) ? $attributes['gaslimit'] :
                //         (!empty($options['gaslimit']) ? esc_attr($options['gaslimit']) : "200000");
                $product_id = get_the_ID();
                if ( !$product_id ) {
                    return '';
                }
                $product = wc_get_product( $product_id );
                if ( !$product ) {
                    return '';
                }
                $products = [];
                // @see https://stackoverflow.com/a/62205042/4256005
                if ( $product->is_downloadable() ) {
                    $products[] = $product;
                }
                if ( !$products ) {
                    return '';
                }
                $str_output = [];
                foreach ( $products as $p ) {
                    $output = [];
                    // Initializing
                    foreach ( $p->get_downloads() as $key_download_id => $download ) {
                        ## Using WC_Product_Download methods (since WooCommerce 3)
                        $download_name = $download->get_name();
                        // File label name
                        $download_link = $download->get_file();
                        // File Url
                        $download_id = $download->get_id();
                        // File Id (same as $key_download_id)
                        // $download_type = $download->get_file_type(); // File type
                        // $download_ext  = $download->get_file_extension(); // File extension
                        $output[$download_id] = _ULTIMATE_DOWNLOADABLE_PRODUCTS_get_rich_download_li( $product_id, $download_link, $download_name );
                    }
                    $str_output[] = '<ul class="ultimate-downloadable-products-list" data-product-id="' . $p->get_id() . '">' . implode( '', $output ) . '</ul>';
                }
                // Loop through WC_Product_Download objects
                ULTIMATE_DOWNLOADABLE_PRODUCTS_enqueue_script();
                wp_enqueue_style( 'ultimate-downloadable-products-for-woocommerce' );
                wp_enqueue_script( 'ultimate-downloadable-products-for-woocommerce' );
                return _ULTIMATE_DOWNLOADABLE_PRODUCTS_get_js( $product->is_type( 'variable' ) ) . implode( '', $str_output );
            }
            
            add_shortcode( 'ultimate-downloadable-list', 'ULTIMATE_DOWNLOADABLE_PRODUCTS_list_shortcode' );
            function _ULTIMATE_DOWNLOADABLE_PRODUCTS_link_to_path( $download_link )
            {
                $parts = explode( '/', $download_link );
                $found = false;
                $res = [];
                if ( $parts ) {
                    foreach ( $parts as $part ) {
                        
                        if ( $part === "wp-content" ) {
                            $found = true;
                            continue;
                        }
                        
                        if ( !$found ) {
                            continue;
                        }
                        $res[] = $part;
                    }
                }
                $download_file_path = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . implode( DIRECTORY_SEPARATOR, $res );
                $file_exists = file_exists( $download_file_path );
                if ( false === $file_exists ) {
                    return null;
                }
                return $download_file_path;
            }
            
            function _ULTIMATE_DOWNLOADABLE_PRODUCTS_get_rich_download_li( $product_id, $download_link, $download_name )
            {
                global  $ULTIMATE_DOWNLOADABLE_PRODUCTS_options ;
                $options = $ULTIMATE_DOWNLOADABLE_PRODUCTS_options;
                $show_downloads_thumbnails = !isset( $options['show_downloads_thumbnails'] ) || !empty($options['show_downloads_thumbnails']);
                $res = wp_check_filetype( $download_link );
                $mime = $res['type'];
                
                if ( !function_exists( 'gd_info' ) || !$show_downloads_thumbnails || !is_string( $mime ) || 0 !== strpos( $mime, 'image/' ) ) {
                    if ( !function_exists( 'gd_info' ) || !is_string( $mime ) || 0 !== strpos( $mime, 'image/' ) ) {
                        ULTIMATE_DOWNLOADABLE_PRODUCTS_log( '_ULTIMATE_DOWNLOADABLE_PRODUCTS_get_rich_download_li: simple li: function_exists(gd_info) = ' . function_exists( 'gd_info' ) . '; res = ' . print_r( $res, true ) );
                    }
                    return _ULTIMATE_DOWNLOADABLE_PRODUCTS_get_simple_li( $download_name );
                } else {
                    list( $base64_data, $width, $height ) = ULTIMATE_DOWNLOADABLE_PRODUCTS_get_small_image_base64_data( $product_id, $download_link, $mime );
                    
                    if ( is_null( $base64_data ) ) {
                        ULTIMATE_DOWNLOADABLE_PRODUCTS_log( '_ULTIMATE_DOWNLOADABLE_PRODUCTS_get_rich_download_li: simple li: base64_data is null: product_id = ' . $product_id . '; download_link = ' . $download_link . '; res = ' . print_r( $res, true ) );
                        return _ULTIMATE_DOWNLOADABLE_PRODUCTS_get_simple_li( $download_name );
                    } else {
                        return _ULTIMATE_DOWNLOADABLE_PRODUCTS_get_icon_li(
                            $download_name,
                            $base64_data,
                            $width,
                            $height,
                            $mime
                        );
                    }
                
                }
                
                return _ULTIMATE_DOWNLOADABLE_PRODUCTS_get_simple_li( $download_name );
            }
            
            function _ULTIMATE_DOWNLOADABLE_PRODUCTS_get_simple_li( $download_name )
            {
                ob_start();
                ?>
                <li class="ultimate-downloadable-products-name">
                    <span class="ultimate-downloadable-products-wrapper">
                        <span class="ultimate-downloadable-products-title">
                            <?php 
                echo  $download_name ;
                ?>
                        </span>
                    </span>
                </li>
            <?php 
                return ob_get_clean();
            }
            
            function _ULTIMATE_DOWNLOADABLE_PRODUCTS_get_icon_li(
                $download_name,
                $base64_data,
                $width,
                $height,
                $mime
            )
            {
                global  $ULTIMATE_DOWNLOADABLE_PRODUCTS_options ;
                $options = $ULTIMATE_DOWNLOADABLE_PRODUCTS_options;
                $image_thumbnail_width = 24;
                $min_size = $image_thumbnail_width;
                $orientation = 'landscape';
                if ( $height > $width ) {
                    $orientation = 'portrait';
                }
                $margin_style = '';
                switch ( $orientation ) {
                    case 'landscape':
                        $margin = ($width - $height) / 2;
                        if ( $margin > 0 ) {
                            $margin_style = 'margin-left:-' . $margin . 'px';
                        }
                        break;
                    case 'portrait':
                        $margin = ($height - $width) / 2;
                        if ( $margin > 0 ) {
                            $margin_style = 'margin-top:-' . $margin . 'px';
                        }
                        break;
                }
                ob_start();
                ?>
                <li class="ultimate-downloadable-products-name">
                    <span class="ultimate-downloadable-products-wrapper">
                        <span class="ultimate-downloadable-products-image-wrapper-<?php 
                echo  $orientation ;
                ?>" style="width:<?php 
                echo  $min_size ;
                ?>px;height:<?php 
                echo  $min_size ;
                ?>px">
                            <img class="ultimate-downloadable-products-image" width="<?php 
                echo  $width ;
                ?>px" height="<?php 
                echo  $height ;
                ?>px" src="data:<?php 
                echo  $mime ;
                ?>;base64, <?php 
                echo  $base64_data ;
                ?>" alt="<?php 
                echo  $download_name ;
                ?>" style="<?php 
                echo  $margin_style ;
                ?>">
                        </span>
                        <span class="ultimate-downloadable-products-title"><?php 
                echo  $download_name ;
                ?></span>
                    </span>
                </li>
                <?php 
                return ob_get_clean();
            }
            
            function _ULTIMATE_DOWNLOADABLE_PRODUCTS_get_js( $variable )
            {
                if ( !$variable ) {
                    return '';
                }
                ob_start();
                return ob_get_clean();
            }
            
            add_filter( 'woocommerce_product_tabs', 'ULTIMATE_DOWNLOADABLE_PRODUCTS_woocommerce_product_tabs' );
            function ULTIMATE_DOWNLOADABLE_PRODUCTS_woocommerce_product_tabs( $tabs )
            {
                global  $ULTIMATE_DOWNLOADABLE_PRODUCTS_options ;
                if ( isset( $ULTIMATE_DOWNLOADABLE_PRODUCTS_options['show_downloads_tab'] ) && empty($ULTIMATE_DOWNLOADABLE_PRODUCTS_options['show_downloads_tab']) ) {
                    return $tabs;
                }
                $product_id = get_the_ID();
                if ( !$product_id ) {
                    return $tabs;
                }
                $product = wc_get_product( $product_id );
                if ( !$product ) {
                    return $tabs;
                }
                if ( !$product->is_downloadable() ) {
                    return $tabs;
                }
                // Adds the new tab
                $tabs['downloads'] = array(
                    'title'    => __( 'Downloads', 'woocommerce' ),
                    'priority' => 5000,
                    'callback' => 'ULTIMATE_DOWNLOADABLE_PRODUCTS_woocommerce_product_tabs_content',
                );
                return $tabs;
            }
            
            function ULTIMATE_DOWNLOADABLE_PRODUCTS_woocommerce_product_tabs_content()
            {
                // The new tab content
                echo  ULTIMATE_DOWNLOADABLE_PRODUCTS_list_shortcode( [] ) ;
            }
            
            /**
             * Php opens and outputs images using different functions. In this method, we choose correct one using mime type.
             * Remember to use function_exists() before using returned functions.
             *
             * @param string $mime - mime type (f.e. image/jpeg).
             * @return array - array where first index is image open function and second index output function.
             */
            function ULTIMATE_DOWNLOADABLE_PRODUCTS_choose_funcs_for_mime_type( $mime )
            {
                $type = explode( '/', $mime )[1];
                return array( "imagecreatefrom{$type}", "image{$type}" );
            }
            
            function ULTIMATE_DOWNLOADABLE_PRODUCTS_get_small_image_base64_data( $product_id, $download_link, $mime )
            {
                global  $ULTIMATE_DOWNLOADABLE_PRODUCTS_options ;
                $blur_downloads_thumbnails = false;
                $image_thumbnail_blur_strength = ( !empty($ULTIMATE_DOWNLOADABLE_PRODUCTS_options['image_thumbnail_blur_strength']) && is_numeric( $ULTIMATE_DOWNLOADABLE_PRODUCTS_options['image_thumbnail_blur_strength'] ) ? intval( $ULTIMATE_DOWNLOADABLE_PRODUCTS_options['image_thumbnail_blur_strength'] ) : 1 );
                $image_thumbnail_width = 24;
                $key = md5( $download_link . (( $blur_downloads_thumbnails ? '-blur_downloads_thumbnails-blur-strength_' . $image_thumbnail_blur_strength : '-no-blur_downloads_thumbnails-blur' )) . '-width_' . $image_thumbnail_width );
                $_udp_image_data_hash = get_post_meta( $product_id, '_udp_image_data_hash', true );
                $image_data_hash = [];
                $no_cache = empty($_udp_image_data_hash);
                
                if ( !$no_cache ) {
                    $image_data_hash = json_decode( $_udp_image_data_hash, true );
                    $no_cache = !is_array( $image_data_hash ) || !isset( $image_data_hash[$key] );
                }
                
                if ( !is_array( $image_data_hash ) ) {
                    $image_data_hash = [];
                }
                
                if ( $no_cache ) {
                    $data = _ULTIMATE_DOWNLOADABLE_PRODUCTS_get_small_image_base64_data_impl( $download_link, $mime );
                    
                    if ( is_null( $data ) ) {
                        ULTIMATE_DOWNLOADABLE_PRODUCTS_log( "ULTIMATE_DOWNLOADABLE_PRODUCTS_get_small_image_base64_data({$product_id}, {$download_link}, {$mime}): null data returned" );
                        return [ null, null, null ];
                    }
                    
                    $image_data_hash[$key] = $data;
                    $_udp_image_data_hash = json_encode( $image_data_hash );
                    update_post_meta( $product_id, '_udp_image_data_hash', $_udp_image_data_hash );
                }
                
                return $image_data_hash[$key];
            }
            
            function _ULTIMATE_DOWNLOADABLE_PRODUCTS_get_small_image_base64_data_impl( $download_link, $mime )
            {
                global  $ULTIMATE_DOWNLOADABLE_PRODUCTS_options ;
                $image_thumbnail_width = 24;
                $image_thumbnail_blur_strength = ( !empty($ULTIMATE_DOWNLOADABLE_PRODUCTS_options['image_thumbnail_blur_strength']) && is_numeric( $ULTIMATE_DOWNLOADABLE_PRODUCTS_options['image_thumbnail_blur_strength'] ) ? intval( $ULTIMATE_DOWNLOADABLE_PRODUCTS_options['image_thumbnail_blur_strength'] ) : 1 );
                $blur_downloads_thumbnails = false;
                list( $create, $output ) = ULTIMATE_DOWNLOADABLE_PRODUCTS_choose_funcs_for_mime_type( $mime );
                
                if ( !function_exists( $create ) || !function_exists( $output ) ) {
                    ULTIMATE_DOWNLOADABLE_PRODUCTS_log( "_ULTIMATE_DOWNLOADABLE_PRODUCTS_get_small_image_base64_data_impl({$download_link}, {$mime}): functions do not exists: " . $create . ', ' . $output );
                    return null;
                }
                
                $image = @$create( $download_link );
                
                if ( !$image ) {
                    $download_path = ABSPATH . parse_url( $download_link, PHP_URL_PATH );
                    // ULTIMATE_DOWNLOADABLE_PRODUCTS_log("_ULTIMATE_DOWNLOADABLE_PRODUCTS_get_small_image_base64_data_impl($download_link, $mime): download_path = " . $download_path);
                    $image = @$create( $download_path );
                }
                
                
                if ( !$image ) {
                    ULTIMATE_DOWNLOADABLE_PRODUCTS_log( "_ULTIMATE_DOWNLOADABLE_PRODUCTS_get_small_image_base64_data_impl({$download_link}, {$mime}): image is null for {$create}" );
                    return null;
                }
                
                $image_manipulation_service = new ImageManipulationService( ( $blur_downloads_thumbnails ? $image_thumbnail_blur_strength : null ), $image_thumbnail_width );
                $image = $image_manipulation_service->process_image( $mime, $image );
                ob_start();
                $output( $image );
                $contents = ob_get_clean();
                $data = base64_encode( $contents );
                $width = imagesx( $image );
                $height = imagesy( $image );
                return [ $data, $width, $height ];
            }
            
            function ULTIMATE_DOWNLOADABLE_PRODUCTS_custom_js()
            {
                global  $post ;
                if ( 'product' != get_post_type() ) {
                    return;
                }
                ULTIMATE_DOWNLOADABLE_PRODUCTS_custom_js_aux();
            }
            
            function ULTIMATE_DOWNLOADABLE_PRODUCTS_custom_js_aux()
            {
                global  $post ;
                wp_enqueue_script( 'ultimate-downloadable-products-for-woocommerce-admin' );
                ?>
                <script type='text/javascript'>
                    jQuery(document).ready(function() {

                        ULTIMATE_DOWNLOADABLE_PRODUCTS_init();
                        <?php 
                ?>

                    });
                </script>
<?php 
            }
            
            add_action( 'admin_footer', 'ULTIMATE_DOWNLOADABLE_PRODUCTS_custom_js' );
            function ULTIMATE_DOWNLOADABLE_PRODUCTS_stylesheet()
            {
                global  $ULTIMATE_DOWNLOADABLE_PRODUCTS_plugin_url_path ;
                
                if ( !wp_style_is( 'ultimate-downloadable-products-for-woocommerce', 'queue' ) && !wp_style_is( 'ultimate-downloadable-products-for-woocommerce', 'done' ) ) {
                    wp_dequeue_style( 'ultimate-downloadable-products-for-woocommerce' );
                    wp_deregister_style( 'ultimate-downloadable-products-for-woocommerce' );
                    wp_register_style(
                        'ultimate-downloadable-products-for-woocommerce',
                        $ULTIMATE_DOWNLOADABLE_PRODUCTS_plugin_url_path . '/ultimate-downloadable-products-for-woocommerce.css',
                        array(),
                        '1.3.1'
                    );
                }
            
            }
            
            add_action( 'wp_enqueue_scripts', 'ULTIMATE_DOWNLOADABLE_PRODUCTS_stylesheet', 20 );
            function ULTIMATE_DOWNLOADABLE_PRODUCTS_enqueue_script_admin()
            {
                global  $ULTIMATE_DOWNLOADABLE_PRODUCTS_plugin_url_path ;
                $min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min' );
                
                if ( !wp_script_is( 'ultimate-downloadable-products-for-woocommerce-admin', 'queue' ) && !wp_script_is( 'ultimate-downloadable-products-for-woocommerce-admin', 'done' ) ) {
                    wp_dequeue_script( 'ultimate-downloadable-products-for-woocommerce-admin' );
                    wp_deregister_script( 'ultimate-downloadable-products-for-woocommerce-admin' );
                    wp_register_script(
                        'ultimate-downloadable-products-for-woocommerce-admin',
                        $ULTIMATE_DOWNLOADABLE_PRODUCTS_plugin_url_path . "/ultimate-downloadable-products-for-woocommerce{$min}.js",
                        array( 'jquery' ),
                        '1.3.1'
                    );
                }
                
                wp_localize_script( 'ultimate-downloadable-products-for-woocommerce-admin', 'ultimate_downloadable', apply_filters( 'cryptocurrency_product_for_woocommerce_wp_localize_script', [] ) );
            }
            
            function ULTIMATE_DOWNLOADABLE_PRODUCTS_enqueue_script()
            {
                global  $ULTIMATE_DOWNLOADABLE_PRODUCTS_plugin_url_path, $ULTIMATE_DOWNLOADABLE_PRODUCTS_plugin_dir ;
            }
            
            add_action( 'admin_enqueue_scripts', 'ULTIMATE_DOWNLOADABLE_PRODUCTS_enqueue_script_admin' );
            add_action( 'wp_enqueue_scripts', 'ULTIMATE_DOWNLOADABLE_PRODUCTS_enqueue_script' );
            //----------------------------------------------------------------------------//
            //                               Admin Options                                //
            //----------------------------------------------------------------------------//
            if ( is_admin() ) {
                include_once $ULTIMATE_DOWNLOADABLE_PRODUCTS_plugin_dir . '/ultimate-downloadable-products-for-woocommerce.admin.php';
            }
            function ULTIMATE_DOWNLOADABLE_PRODUCTS_add_menu_link()
            {
                $page = add_options_page(
                    __( 'Ultimate Downloadable Products Settings', 'ultimate-downloadable-products-for-woocommerce' ),
                    __( 'Ultimate Downloadable Products', 'ultimate-downloadable-products-for-woocommerce' ),
                    'manage_options',
                    'ultimate-downloadable-products-for-woocommerce',
                    'ULTIMATE_DOWNLOADABLE_PRODUCTS_options_page'
                );
            }
            
            add_filter( 'admin_menu', 'ULTIMATE_DOWNLOADABLE_PRODUCTS_add_menu_link' );
            // Place in Option List on Settings > Plugins page
            function ULTIMATE_DOWNLOADABLE_PRODUCTS_actlinks( $links, $file )
            {
                // Static so we don't call plugin_basename on every plugin row.
                static  $this_plugin ;
                if ( !$this_plugin ) {
                    $this_plugin = plugin_basename( __FILE__ );
                }
                
                if ( $file == $this_plugin ) {
                    $settings_link = '<a href="options-general.php?page=ultimate-downloadable-products-for-woocommerce">' . __( 'Settings' ) . '</a>';
                    array_unshift( $links, $settings_link );
                    // before other links
                }
                
                return $links;
            }
            
            add_filter(
                'plugin_action_links',
                'ULTIMATE_DOWNLOADABLE_PRODUCTS_actlinks',
                10,
                2
            );
            class ULTIMATE_DOWNLOADABLE_PRODUCTS_Logger
            {
                /**
                 * Add a log entry.
                 *
                 * This is not the preferred method for adding log messages. Please use log() or any one of
                 * the level methods (debug(), info(), etc.). This method may be deprecated in the future.
                 *
                 * @param string $handle
                 * @param string $message
                 * @param string $level
                 *
                 * @see https://docs.woocommerce.com/wc-apidocs/source-class-WC_Logger.html#105
                 *
                 * @return bool
                 */
                public function add( $handle, $message, $level = 'unused' )
                {
                    error_log( $handle . ': ' . $message );
                    return true;
                }
            
            }
            function ULTIMATE_DOWNLOADABLE_PRODUCTS_log( $error )
            {
                static  $logger = false ;
                // Create a logger instance if we don't already have one.
                if ( false === $logger ) {
                    /**
                     * Check if WooCommerce is active
                     * https://wordpress.stackexchange.com/a/193908/137915
                     **/
                    
                    if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) && class_exists( "WC_Logger", false ) ) {
                        $logger = new WC_Logger();
                    } else {
                        $logger = new ULTIMATE_DOWNLOADABLE_PRODUCTS_Logger();
                    }
                
                }
                $logger->add( 'ultimate-downloadable-products-for-woocommerce', $error );
            }
            
            //----------------------------------------------------------------------------//
            //                                   L10n                                     //
            //----------------------------------------------------------------------------//
            function ULTIMATE_DOWNLOADABLE_PRODUCTS_load_textdomain()
            {
                /**
                 * Localise.
                 */
                load_plugin_textdomain( 'ultimate-downloadable-products-for-woocommerce', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
            }
            
            add_action( 'plugins_loaded', 'ULTIMATE_DOWNLOADABLE_PRODUCTS_load_textdomain' );
        }
        
        //if ( ! function_exists( 'ultimate_downloadable_products_for_woocommerce_freemius_init' ) ) {
    }
    
    // WooCommerce activated
}

// PHP version
