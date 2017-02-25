<?php
/**
 *  Stucky Posts
 *
 *  @package Stucky_Posts
 *
 *  @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 *  @version     1.0.0
 *
 *  Plugin Name: Stucky Posts
 *  Plugin URI:
 *  Description: Allows any content type to be "stuck" on the home page. A twist on WP internal Sticky Posts.
 *  Version:     1.0.0
 *  Author:      Darrin Boutote
 *  Author URI:  http://darrinb.com
 *  Text Domain:
 *  Domain Path:
 *  License:     GPL-2.0+
 *  License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


/**
 *  Add the checkbox to the publish meta box
 *
 *  Only adds the input is the current user can publish posts.
 *  Called on `post_submitbox_misc_actions` hook.
 *
 *  @since 1.0.0
 *
 *  @param WP_Post_Object $post The current post
 */
function stucky_post_submitbox_misc_actions( $post ){

	$post_type_object = get_post_type_object( $post->post_type );
	$can_publish = current_user_can( $post_type_object->cap->publish_posts );

	if ( $can_publish ) : ?>
		<div class="misc-pub-section stucky misc-pub-stucky">
			<input id="stucky" name="stucky" type="checkbox" value="stucky" <?php checked( stucky_is_sticky( $post->ID ) ); ?> /> <label for="stucky" class="selectit"><?php _e( 'Feature this content on the front page' ); ?></label><br />
		</div>
	<?php endif;
}
add_action( 'post_submitbox_misc_actions', 'stucky_post_submitbox_misc_actions' );

/**
 *  Stick or unstick post
 *
 *  Called on `save_post` hook.
 *
 *  @since 1.0.0
 *
 *  @param int     $post_id Post ID.
 *  @param WP_Post $post    Post object.
 *  @param bool    $update  Whether this is an existing post being updated or not.
 */
function stucky_save_post( $post_id, $post, $update ){

	do_action( 'pre_stucky_save_post', $post_id, $post, $update );

	$ptype = get_post_type_object( $post->post_type );

	if ( current_user_can( $ptype->cap->edit_others_posts ) && current_user_can( $ptype->cap->publish_posts ) ) {
		if ( ! empty( $_POST['stucky'] ) ){
			stucky_stick_post( $post_id );
		} else {
			stucky_unstick_post( $post_id );
		}
	};

	return $post_id;

}
add_action( 'save_post', 'stucky_save_post', 0, 3 );


/**
 *  Stick a post.
 *
 *  Stucky posts should be displayed at the top of the front page.
 *
 *  @since 1.0.0
 *
 *  @param int $post_id Post ID.
 */
function stucky_stick_post( $post_id ) {

	do_action( 'pre_stucky_stick_post', $post_id );

	$stickies = get_option( 'stucky_posts' );

	if ( ! is_array( $stickies ) ) {
		$stickies = array( $post_id );
	}

	if ( ! in_array( $post_id, $stickies ) ) {
		$stickies[] = $post_id;
	}

	$updated = update_option( 'stucky_posts', $stickies );

	if ( $updated ) {
		/**
		 * Fires once a post has been added to the sticky list.
		 *
		 * @since 1.0.0
		 *
		 * @param int $post_id ID of the post that was stuck.
		 */
		do_action( 'stucky_post_stuck', $post_id );
	}
}


/**
 *  Un-stick a post.
 *
 *  Stucky posts should be displayed at the top of the front page.
 *
 *  @since 1.0.0
 *
 *  @param int $post_id Post ID.
 */
function stucky_unstick_post( $post_id ) {

	do_action( 'pre_stucky_unstick_post', $post_id );

	$stickies = get_option( 'stucky_posts' );

	if ( ! is_array( $stickies ) ) {
		return;
	}

	if ( ! in_array( $post_id, $stickies ) ) {
		return;
	}

	$offset = array_search( $post_id, $stickies );
	if ( false === $offset ){
		return;
	}

	array_splice( $stickies, $offset, 1 );

	$updated = update_option( 'stucky_posts', $stickies );

	if ( $updated ) {
		/**
		 * Fires once a post has been reomved to the sticky list.
		 *
		 * @since 1.0.0
		 *
		 * @param int $post_id ID of the post that was unstuck.
		 */
		do_action( 'stucky_post_unstuck', $post_id );
	}
}


/**
 *  Check if post is stucky.
 *
 *  If the post ID is not given, then The Loop ID for the current post will be used.
 *
 *  @since 1.0.0
 *
 *  @param int $post_id Optional. Post ID. Default is ID of the global $post.
 *
 *  @return bool Whether post is sticky.
 */
function stucky_is_sticky( $post_id = 0 ) {

	$post_id = absint( $post_id );

	if ( ! $post_id ){
		$post_id = get_the_ID();
	}

	$stickies = get_option( 'stucky_posts' );

	if ( ! is_array( $stickies ) ) {
		return false;
	}

	if ( in_array( $post_id, $stickies ) ) {
		return true;
	}


	return false;
}
