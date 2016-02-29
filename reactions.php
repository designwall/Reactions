<?php
/*
* Plugin Name: Reactions
* Plugin URI: http://www.designwal.com/
* Description:
* Author: DesignWall
* Author URI: http://www.designwal.com/
*
* Version: 1.0.0
* Text Domain: reactions
*/

class DW_Reaction {
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ) );
		add_filter( 'the_content', array( $this, 'replace_content' ) );

		add_action( 'wp_ajax_dw_reaction_save_action', array( $this, 'ajax' ) );
		add_action( 'init', array( $this, 'debug' ) );
	}

	public function replace_content( $content ) {
		return $content . $this->layout();
	}

	public function layout() {
		if ( is_user_logged_in() ) :
		?>
		<div class="dw-reactions">
			<span class="like-btn">Like
				<ul class="reactions-box" data-nonce="<?php echo wp_create_nonce( '_dw_reaction_action' ) ?>" data-post="<?php the_ID() ?>">
					<li class="reaction reaction-like"></li>
					<li class="reaction reaction-love"></li>
					<li class="reaction reaction-haha"></li>
					<li class="reaction reaction-wow"></li>
					<li class="reaction reaction-sad"></li>
					<li class="reaction reaction-angry"></li>
				</ul>
			</span>
		</div>
		<?php
		endif;
	}

	public function enqueue_script() {
		wp_enqueue_style( 'dw-reaction-style', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/css/style.css' );
		wp_enqueue_script( 'dw-reaction-script', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/js/script.js', array( 'jquery' ), true );
		$localize = array(
			'ajax' => admin_url( 'admin-ajax.php' ),
		);

		wp_localize_script( 'dw-reaction-script', 'dw_reaction', $localize );
	}

	public function ajax() {
		check_admin_referer( '_dw_reaction_action', 'nonce' );

		if ( empty( $_POST['post'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing post', 'reactions' ) ) );
		}

		if ( empty( $_POST['type'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing type', 'reactions' ) ) );
		}

		$reaction = get_post_meta( $_POST['post'], 'dw_reaction_' . $_POST['type'], true );
		$reaction = $reaction . ' ' .get_current_user_id();

		update_post_meta( $_POST['post'], 'dw_reaction_' . $_POST['type'], $reaction );
	}

	public function is_like( $user_id = 0, $post_id = false ) {
		global $wpdb;

		$query = "SELECT * FROM {$wpdb->postmeta} WHERE meta_key = 'dw_reaction_love' AND FIND_IN_SET('{$user_id}', meta_value) > 0";

		if ( $post_id ) {
			$query .= " AND post_id = {$post_id}";
		}

		$result = $wpdb->get_col( $query );

		//return $query;

		return !empty( $result ) ? $result : false;
	}

	public function debug() {
		if ( isset( $_GET{'test'} ) ) {
			print_r( $this->is_like( 1, 1 ) );
			die;
		}
	}
}

new DW_Reaction();