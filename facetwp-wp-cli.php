<?php
/*
 * Plugin Name: FacetWP index via WP-CLI
 * Version: 1.1
 * Description: Run indexing of FacetWP via WP-CLI
 * Author: Level Level
 * Author URI: http://www.level-level.com
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: facetwp-wp-cli
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Level Level
 * @since 1.0.0
 */
namespace WP_Facet;

if ( !defined ('WP_CLI') )
    return;

use WP_CLI;
use WP_CLI_Command;
use FacetWP_Indexer;
use WP_Query;

class CLI extends WP_CLI_Command {

    /**
     * Indexes all posts
     *
     * ## OPTIONS
     * [--post-type=<name>]
     * : post type, 'any' if not defined
     *
     * ## EXAMPLES
     *
     *     wp facet index
     *     wp facet index --post-type=product
     *
     * @synopsis
     */
    function index( $args, $assoc_args ) {

        error_reporting(0);

        if ( empty( $assoc_args['post-type'] ) )
		$post_type = 'any';
	else
		$post_type = $assoc_args['post-type'];

        $posts_per_page = 100;
        $page = 1;

	$args = array(
                'posts_per_page'    => $posts_per_page,
                'paged'             => $page,
                'post_type'         => $post_type,
                'post_status'       => 'publish',
                'fields'            => 'ids',
                'orderby'           => 'ID',
                'cache_results'     => false,
        );

	$total = 0;

        do {
		$args['paged'] = $page;
		$my_query = new WP_Query( $args );

		if ($my_query->have_posts())
		{
			if ($page == 1)
			{
				$total = $my_query->found_posts;
            			WP_CLI::line( 'Found '.$total.' posts of type "'.$post_type.'"' );
				$progress_bar = WP_CLI\Utils\make_progress_bar('Indexing', $total );
			}

			foreach ( $my_query->posts as $key => $post_id )
			{
            			//WP_CLI::line( $post_id );
	               		$progress_bar->tick();
				FWP()->indexer->index( $post_id );
			}

	                // Free up memory
        	        $this->stop_the_insanity();

	                $page++;
		}


        } while ( $my_query->have_posts() );

	if ($total > 0)
	{
		$progress_bar->finish();
		WP_CLI::success( 'All posts indexed' );
	} else {
		WP_CLI::error( 'No posts of type "'.$post_type.'" found!' );
	}
    }

    /*
	 *  Clear all of the caches for memory management
	 */
    protected function stop_the_insanity() {
        global $wpdb, $wp_object_cache;
        $wpdb->queries = array(); // or define( 'WP_IMPORTING', true );
        if ( !is_object( $wp_object_cache ) )
            return;
        $wp_object_cache->group_ops = array();
        $wp_object_cache->stats = array();
        $wp_object_cache->memcache_debug = array();
        $wp_object_cache->cache = array();
        if ( is_callable( $wp_object_cache, '__remoteset' ) )
            $wp_object_cache->__remoteset(); // important
    }

}

WP_CLI::add_command( 'facet', __NAMESPACE__ . '\\CLI' );
