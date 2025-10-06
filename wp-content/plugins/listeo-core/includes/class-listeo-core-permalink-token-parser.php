<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Token Parser for Listeo Custom Permalinks
 * 
 * Handles parsing of permalink tokens and replacing them with actual values
 *
 * @package listeo-core
 * @since 1.9.51
 */
class Listeo_Core_Permalink_Token_Parser {
	/**
	 * Supported tokens and their handlers
	 *
	 * @var array
	 */
	private $token_handlers = array(
		'%listing%'          => 'get_listing_slug',
		'%listing_category%' => 'get_category_slug',
		'%region%'           => 'get_region_slug',
		'%listing_id%'       => 'get_listing_id',
		'%listing_type%'     => 'get_listing_type',
		'%year%'             => 'get_post_year',
		'%monthnum%'         => 'get_post_month',
		'%author%'           => 'get_author_slug',
	);

	/**
	 * Parse structure and replace tokens with actual values
	 *
	 * @param int    $post_id   The post ID
	 * @param string $structure The permalink structure with tokens
	 * @return string Parsed structure with tokens replaced
	 */
	public function parse_structure( $post_id, $structure ) {
		if ( empty( $post_id ) || empty( $structure ) ) {
			return '';
		}

		// Get all tokens in the structure
		$tokens = $this->extract_tokens( $structure );
		$replacements = array();

		// Process each token
		foreach ( $tokens as $token ) {
			$value = $this->get_token_value( $post_id, $token );
			$processed_value = $this->handle_empty_value( $token, $value );
			
			// Only abort if a REQUIRED token is truly empty (not a dash placeholder)
			if ( $processed_value === '' && $token === '%listing%' ) {
				return '';
			}
			
			$replacements[ $token ] = $processed_value;
		}

		// Replace tokens with values
		$parsed = str_replace( array_keys( $replacements ), array_values( $replacements ), $structure );

		// Clean up multiple consecutive slashes
		$parsed = preg_replace( '#/+#', '/', $parsed );

		// Remove leading/trailing slashes
		$parsed = trim( $parsed, '/' );

		// Additional validation - check for any remaining tokens or malformed parts
		if ( strpos( $parsed, '%' ) !== false || strpos( $parsed, '--' ) !== false || empty( $parsed ) ) {
			return '';
		}

		return $parsed;
	}

	/**
	 * Extract tokens from structure
	 *
	 * @param string $structure The permalink structure
	 * @return array Array of tokens found
	 */
	private function extract_tokens( $structure ) {
		preg_match_all( '/%([^%]+)%/', $structure, $matches );
		return isset( $matches[0] ) ? array_unique( $matches[0] ) : array();
	}

	/**
	 * Get value for a specific token
	 *
	 * @param int    $post_id The post ID
	 * @param string $token   The token to process
	 * @return string Token value
	 */
	private function get_token_value( $post_id, $token ) {
		if ( ! isset( $this->token_handlers[ $token ] ) ) {
			return '';
		}

		$handler = $this->token_handlers[ $token ];
		
		if ( method_exists( $this, $handler ) ) {
			return $this->$handler( $post_id );
		}

		return '';
	}

	/**
	 * Handle empty values with fallbacks
	 *
	 * @param string $token The token that was empty
	 * @param string $value The value (empty or not)
	 * @return string Processed value with fallback if needed
	 */
	private function handle_empty_value( $token, $value ) {
		if ( ! empty( $value ) ) {
			return $value;
		}

		// %listing% token should never be empty - this is required
		if ( $token === '%listing%' ) {
			// Fallback to post ID if slug is somehow empty
			$post_id = get_the_ID();
			return ! empty( $post_id ) ? (string) $post_id : 'listing';
		}

		// For taxonomy and other optional tokens, use a dash as placeholder
		// This allows URLs to work even when some taxonomy terms are missing
		return '-';
	}

	/**
	 * Get listing slug (post name)
	 *
	 * @param int $post_id Post ID
	 * @return string Post slug
	 */
	private function get_listing_slug( $post_id ) {
		$post = get_post( $post_id );
		return ! empty( $post->post_name ) ? $post->post_name : '';
	}

