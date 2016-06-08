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
 * Text Domain: wp-cli-facetwp
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

        $args = array(
            'post_type'         => 'any',
            'post_status'       => 'publish',
            'posts_per_page'    => -1,
            'fields'            => 'ids',
            'orderby'           => 'ID',
            'cache_results'     => false,
        );

        $query = new WP_Query( $args );
        $post_ids = $query->posts;

        $indexer = new FacetWP_Indexer;
        $indexer->is_overridden = true;

        $progress_bar = WP_CLI\Utils\make_progress_bar('Indexing', count($post_ids));

        foreach($post_ids as $post_id){
            $progress_bar->tick();
            $indexer->index($post_id);
        }

        $progress_bar->finish();
    }

}


WP_CLI::add_command( 'facet', __NAMESPACE__ . '\\CLI' );