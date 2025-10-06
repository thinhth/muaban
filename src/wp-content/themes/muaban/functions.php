<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Include the theme framework.
require_once __DIR__ . '/vendor/hivepress/hivetheme/hivetheme.php';

// Remove slug for hp_listing post type
add_filter( 'hivepress/v1/post_types', function ( $post_types ) {
	if ( ! empty( $post_types['listing'] ) ) {
		$post_types['listing']['rewrite'] = [
			'slug'       => '',
			'with_front' => false,
		];
		// Avoid taking over the root archive
		$post_types['listing']['has_archive'] = false;
	}

	return $post_types;
}, 999 );

// Remove slug for hp_listing_category taxonomy
add_filter( 'hivepress/v1/taxonomies', function ( $taxonomies ) {
	if ( ! empty( $taxonomies['listing_category'] ) ) {
		$taxonomies['listing_category']['rewrite'] = [
			'slug'         => '',
			'with_front'   => false,
			'hierarchical' => true,
		];
	}

	return $taxonomies;
}, 999 );

// Normalize frontend links for hp_listing
add_filter( 'post_type_link', function ( $permalink, $post ) {
	if ( $post->post_type === 'hp_listing' ) {
		return home_url( '/' . $post->post_name . '/' );
	}

	return $permalink;
}, 10, 2 );

// Normalize frontend links for hp_listing_category
add_filter( 'term_link', function ( $termlink, $term, $taxonomy ) {
	if ( $taxonomy === 'hp_listing_category' ) {
		return home_url( '/' . $term->slug . '/' );
	}

	return $termlink;
}, 10, 3 );

// Route incoming pretty URLs to hp_listing or hp_listing_category without prefixes
add_filter( 'request', function ( $query_vars ) {
	if ( is_admin() ) {
		return $query_vars;
	}

	// Only handle single-segment paths like /nha-dat/ or /van-chuyen-do-noi-that/
	if ( empty( $query_vars['name'] ) || ! empty( $query_vars['pagename'] ) || ! empty( $query_vars['post_type'] ) || ! empty( $query_vars['taxonomy'] ) ) {
		return $query_vars;
	}

	$slug = $query_vars['name'];

	// If a WP page or post uses this slug, don't override
	if ( get_page_by_path( $slug, OBJECT, 'page' ) || get_page_by_path( $slug, OBJECT, 'post' ) ) {
		return $query_vars;
	}

	// Match HivePress listing category first
	$term = get_term_by( 'slug', $slug, 'hp_listing_category' );
	if ( $term && ! is_wp_error( $term ) ) {
		unset( $query_vars['name'] );
		$query_vars['taxonomy'] = 'hp_listing_category';
		$query_vars['term']     = $slug;
		return $query_vars;
	}

	// Match HivePress listing
	$post = get_page_by_path( $slug, OBJECT, 'hp_listing' );
	if ( $post ) {
		// Route as a single hp_listing by slug
		$query_vars['post_type'] = 'hp_listing';
		$query_vars['name']      = $slug;
		unset( $query_vars['pagename'] );
		return $query_vars;
	}

	return $query_vars;
} );

// Fallback: if 404 but slug matches a published hp_listing, force-load its single
add_action( 'template_redirect', function () {
	if ( is_admin() || ! is_404() ) {
		return;
	}

	$path = trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
	if ( $path === '' ) {
		return;
	}

	// Do not hijack if a WP Page/Post exists with same slug
	if ( get_page_by_path( $path, OBJECT, 'page' ) || get_page_by_path( $path, OBJECT, 'post' ) ) {
		return;
	}

	$posts = get_posts( [
		'post_type'      => 'hp_listing',
		'name'           => $path,
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'suppress_filters' => false,
	] );

	if ( $posts ) {
		global $wp_query;
		$wp_query = new WP_Query( [ 'post_type' => 'hp_listing', 'name' => $path ] );
		status_header( 200 );
		$template = get_single_template();
		if ( $template ) {
			include $template;
			exit;
		}
	}
} );

