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

		// ajax action
		add_action( 'wp_ajax_dw_reaction_save_action', array( $this, 'ajax' ) );
	}

	public function replace_content( $content ) {
		return $content . $this->layout() . $this->count_like_layout();
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

	public function count_like_layout() {
		$reactions = array( 'like', 'love', 'haha', 'wow', 'sad', 'angry' );
		echo '<div class="dw-reactions-count">';
		echo '<ul>';
		foreach( $reactions as $reaction ) {
			$count = get_post_meta( get_the_ID(), 'dw_reaction_' . $reaction );

			if ( !empty( $count ) ) {
				echo '<li><img src="'. trailingslashit( plugin_dir_url( __FILE__ ) ) .'assets/img/'. $reaction .'.png"><span class="count">'. count( $count ) .'</span></li>';
			}
		}
		echo '</ul>';
		echo '</div>';
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

		// delete old reactions
		$is_liked = $this->is_liked( get_current_user_id(), $_POST['post'] );
		if ( $is_liked ) {
			delete_post_meta( $_POST['post'], $is_liked );
		}

		// update to database
		add_post_meta( $_POST['post'], 'dw_reaction_' . $_POST['type'], get_current_user_id() );
	}

	public function is_liked( $user_id = 0, $post_id = false ) {
		global $wpdb;

		$query = "SELECT meta_key FROM {$wpdb->postmeta} WHERE meta_key IN ( 'dw_reaction_love', 'dw_reaction_like', 'dw_reaction_haha', 'dw_reaction_wow', 'dw_reaction_sad', 'dw_reaction_angry' ) AND meta_value = {$user_id}";

		if ( $post_id ) {
			$query .= " AND post_id = {$post_id}";
		}

		$result = $wpdb->get_var( $query );

		return !empty( $result ) ? $result : false;
	}
}

new DW_Reaction();