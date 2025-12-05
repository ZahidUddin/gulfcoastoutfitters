<?php
/**
 * Plugin Name: MW Product Loop Grid (Elementor)
 * Description: Elementor widget to render WooCommerce products using an Elementor loop template in a grid.
 * Author: MW
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class MW_Product_Loop_Grid_Plugin {

    public function __construct() {
        // Register widget
        add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );

        // Make sure Elementor & WooCommerce exist
        add_action( 'plugins_loaded', [ $this, 'check_dependencies' ] );
    }

    public function check_dependencies() {
        if ( ! did_action( 'elementor/loaded' ) ) {
            add_action( 'admin_notices', [ $this, 'admin_notice_missing_elementor' ] );
        }

        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', [ $this, 'admin_notice_missing_wc' ] );
        }
    }

    public function admin_notice_missing_elementor() {
        ?>
        <div class="notice notice-warning">
            <p><?php esc_html_e( 'MW Product Loop Grid requires Elementor to be installed and activated.', 'mw-product-loop-grid' ); ?></p>
        </div>
        <?php
    }

    public function admin_notice_missing_wc() {
        ?>
        <div class="notice notice-warning">
            <p><?php esc_html_e( 'MW Product Loop Grid requires WooCommerce to be installed and activated.', 'mw-product-loop-grid' ); ?></p>
        </div>
        <?php
    }

    public function register_widgets( $widgets_manager ) {
        require_once __DIR__ . '/widgets/class-mw-product-loop-grid-widget.php';

        $widgets_manager->register( new \MW_Elementor_Product_Loop_Grid_Widget() );
    }
}

new MW_Product_Loop_Grid_Plugin();
