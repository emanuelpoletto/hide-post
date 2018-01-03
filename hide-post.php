<?php
/*
Plugin Name: Hide Post
Plugin URI: https://github.com/emanuelpoletto/hide-post/
Description: Hide a post everywhere except when accessed directly.
Version: 1.0.0
Author: Emanuel Poletto
Author URI: https://emanuelpoletto.com/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: hide-post
*/

/*
Copyright Emanuel Poletto.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Hide_Post' ) ) :

class Hide_Post {

    static $instance = null;
    
    /**
     * Hook into the appropriate actions when the class is constructed.
     */
    public function __construct() {
        add_action( 'init', array( $this, 'register_taxonomy' ) );
        add_action( 'wp_loaded', array( $this, 'create_term' ) );
        add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
        add_action( 'wp_head', array( $this, 'meta_robots' ), 0 );
        add_filter( 'get_previous_post_where', array( $this, 'get_adjacent_post_where' ), 10, 5 );
        add_filter( 'get_next_post_where', array( $this, 'get_adjacent_post_where' ), 10, 5 );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_post' ) );
    }

    public static function initialize() {
        if ( null == self::$instance ) {
            self::$instance = new self;
        }
    }

    public function register_taxonomy() {
        $args = array(
            'public'                => false,
            'hierarchical'          => true,
            'label'                 => __( 'Post Visibility', 'hide-post' ),
            'rewrite'               => false,
            'show_admin_column'     => true,
            'show_in_menu'          => false,
            'show_in_nav_menus'     => false,
            'show_in_quick_edit'    => true,
            'show_tagcloud'         => false,
            'meta_box_cb'           => false, // array( $this, 'render_meta_box_content' ),
            /*'capabilities'        => array(
                'manage_terms'  => '', //'manage_categories',
                'edit_terms'    => '', //'manage_categories',
                'delete_terms'  => '', //'manage_categories',
                'assign_terms'  => 'edit_posts',
            ),*/
        );

        register_taxonomy( 'hide-post-visibility', array( 'post' ), $args );
    }

    public function create_term() {
        $term = get_term_by( 'slug', 'hidden', 'hide-post-visibility' );
        if ( empty( $term ) || is_wp_error( $term ) ) {
            $term = __( 'Hidden', 'hide-post' );
            wp_insert_term( $term, 'hide-post-visibility', array( 'slug' => 'hidden' ) );
        }
    }

    public function pre_get_posts( $query ) {
        if ( ! is_admin() ) {
            if ( $query->is_main_query() && $query->is_single() ) {
                return;
            }

            $post_type = $query->get( 'post_type' );

            if ( ( is_array( $post_type ) && in_array( 'post', $post_type ) ) || 'post' == $post_type || null == $post_type ) {
                $term = get_term_by( 'slug', 'hidden', 'hide-post-visibility' );
                $new_query = array(
                    'taxonomy' => 'hide-post-visibility',
                    'field'    => 'term_id',
                    'terms'    => array( (int) $term->term_id ),
                    'operator' => 'NOT IN',
                );
                $tax_query = $query->get( 'tax_query' );

                if ( $tax_query && is_array( $tax_query ) ) {
                    $tax_query[] = $new_query;
                } else {
                    $tax_query = array( $new_query );
                }

                $query->set( 'tax_query', $tax_query );
            }
        }
    }

    public function get_adjacent_post_where( $where, $in_same_term = false, $excluded_terms = '', $previous = true, $taxonomy = 'category' ) {
        global $wpdb;
        $tr = $wpdb->term_relationships;

        $term = get_term_by( 'slug', 'hidden', 'hide-post-visibility' );

        $where .= $wpdb->prepare( " AND p.ID NOT IN (SELECT $tr.object_id FROM $tr WHERE $tr.term_taxonomy_id = %s)", $term->term_taxonomy_id );

        return $where;
    }

    public function meta_robots() {
        global $post;
        if ( is_singular( 'post' ) && has_term( 'hidden', 'hide-post-visibility', $post->ID ) ) {
            echo '<meta name="robots" content="noindex, nofollow">';
        }
    }

    /**
     * Adds the meta box container.
     */
    public function add_meta_box( $post_type ) {
        $post_types = array( 'post' );
        if ( in_array( $post_type, $post_types ) ) {
            add_meta_box(
                'hide-post-visibility',
                __( 'Post Visibility', 'hide-post' ),
                array( $this, 'render_meta_box_content' ),
                $post_type,
                'side', // context
                'default' // priority
            );
        }
    }

    /**
     * Save the meta when the post is saved.
     *
     * @param int $post_id The ID of the post being saved.
     */
    public function save_post( $post_id ) {
    
        /*
         * We need to verify this came from the our screen and with proper authorization,
         * because save_post can be triggered at other times.
         */

        // Check if our nonce is set.
        if ( ! isset( $_POST['hide_post_inner_custom_box_nonce'] ) )
            return $post_id;

        $nonce = $_POST['hide_post_inner_custom_box_nonce'];

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $nonce, '*1VLhU&qD!~2`v2W )(5c:wh6P|=p&=m]v,>C_f~=)ghcrD5+P|+d|IfG}t ,m!Z' ) )
            return $post_id;

        // If this is an autosave, our form has not been submitted,
        // so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
            return $post_id;

        // Check the user's permissions.
        if ( 'page' == $_POST['post_type'] ) {

            if ( ! current_user_can( 'edit_page', $post_id ) )
                return $post_id;
    
        } else {

            if ( ! current_user_can( 'edit_post', $post_id ) )
                return $post_id;
        }

        /* OK, its safe for us to save the data now. */

        // Sanitize the user input.
        $tax_id = sanitize_text_field( $_POST['hide_post_term'] );
        $tax_id = intval( $tax_id );

        if ( empty( $tax_id ) ) {
            $tax_id = null;
        }

        $term_taxonomy_ids = wp_set_object_terms( $post_id, $tax_id, 'hide-post-visibility' );

        if ( is_wp_error( $term_taxonomy_ids ) ) {
            // There was an error somewhere and the terms couldn't be set.
        } else {
            // Success! The post's categories were set.
        }
    }

    /**
     * Render Meta Box content.
     *
     * @param WP_Post $post The post object.
     */
    public function render_meta_box_content( $post ) {
    
        // Add an nonce field so we can check for it later.
        wp_nonce_field( '*1VLhU&qD!~2`v2W )(5c:wh6P|=p&=m]v,>C_f~=)ghcrD5+P|+d|IfG}t ,m!Z', 'hide_post_inner_custom_box_nonce' );
        $post_terms = get_the_terms( $post->ID, 'hide-post-visibility' );
        if ( $post_terms && ! is_wp_error( $post_terms ) ) {
            foreach ( $post_terms as $post_term ) {
                $checked[] = $post_term->term_id;
            }
        } else {
            $checked = array();
        }
        ?>
        <p>
            <input type="radio" id="hide_post_term_null" name="hide_post_term"
                value="0" <?php if ( empty( $checked ) ) echo 'checked="checked"'; ?>>
            <label for="hide_post_term_null"><?php _e( 'Visible', 'hide-post' ); ?></label>
        </p>
        <?php
        $terms = get_terms( 'hide-post-visibility', array( 'hide_empty' => false ) );
        if ( $terms && ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) : ?>
            <p>
                <input type="radio" id="hide_post_term_<?php echo $term->term_id; ?>" name="hide_post_term"
                    value="<?php echo $term->term_id; ?>" <?php if ( in_array( $term->term_id, $checked ) ) echo 'checked="checked"'; ?>>
                <label for="hide_post_term_<?php echo $term->term_id; ?>"><?php echo $term->name; ?></label>
            </p>
            <?php
            endforeach;
        }
    }
}

endif;

Hide_Post::initialize();