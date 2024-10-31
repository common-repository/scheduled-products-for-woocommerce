<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woo_Scheduled_Products_Admin {
  /**
   * Constructor
   */
  public function __construct() {
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_schedule_product_tab' ), 50, 1 );
		add_action( 'woocommerce_product_data_panels', array( $this, 'add_schedule_data_panel' ), 50, 1 );
		add_action( 'woocommerce_process_product_meta', array( $this, 'process_product_meta' ), 50, 1 );

		add_action( 'woocommerce_variation_options', array( $this, 'add_schedule_option' ), 50, 3 );
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'add_schedule_fields' ), 50, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation_fields' ), 50, 2 );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
  }

	/**
	 * Enqueue admin scripts
	 */
	public function admin_enqueue_scripts( $hook ) {
		if ( $hook == 'post.php' ) {
			global $post;

			if ( $post && $post->post_type == 'product' ) {
				wp_enqueue_style( 'woo-scheduled-products-admin', plugin_dir_url( __FILE__ ) . '../../admin/css/woo-scheduled-products-admin.css' );

				wp_enqueue_script( 'jquery-ui-datepicker' );

				wp_enqueue_script( 'woo-scheduled-products-admin', plugin_dir_url( __FILE__ ) . '../../admin/js/woo-scheduled-products-admin.js', array( 'jquery' ) );
			}
		}
	}

	/**
	 * Save variation fields
	 */
	public function save_variation_fields( $variation_id, $i ) {
		if ( isset( $_POST['variable_is_scheduled'][$i] ) && $_POST['variable_is_scheduled'][$i] ) {
			$from = array();
			$to = array();

			if ( isset( $_POST['_woo_scheduled_products_publish_from'][$i] ) ) {
				$data = $_POST['_woo_scheduled_products_publish_from'][$i];
				$from = array(
					'date' => isset( $data['date'] ) ? $data['date'] : '',
					'hours' => isset( $data['hours'] ) ? $data['hours'] : 0,
					'mins' => isset( $data['mins'] ) ? $data['mins'] : 0,
					'timestamp' => FALSE,
				);
			}

			if ( isset( $_POST['_woo_scheduled_products_publish_to'][$i] ) ) {
				$data = $_POST['_woo_scheduled_products_publish_to'][$i];
				$to = array(
					'date' => isset( $data['date'] ) ? $data['date'] : '',
					'hours' => isset( $data['hours'] ) ? $data['hours'] : 0,
					'mins' => isset( $data['mins'] ) ? $data['mins'] : 0,
					'timestamp' => FALSE,
				);
			}

			// Parse dates into timestamps
			if ( ! empty( $from ) && ! empty( $from['date'] ) ) {
				$from['timestamp'] = $this->wp_strtotime( sprintf( '%s %s:%s', $from['date'], $from['hours'], $from['mins'] ) );
			}

			if ( ! empty( $to ) && ! empty( $to['date'] ) ) {
				$to['timestamp'] = $this->wp_strtotime( sprintf( '%s %s:%s', $to['date'], $to['hours'], $to['mins'] ) );
			}

			update_post_meta( $variation_id, '_woo_scheduled_products_is_scheduled', TRUE );
			update_post_meta( $variation_id, '_woo_scheduled_products_publish_from', $from );
			update_post_meta( $variation_id, '_woo_scheduled_products_publish_to', $to );
		} else {
			update_post_meta( $variation_id, '_woo_scheduled_products_is_scheduled', FALSE );
			update_post_meta( $variation_id, '_woo_scheduled_products_publish_from', array() );
			update_post_meta( $variation_id, '_woo_scheduled_products_publish_to', array() );
		}
	}

	/**
	 * Add schedule option for variations
	 */
	public function add_schedule_option( $loop, $variation_data, $variation ) {
		$is_scheduled = get_post_meta( $variation->ID, '_woo_scheduled_products_is_scheduled', TRUE );

	?>
		<label>
			<?php esc_html_e( 'Schedule', 'woo-scheduled-products' ); ?>
			<input type="checkbox" class="checkbox variable_is_scheduled" value="1" name="variable_is_scheduled[<?php echo esc_attr( $loop ); ?>]" <?php checked( $is_scheduled, true ); ?> />
		</label>
	<?php
	}

	/**
	 * Add schedule fields for variations
	 */
	public function add_schedule_fields( $loop, $variation_data, $variation ) {
		$is_scheduled = get_post_meta( $variation->ID, '_woo_scheduled_products_is_scheduled', TRUE );
		$from = (array) get_post_meta( $variation->ID, '_woo_scheduled_products_publish_from', TRUE );
		$to = (array) get_post_meta( $variation->ID, '_woo_scheduled_products_publish_to', TRUE );

		$defaults = array(
			'date' => '',
			'hours' => 0,
			'mins' => 0,
		);

		$from = wp_parse_args( $from, $defaults );
		$to = wp_parse_args( $to, $defaults );

	?>
		<div class="publish-fields" style="display:none;">
			<div class="form-field form-row form-row-first">
				<div>
					<label for=""><?php _e( 'Publish From', 'woo-scheduled-products' ); ?></label>
				</div>

				<input type="text" name="_woo_scheduled_products_publish_from[<?php echo $loop; ?>][date]" class="woo-scheduled-products-datepicker" value="<?php echo $from['date']; ?>" />

				<div class="timepicker-container">
					<select name="_woo_scheduled_products_publish_from[<?php echo $loop; ?>][hours]">
						<?php for ( $i = 0; $i < 24; $i++ ) { ?>
							<option value="<?php echo $i; ?>" <?php selected( $from['hours'], $i ); ?>><?php printf( '%02d', $i ); ?></option>
						<?php } ?>
					</select>
					<span class="divider">:</span>
					<select name="_woo_scheduled_products_publish_from[<?php echo $loop; ?>][mins]">
						<?php for ( $i = 0; $i < 60; $i += 10 ) { ?>
							<option value="<?php echo $i; ?>" <?php selected( $from['mins'], $i ); ?>><?php printf( '%02d', $i ); ?></option>
						<?php } ?>
					</select>
				</div>
			</div>

			<div class="form-field form-row form-row-last">
				<div>
					<label for=""><?php _e( 'Publish To', 'woo-scheduled-products' ); ?></label>
				</div>

				<input type="text" name="_woo_scheduled_products_publish_to[<?php echo $loop; ?>][date]" class="woo-scheduled-products-datepicker" value="<?php echo $to['date']; ?>" />

				<div class="timepicker-container">
					<select name="_woo_scheduled_products_publish_to[<?php echo $loop; ?>][hours]">
						<?php for ( $i = 0; $i < 24; $i++ ) { ?>
							<option value="<?php echo $i; ?>" <?php selected( $to['hours'], $i ); ?>><?php printf( '%02d', $i ); ?></option>
						<?php } ?>
					</select>
					<span class="divider">:</span>
					<select name="_woo_scheduled_products_publish_to[<?php echo $loop; ?>][mins]">
						<?php for ( $i = 0; $i < 60; $i += 10 ) { ?>
							<option value="<?php echo $i; ?>" <?php selected( $to['mins'], $i ); ?>><?php printf( '%02d', $i ); ?></option>
						<?php } ?>
					</select>
				</div>
			</div>
		</div>
	<?php
	}

	/**
	 * Add schedule product tab
	 */
	public function add_schedule_product_tab( $tabs ) {
	  $tabs['schedule-tab'] = array(
	    'label' => __( 'Schedule', 'woo-scheduled-products' ),
	    'target' => 'schedule_product_data',
	  );

	  return $tabs;
	}

	public function add_schedule_data_panel() {
	  global $woocommerce, $post;

		$from = (array) get_post_meta( $post->ID, '_woo_scheduled_products_publish_from', TRUE );
		$to = (array) get_post_meta( $post->ID, '_woo_scheduled_products_publish_to', TRUE );

		$defaults = array(
			'date' => '',
			'hours' => 0,
			'mins' => 0,
		);

		$from = wp_parse_args( $from, $defaults );
		$to = wp_parse_args( $to, $defaults );

	  ?>
	  <div id="schedule_product_data" class="panel woocommerce_options_panel">
			<div class="form-field">
				<label for=""><?php _e( 'Publish From', 'woo-scheduled-products' ); ?></label>
				<input type="text" name="_woo_scheduled_products_publish_from[date]" class="woo-scheduled-products-datepicker" value="<?php echo $from['date']; ?>" />

				<div class="timepicker-container">
					<select name="_woo_scheduled_products_publish_from[hours]">
						<?php for ( $i = 0; $i < 24; $i++ ) { ?>
							<option value="<?php echo $i; ?>" <?php selected( $from['hours'], $i ); ?>><?php printf( '%02d', $i ); ?></option>
						<?php } ?>
					</select>
					<span class="divider">:</span>
					<select name="_woo_scheduled_products_publish_from[mins]">
						<?php for ( $i = 0; $i < 60; $i += 10 ) { ?>
							<option value="<?php echo $i; ?>" <?php selected( $from['mins'], $i ); ?>><?php printf( '%02d', $i ); ?></option>
						<?php } ?>
					</select>
				</div>
			</div>

			<div class="form-field">
				<label for=""><?php _e( 'Publish To', 'woo-scheduled-products' ); ?></label>
				<input type="text" name="_woo_scheduled_products_publish_to[date]" class="woo-scheduled-products-datepicker" value="<?php echo $to['date']; ?>" />

				<div class="timepicker-container">
					<select name="_woo_scheduled_products_publish_to[hours]">
						<?php for ( $i = 0; $i < 24; $i++ ) { ?>
							<option value="<?php echo $i; ?>" <?php selected( $to['hours'], $i ); ?>><?php printf( '%02d', $i ); ?></option>
						<?php } ?>
					</select>
					<span class="divider">:</span>
					<select name="_woo_scheduled_products_publish_to[mins]">
						<?php for ( $i = 0; $i < 60; $i += 10 ) { ?>
							<option value="<?php echo $i; ?>" <?php selected( $to['mins'], $i ); ?>><?php printf( '%02d', $i ); ?></option>
						<?php } ?>
					</select>
				</div>
			</div>
	  </div>
	  <?php
	}

	public function process_product_meta( $post_id ) {
		$from = array();
		$to = array();

		if ( isset( $_POST['_woo_scheduled_products_publish_from'] ) ) {
			$data = $_POST['_woo_scheduled_products_publish_from'];
			$from = array(
				'date' => isset( $data['date'] ) ? $data['date'] : '',
				'hours' => isset( $data['hours'] ) ? $data['hours'] : 0,
				'mins' => isset( $data['mins'] ) ? $data['mins'] : 0,
				'timestamp' => FALSE,
			);
		}

		if ( isset( $_POST['_woo_scheduled_products_publish_to'] ) ) {
			$data = $_POST['_woo_scheduled_products_publish_to'];
			$to = array(
				'date' => isset( $data['date'] ) ? $data['date'] : '',
				'hours' => isset( $data['hours'] ) ? $data['hours'] : 0,
				'mins' => isset( $data['mins'] ) ? $data['mins'] : 0,
				'timestamp' => FALSE,
			);
		}

		// Parse dates into timestamps
		if ( ! empty( $from ) && ! empty( $from['date'] ) ) {
			$from['timestamp'] = $this->wp_strtotime( sprintf( '%s %s:%s', $from['date'], $from['hours'], $from['mins'] ) );
		}

		if ( ! empty( $to ) && ! empty( $to['date'] ) ) {
			$to['timestamp'] = $this->wp_strtotime( sprintf( '%s %s:%s', $to['date'], $to['hours'], $to['mins'] ) );
		}

	  update_post_meta( $post_id, '_woo_scheduled_products_publish_from', $from );
	  update_post_meta( $post_id, '_woo_scheduled_products_publish_to', $to );

		$this->schedule_publishing( $post_id, $from, $to );
	}

	private function schedule_publishing( $post_id, $from, $to ) {
		if ( $from && $from['timestamp'] ) {
			wp_schedule_single_event( $from['timestamp'], 'woo_scheduled_products_publish', array( $post_id ) );
		} else {
			wp_clear_scheduled_hook( 'woo_scheduled_products_publish', array( $post_id ) );
		}

		if ( $to && $to['timestamp'] ) {
			wp_schedule_single_event( $to['timestamp'], 'woo_scheduled_products_unpublish', array( $post_id ) );
		} else {
			wp_clear_scheduled_hook( 'woo_scheduled_products_unpublish', array( $post_id ) );
		}
	}

	private function wp_strtotime( $str ) {
		$tz_string = get_option( 'timezone_string' );
		$tz_offset = get_option( 'gmt_offset', 0 );

		if ( ! empty( $tz_string ) ) {
			$timezone = $tz_string;
		} elseif ( $tz_offset == 0 ) {
			$timezone = 'UTC';
		} else {
			$timezone = $tz_offset;
			if ( substr( $tz_offset, 0, 1 ) != "-" && substr( $tz_offset, 0, 1 ) != "+" && substr( $tz_offset, 0, 1 ) != "U") {
				$timezone = "+" . $tz_offset;
			}
		}

		$datetime = new DateTime( $str, new DateTimeZone( $timezone ) );

		return $datetime->format('U');
	}
}
