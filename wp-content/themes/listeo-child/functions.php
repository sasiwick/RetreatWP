<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if ( !function_exists( 'chld_thm_cfg_locale_css' ) ):
    function chld_thm_cfg_locale_css( $uri ){
        if ( empty( $uri ) && is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) )
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter( 'locale_stylesheet_uri', 'chld_thm_cfg_locale_css' );

if ( !function_exists( 'chld_thm_cfg_parent_css' ) ):
    function chld_thm_cfg_parent_css() {
        wp_enqueue_style( 'chld_thm_cfg_parent', trailingslashit( get_template_directory_uri() ) . 'style.css', array( 'bootstrap','font-awesome-5','font-awesome-5-shims','simple-line-icons','listeo-woocommerce' ) );
    }
endif;
add_action( 'wp_enqueue_scripts', 'chld_thm_cfg_parent_css', 10 );

// END ENQUEUE PARENT ACTION


// [term_names taxonomy="category" first_only="yes" sep=", "]
function sb_term_names_shortcode( $atts ) {
    $a = shortcode_atts([
        'taxonomy'   => 'category', // e.g., 'category', 'product_cat', or your custom taxonomy
        'sep'        => ', ',
        'first_only' => 'yes',      // 'yes' or 'no'
    ], $atts);

    $post_id = get_the_ID();
    if ( ! $post_id ) return '';

    $terms = get_the_terms( $post_id, $a['taxonomy'] );
    if ( is_wp_error( $terms ) || empty( $terms ) ) return '';

    $names = array_map( fn($t) => $t->name, $terms );

    if ( strtolower($a['first_only']) === 'yes' ) {
        return esc_html( $names[0] );
    }
    return esc_html( implode( $a['sep'], $names ) );
}
add_shortcode( 'term_names', 'sb_term_names_shortcode' );

