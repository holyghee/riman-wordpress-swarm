<?php
/**
 * Twenty Twenty-Five functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_Five
 * @since Twenty Twenty-Five 1.0
 */

// Adds theme support for post formats.
if ( ! function_exists( 'twentytwentyfive_post_format_setup' ) ) :
	/**
	 * Adds theme support for post formats.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_post_format_setup() {
		add_theme_support( 'post-formats', array( 'aside', 'audio', 'chat', 'gallery', 'image', 'link', 'quote', 'status', 'video' ) );
	}
endif;
add_action( 'after_setup_theme', 'twentytwentyfive_post_format_setup' );

// Enqueues editor-style.css in the editors.
if ( ! function_exists( 'twentytwentyfive_editor_style' ) ) :
	/**
	 * Enqueues editor-style.css in the editors.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_editor_style() {
		add_editor_style( get_parent_theme_file_uri( 'assets/css/editor-style.css' ) );
	}
endif;
add_action( 'after_setup_theme', 'twentytwentyfive_editor_style' );

// Enqueues style.css on the front.
if ( ! function_exists( 'twentytwentyfive_enqueue_styles' ) ) :
	/**
	 * Enqueues style.css on the front.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_enqueue_styles() {
		wp_enqueue_style(
			'twentytwentyfive-style',
			get_parent_theme_file_uri( 'style.css' ),
			array(),
			wp_get_theme()->get( 'Version' )
		);
	}
endif;
add_action( 'wp_enqueue_scripts', 'twentytwentyfive_enqueue_styles' );

// Registers custom block styles.
if ( ! function_exists( 'twentytwentyfive_block_styles' ) ) :
	/**
	 * Registers custom block styles.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_block_styles() {
		register_block_style(
			'core/list',
			array(
				'name'         => 'checkmark-list',
				'label'        => __( 'Checkmark', 'twentytwentyfive' ),
				'inline_style' => '
				ul.is-style-checkmark-list {
					list-style-type: "\2713";
				}

				ul.is-style-checkmark-list li {
					padding-inline-start: 1ch;
				}',
			)
		);
	}
endif;
add_action( 'init', 'twentytwentyfive_block_styles' );

// Registers pattern categories.
if ( ! function_exists( 'twentytwentyfive_pattern_categories' ) ) :
	/**
	 * Registers pattern categories.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_pattern_categories() {

		register_block_pattern_category(
			'twentytwentyfive_page',
			array(
				'label'       => __( 'Pages', 'twentytwentyfive' ),
				'description' => __( 'A collection of full page layouts.', 'twentytwentyfive' ),
			)
		);

		register_block_pattern_category(
			'twentytwentyfive_post-format',
			array(
				'label'       => __( 'Post formats', 'twentytwentyfive' ),
				'description' => __( 'A collection of post format patterns.', 'twentytwentyfive' ),
			)
		);
	}
endif;
add_action( 'init', 'twentytwentyfive_pattern_categories' );

// Registers block binding sources.
if ( ! function_exists( 'twentytwentyfive_register_block_bindings' ) ) :
	/**
	 * Registers the post format block binding source.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_register_block_bindings() {
		register_block_bindings_source(
			'twentytwentyfive/format',
			array(
				'label'              => _x( 'Post format name', 'Label for the block binding placeholder in the editor', 'twentytwentyfive' ),
				'get_value_callback' => 'twentytwentyfive_format_binding',
			)
		);
	}
endif;
add_action( 'init', 'twentytwentyfive_register_block_bindings' );

// Registers block binding callback function for the post format name.
if ( ! function_exists( 'twentytwentyfive_format_binding' ) ) :
	/**
	 * Callback function for the post format name block binding source.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return string|void Post format name, or nothing if the format is 'standard'.
	 */
	function twentytwentyfive_format_binding() {
		$post_format_slug = get_post_format();

		if ( $post_format_slug && 'standard' !== $post_format_slug ) {
			return get_post_format_string( $post_format_slug );
		}
	}
endif;
// Enable Application Passwords on local development
add_filter( "wp_is_application_passwords_available", "__return_true" );

/**
 * Ensure Playfair Display loads everywhere so typography stays consistent.
 */
function riman_enqueue_playfair_display() {
    wp_enqueue_style(
        'riman-playfair-display',
        'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap',
        [],
        null
    );
}
add_action( 'wp_enqueue_scripts', 'riman_enqueue_playfair_display', 5 );
add_action( 'admin_enqueue_scripts', 'riman_enqueue_playfair_display', 5 );

/**
 * Add custom CSS for mobile hero video handling
 */
function riman_add_mobile_hero_video_css() {
    echo '<style>
/* Mobile Hero Video - Normal object-fit: cover for compressed originals */
@media (max-width: 780px) {
    /* Ensure hero videos maintain proper cover behavior on mobile */
    .riman-page-hero .riman-hero__video,
    .wp-block-riman-page-hero .riman-hero__video,
    .riman-hero-page-video,
    .riman-cover-video {
        object-fit: cover !important;
        object-position: center center !important;
        width: 100% !important;
        height: 100% !important;
    }

    /* Ensure media container is properly sized */
    .riman-page-hero .riman-hero__media,
    .wp-block-riman-page-hero .riman-hero__media {
        overflow: hidden;
    }
}
</style>';
}
add_action('wp_head', 'riman_add_mobile_hero_video_css', 10);

/**
 * Replace leftover local font URLs from development builds in the final HTML output.
 * Prevents browsers from attempting to load fonts from http://127.0.0.1:8801 (which triggers CORS warnings).
 */
function riman_start_output_buffer() {
    if ( is_admin() ) {
        return;
    }

    $host = $_SERVER['HTTP_HOST'] ?? '';
    $is_local = strpos($host, '127.0.0.1') !== false || strpos($host, 'localhost') !== false;
    if ( $is_local ) {
        return;
    }

    ob_start( function ( $html ) {
        return str_replace(
            'http://127.0.0.1:8801',
            'https://riman-wordpress-swarm.ecomwy.com',
            $html
        );
    } );
}
add_action( 'template_redirect', 'riman_start_output_buffer', 0 );


// Fix Meta Box text direction (RTL problem)
add_action('admin_head', function() {
    echo '<style>
    /* Meta Box Text Direction Fix */
    .rwmb-input,
    .rwmb-text,
    .rwmb-textarea,
    .meta-box-input,
    input[type="text"].meta-box-field,
    textarea.meta-box-field,
    .cmb2-text,
    .cmb2-textarea {
        direction: ltr !important;
        text-align: left !important;
        unicode-bidi: embed !important;
    }

    /* Service Cards Meta Box spezifisch */
    .service-cards-meta .rwmb-input,
    .service-cards-meta input[type="text"],
    .service-cards-meta textarea {
        direction: ltr !important;
        text-align: left !important;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    }

    /* Allgemeine Meta Box Container */
    .postbox .inside input[type="text"],
    .postbox .inside textarea {
        direction: ltr !important;
        text-align: left !important;
    }
    </style>';
});
