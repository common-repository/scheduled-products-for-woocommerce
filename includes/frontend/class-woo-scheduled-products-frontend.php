<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woo_Scheduled_Products_Frontend {
  /**
   * Constructor
   */
  public function __construct() {
		add_filter( 'woocommerce_variation_is_visible', array( $this, 'hide_unpublished_variations' ), 50, 4 );

		add_action( 'woo_scheduled_products_publish', array( $this, 'run_publish' ), 10, 1 );
		add_action( 'woo_scheduled_products_unpublish', array( $this, 'run_unpublish' ), 10, 1 );
  }

	/**
	 * Publish scheduled products
	 */
	public function run_publish( $post_id ) {
		$post = get_post( $post_id );

		if ( $post ) {
			wp_update_post( array(
        'ID' => $post_id,
        'post_status' => 'publish'
      ) );
		}
	}

	/**
	 * Unpublish scheduled products
	 */
	public function run_unpublish( $post_id ) {
		$post = get_post( $post_id );

		if ( $post ) {
			wp_update_post( array(
        'ID' => $post_id,
        'post_status' => 'private'
      ) );
		}
	}

	/**
	 * Hide unpublished variations
	 */
	public function hide_unpublished_variations( $published, $id, $parent_id, $variation ) {
		$is_scheduled = get_post_meta( $variation->get_id(), '_woo_scheduled_products_is_scheduled', TRUE );

		if ( $is_scheduled ) {
			$from = get_post_meta( $variation->get_id(), '_woo_scheduled_products_publish_from', TRUE );
			$to = get_post_meta( $variation->get_id(), '_woo_scheduled_products_publish_to', TRUE );

			if ( $to && $to['timestamp'] && $to['timestamp'] < time() ) {
				return FALSE;
			}

			if ( $from && $from['timestamp'] && $from['timestamp'] > time() ) {
				return FALSE;
			}
		}

		return $published;
	}
}
