<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class MW_Elementor_Product_Loop_Grid_Widget extends Widget_Base {

    public function get_name() {
        return 'mw-product-loop-grid';
    }

    public function get_title() {
        return __( 'Product Loop Grid', 'mw-product-loop-grid' );
    }

    public function get_icon() {
        return 'eicon-products';
    }

    public function get_categories() {
        return [ 'general' ]; // adjust to your Elementor category
    }

    public function get_keywords() {
        return [ 'product', 'loop', 'grid', 'woocommerce' ];
    }

    /**
     * Fetch Elementor loop templates (Loop Builder items)
     */
    private function get_loop_templates_options() {
        $options = [ '' => __( '— Select Loop Template —', 'mw-product-loop-grid' ) ];

        $args = [
            'post_type'      => 'elementor_library',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'tax_query'      => [
                [
                    'taxonomy' => 'elementor_library_type',
                    'field'    => 'slug',
                    'terms'    => [ 'loop-item', 'product' ], // tweak if your setup uses a different slug
                ],
            ],
        ];

        $templates = get_posts( $args );

        if ( $templates ) {
            foreach ( $templates as $template ) {
                $options[ $template->ID ] = $template->post_title . ' (#' . $template->ID . ')';
            }
        }

        return $options;
    }

    /**
     * Get WooCommerce product categories as options for SELECT2
     */
    private function get_product_cat_options() {
        $options = [ '' => __( 'All Categories', 'mw-product-loop-grid' ) ];

        if ( ! taxonomy_exists( 'product_cat' ) ) {
            return $options;
        }

        $terms = get_terms(
            [
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
            ]
        );

        if ( is_array( $terms ) && ! empty( $terms ) ) {
            foreach ( $terms as $term ) {
                $options[ $term->slug ] = $term->name . ' (' . $term->slug . ')';
            }
        }

        return $options;
    }

    /**
     * Get WooCommerce product tags as options for SELECT2
     */
    private function get_product_tag_options() {
        $options = [ '' => __( 'All Tags', 'mw-product-loop-grid' ) ];

        if ( ! taxonomy_exists( 'product_tag' ) ) {
            return $options;
        }

        $terms = get_terms(
            [
                'taxonomy'   => 'product_tag',
                'hide_empty' => false,
            ]
        );

        if ( is_array( $terms ) && ! empty( $terms ) ) {
            foreach ( $terms as $term ) {
                $options[ $term->slug ] = $term->name . ' (' . $term->slug . ')';
            }
        }

        return $options;
    }

    protected function register_controls() {

        /**
         * CONTENT → Query
         */
        $this->start_controls_section(
            'section_query',
            [
                'label' => __( 'Query', 'mw-product-loop-grid' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'products_per_page',
            [
                'label'   => __( 'Products Per Page', 'mw-product-loop-grid' ),
                'type'    => Controls_Manager::NUMBER,
                'min'     => 1,
                'max'     => 100,
                'step'    => 1,
                'default' => 8,
            ]
        );

        // Data Source selector
        $this->add_control(
            'data_source',
            [
                'label'   => __( 'Data Source', 'mw-product-loop-grid' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'all',
                'options' => [
                    'all'                 => __( 'All Products', 'mw-product-loop-grid' ),
                    'featured'            => __( 'Featured', 'mw-product-loop-grid' ),
                    'sale'                => __( 'On Sale', 'mw-product-loop-grid' ),
                    'new'                 => __( 'New', 'mw-product-loop-grid' ),
                    'bestselling'         => __( 'Best Selling', 'mw-product-loop-grid' ),
                    'top_rated_products'  => __( 'Top Rated Products', 'mw-product-loop-grid' ),
                ],
            ]
        );

        // "New" products definition
        $this->add_control(
            'newness_days',
            [
                'label'       => __( 'New Products: Days Back', 'mw-product-loop-grid' ),
                'type'        => Controls_Manager::NUMBER,
                'min'         => 1,
                'max'         => 365,
                'step'        => 1,
                'default'     => 30,
                'condition'   => [
                    'data_source' => 'new',
                ],
                'description' => __( 'Products published within the last X days are considered "New".', 'mw-product-loop-grid' ),
            ]
        );

        $this->add_control(
            'orderby',
            [
                'label'   => __( 'Order By', 'mw-product-loop-grid' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'date',
                'options' => [
                    'date'       => __( 'Date', 'mw-product-loop-grid' ),
                    'title'      => __( 'Title', 'mw-product-loop-grid' ),
                    'menu_order' => __( 'Menu Order', 'mw-product-loop-grid' ),
                    'rand'       => __( 'Random', 'mw-product-loop-grid' ),
                ],
                'description' => __( 'For some data sources (Best Selling / Top Rated / New), this may be overridden.', 'mw-product-loop-grid' ),
            ]
        );

        $this->add_control(
            'order',
            [
                'label'   => __( 'Order', 'mw-product-loop-grid' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'DESC',
                'options' => [
                    'DESC' => __( 'Descending', 'mw-product-loop-grid' ),
                    'ASC'  => __( 'Ascending', 'mw-product-loop-grid' ),
                ],
            ]
        );

        // Category SELECT2 (searchable)
        $this->add_control(
            'product_cat',
            [
                'label'       => __( 'Product Category', 'mw-product-loop-grid' ),
                'type'        => Controls_Manager::SELECT2,
                'options'     => $this->get_product_cat_options(),
                'default'     => '',
                'multiple'    => false,
                'label_block' => true,
            ]
        );

        // Tag SELECT2 (searchable)
        $this->add_control(
            'product_tag',
            [
                'label'       => __( 'Product Tag', 'mw-product-loop-grid' ),
                'type'        => Controls_Manager::SELECT2,
                'options'     => $this->get_product_tag_options(),
                'default'     => '',
                'multiple'    => false,
                'label_block' => true,
            ]
        );

        $this->end_controls_section();

        /**
         * CONTENT → Loop Template
         */
        $this->start_controls_section(
            'section_loop_template',
            [
                'label' => __( 'Loop Template', 'mw-product-loop-grid' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'loop_template_id',
            [
                'label'   => __( 'Loop Template', 'mw-product-loop-grid' ),
                'type'    => Controls_Manager::SELECT2,
                'options' => $this->get_loop_templates_options(),
                'default' => '',
            ]
        );

        $this->add_control(
            'columns',
            [
                'label'   => __( 'Columns', 'mw-product-loop-grid' ),
                'type'    => Controls_Manager::NUMBER,
                'min'     => 1,
                'max'     => 6,
                'step'    => 1,
                'default' => 4,
            ]
        );

        $this->end_controls_section();

        /**
         * STYLE → Grid
         */
        $this->start_controls_section(
            'section_style_grid',
            [
                'label' => __( 'Grid', 'mw-product-loop-grid' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'grid_gap',
            [
                'label'      => __( 'Grid Gap (px)', 'mw-product-loop-grid' ),
                'type'       => Controls_Manager::NUMBER,
                'min'        => 0,
                'max'        => 100,
                'step'       => 1,
                'default'    => 20,
                'selectors'  => [
                    '{{WRAPPER}} .mw-product-loop-grid' => 'gap: {{VALUE}}px;',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $loop_template_id   = ! empty( $settings['loop_template_id'] ) ? (int) $settings['loop_template_id'] : 0;
        $products_per_page  = ! empty( $settings['products_per_page'] ) ? (int) $settings['products_per_page'] : 8;
        $columns            = ! empty( $settings['columns'] ) ? max( 1, (int) $settings['columns'] ) : 4;
        $orderby            = ! empty( $settings['orderby'] ) ? $settings['orderby'] : 'date';
        $order              = ! empty( $settings['order'] ) ? $settings['order'] : 'DESC';
        $product_cat        = ! empty( $settings['product_cat'] ) ? $settings['product_cat'] : '';
        $product_tag        = ! empty( $settings['product_tag'] ) ? $settings['product_tag'] : '';
        $data_source        = ! empty( $settings['data_source'] ) ? $settings['data_source'] : 'all';
        $newness_days       = ! empty( $settings['newness_days'] ) ? (int) $settings['newness_days'] : 30;

        if ( ! $loop_template_id ) {
            echo '<div class="elementor-alert elementor-alert-warning">'
                . esc_html__( 'Please select an Elementor Loop Template.', 'mw-product-loop-grid' )
                . '</div>';
            return;
        }

        // Base query args (optimized)
        $query_args = [
            'post_type'           => 'product',
            'post_status'         => 'publish',
            'posts_per_page'      => $products_per_page,
            'orderby'             => $orderby,
            'order'               => $order,
            'no_found_rows'       => true,  // performance: no pagination counts
            'ignore_sticky_posts' => true,  // performance: ignore sticky logic
            'cache_results'       => true,
        ];

        // Tax query: category, tag, stock, visibility-based filters
        $tax_query = [];

        // Woo product visibility term IDs
        $visibility_ids = function_exists( 'wc_get_product_visibility_term_ids' )
            ? wc_get_product_visibility_term_ids()
            : [];

        // Exclude out-of-stock products via taxonomy (faster than _stock_status meta)
        if ( ! empty( $visibility_ids['outofstock'] ) ) {
            $tax_query[] = [
                'taxonomy' => 'product_visibility',
                'field'    => 'term_taxonomy_id',
                'terms'    => [ $visibility_ids['outofstock'] ],
                'operator' => 'NOT IN',
            ];
        }

        // Category filter
        if ( $product_cat ) {
            $tax_query[] = [
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $product_cat,
            ];
        }

        // Tag filter
        if ( $product_tag ) {
            $tax_query[] = [
                'taxonomy' => 'product_tag',
                'field'    => 'slug',
                'terms'    => $product_tag,
            ];
        }

        // Data Source adjustments
        switch ( $data_source ) {
            case 'featured':
                // Featured products via product_visibility taxonomy
                if ( ! empty( $visibility_ids['featured'] ) ) {
                    $tax_query[] = [
                        'taxonomy' => 'product_visibility',
                        'field'    => 'term_taxonomy_id',
                        'terms'    => [ $visibility_ids['featured'] ],
                        'operator' => 'IN',
                    ];
                }
                break;

            case 'sale':
                // On-sale products via wc_get_product_ids_on_sale()
                if ( function_exists( 'wc_get_product_ids_on_sale' ) ) {
                    $on_sale_ids = wc_get_product_ids_on_sale();
                    $on_sale_ids = array_map( 'intval', (array) $on_sale_ids );

                    if ( ! empty( $on_sale_ids ) ) {
                        $query_args['post__in'] = $on_sale_ids;
                    } else {
                        echo '<div class="elementor-alert elementor-alert-info">'
                            . esc_html__( 'No on-sale products found.', 'mw-product-loop-grid' )
                            . '</div>';
                        return;
                    }
                }
                break;

            case 'new':
                // Products published within last X days
                $query_args['date_query'] = [
                    [
                        'after'     => sprintf( '%d days ago', $newness_days ),
                        'inclusive' => true,
                    ],
                ];
                // Force order by date DESC for "new"
                $query_args['orderby'] = 'date';
                $query_args['order']   = 'DESC';
                break;

            case 'bestselling':
                // Order by total_sales meta
                $query_args['meta_key'] = 'total_sales';
                $query_args['orderby']  = 'meta_value_num';
                $query_args['order']    = 'DESC';
                break;

            case 'top_rated_products':
                // Order by average rating
                $query_args['meta_key'] = '_wc_average_rating';
                $query_args['orderby']  = 'meta_value_num';
                $query_args['order']    = 'DESC';
                break;

            case 'all':
            default:
                // no extra changes
                break;
        }

        // Finalize tax_query if any
        if ( ! empty( $tax_query ) ) {
            if ( count( $tax_query ) > 1 ) {
                $tax_query['relation'] = 'AND';
            }
            $query_args['tax_query'] = $tax_query;
        }

        $products_query = new WP_Query( $query_args );

        if ( ! $products_query->have_posts() ) {
            echo '<div class="elementor-alert elementor-alert-info">'
                . esc_html__( 'No products found.', 'mw-product-loop-grid' )
                . '</div>';
            return;
        }

        // Grid wrapper
        $column_style = 'grid-template-columns: repeat(' . esc_attr( $columns ) . ', minmax(0, 1fr));';

        echo '<div class="mw-product-loop-grid" style="display:grid;' . esc_attr( $column_style ) . '">';

        while ( $products_query->have_posts() ) {
            $products_query->the_post();

            global $post, $product;

            $post    = get_post( get_the_ID() );
            $product = wc_get_product( $post );

            setup_postdata( $post );

            echo '<div class="mw-product-loop-grid__item">';
                // Render Elementor Loop Template with CSS
                echo \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $loop_template_id, true );
            echo '</div>';
        }

        wp_reset_postdata();

        echo '</div>';
    }

    public function get_style_depends() {
        return [];
    }

    public function get_script_depends() {
        return [];
    }
}
