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
	/**
	* Class Construct
	*/
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	* Class Init
	*/
	public function init() {
		// Load translate text domain
		load_plugin_textdomain( 'reactions', false,  plugin_basename( dirname( __FILE__ ) )  . '/languages' );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ) );
		add_action( 'wp_head', array( $this, 'head' ) );
		add_action( 'admin_menu', array( $this, 'settings_page' ) );
		add_action( 'admin_init', array( $this, 'save' ) );

		// register shortcode
		add_shortcode( 'reactions', array( $this, 'shortcode' ) );
		add_shortcode( 'reactions_count', array( $this, 'shortcode_reactions_count' ) );

		// ajax action
		add_action( 'wp_ajax_dw_reaction_save_action', array( $this, 'ajax' ) );
	}

	/**
	* WordPress header hook
	*/
	public function head() {
		add_filter( 'the_content', array( $this, 'replace_content' ), 99 );
	}

	/**
	* Add reactions to post/page content
	*
	* @param string $content
	* @return string
	*/
	public function replace_content( $content ) {
		if ( $this->is_enable() ) {
			if ( $this->enable_in_single_post() || $this->enable_in_archive() || $this->enable_in_pages() || $this->enable_in_home() ) {
				if ( $this->position( 'above' ) ) {
					echo $this->layout();
				}

				echo $content;

				if ( $this->position( 'below' ) ) {
					echo $this->layout();
				}

				return;
			}
		}

		return $content;
	}

	/**
	* Print Reactions layout
	*
	* @param int $post_id (default: false)
	*/
	public function layout( $post_id = false ) {
		if ( $post_id ) {
			$post_id = get_the_ID();
		}

		$text = $this->get_reactions_text( get_current_user_id(), get_the_ID() );
		$is_liked = $this->is_liked( get_current_user_id(), get_the_ID() );

		if ( is_user_logged_in() ) :
		?>
		<div class="dw-reactions dw-reactions-post-<?php the_ID() ?>">
			<div class="dw-reactions-button">
				<span class="dw-reactions-main-button <?php echo strtolower( $is_liked ) ?>"><?php echo $text ?></span>
				<div class="dw-reactions-box" data-nonce="<?php echo wp_create_nonce( '_dw_reaction_action' ) ?>" data-post="<?php the_ID() ?>">
					<span class="dw-reaction dw-reaction-like"><strong><?php _e( 'Like', 'reactions' ) ?></strong></span>
					<span class="dw-reaction dw-reaction-love"><strong><?php _e( 'Love', 'reactions' ) ?></strong></span>
					<span class="dw-reaction dw-reaction-haha"><strong><?php _e( 'Haha', 'reactions' ) ?></strong></span>
					<span class="dw-reaction dw-reaction-wow"><strong><?php _e( 'Wow', 'reactions' ) ?></strong></span>
					<span class="dw-reaction dw-reaction-sad"><strong><?php _e( 'Sad', 'reactions' ) ?></strong></span>
					<span class="dw-reaction dw-reaction-angry"><strong><?php _e( 'Angry', 'reactions' ) ?></strong></span>
				</div>
			</div>
			<?php if ( $this->enable_count() ) : ?>
				<?php $this->count_like_layout( $post_id ); ?>
			<?php endif; ?>
		</div>
		<?php
		endif;
	}

	/**
	* Print count vote reactions
	*
	* @param int $post_id
	*/
	public function count_like_layout( $post_id = false ) {
		if ( !$post_id ) {
			$post_id = get_the_ID();
		}
		$reactions = array( 'like', 'love', 'haha', 'wow', 'sad', 'angry' );
		$total = get_post_meta( $post_id, 'dw_reaction_total_liked', true );
		echo '<div class="dw-reactions-count">';
		foreach( $reactions as $reaction ) {
			$count = get_post_meta( $post_id, 'dw_reaction_' . $reaction );

			if ( !empty( $count ) ) {
				echo '<span class="dw-reaction-count dw-reaction-count-'.$reaction.'"><strong>'.count( $count ).'</strong></span>';
			}
		}
		echo '</div>';
	}

	/**
	* Enqueue plugin's style/script
	*/
	public function enqueue_script() {
		wp_enqueue_style( 'dw-reaction-style', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/css/style.css' );
		wp_enqueue_script( 'dw-reaction-script', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/js/script.js', array( 'jquery' ), true );
		$localize = array(
			'ajax' => admin_url( 'admin-ajax.php' ),
		);

		wp_localize_script( 'dw-reaction-script', 'dw_reaction', $localize );
	}

	/**
	* Ajax vote
	*/
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

	/**
	* Check user is liked
	*
	* @param int $user_id
	* @param int $post_id (default: false)
	*/
	public function is_liked( $user_id, $post_id = false ) {
		global $wpdb;

		$query = "SELECT meta_key FROM {$wpdb->postmeta} WHERE meta_key IN ( 'dw_reaction_love', 'dw_reaction_like', 'dw_reaction_haha', 'dw_reaction_wow', 'dw_reaction_sad', 'dw_reaction_angry' ) AND meta_value = {$user_id}";

		if ( $post_id ) {
			$query .= " AND post_id = {$post_id}";
		}

		$result = $wpdb->get_var( $query );

		return !empty( $result ) ? $result : false;
	}

	/**
	* Get reactions text
	*
	* @param int $user_id
	* @param int $post_id (default: false)
	*/
	public function get_reactions_text( $user_id, $post_id = false ) {
		$is_liked = $this->is_liked( $user_id, $post_id );
		$default = __( 'Like', 'reactions' );
		if ( !$is_liked ) {
			return $default;
		} else {
			if ( strpos( $is_liked, 'like' ) ) {
				return $default;
			} elseif ( strpos( $is_liked, 'haha' ) ) {
				return __( 'Haha', 'reactions' );
			} elseif ( strpos( $is_liked, 'love' ) ) {
				return __( 'Love', 'reactions' );
			} elseif ( strpos( $is_liked, 'wow' ) ) {
				return __( 'Wow', 'reactions' );
			} elseif ( strpos( $is_liked, 'angry' ) ) {
				return __( 'Angry', 'reactions' );
			} elseif ( strpos( $is_liked, 'sad' ) ) {
				return __( 'Sad', 'reactions' );
			}
		}
	}

	/**
	* Reactions short code
	*
	* @param array $atts
	*/
	public function shortcode_reactions( $atts = array() ) {
		extract( shortcode_atts( array(
			'id' => get_the_ID()
		), $atts, 'reactions' ) );

		echo $this->layout( $id );
	}

	/**
	* Reactions count short code
	*
	* @param array $atts
	*/
	public function shortcode_reactions_count( $atts = array() ) {
		extract( shortcode_atts( array(
			'id' => get_the_ID()
		), $atts, 'reactions_count' ) );

		echo $this->count_like_layout( $id );
	}

	/**
	* Register settings page
	*/
	public function settings_page() {
		add_submenu_page( 'options-general.php', __( 'Reactions Settings', 'reactions' ), __( 'Reactions', 'reactions' ), 'manage_options', 'dw_reaction_settings', array( $this, 'setting_layout' ) );
	}

	/**
	* Print setting layout
	*/
	public function setting_layout() {
		$options = get_option( 'dw_reactions', array() );
		$above = isset( $options['position']['above'] ) ? $options['position']['above'] : false;
		$below = isset( $options['position']['below'] ) ? $options['position']['below'] : false;
		$archive = isset( $options['pages']['archive'] ) ? $options['pages']['archive'] : false;
		$posts = isset( $options['pages']['posts'] ) ? $options['pages']['posts'] : false;
		$pages = isset( $options['pages']['pages'] ) ? $options['pages']['pages'] : false;
		$home = isset( $options['pages']['home'] ) ? $options['pages']['home'] : false;
		?>
		<div class="wrap">
			<h2><?php echo get_admin_page_title(); ?></h2>
			<form method="post">
				<h3><?php _e( '1. Auto display reactions button', 'reactions' ) ?></h3>
				<table class="form-table">
					<tr>
						<th><?php _e( 'Enable?', 'reactions' ) ?></th>
						<td><label>
							<input type="checkbox" name="reactions[enable]" <?php checked( $this->is_enable(), true ) ?>><span class="description"><?php _e( 'Enable Reactions', 'reactions' ) ?></span>
						</label></td>
					</tr>
					<tr>
						<th><?php _e( 'Reactions Vote Count', 'reactions' ) ?></th>
						<td>
							<label><input type="checkbox" name="reactions[enable_count]" <?php checked( $this->enable_count(), true ) ?>><span class="description"><?php _e( 'Enable', 'reactions' ) ?></span></label>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Position', 'reactions' ); ?></th>
						<td>
							<p><label><input type="checkbox" name="reactions[position][above]" <?php checked( $above, 'on' ) ?>><span class="description"><?php _e( 'Above content', 'reactions' ) ?></span></label></p>
							<p><label><input type="checkbox" name="reactions[position][below]" <?php checked( $below, 'on' ) ?>><span class="description"><?php _e( 'Below content', 'reactions' ) ?></span></label></p>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Pages', 'reactions' ) ?></th>
						<td>
							<p><label><input type="checkbox" name="reactions[pages][home]"<?php checked( $home, 'on' ) ?>><span class="description" ><?php _e( 'Homepage', 'reactions' ) ?></span></label></p>
							<p><label><input type="checkbox" name="reactions[pages][archive]" <?php checked( $archive, 'on' ) ?>><span class="description"><?php _e( 'Archives', 'reactions' ) ?></span></label></p>
							<p><label><input type="checkbox" name="reactions[pages][posts]"<?php checked( $posts, 'on' ) ?>><span class="description"><?php _e( 'Posts', 'reactions' ) ?></span></label></p>
							<p><label><input type="checkbox" name="reactions[pages][pages]"<?php checked( $pages, 'on' ) ?>><span class="description" ><?php _e( 'Pages', 'reactions' ) ?></span></label></p>
						</td>
					</tr>
				</table>
				<hr>
				<h3><?php _e( '2. Manually Insert Short codes or function to your theme', 'reactions' ) ?></h3>
				<p>
					<ol>
						<li><?php _e( 'Open <code>wp-content/themes/&lt;Your theme folder&gt;/</code>', 'reactions' ); ?></li>
						<li><?php _e( 'You may place it in <code>archive.php</code>, <code>single.php</code>, <code>post.php</code> or <code>page.php</code> also.', 'reactions' ); ?></li>
						<li><?php _e( 'Find <code>&lt;&#63;php while (have_posts()) : the_post(); &#63;&gt;</code>', 'reactions' ); ?></li>
						<li><?php _e( "Add Anywhere Below it(The place you want Reactions to show): <code>&lt;&#63;php if (function_exists('dw_reactions')) { dw_reactions() } &#63;&gt;</code>", 'reactions' ); ?></li>
					</ol>
					<ul>
						<li><?php _e( '- If you DO NOT want the reactions to appear in every post/page, DO NOT use the code above. Just type in <code>[reactions]</code> into the selected post/page and it will embed reactions into that post/page only.', 'reactions' ); ?></li>
						<li><?php _e( '- If you want to embed other post reactions user <code>[reactions id="1"]</code>, where 1 is the ID of the post/page ratings that you want to display.', 'reactions' ); ?></li>
					</ul>
				</p>
				<button type="submit" class="button button-primary"><?php _e( 'Save changes', 'reactions' ) ?></button>
			</form>
		</div>
		<?php
	}

	/**
	* Save settings action
	*/
	public function save() {
		if ( isset( $_POST['reactions'] ) ) {
			update_option( 'dw_reactions', $_POST['reactions'] );
		}
	}

	/**
	* Check is enable reactions
	*/
	public function is_enable() {
		$options = get_option( 'dw_reactions', array() );

		return isset( $options['enable'] ) && 'on' == $options['enable'] ? true : false;
	}

	public function enable_count() {
		$options = get_option( 'dw_reactions', array() );

		return isset( $options['enable_count'] ) && 'on' == $options['enable_count'] ? true : false;
	}

	/**
	* Check reactions enable in posts
	*
	* @return bool
	*/
	public function enable_in_single_post() {
		$options = get_option( 'dw_reactions', array() );

		if ( 'posts' == $this->template_type() && isset( $options['pages']['posts'] ) && 'on' == $options['pages']['posts'] ) {
			return true;
		}

		return false;
	}

	/**
	* Check reactions enable in pages
	*
	* @return bool
	*/
	public function enable_in_pages() {
		$options = get_option( 'dw_reactions', array() );

		if ( 'pages' == $this->template_type() && isset( $options['pages']['pages'] ) && 'on' == $options['pages']['pages'] ) {
			return true;
		}

		return false;
	}

	/**
	* Check reactions enable in archive pages
	*
	* @return bool
	*/
	public function enable_in_archive() {
		$options = get_option( 'dw_reactions', array() );

		if ( 'archive' == $this->template_type() && isset( $options['pages']['archive'] ) && 'on' == $options['pages']['archive'] ) {
			return true;
		}

		return false;
	}

	/**
	* Check reactions enable in home or front page
	*
	* @return bool
	*/
	public function enable_in_home() {
		$options = get_option( 'dw_reactions', array() );

		if ( 'home' == $this->template_type() && isset( $options['pages']['home'] ) && 'on' == $options['pages']['home'] ) {
			return true;
		}

		return false;
	}

	/**
	* Get current page template type
	*
	* @return string|bool
	*/
	public function template_type() {
		global $post;

		if ( is_home() || is_front_page() ) {
			$type = 'home';
		} elseif ( is_archive() ) {
			$type = 'archive';
		} elseif ( is_object( $post ) && is_page( $post->ID ) ) {
			$type = 'pages';
		} elseif ( is_single() ) {
			$type = 'posts';
		} else {
			$type = false;
		}

		return $type;
	}

	/**
	* Get positions had enable
	*
	* @return bool
	*/
	public function position( $type ) {
		$options = get_option( 'dw_reactions', array() );

		return isset( $options['position'][ $type ] ) && 'on' == $options['position'][ $type ] ? true : false;
	}
}

/**
* Print reactions
*
* @param int $post_id (default: false)
*/
function dw_reactions( $post_id = false ) {
	$reactions = new DW_Reaction();
	echo $reactions->layout( $post_id );
}

/**
* Print reactions vote count
*
* @param int $post_id (default: false)
*/
function dw_reactions_count( $post_id = false ) {
	$reactions = new DW_Reaction();
	echo $reactions->count_like_layout( $post_id );
}

new DW_Reaction();