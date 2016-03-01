<?php
/*
Plugin Name: Article Feedback
Plugin URI: http://www.themeidol.com
Description: Add "Was this article helpful?" at the end or start or both of article with thumbs up and thumbs down . Thumbs up would make to share and thumbs down would make to provide feedback to author via email
Version: 1.0
Author: the ThemeIdol Team
Author URI: http://www.themeidol.com
Author Email: themeidol@gmail.com
Credits:
	The Font Awesome icon set was created by Dave Gandy (dave@davegandy.com)
	 http://fontawesome.io

License:

  Copyright (C) 2016 the ThemeIdol Team

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

class Articlefeedback {
	private static $instance;
	const VERSION = '1.0';

	private static function has_instance() {
		return isset( self::$instance ) && null != self::$instance;
	}

	public static function get_instance() {
		if ( ! self::has_instance() ) {
			self::$instance = new Articlefeedback;
		}
		return self::$instance;
	}

	public static function setup() {
		self::get_instance();
	}

	protected function __construct() {
		if ( ! self::has_instance() ) {
			$this->init();
		}
	}

	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_plugin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_plugin_admin_styles' ) );
		add_shortcode( 'feedback_prompt', array( $this, 'feedback_content' ) );
		// register our settings page
		add_action( 'admin_menu', array( $this, 'register_submenu' ) );
		// register setting
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'the_content', array( $this, 'append_feedack_html' ) );
		register_activation_hook( __FILE__, array( $this, 'load_defaults' ) );	
		add_action('wp_ajax_join_mailinglist', array( $this,'feedback_sendmail'));
		add_action('wp_ajax_nopriv_join_mailinglist', array( $this,'feedback_sendmail'));
	}

	
	public function register_plugin_styles() {
		global $wp_styles;
		wp_enqueue_style( 'font-awesome-styles', plugins_url( 'assets/css/font-awesome.min.css', __FILE__ ), array(), self::VERSION, 'all' );
		wp_enqueue_style( 'feedback-front-styles', plugins_url( 'assets/css/front-feedback-styles.css', __FILE__ ), array(), self::VERSION, 'all' );
		wp_enqueue_script( 'feedback-front-script', plugins_url( 'assets/js/article-feedback.js', __FILE__ ), array('jquery'), self::VERSION, 'all' );
		// declare the URL to the file that handles the AJAX request (wp-admin/admin-ajax.php)
		wp_localize_script( 'feedback-front-script', 'FeedbackAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	}
	public function register_plugin_admin_styles() {
		global $wp_styles;
		wp_enqueue_style( 'feedback-admin-styles', plugins_url( 'assets/css/admin-feedback-styles.css', __FILE__ ), array(), self::VERSION, 'all' );
	}

	public function feedback_content() {
		global $post;
		$onclick="javascript:window.open(this.href,
  '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=600,width=600');return false;";
		return '<div class="m-entry__feedback"><div class="m-feedback-prompt">
		  <h4 class="m-feedback-prompt__header">Was this article helpful?</h4>
		  <a href="#" class="m-feedback-prompt__button m-feedback-prompt__social m-feedback-prompt__social_thumbsup yes" data-analytics-link="feedback-prompt:yes">
		    <i class="fa fa-thumbs-up">&nbsp;</i>
		  </a>
		  <a href="#" class="m-feedback-prompt__button m-feedback-prompt_form no" data-analytics-link="feedback-prompt:no">
		    <i class="fa fa-thumbs-down">&nbsp;</i>
		  </a><br>
		  <div class="m-feedback-prompt__display m-feedback-prompt__social yes">
		    <p class="m-feedback-prompt__text">Awesome, share it:</p>
		    <a class="m-feedback-prompt__social--button p-button-social has-icon facebook fa fa-facebook" href="https://www.facebook.com/sharer/sharer.php?u='.urldecode(get_permalink($post->ID)).'" onclick="'.$onclick.'">
		      <span class="p-button-social__social-text">Share</span>
		    </a>
		    <a class="m-feedback-prompt__social--button p-button-social has-icon twitter fa fa-twitter" href="https://twitter.com/intent/tweet?url='.urldecode(get_permalink($post->ID)).'&text='.urldecode(get_the_title($post->ID)).'" onclick="'.$onclick.'">
		      <span class="p-button-social__social-text">Tweet</span>
		    </a>
		    <a class="m-feedback-prompt__social--button p-button-social has-icon googleplus fa fa-google-plus" href="https://plus.google.com/share?url='.urldecode(get_permalink($post->ID)).'" onclick="'.$onclick.'">
		      <span class="p-button-social__social-text">Google Plus</span>
		    </a>
		    <a class="m-feedback-prompt__social--button p-button-social has-icon linkedin fa fa-linkedin" href="https://www.linkedin.com/shareArticle?mini=true&url='.urldecode(get_permalink($post->ID)).'&title='.urldecode(get_the_title($post->ID)).'" onclick="'.$onclick.'">
		      <span class="p-button-social__social-text">LinkedIn</span>
		    </a>
		  </div>
		  	<div class="m-feedback-prompt__display m-feedback-prompt__form no">
		  	<div class="thanks feedback-nodisplayall"><h2>Thanks!<h2><div class="m-contact"><p>Thanks for getting in touch with us.</p></div></div>
		    <form id="contact-form" class="new_support_request" action="" accept-charset="UTF-8" method="post">
		    '.wp_nonce_field(-1,'authenticity_token',true, false).'
		      <input value="'.urldecode(get_permalink($post->ID)).'" type="hidden" name="currenturl" id="currenturl">
		      <input value="'.urldecode(get_the_title($post->ID)).'" type="hidden" name="currenttitle" id="currenttitle">
		      <label class="is-required">Help us improve. Give us your feedback:</label>
		      <textarea class="p-input__textarea" name="feedbackmessage" id="feedbackmessage"></textarea>
		      <label class="is-required">Your Full Name:</label>
		      <input class="p-input__text" type="text" name="feedbackfullname" id="feedbackfullname">
		      <label class="is-required">Your email address:</label>
		      <input class="p-input__text" type="text" name="mailinglistemail" id="mailinglistemail">
		      <div class="feedback-message" id="feedback-message"></div>
		      <div class="__submit">
		        <input type="submit" name="commit" value="Submit" class="p-button" id="submit-contact-form" data-analytics-link="feedback-prompt:submit">
		      </div>
			</form>
			</div>
			</div>
			</div>';
	}

		public function append_feedack_html( $content ) {

		$feedack_options = $this->get_feedback_options('feedback_options');
		
		// get current post's id
		global $post;
		$post_id = $post->ID;
		
		if( in_array($post_id,explode(',',$feedack_options['ss-exclude-on'])) )
			return $content;
		if( is_home() && !in_array( 'home', (array)$feedack_options['ss-show-on'] ) )
			return $content;
		if( is_single() && !in_array( 'posts', (array)$feedack_options['ss-show-on'] ) )
			return $content;
		if( is_page() && !in_array( 'pages', (array)$feedack_options['ss-show-on'] ) )
			return $content;
		if( is_archive() && !in_array( 'archive', (array)$feedack_options['ss-show-on'] ) )
			return $content;
		
		$feedback_html_markup = $this->feedback_content();
		
		if( is_array($feedack_options['ss-select-position']) && in_array('before-content', $feedack_options['ss-select-position']) )
			$content = $feedback_html_markup.$content;
		if( is_array($feedack_options['ss-select-position']) && in_array('after-content', (array)$feedack_options['ss-select-position']) )
			$content .= $feedback_html_markup;
		return $content;

	}
	public function load_defaults(){

		update_option( 'feedback_options', $this->get_defaults() );

	}
	public function get_defaults($preset=true) {
		return array(
				'ss-select-position' => $preset ? array('before-content') : array(),
				'ss-show-on' => $preset ? array('pages', 'posts') : array(),
				'ss-exclude-on' => '',
				'ss-feedback-email'=>''
				);
		
	}

	public function register_settings(){

		register_setting( 'feedback_options', 'feedback_options' );

	}

	/*
	 * Add sub menu page in Settings for configuring plugin
	 */
	public function register_submenu(){

		add_submenu_page( 'options-general.php', 'Article Feedback settings', 'Article Feedback', 'activate_plugins', 'article-feeback-settings', array( $this, 'submenu_page' ) );

	}

	public function get_feedback_options() {
		return array_merge( $this->get_defaults(false), get_option('feedback_options') );
	}

	/*
	 * Callback for add_submenu_page for generating markup of page
	 */
	public function submenu_page() {
		?>
		<div class="wrap">
			<h2>Article Feedback Settings</h2>
			<form method="POST" action="options.php">
			<?php settings_fields('feedback_options'); ?>
			<?php
			$feedback_options = get_option('feedback_options');
			?>
			<?php echo $this->admin_form($feedback_options); ?>
		</div>
		<?php
	}

	/*
	 * Admin form for Feedabck Settings
	 */
	public function admin_form( $feedback_options ){
		
		return '<table class="form-table settings-table">
			<tr>
				<th><label for="ss-select-postion">Select Position</label></th>
				<td>
					<input type="checkbox" name="feedback_options[ss-select-position][]" value="before-content" '.__checked_selected_helper( in_array( 'before-content', (array)$feedback_options['ss-select-position'] ),true, false,'checked' ).'>
					Before Content
					<br>
					<input type="checkbox" name="feedback_options[ss-select-position][]" value="after-content" '.__checked_selected_helper( in_array( 'after-content', (array)$feedback_options['ss-select-position'] ),true, false,'checked' ).'>
					After Content
					<br/>
					<p>
					You can place the shortcode <code>[feedback_prompt]</code> wherever you want to display the Article Feedback.
				</p>
				</td>
			</tr>
			<tr>
				<th><label for="ss-select-postion">Show on</label></th>
				<td>
					<input type="checkbox" name="feedback_options[ss-show-on][]" value="home" '.__checked_selected_helper( in_array( 'home', (array)$feedback_options['ss-show-on'] ),true, false,'checked' ).'>
					Home Page
					<br>
					<input type="checkbox" name="feedback_options[ss-show-on][]" value="pages" '.__checked_selected_helper( in_array( 'pages', (array)$feedback_options['ss-show-on'] ),true, false,'checked' ).'>
					Pages
					<br>
					<input type="checkbox" name="feedback_options[ss-show-on][]" value="posts" '.__checked_selected_helper( in_array( 'posts', (array)$feedback_options['ss-show-on'] ),true, false,'checked' ).'>
					Posts
					<br/>
					<input type="checkbox" name="feedback_options[ss-show-on][]" value="archive" '.__checked_selected_helper( in_array( 'archive', (array)$feedback_options['ss-show-on'] ),true, false,'checked' ).'>
					Archives
				</td>
			</tr>

			<tr>
				<th><label for="ss-exclude-on">Exclude on</label></th>
				<td>
					<input type="text" name="feedback_options[ss-exclude-on]" value="'.$feedback_options['ss-exclude-on'].'">
					<small><em>Comma seperated post id\'s Eg: </em><code>1207,1222</code></small>
				</td>
			</tr>
			<tr>
				<th><label for="ss-select-emailsetting">Thumbs Down Email To</label></th>
				<td>
					<input type="text" name="feedback_options[ss-feedback-email]" value="'.$feedback_options['ss-feedback-email'].'">
					<small><em>If Empty Then Feedback Mail would Directly Go To Post/Page Author\'s Email</em></small>
				</td>

			</tr>
		</table>
		<p class="submit">
			<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
		</p>
	</form>';
	
	}

	function feedback_sendmail()
{
	$feedback_options = get_option('feedback_options');
	$to=$feedback_options['ss-feedback-email'];
	$to=($to=="")?get_the_author_meta( 'user_email' ):$to;
	$email = esc_html($_POST['email']);
	$name=esc_html($_POST['name']);
	$message=esc_textarea($_POST['message']);
	$url=esc_url($_POST['url']);
	$title=esc_html($_POST['title']);
	$allMesage="Feedback For: ".$title."<br/> Feedback URL:".$url."<br/> Feedack Message: <br/>".$message."<br/>Feedback From : ".$email.'<br/>Full Name: '.$name ;
	if(!empty($email)) {
   
    $headers = 'From: '.get_bloginfo( 'admin_email' ) ."\r\n".'Reply-To: '.$email;
 	add_filter( 'wp_mail_content_type', function( $content_type ) {
	return 'text/html';
	});
        if(wp_mail( $to, "Feedback For: ".$title, $allMesage, $headers)) {
			echo 'success';

		} else {
			echo 'There was a problem. Please try again.';
		}
	}
	die();
}


}

Articlefeedback::setup();
