<?php
/*
* Plugin Name: DW Reactions
* Plugin URI: https://wordpress.org/plugins/dw-reactions/
* Description: A simple plugin that helps you integrate reactions buttons into your WordPress site look like Facebook.
* Author: DesignWall
* Author URI: https://www.designwal.com/
*
* Version: 1.0.1
* Text Domain: reactions
*/

class DW_Reaction {
	/**
	* Class Construct
	*/
	public function __construct() {
		// register shortcode
		add_shortcode( 'reactions', array( $this, 'shortcode_reactions' ) );
		register_activation_hook( __FILE__, array( $this, 'set_default_setting' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ) );
		add_action( 'wp_head', array( $this, 'head' ) );
		add_action( 'admin_menu', array( $this, 'settings_page' ) );
		add_action( 'admin_init', array( $this, 'save' ) );

		add_action( 'plugins_loaded', array( $this, 'load_translate' ) );

		// ajax action
		add_action( 'wp_ajax_dw_reaction_save_action', array( $this, 'ajax' ) );
		add_action( 'wp_ajax_nopriv_dw_reaction_save_action', array( $this, 'ajax' ) );
	}

	/**
	* Load translate
	*/
	public function load_translate() {
		// Load translate text domain
		load_plugin_textdomain( 'reactions', false,  plugin_basename( dirname( __FILE__ ) )  . '/languages' );
	}

	public function set_default_setting() {
		$data = array(
			'enable' 				=> 'on',
			'enable_count' 			=> 'on',
			'anonymous_can_vote' 	=> 'on',
			'position'				=> array(
				'above' 			=> 'on',
				'below' 			=> 'on',
			),
			'pages' 				=> array(
				'home' 				=> 'on',
				'archive' 			=> 'on',
				'posts' 			=> 'on',
				'pages' 			=> 'on'
			)
		);

		update_option( 'dw_reactions', $data );
	}

