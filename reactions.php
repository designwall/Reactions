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
		add_action( 'wp_head', array( $this, 'head' ) );
		add_action( 'admin_menu', array( $this, 'settings_page' ) );
		add_action( 'admin_init', array( $this, 'save' ) );

		// ajax action
		add_action( 'wp_ajax_dw_reaction_save_action', array( $this, 'ajax' ) );
	}

	public function head() {
		add_filter( 'the_content', array( $this, 'replace_content' ), 15 );
	}

	public function replace_content( $content ) {
		global $wp_query;
		if ( is_single() ) {
			return $content . $this->layout();
		}

		return $content;
	}

	public function layout( $post_id = false ) {
		if ( $post_id ) {
			$post_id = get_the_ID();
		}

		if ( is_user_logged_in() ) :
		?>
		<div class="dw-reactions">
			<div class="dw-reactions-button">
				<span class="dw-reactions-main-button"><?php _e( 'Like', 'reactions' ) ?></span>
				<div class="dw-reactions-box" data-nonce="<?php echo wp_create_nonce( '_dw_reaction_action' ) ?>" data-post="<?php the_ID() ?>">
					<span class="dw-reaction dw-reaction-like"><strong><?php _e( 'Like', 'reactions' ) ?></strong></span>
					<span class="dw-reaction dw-reaction-love"><strong><?php _e( 'Love', 'reactions' ) ?></strong></span>
					<span class="dw-reaction dw-reaction-haha"><strong><?php _e( 'Haha', 'reactions' ) ?></strong></span>
					<span class="dw-reaction dw-reaction-wow"><strong><?php _e( 'Wow', 'reactions' ) ?></strong></span>
					<span class="dw-reaction dw-reaction-sad"><strong><?php _e( 'Sad', 'reactions' ) ?></strong></span>
					<span class="dw-reaction dw-reaction-angry"><strong><?php _e( 'Angry', 'reactions' ) ?></strong></span>
				</div>
			</div>
			<div class="dw-reactions-count">
				<span class="dw-reaction-count dw-reaction-count-like"><strong>2</strong></span>
				<span class="dw-reaction-count dw-reaction-count-love"><strong>0</strong></span>
				<span class="dw-reaction-count dw-reaction-count-haha"><strong>4</strong></span>
				<span class="dw-reaction-count dw-reaction-count-wow"><strong>5</strong></span>
				<span class="dw-reaction-count dw-reaction-count-sad"><strong>1</strong></span>
				<span class="dw-reaction-count dw-reaction-count-angry"><strong>7</strong></span>
			</div>
		</div>
		<!-- <div class="dw-reactions">
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
		</div> -->
		<?php
		$this->count_like_layout( $post_id );
		endif;
	}

	public function count_like_layout( $post_id = false ) {
		if ( !$post_id ) {
			$post_id = get_the_ID();
		}
		$reactions = array( 'like', 'love', 'haha', 'wow', 'sad', 'angry' );
		$total = get_post_meta( $post_id, 'dw_reaction_total_liked', true );
		// echo '<div class="dw-reactions-count">';
		// echo '<ul>';
		// foreach( $reactions as $reaction ) {
		// 	$count = get_post_meta( $post_id, 'dw_reaction_' . $reaction );

		// 	if ( !empty( $count ) ) {
		// 		echo '<li><img src="'. trailingslashit( plugin_dir_url( __FILE__ ) ) .'assets/img/'. $reaction .'.png"><span class="count">'. count( $count ) .'</span></li>';
		// 	}
		// }
		// echo '</ul>';
		// echo '</div>';
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
			delete_post_meta( $_POST['post'], $is_liked, get_current_user_id() );
		}

		if ( !$is_liked ) {
			$total = get_post_meta( $_POST['post'], 'dw_reaction_total_liked', true ) ? get_post_meta( $_POST['post'], 'dw_reaction_total_liked', true ) : 0;
			$total = (int) $total + 1;

			update_post_meta( $_POST['post'], 'dw_reaction_total_liked', $total );
		}

		$count = get_post_meta( $_POST['post'], 'dw_reaction_' . $_POST['type'] );

		// update to database
		add_post_meta( $_POST['post'], 'dw_reaction_' . $_POST['type'], get_current_user_id() );

		ob_start();
		$this->count_like_layout( $_POST['post'] );
		$content = ob_get_clean();

		wp_send_json_success( array( 'html' => $content ) );
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

	public function shortcode( $atts = array() ) {
		extract( shortcode_atts( array(
			'id' => get_the_ID()
		), $atts, 'reactions' ) );

		echo $this->layout( $id );
	}

	public function settings_page() {
		add_submenu_page( 'options-general.php', __( 'Reactions Settings', 'reactions' ), __( 'Reactions Settings', 'reactions' ), 'manage_options', 'dw_reaction_settings', array( $this, 'setting_layout' ) );
	}

	public function setting_layout() {
		$options = get_option( 'dw_reactions', array() );
		$position = isset( $options['position'] ) ? $options['position'] : 'above';
		$archive = isset( $options['pages']['archive'] ) ? $options['pages']['archive'] : false;
		$posts = isset( $options['pages']['posts'] ) ? $options['pages']['posts'] : false;
		$pages = isset( $options['pages']['pages'] ) ? $options['pages']['pages'] : false;
		?>
		<div class="wrap">
			<form method="post">
				<table class="form-table">
					<tr>
						<th><?php _e( 'Position', 'reactions' ); ?></th>
						<td>
							<label><input type="radio" name="reactions[position]" value="above" <?php checked( $position, 'above' ) ?>><span class="description"><?php _e( 'Above content', 'reactions' ) ?></span></label>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<label><input type="radio" name="reactions[position]" value="below" <?php checked( $position, 'below' ) ?>><span class="description"><?php _e( 'Below content', 'reactions' ) ?></span></label>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Pages', 'reactions' ) ?></th>
						<td>
							<label><input type="checkbox" name="reactions[pages][archive]" <?php checked( $archive, 'on' ) ?>><span class="description"><?php _e( 'Archive Pages', 'reactions' ) ?></span></label>
						</td>
					</tr>
					<tr>
						<th></th>
						<td><label><input type="checkbox" name="reactions[pages][posts]"<?php checked( $posts, 'on' ) ?>><span class="description"><?php _e( 'All Posts', 'reactions' ) ?></span></label></td>
					</tr>
					<tr>
						<th></th>
						<td><label><input type="checkbox" name="reactions[pages][pages]"<?php checked( $pages, 'on' ) ?>><span class="description" ><?php _e( 'All Pages', 'reactions' ) ?></span></label></td>
					</tr>
				</table>
				<button type="submit" class="button button-primary"><?php _e( 'Save changes', 'reactions' ) ?></button>
			</form>
		</div>
		<?php
	}

	public function save() {
		if ( isset( $_POST['reactions'] ) ) {
			update_option( 'dw_reactions', $_POST['reactions'] );
		}
	}
}

function dw_reactions() {
	$reactions = new DW_Reaction();
	echo $reactions->layout();
}

new DW_Reaction();