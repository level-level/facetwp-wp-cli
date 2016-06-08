<?php
/*
 * Plugin Name: FacetWP index via WP-CLI
 * Version: 1.0
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
     *
     * ## EXAMPLES
     *
     *     wp facet index
     *
     * @synopsis
     */
    function index( $args, $assoc_args ) {

        error_reporting(0);

        $posts_per_page = 100;
        $page = 1;

        do {
            $post_ids = get_posts( array(
                'posts_per_page' => $posts_per_page,
                'paged' => $page,
                'post_type'         => 'any',
                'post_status'       => 'publish',
                'fields'            => 'ids',
                'orderby'           => 'ID',
                'cache_results'     => false,
            ));

            // Do stuff

            $progress_bar = WP_CLI\Utils\make_progress_bar('Indexing', count( $post_ids ));

            $indexer = new FacetWP_Indexer;
            $indexer->is_overridden = true;

            foreach( $post_ids as $post_id ){
                $progress_bar->tick();
                $indexer->index( $post_id );
            }

            $progress_bar->finish();

            $page++;

            // Free up memory
            $this->stop_the_insanity();

        } while ( count( $post_ids ) );
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