	/**
	* WordPress header hook
	*/
	public function head() {
		// add reactions to the content
		add_filter( 'the_content', array( $this, 'replace_content' ) );
		// add reactions to the excerpt
		add_filter( 'get_the_excerpt', array( $this, 'replace_content_excerpt' ) );
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
				$fullcontent = '';
				if ( $this->position( 'above' ) ) {
					$fullcontent .= $this->layout();
				}

				$fullcontent .= $content;

				if ( $this->position( 'below' ) ) {
					$fullcontent .= $this->layout();
				}

				return $fullcontent;
			}
		}

		return $content;
	}

	/**
	* Add reactions to post/page excerpt
	*
	* @param string $content
	* @param string
	*/
	public function replace_content_excerpt( $content ) {
		if ( has_excerpt() && !is_single() ) {
			$content = $this->replace_content( $content );
		}

		return $content;
	}

	/**
	* Print Reactions layout
	*
	* @param int $post_id (default: false)
	*/
	public function layout( $post_id = false, $button = true, $count = true ) {
		if ( $post_id ) {
			$post_id = get_the_ID();
		}

		$text = $this->get_reactions_text( get_current_user_id(), get_the_ID() );
		$is_liked = $this->is_liked( get_current_user_id(), get_the_ID() );
		$type = $is_liked ? 'unvote' : 'vote';
		ob_start();
		?>
		<div class="dw-reactions dw-reactions-post-<?php the_ID() ?>" data-type="<?php echo esc_attr( $type ) ?>" data-nonce="<?php echo wp_create_nonce( '_dw_reaction_action' ) ?>" data-post="<?php the_ID() ?>">
			<?php if ( $button ) : ?>
				<?php if ( ( !$this->anonymous_can_vote() && !is_user_logged_in() ) || is_user_logged_in() ) : ?>
				<div class="dw-reactions-button">
					<span class="dw-reactions-main-button <?php echo esc_attr( strtolower( $is_liked ) ) ?>"><?php echo esc_html( $text ) ?></span>
					<div class="dw-reactions-box">
						<span class="dw-reaction dw-reaction-like"><strong><?php esc_attr_e( 'Like', 'reactions' ) ?></strong></span>
						<span class="dw-reaction dw-reaction-love"><strong><?php esc_attr_e( 'Love', 'reactions' ) ?></strong></span>
						<span class="dw-reaction dw-reaction-haha"><strong><?php esc_attr_e( 'Haha', 'reactions' ) ?></strong></span>
						<span class="dw-reaction dw-reaction-wow"><strong><?php esc_attr_e( 'Wow', 'reactions' ) ?></strong></span>
						<span class="dw-reaction dw-reaction-sad"><strong><?php esc_attr_e( 'Sad', 'reactions' ) ?></strong></span>
						<span class="dw-reaction dw-reaction-angry"><strong><?php esc_attr_e( 'Angry', 'reactions' ) ?></strong></span>
					</div>
				</div>
				<?php endif; ?>
			<?php endif; ?>
			<?php if ( ( $this->enable_count() && $this->is_enable() ) || ( !$this->is_enable() && $count ) ) : ?>
				<div class="dw-reactions-count">
					<?php echo $this->count_like_layout( $post_id ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		$content = ob_get_contents();
		ob_get_clean();

		return $content;
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
		$output = '';
		foreach( $reactions as $reaction ) {
			$count = get_post_meta( $post_id, 'dw_reaction_' . $reaction );

			if ( !empty( $count ) ) {
				$output .= '<span class="dw-reaction-count dw-reaction-count-'.esc_attr( $reaction ).'"><strong>'.esc_attr( count( $count ) ).'</strong></span>';
			}
		}

		return $output;
	}

	/**
	* Enqueue plugin's style/script
	*/
	public function enqueue_script() {
		wp_enqueue_style( 'dw-reaction-style', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/css/style.css' );
		wp_enqueue_script( 'dw-reaction-script', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/js/script.js', array( 'jquery' ), true );
		wp_enqueue_script( 'jquery_mobile', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/js/jquery.mobile-1.4.5.min.js', array( 'jquery' ), '1.4.5' );
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

		$post_id = intval( $_POST['post'] );
		$type = sanitize_title( $_POST['type'] );
		$vote_type = sanitize_title( $_POST['vote_type'] );
		$voted = sanitize_title( $_POST['voted'] );

		if ( empty( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing post.', 'reactions' ) ) );
		}

		if ( empty( $type ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing type.', 'reactions' ) ) );
		}

		if ( 'yes' == $voted && !is_user_logged_in() ) return;
		// delete old reactions
		$is_liked = $this->is_liked( get_current_user_id(), $post_id );
		if ( $is_liked ) {
			if ( !is_user_logged_in() ) return;
			delete_post_meta( $post_id, $is_liked, get_current_user_id() );
			if ( isset( $vote_type ) && 'unvote' == $vote_type ) {
				$total = get_post_meta( $post_id, 'dw_reaction_total_liked', true ) ? get_post_meta( $post_id, 'dw_reaction_total_liked', true ) : 0;
				if ( $total >= 0 ) {
					$total = (int) $total - 1;
					update_post_meta( $post_id, 'dw_reaction_total_liked', $total );
				}

				$content = $this->count_like_layout( $post_id );

				wp_send_json_success( array( 'html' => $content, 'type' => 'unvoted' ) );
				exit();
			}
		}

		if ( !$is_liked ) {
			$total = get_post_meta( $post_id, 'dw_reaction_total_liked', true ) ? get_post_meta( $post_id, 'dw_reaction_total_liked', true ) : 0;
			$total = (int) $total + 1;

			update_post_meta( $post_id, 'dw_reaction_total_liked', $total );
		}

		$count = get_post_meta( $post_id, 'dw_reaction_' . $type );

		$user_id = get_current_user_id();
		if ( !is_user_logged_in() && !$this->anonymous_can_vote() ) {
			$user_id = 'anonymous';
		}

		// update to database
		add_post_meta( $post_id, 'dw_reaction_' . $type, $user_id );

		$content = $this->count_like_layout( $post_id );

		wp_send_json_success( array( 'html' => $content, 'type' => 'voted' ) );
		exit();
	}

	/**
	* Check user is liked
	*
	* @param int $user_id
	* @param int $post_id (default: false)
	*/
	public function is_liked( $user_id, $post_id = false ) {
		global $wpdb;

		$query = "SELECT meta_key FROM {$wpdb->postmeta} WHERE meta_key IN ( 'dw_reaction_love', 'dw_reaction_like', 'dw_reaction_haha', 'dw_reaction_wow', 'dw_reaction_sad', 'dw_reaction_angry' ) AND meta_value = '{$user_id}'";

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
			'id' => get_the_ID(),
			'button' => 'true',
			'count'	=> 'true',
		), $atts, 'reactions' ) );

		$button = 'true' == $button ? true : false;
		$count = 'true' == $count ? true : false;

		echo $this->layout( $id, $button, $count );
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
			<?php esc_attr_e( 'To display the reactions button on your blog posts, you can use one of two ways below:', 'reactions' ); ?>
			<form method="post">
				<h3><?php esc_attr_e( '1. Automatically display on the content of each post.', 'reactions' ) ?></h3>
				<table class="form-table">
					<tr>
						<td colspan="2">
							<p><label>
								<input type="checkbox" name="reactions[enable]" <?php checked( $this->is_enable(), true ) ?>><span class="description"><?php esc_attr_e( 'Show reactions button.', 'reactions' ) ?></span>
							</label></p>
							<p><label><input type="checkbox" name="reactions[enable_count]" <?php checked( $this->enable_count(), true ) ?>><span class="description"><?php esc_attr_e( 'Show reactions count.', 'reactions' ) ?></span></label></p>
							<p>
								<label>
									<input type="checkbox" name="reactions[anonymous_can_vote]" <?php checked( $this->anonymous_can_vote(), true ) ?>><span class="description"><?php esc_attr_e( 'Users must be registered and logged in to add reaction.', 'reactions' ) ?></span>
								</label>
							</p>
						</td>
					</tr>
					<tr>
						<th><?php esc_attr_e( 'Positions', 'reactions' ); ?></th>
						<td>
							<p><label><input type="checkbox" name="reactions[position][above]" <?php checked( esc_attr( $above ), 'on' ) ?>><span class="description"><?php esc_attr_e( 'Show the reactions button above the post content.', 'reactions' ) ?></span></label></p>
							<p><label><input type="checkbox" name="reactions[position][below]" <?php checked( esc_attr( $below ), 'on' ) ?>><span class="description"><?php esc_attr_e( 'Show the reactions button below the post content.', 'reactions' ) ?></span></label></p>
						</td>
					</tr>
					<tr>
						<th><?php esc_attr_e( 'Pages', 'reactions' ) ?></th>
						<td>
							<p><label><input type="checkbox" name="reactions[pages][home]"<?php checked( esc_attr( $home ), 'on' ) ?>><span class="description" ><?php esc_attr_e( 'Show on Homepage', 'reactions' ) ?></span></label></p>
							<p><label><input type="checkbox" name="reactions[pages][archive]" <?php checked( esc_attr( $archive ), 'on' ) ?>><span class="description"><?php esc_attr_e( 'Show on Archive pages', 'reactions' ) ?></span></label></p>
							<p><label><input type="checkbox" name="reactions[pages][posts]"<?php checked( esc_attr( $posts ), 'on' ) ?>><span class="description"><?php esc_attr_e( 'Show on all Posts', 'reactions' ) ?></span></label></p>
							<p><label><input type="checkbox" name="reactions[pages][pages]"<?php checked( esc_attr( $pages ), 'on' ) ?>><span class="description" ><?php esc_attr_e( 'Show on all Pages', 'reactions' ) ?></span></label></p>
						</td>
					</tr>
				</table>
				<hr>
				<h3><?php esc_attr_e( '2. Manually insert into your theme.', 'reactions' ) ?></h3>
				<p>

					<p><?php _e( '1. Open <code>wp-content/themes/&lt;Your theme folder&gt;/</code>.', 'reactions' ); ?></p>
					<p><?php _e( '2. You may place it in <code>archive.php</code>, <code>single.php</code>, <code>post.php</code> or <code>page.php</code> also.', 'reactions' ); ?></p>
					<p><?php _e( '3. Find <code>&lt;&#63;php while (have_posts()) : the_post(); &#63;&gt;</code>.', 'reactions' ); ?></p>
					<p><?php _e( "4. Add anywhere below it (The place you want Reactions to show): <code>&lt;&#63;php if (function_exists('dw_reactions')) { dw_reactions() } &#63;&gt;</code>.", 'reactions' ); ?></p>
					<hr>
					<p><?php _e( 'If you DO NOT want the reactions to appear in every post/page, DO NOT use the code above. Just type in <code>[reactions]</code> into the selected post/page and it will embed reactions into that post/page only.', 'reactions' ); ?></p>
					<p><?php _e( 'If you to use reactions button for specific post/page you can use this short code <code>[reactions id="1"]</code>, where 1 is the ID of the post/page.', 'reactions' ); ?></p>
					<p><?php _e( 'If you want to show reactions button you can use <code>[reactions count=false button=true]</code>.', 'reactions' ) ?></p>
					<p><?php _e( 'If you want to show reactions count you can use <code>[reactions count=true button=false]</code>.', 'reactions' ) ?></p>
				</p>
				<button type="submit" class="button button-primary"><?php esc_attr_e( 'Save changes', 'reactions' ) ?></button>
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
	* Check is enable reactions buttons
	*
	* @return bool
	*/
	public function is_enable() {
		$options = get_option( 'dw_reactions', array() );

		return isset( $options['enable'] ) && 'on' == $options['enable'] ? true : false;
	}

	/**
	* Check is enable reactions count
	*
	* @return bool
	*/
	public function enable_count() {
		$options = get_option( 'dw_reactions', array() );

		return isset( $options['enable_count'] ) && 'on' == $options['enable_count'] ? true : false;
	}

	/**
	* Check anonymous can vote
	*
	* @return bool
	*/
	public function anonymous_can_vote() {
		$options = get_option( 'dw_reactions', array() );

		return isset( $options['anonymous_can_vote'] ) || 'on' == $options['anonymous_can_vote'] ? true : false;
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
* @param bool $button (default: true)
* @param bool $count (default: true)
*/
function dw_reactions( $post_id = false, $button = true, $count = true ) {
	$reactions = new DW_Reaction();
	echo $reactions->layout( $post_id, $button, $count );
}

new DW_Reaction();