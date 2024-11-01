<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Exit if accessed directly
function ULTIMATE_DOWNLOADABLE_PRODUCTS_options_page()
{
    // Require admin privs
    if ( !current_user_can( 'manage_options' ) ) {
        return false;
    }
    $new_options = array();
    // Which tab is selected?
    $possible_screens = array(
        'default' => esc_html( __( 'Standard', 'ultimate-downloadable-products-for-woocommerce' ) ),
    );
    $possible_screens = apply_filters( 'ultimate_downloadable_products_for_woocommerce_settings_tabs', $possible_screens );
    asort( $possible_screens );
    $current_screen = ( isset( $_GET['tab'] ) && isset( $possible_screens[$_GET['tab']] ) ? sanitize_url( $_GET['tab'] ) : 'default' );
    
    if ( isset( $_POST['Submit'] ) ) {
        // Nonce verification
        check_admin_referer( 'ultimate-downloadable-products-for-woocommerce-update-options' );
        // Standard options screen
        
        if ( 'default' == $current_screen ) {
            $new_options['show_downloads_tab'] = ( !empty($_POST['ULTIMATE_DOWNLOADABLE_PRODUCTS_show_downloads_tab']) ? 'on' : '' );
            $new_options['show_downloads_thumbnails'] = ( !empty($_POST['ULTIMATE_DOWNLOADABLE_PRODUCTS_show_downloads_thumbnails']) ? 'on' : '' );
        }
        
        $new_options = apply_filters( 'ultimate_downloadable_products_for_woocommerce_get_save_options', $new_options, $current_screen );
        // Get all existing Ultimate Downloadable Products options
        $existing_options = get_option( 'ultimate-downloadable-products-for-woocommerce_options', array() );
        // Merge $new_options into $existing_options to retain Ultimate Downloadable Products options from all other screens/tabs
        if ( $existing_options ) {
            $new_options = array_merge( $existing_options, $new_options );
        }
        
        if ( false !== get_option( 'ultimate-downloadable-products-for-woocommerce_options' ) ) {
            update_option( 'ultimate-downloadable-products-for-woocommerce_options', $new_options );
        } else {
            $deprecated = '';
            $autoload = 'no';
            add_option(
                'ultimate-downloadable-products-for-woocommerce_options',
                $new_options,
                $deprecated,
                $autoload
            );
        }
        
        ?>
        <div class="updated">
            <p><?php 
        _e( 'Settings saved.' );
        ?></p>
        </div>
    <?php 
    } else {
        
        if ( isset( $_POST['Reset'] ) ) {
            // Nonce verification
            check_admin_referer( 'ultimate-downloadable-products-for-woocommerce-update-options' );
            delete_option( 'ultimate-downloadable-products-for-woocommerce_options' );
        }
    
    }
    
    $existing_options = get_option( 'ultimate-downloadable-products-for-woocommerce_options', array() );
    $options = stripslashes_deep( get_option( 'ultimate-downloadable-products-for-woocommerce_options', array() ) );
    ?>

    <div class="wrap">

        <h1><?php 
    _e( 'Ultimate Downloadable Products Settings', 'ultimate-downloadable-products-for-woocommerce' );
    ?></h1>

        <?php 
    settings_errors();
    ?>

        <?php 
    
    if ( ultimate_downloadable_products_for_woocommerce_freemius_init()->is_not_paying() ) {
        echo  '<section><h1>' . esc_html__( 'Awesome Premium Features', 'ultimate-downloadable-products-for-woocommerce' ) . '</h1>' ;
        echo  esc_html__( 'Per product ultimate downloadable input and more.', 'ultimate-downloadable-products-for-woocommerce' ) ;
        echo  ' <a href="' . esc_attr( ultimate_downloadable_products_for_woocommerce_freemius_init()->get_upgrade_url() ) . '">' . esc_html__( 'Upgrade Now!', 'ultimate-downloadable-products-for-woocommerce' ) . '</a>' ;
        echo  '</section>' ;
    }
    
    ?>

        <h2 class="nav-tab-wrapper">
            <?php 
    if ( $possible_screens ) {
        foreach ( $possible_screens as $s => $sTitle ) {
            ?>
                <a href="<?php 
            echo  admin_url( 'options-general.php?page=ultimate-downloadable-products-for-woocommerce&tab=' . esc_attr( $s ) ) ;
            ?>" class="nav-tab<?php 
            if ( $s == $current_screen ) {
                echo  ' nav-tab-active' ;
            }
            ?>"><?php 
            echo  esc_html( $sTitle ) ;
            ?></a>
            <?php 
        }
    }
    ?>
        </h2>

        <form id="ultimate-downloadable-products-for-woocommerce_admin_form" method="post" action="">

            <?php 
    wp_nonce_field( 'ultimate-downloadable-products-for-woocommerce-update-options' );
    ?>

            <table class="form-table">

                <?php 
    
    if ( 'default' == $current_screen ) {
        ?>
                    <tr valign="top">
                        <th scope="row"><?php 
        _e( "Show downloads tab", 'ultimate-downloadable-products-for-woocommerce' );
        ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input class="checkbox" name="ULTIMATE_DOWNLOADABLE_PRODUCTS_show_downloads_tab" type="checkbox" <?php 
        echo  ( !isset( $options['show_downloads_tab'] ) || !empty($options['show_downloads_tab']) ? 'checked' : '' ) ;
        ?>>
                                    <p class="description"><?php 
        _e( "If this setting is set, the Downloads tab is shown on the WooCommerce product page.", 'ultimate-downloadable-products-for-woocommerce' );
        ?></p>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row" colspan="2">
                            <h2><?php 
        _e( "Image thumbnails settings", 'ultimate-downloadable-products-for-woocommerce' );
        ?></h2>
                        </th>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php 
        _e( "Show image thumbnails", 'ultimate-downloadable-products-for-woocommerce' );
        ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input class="checkbox" name="ULTIMATE_DOWNLOADABLE_PRODUCTS_show_downloads_thumbnails" type="checkbox" <?php 
        echo  ( !isset( $options['show_downloads_thumbnails'] ) || !empty($options['show_downloads_thumbnails']) ? 'checked' : '' ) ;
        ?>>
                                    <p class="description"><?php 
        _e( "If this setting is set, image type files are shown with thumbnails generated.", 'ultimate-downloadable-products-for-woocommerce' );
        ?></p>
                                    <?php 
        
        if ( !function_exists( 'gd_info' ) ) {
            ?>
                                        <p class="description">
                                            <?php 
            echo  sprintf( __( '%1$sThe GD PHP module%2$s is required to enable this feature.', 'ultimate-downloadable-products-for-woocommerce' ), '<a href="https://www.php.net/manual/ru/book.image.php" target="_blank">', '</a>' ) ;
            ?></p>
                                    <?php 
        }
        
        ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php 
        _e( "Image thumbnail width", 'ultimate-downloadable-products-for-woocommerce' );
        ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input <?php 
        if ( !function_exists( 'gd_info' ) || !ultimate_downloadable_products_for_woocommerce_freemius_init()->is__premium_only() || !ultimate_downloadable_products_for_woocommerce_freemius_init()->can_use_premium_code() ) {
            echo  'disabled' ;
        }
        ?> class="checkbox" name="ULTIMATE_DOWNLOADABLE_PRODUCTS_image_thumbnail_width" type="number" value="<?php 
        echo  ( !empty($options['image_thumbnail_width']) ? esc_attr( $options['image_thumbnail_width'] ) : 24 ) ;
        ?>">
                                    <p class="description"><?php 
        _e( "The generated thumbnail image width in pixels.", 'ultimate-downloadable-products-for-woocommerce' );
        ?></p>
                                    <?php 
        
        if ( ultimate_downloadable_products_for_woocommerce_freemius_init()->is_not_paying() ) {
            ?>
                                        <p class="description">
                                            <?php 
            echo  sprintf( __( '%1$sUpgrade Now!%2$s to enable this feature.', 'ultimate-downloadable-products-for-woocommerce' ), '<a href="' . esc_attr( ultimate_downloadable_products_for_woocommerce_freemius_init()->get_upgrade_url() ) . '" target="_blank">', '</a>' ) ;
            ?></p>
                                    <?php 
        }
        
        ?>
                                    <?php 
        
        if ( !function_exists( 'gd_info' ) ) {
            ?>
                                        <p class="description">
                                            <?php 
            echo  sprintf( __( '%1$sThe GD PHP module%2$s is required to enable this feature.', 'ultimate-downloadable-products-for-woocommerce' ), '<a href="https://www.php.net/manual/ru/book.image.php" target="_blank">', '</a>' ) ;
            ?></p>
                                    <?php 
        }
        
        ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php 
        _e( "Blur image thumbnails", 'ultimate-downloadable-products-for-woocommerce' );
        ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input <?php 
        if ( !function_exists( 'gd_info' ) || !ultimate_downloadable_products_for_woocommerce_freemius_init()->is__premium_only() || !ultimate_downloadable_products_for_woocommerce_freemius_init()->can_use_premium_code() ) {
            echo  'disabled' ;
        }
        ?> class="checkbox" name="ULTIMATE_DOWNLOADABLE_PRODUCTS_blur_downloads_thumbnails" type="checkbox" <?php 
        echo  ( !isset( $options['blur_downloads_thumbnails'] ) || !empty($options['blur_downloads_thumbnails']) ? 'checked' : '' ) ;
        ?>>
                                    <p class="description"><?php 
        _e( "If this setting is set, thumbnails shown for image type files will be blurred with a gaussian filter.", 'ultimate-downloadable-products-for-woocommerce' );
        ?></p>
                                    <?php 
        
        if ( ultimate_downloadable_products_for_woocommerce_freemius_init()->is_not_paying() ) {
            ?>
                                        <p class="description">
                                            <?php 
            echo  sprintf( __( '%1$sUpgrade Now!%2$s to enable this feature.', 'ultimate-downloadable-products-for-woocommerce' ), '<a href="' . esc_attr( ultimate_downloadable_products_for_woocommerce_freemius_init()->get_upgrade_url() ) . '" target="_blank">', '</a>' ) ;
            ?></p>
                                    <?php 
        }
        
        ?>
                                    <?php 
        
        if ( !function_exists( 'gd_info' ) ) {
            ?>
                                        <p class="description">
                                            <?php 
            echo  sprintf( __( '%1$sThe GD PHP module%2$s is required to enable this feature.', 'ultimate-downloadable-products-for-woocommerce' ), '<a href="https://www.php.net/manual/ru/book.image.php" target="_blank">', '</a>' ) ;
            ?></p>
                                    <?php 
        }
        
        ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php 
        _e( "Blur thumbnail strength", 'ultimate-downloadable-products-for-woocommerce' );
        ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input <?php 
        if ( !function_exists( 'gd_info' ) || !ultimate_downloadable_products_for_woocommerce_freemius_init()->is__premium_only() || !ultimate_downloadable_products_for_woocommerce_freemius_init()->can_use_premium_code() ) {
            echo  'disabled' ;
        }
        ?> class="checkbox" name="ULTIMATE_DOWNLOADABLE_PRODUCTS_image_thumbnail_blur_strength" type="number" value="<?php 
        echo  ( !empty($options['image_thumbnail_blur_strength']) ? esc_attr( $options['image_thumbnail_blur_strength'] ) : 1 ) ;
        ?>">
                                    <p class="description"><?php 
        _e( "The generated thumbnail image blur strength in the gaussian filter runs count.", 'ultimate-downloadable-products-for-woocommerce' );
        ?></p>
                                    <?php 
        
        if ( ultimate_downloadable_products_for_woocommerce_freemius_init()->is_not_paying() ) {
            ?>
                                        <p class="description">
                                            <?php 
            echo  sprintf( __( '%1$sUpgrade Now!%2$s to enable this feature.', 'ultimate-downloadable-products-for-woocommerce' ), '<a href="' . esc_attr( ultimate_downloadable_products_for_woocommerce_freemius_init()->get_upgrade_url() ) . '" target="_blank">', '</a>' ) ;
            ?></p>
                                    <?php 
        }
        
        ?>
                                    <?php 
        
        if ( !function_exists( 'gd_info' ) ) {
            ?>
                                        <p class="description">
                                            <?php 
            echo  sprintf( __( '%1$sThe GD PHP module%2$s is required to enable this feature.', 'ultimate-downloadable-products-for-woocommerce' ), '<a href="https://www.php.net/manual/ru/book.image.php" target="_blank">', '</a>' ) ;
            ?></p>
                                    <?php 
        }
        
        ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row" colspan="2">
                            <h2><?php 
        _e( "Archive file format settings", 'ultimate-downloadable-products-for-woocommerce' );
        ?></h2>
                        </th>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php 
        _e( "RAR format support", 'ultimate-downloadable-products-for-woocommerce' );
        ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input <?php 
        if ( !ultimate_downloadable_products_for_woocommerce_freemius_init()->is__premium_only() || !ultimate_downloadable_products_for_woocommerce_freemius_init()->can_use_premium_code() ) {
            echo  'disabled' ;
        }
        ?> class="checkbox" name="ULTIMATE_DOWNLOADABLE_PRODUCTS_rar_format_support" type="checkbox" <?php 
        echo  ( !isset( $options['rar_format_support'] ) || !empty($options['rar_format_support']) ? 'checked' : '' ) ;
        ?>>
                                    <p class="description"><?php 
        _e( "If this setting is set, RAR file type can be uploaded.", 'ultimate-downloadable-products-for-woocommerce' );
        ?></p>
                                    <?php 
        
        if ( ultimate_downloadable_products_for_woocommerce_freemius_init()->is_not_paying() ) {
            ?>
                                        <p class="description">
                                            <?php 
            echo  sprintf( __( '%1$sUpgrade Now!%2$s to enable this feature.', 'ultimate-downloadable-products-for-woocommerce' ), '<a href="' . esc_attr( ultimate_downloadable_products_for_woocommerce_freemius_init()->get_upgrade_url() ) . '" target="_blank">', '</a>' ) ;
            ?></p>
                                    <?php 
        }
        
        ?>
                                    <?php 
        
        if ( !function_exists( 'rar_wrapper_cache_stats' ) ) {
            ?>
                                        <p class="description">
                                            <?php 
            echo  sprintf( __( '%1$sThe PHP RAR module%2$s is required to enable this feature.', 'ultimate-downloadable-products-for-woocommerce' ), '<a href="https://www.php.net/manual/en/book.rar.php" target="_blank">', '</a>' ) ;
            ?></p>
                                    <?php 
        }
        
        ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php 
        _e( "ISO format support", 'ultimate-downloadable-products-for-woocommerce' );
        ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input <?php 
        if ( !ultimate_downloadable_products_for_woocommerce_freemius_init()->is__premium_only() || !ultimate_downloadable_products_for_woocommerce_freemius_init()->can_use_premium_code() ) {
            echo  'disabled' ;
        }
        ?> class="checkbox" name="ULTIMATE_DOWNLOADABLE_PRODUCTS_iso_format_support" type="checkbox" <?php 
        echo  ( !isset( $options['iso_format_support'] ) || !empty($options['iso_format_support']) ? 'checked' : '' ) ;
        ?>>
                                    <p class="description"><?php 
        _e( "If this setting is set, *.iso file type can be uploaded.", 'ultimate-downloadable-products-for-woocommerce' );
        ?></p>
                                    <?php 
        
        if ( ultimate_downloadable_products_for_woocommerce_freemius_init()->is_not_paying() ) {
            ?>
                                        <p class="description">
                                            <?php 
            echo  sprintf( __( '%1$sUpgrade Now!%2$s to enable this feature.', 'ultimate-downloadable-products-for-woocommerce' ), '<a href="' . esc_attr( ultimate_downloadable_products_for_woocommerce_freemius_init()->get_upgrade_url() ) . '" target="_blank">', '</a>' ) ;
            ?></p>
                                    <?php 
        }
        
        ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                <?php 
    }
    
    ?>
                <?php 
    do_action( 'ultimate_downloadable_products_for_woocommerce_print_options', $options, $current_screen );
    ?>

            </table>

            <?php 
    
    if ( ultimate_downloadable_products_for_woocommerce_freemius_init()->is_not_paying() ) {
        ?>
                <h2><?php 
        _e( "Want more features?", 'ultimate-downloadable-products-for-woocommerce' );
        ?></h2>
                <p>
                    <?php 
        echo  sprintf( __( 'Install the %1$sPRO plugin version%2$s!', 'ultimate-downloadable-products-for-woocommerce' ), '<a target="_blank" href="' . esc_attr( ultimate_downloadable_products_for_woocommerce_freemius_init()->get_upgrade_url() ) . '">', '</a>' ) ;
        ?></p>

            <?php 
    }
    
    ?>

            <p class="submit">
                <input class="button-primary" type="submit" name="Submit" value="<?php 
    _e( 'Save Changes', 'ultimate-downloadable-products-for-woocommerce' );
    ?>" />
                <input id="ULTIMATE_DOWNLOADABLE_PRODUCTS_reset_options" type="submit" name="Reset" onclick="return confirm('<?php 
    _e( 'Are you sure you want to delete all Ultimate Downloadable Products options?', 'ultimate-downloadable-products-for-woocommerce' );
    ?>')" value="<?php 
    _e( 'Reset' );
    ?>" />
            </p>

        </form>

        <p class="alignleft">
            <?php 
    echo  sprintf( __( 'If you like <strong>Ultimate Downloadable Products for WooCommerce</strong> please leave us a %1$s rating. A huge thanks in advance!', 'ultimate-downloadable-products-for-woocommerce' ), '<a href="https://wordpress.org/support/plugin/ultimate-downloadable-products-for-woocommerce/reviews?rate=5#new-post" target="_blank">★★★★★</a>' ) ;
    ?></p>


    </div>

<?php 
}