	/**
	 * Get listing category slug
	 *
	 * @param int $post_id Post ID
	 * @return string Category slug or empty string
	 */
	private function get_category_slug( $post_id ) {
		$terms = get_the_terms( $post_id, 'listing_category' );
		
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '';
		}

		// Get the first (primary) category
		$term = reset( $terms );
		return $term->slug;
	}

	/**
	 * Get region slug
	 *
	 * @param int $post_id Post ID
	 * @return string Region slug or empty string
	 */
	private function get_region_slug( $post_id ) {
		$terms = get_the_terms( $post_id, 'region' );
		
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '';
		}

		// Get the first (primary) region
		$term = reset( $terms );
		return $term->slug;
	}

	/**
	 * Get listing ID
	 *
	 * @param int $post_id Post ID
	 * @return string Post ID as string
	 */
	private function get_listing_id( $post_id ) {
		return (string) $post_id;
	}

	/**
	 * Get listing type from meta with translation support
	 *
	 * @param int $post_id Post ID
	 * @return string Listing type slug (translated) or empty string
	 */
	private function get_listing_type( $post_id ) {
		$listing_type = get_post_meta( $post_id, '_listing_type', true );
		
		if ( empty( $listing_type ) ) {
			return '';
		}
		
		// Get translated listing type for URL
		$translated_type = $this->get_translated_listing_type( $listing_type );
		
		return sanitize_title( $translated_type );
	}

	/**
	 * Get translated listing type using WordPress translation functions
	 *
	 * @param string $listing_type The original listing type
	 * @return string Translated listing type
	 */
	private function get_translated_listing_type( $listing_type ) {
		// Debug: Check current language
		$current_locale = get_locale();
	
		// Define translatable strings with context for URL slugs
		$translatable_types = array(
			'service'     => _x( 'service', 'URL slug for listing type', 'listeo_core' ),
			'rental'      => _x( 'rental', 'URL slug for listing type', 'listeo_core' ),
			'event'       => _x( 'event', 'URL slug for listing type', 'listeo_core' ),
			'classifieds' => _x( 'classifieds', 'URL slug for listing type', 'listeo_core' ),
		);
		
		// Debug: Log the translated values
		
		// Return translated version if available
		if ( isset( $translatable_types[ $listing_type ] ) ) {
			$translated = $translatable_types[ $listing_type ];
			
			return $translated;
		}
		
		// Fallback: try to translate the original value directly
		$translated = _x( $listing_type, 'URL slug for listing type', 'listeo_core' );
	
		// If translation is the same as original, return original
		// Otherwise return the translated version
		return $translated !== $listing_type ? $translated : $listing_type;
	}

	/**
	 * Get post year
	 *
	 * @param int $post_id Post ID
	 * @return string 4-digit year
	 */
	private function get_post_year( $post_id ) {
		return get_the_time( 'Y', $post_id );
	}

	/**
	 * Get post month number
	 *
	 * @param int $post_id Post ID
	 * @return string Month number (01-12)
	 */
	private function get_post_month( $post_id ) {
		return get_the_time( 'm', $post_id );
	}

	/**
	 * Get author slug
	 *
	 * @param int $post_id Post ID
	 * @return string Author nicename/slug
	 */
	private function get_author_slug( $post_id ) {
		$post = get_post( $post_id );
		
		if ( empty( $post->post_author ) ) {
			return '';
		}

		$author_nicename = get_the_author_meta( 'user_nicename', $post->post_author );
		return ! empty( $author_nicename ) ? $author_nicename : '';
	}

	/**
	 * Generate example URL for preview
	 *
	 * @param string $structure The permalink structure
	 * @return string Example URL
	 */
	public function generate_example( $structure ) {
		// Sample data for preview
		$sample_data = array(
			'%listing%'          => 'amazing-restaurant',
			'%listing_category%' => 'restaurants',
			'%region%'           => 'new-york',
			'%listing_id%'       => '123',
			'%listing_type%'     => $this->get_translated_listing_type('service'),
			'%year%'             => date( 'Y' ),
			'%monthnum%'         => date( 'm' ),
			'%author%'           => 'john-smith',
		);

		// Replace tokens with sample data
		$example = $structure;
		foreach ( $sample_data as $token => $value ) {
			$example = str_replace( $token, $value, $example );
		}

		// Handle any remaining tokens or empty values
		$example = str_replace( array( '%', '//' ), array( '', '/' ), $example );
		$example = trim( $example, '/' );

		// Check if Safe Mode is enabled and prepend listing base to examples
		if (class_exists('Listeo_Core_Custom_Permalink_Manager')) {
			$manager = Listeo_Core_Custom_Permalink_Manager::instance();
			if ($manager->is_safe_mode_enabled()) {
				// Get the dynamic listing base (don't hardcode)
				$permalink_structure = Listeo_Core_Post_Types::get_permalink_structure();
				$listing_base = !empty($permalink_structure['listing_rewrite_slug']) ? $permalink_structure['listing_rewrite_slug'] : 'listing';
				
				// Ensure example starts with the listing base for Safe Mode
				if (strpos($example, $listing_base . '/') !== 0) {
					$example = $listing_base . '/' . $example;
				}
			}
		}

		return home_url( $example );
	}

	/**
	 * Get all available tokens with descriptions
	 *
	 * @return array Token information
	 */
	public function get_available_tokens() {
		return array(
			'%listing%' => array(
				'label'       => __( 'Listing Slug', 'listeo_core' ),
				'description' => __( 'The listing\'s URL slug (required)', 'listeo_core' ),
				'example'     => 'amazing-restaurant',
				'required'    => true,
			),
			'%listing_category%' => array(
				'label'       => __( 'Category', 'listeo_core' ),
				'description' => __( 'The listing\'s primary category slug', 'listeo_core' ),
				'example'     => 'restaurants',
				'required'    => false,
			),
			'%region%' => array(
				'label'       => __( 'Region', 'listeo_core' ),
				'description' => __( 'The listing\'s primary region slug', 'listeo_core' ),
				'example'     => 'new-york',
				'required'    => false,
			),
			'%listing_id%' => array(
				'label'       => __( 'Listing ID', 'listeo_core' ),
				'description' => __( 'The listing\'s unique post ID', 'listeo_core' ),
				'example'     => '123',
				'required'    => false,
			),
			'%listing_type%' => array(
				'label'       => __( 'Listing Type', 'listeo_core' ),
				'description' => __( 'The listing type (service, rental, etc.)', 'listeo_core' ),
				'example'     => 'service',
				'required'    => false,
			),
			'%year%' => array(
				'label'       => __( 'Year', 'listeo_core' ),
				'description' => __( 'The listing\'s publication year', 'listeo_core' ),
				'example'     => date( 'Y' ),
				'required'    => false,
			),
			'%monthnum%' => array(
				'label'       => __( 'Month', 'listeo_core' ),
				'description' => __( 'The listing\'s publication month', 'listeo_core' ),
				'example'     => date( 'm' ),
				'required'    => false,
			),
			'%author%' => array(
				'label'       => __( 'Author', 'listeo_core' ),
				'description' => __( 'The listing author\'s username slug', 'listeo_core' ),
				'example'     => 'john-smith',
				'required'    => false,
			),
		);
	}

	/**
	 * Check if structure contains required tokens
	 *
	 * @param string $structure The permalink structure
	 * @return bool True if valid, false if missing required tokens
	 */
	public function has_required_tokens( $structure ) {
		// %listing% is the only required token
		return strpos( $structure, '%listing%' ) !== false;
	}

	/**
	 * Get tokens used in a structure
	 *
	 * @param string $structure The permalink structure
	 * @return array Array of tokens used
	 */
	public function get_structure_tokens( $structure ) {
		$tokens = $this->extract_tokens( $structure );
		$available_tokens = array_keys( $this->token_handlers );
		
		// Filter to only valid tokens
		return array_intersect( $tokens, $available_tokens );
	}
}