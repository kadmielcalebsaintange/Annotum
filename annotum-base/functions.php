<?php

/**
 * @package anno
 * This file is part of the Annotum theme for WordPress
 * Built on the Carrington theme framework <http://carringtontheme.com>
 *
 * Copyright 2008-2010 Crowd Favorite, Ltd. All rights reserved. <http://crowdfavorite.com>
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 */
if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { die(); }

define('CFCT_DEBUG', false);
define('CFCT_PATH', trailingslashit(TEMPLATEPATH));
define('ANNO_VER', '1.1');

include_once(CFCT_PATH.'carrington-core/carrington.php');
include_once(CFCT_PATH.'functions/Anno_Keeper.php');
include_once(CFCT_PATH.'functions/article-post-type.php');
include_once(CFCT_PATH.'functions/appendix-repeater.php');
include_once(CFCT_PATH.'functions/taxonomies.php');
include_once(CFCT_PATH.'functions/capabilities.php');
include_once(CFCT_PATH.'functions/featured-articles.php');
include_once(CFCT_PATH.'functions/template.php');
include_once(CFCT_PATH.'functions/widgets.php');
include_once(CFCT_PATH.'functions/profile.php');
include_once(CFCT_PATH.'functions/tinymce.php');
include_once(CFCT_PATH.'functions/tinymce-uploader.php');
include_once(CFCT_PATH.'functions/tinymce-upload/image-popup.php');
include_once(CFCT_PATH.'functions/phpquery/phpquery.php');
include_once(CFCT_PATH.'functions/anno-xml-download.php');
include_once(CFCT_PATH.'plugins/load.php');

function anno_setup() {
	$path = trailingslashit(TEMPLATEPATH);

	// i18n support
	load_theme_textdomain('anno', $path . 'languages');
	$locale = get_locale();
	$locale_file = $path . '/languages/' . $locale . '.php';
	if ( is_readable( $locale_file ) ) {
		require_once( $locale_file );
	}
	
	add_theme_support('automatic-feed-links');
	add_theme_support('post-thumbnails', array( 'article', 'post' ) );
	add_image_size( 'post-excerpt', 140, 120, true);
	add_image_size( 'post-teaser', 100, 79, true);
	add_image_size( 'featured', 270, 230, true);
	add_image_size( 'header', 500, 500, false);
	
	$header_image = Anno_Keeper::retrieve('header_image');
	$header_image->add_custom_image_header();
	
	$menus = array(
		'main' => 'Main Menu (Header)',
		'secondary' => 'Secondary Menu (Header)',
		'footer' => 'Footer Menu',
	);
	register_nav_menus($menus);
	
	$sidebar_defaults = array(
		'before_widget' => '<aside id="%1$s" class="widget clearfix %2$s">',
		'after_widget' => '</aside>',
		'before_title' => '<h1 class="title">',
		'after_title' => '</h1>'
	);
	register_sidebar(array_merge($sidebar_defaults, array(
		'name' => __('Default Sidebar', 'anno'),
		'id' => 'default'
	)));
	register_sidebar(array_merge($sidebar_defaults, array(
		'name' => __('Page Sidebar', 'anno'),
		'id' => 'sidebar-page',
		'description' => __('This sidebar will be shown on Pages.', 'anno')
	)));
	register_sidebar(array_merge($sidebar_defaults, array(
		'name' => __('Article Sidebar', 'anno'),
		'id' => 'sidebar-article',
		'description' => __('This sidebar will be shown single Articles.', 'anno')
	)));
	register_sidebar(array_merge($sidebar_defaults, array(
		'name' => __('Masthead Teasers', 'anno'),
		'id' => 'masthead',
		'description' => __('Display items on the home page masthead.'),
		'before_widget' => '<aside id="%1$s" class="teaser clearfix %2$s">'
	)));
	// Customize the Carrington Core Admin Settings Form Title
	add_filter('cfct_admin_settings_menu', 'anno_admin_settings_menu_form_title');
	add_filter('cfct_admin_settings_form_title', 'anno_admin_settings_menu_form_title');
}
add_action('after_setup_theme', 'anno_setup');

// Filter to customize the Carrington Core Admin Settings Form Title
function anno_admin_settings_menu_form_title() {
	return _x('Annotum Settings', 'menu and options heading', 'anno');
}

/**
 * Add theme CSS, JS here. Everything should run through the enqueue system so that
 * child themes/plugins have access to override whatever they need to.
 * Run at 'wp' hook so we have access to conditional functions, like is_single(), etc.
 */
function anno_assets() {
	if (!is_admin()) {
		cfct_template_file('assets', 'load');
	}
}
add_action('wp', 'anno_assets');

/**
 * Enqueue our custom JS on the edit post page (currently used
 * for TinyMCE trigger usage during article save)
 *
 * @return void
 */
function anno_edit_post_assets() {
	wp_enqueue_script('anno-article-admin', get_template_directory_uri().'/js/article-admin.js');
}
add_action('load-post.php', 'anno_edit_post_assets');
add_action('load-post-new.php', 'anno_edit_post_assets');

/**
 * Bring in our main.css on the dashboard page.  Should be cached
 * already so it shouldn't be a big thing, even though we only need
 * one definition from it.
 *
 * @return void
 */
function anno_dashboard_assets() {
	wp_enqueue_style('anno', trailingslashit(get_bloginfo('template_directory')) .'assets/main/css/main.css', array(), ANNO_VER);
}
add_action('load-index.php', 'anno_dashboard_assets');

/**
 * Register custom widgets extended from WP_Widget
 */
function anno_widgets_init() {
	include_once(CFCT_PATH . 'functions/Anno_Widget_Recently.php');
	register_widget('Anno_Widget_Recently');
	register_widget('WP_Widget_Solvitor_Ad');
}
add_action('widgets_init', 'anno_widgets_init');

// Adding favicon, each theme has it's own which we get with stylesheet directory
function anno_favicon() {
	echo '<link rel="shortcut icon" href="'.get_bloginfo('stylesheet_directory').'/assets/main/img/favicon.ico" />';
}
add_action('wp_head', 'anno_favicon');

/*
 * Outputs a few extra semantic tags in the <head> area.
 * Hook into 'wp_head' action.
 */
function anno_head_extra() {
	echo '<link rel="pingback" href="'.get_bloginfo('pingback_url').'" />'."\n";
	$args = array(
		'type' => 'monthly',
		'format' => 'link'
	);
	wp_get_archives($args);
}
add_action('wp_head', 'anno_head_extra');

/**
 * Filter the default menu arguments
 */
function anno_wp_nav_menu_args($args) {
	$args['fallback_cb'] = null;
	if ($args['container'] == 'div') {
		$args['container'] = 'nav';
	}
	if ($args['depth'] == 0) {
		$args['depth'] = 2;
	}
	if ($args['menu_class'] == 'menu') {
		$args['menu_class'] = 'nav';
	}
	
	return $args;
}
add_filter('wp_nav_menu_args', 'anno_wp_nav_menu_args');

/**
 * Filter the post class to add a .has-featured-image class when featured image
 * is present.
 * @return array $classes array of post classes
 */
function anno_post_class($classes, $class) {
	$has_img = 'has-featured-image';
	
	/* An array of classes that we want to create an additional faux compound class for.
	This lets us avoid having to do something like
	.article-excerpt.has-featured-image, which doesn't work in IE6.
	Instead, we can do .article-excerpt-has-featured-image. While a bit verbose,
	it will nonetheless do the trick. */
	$compoundify = array(
		'article-excerpt'
	);
	
	if (has_post_thumbnail()) {
		$classes[] = $has_img;
		
		foreach ($compoundify as $compound_plz) {
			if (in_array($compound_plz, $classes)) {
				$classes[] = $compound_plz . '-' . $has_img;
			}
		}
	}
	
	return $classes;
}
add_filter('post_class', 'anno_post_class', 10, 2);

function anno_post_category_list($separator) {
	$html = '';
	
	$cat_list = get_the_category_list( $separator);
	if(!empty($cat_list)) {
		$html.=' &middot ' . $cat_list;
	}
	return $html;
	
}

/**
 * Customize comment form defaults
 */
function anno_comment_form_defaults($defaults) {
	$req = get_option( 'require_name_email' );
	$req_attr = ( $req ? ' required' : '' );
	$req_label = ( $req ? '<abbr class="required" title="'.__('Required', 'anno').'">*</abbr>' : '');
	$commenter = wp_get_current_commenter();
	
	$fields = apply_filters('comment_form_default_fields', array(
		'author' => '<p class="row author">' . '<label for="author">' . __('Your Name', 'anno') . $req_label . '</label> <input id="author" class="type-text" name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '"' . $req_attr . '></p>',
		'email' => '<p class="row email"><label for="email">' . __('Email Address', 'anno') . $req_label . '</label> <input id="email" class="type-text" name="email" type="email" value="' . esc_attr(  $commenter['comment_author_email'] ) . '"' . $req_attr . '></p>',
		'url' => '<p class="row url"><label for="url">' . __( 'Website' ) . '</label> <input id="url" class="type-text" name="url" type="url" value="' . esc_attr( $commenter['comment_author_url'] ) . '"></p>'
	));
	
	$new = array(
		'comment_field' => '<p class="row"><label for="comment">' . _x('Comment', 'noun', 'anno') . '</label> <textarea id="comment" name="comment" required></textarea></p>',
		'fields' => $fields,
		'cancel_reply_link' => __('(cancel)', 'anno'),
		'title_reply' => __('Leave a Comment', 'anno'),
		'title_reply_to' => __('Leave a Reply to %s', 'anno'),
		'label_submit' => __('Submit', 'anno'),
		'comment_notes_after' => '',
		'comment_notes_before' => ''
	);
	
	return array_merge($defaults, $new);
}
add_filter('comment_form_defaults', 'anno_comment_form_defaults');

/**
 * Register Theme settings
 */
function anno_settings($settings) {
	unset($settings['cfct']['fields']['login']);
	unset($settings['cfct']['fields']['copyright']);
	unset($settings['cfct']['fields']['credit']);
	unset($settings['cfct']['fields']['about']);
	
	$yn_options = array(
		'1' => __('Yes', 'carrington'),
		'0' => __('No', 'carrington')
	);
	
	$anno_settings_top = array(
		'anno_top' => array(
			'label' => _x('Theme Settings', 'options heading', 'anno'),
			'fields' => array(
				'callout-left-title' => array(
					'type' => 'text',
					'name' => 'anno_callouts[0][title]',
					'label' => _x('Home Page Callout Left Title', 'Label text for settings screen', 'anno'),
					'class' => 'cfct-text-long',
				),
				'callout-left-url' => array(
					'type' => 'text',
					'name' => 'anno_callouts[0][url]',
					'label' => _x('Home Page Callout Left URL', 'Label text for settings screen', 'anno'),
					'class' => 'cfct-text-long',
				),
				'callout_left-content' => array(
					'type' => 'textarea',
					'name' => 'anno_callouts[0][content]',
					'label' => _x('Home Page Callout Left Content', 'Label text for settings screen', 'anno'),
				),
				'callout-right-title' => array(
					'type' => 'text',
					'name' => 'anno_callouts[1][title]',
					'label' => _x('Home Page Callout Right Title', 'Label text for settings screen', 'anno'),
					'class' => 'cfct-text-long',
				),
				'callout-right-url' => array(
					'type' => 'text',
					'name' => 'anno_callouts[1][url]',
					'label' => _x('Home Page Callout Right URL', 'Label text for settings screen', 'anno'),
					'class' => 'cfct-text-long',
				),
				'callout-right-content' => array(
					'type' => 'textarea',
					'name' => 'anno_callouts[1][content]',
					'label' => _x('Home Page Callout Right Content', 'Label text for settings screen', 'anno'),
				),
			),		
		),
	);
	
	$settings = array_merge($anno_settings_top, $settings);
	
	$anno_settings_bottom = array(
		'anno_bottom' => array(
			'label' => '',
			'fields' => array(
				'callout-right-content' => array(
					'type' => 'select',
					'name' => 'anno_home_post_type',
					'label' => _x('Front Page Post Type', 'Label text for settings screen', 'anno'),
					'options' => array(
						'article' => _x('Article', 'post type name for select option', 'anno'),
						'post' => _x('Post', 'post type name for select option', 'anno'),
					),
				),
			),		
		),
		'anno_ga' => array(
			'label' =>  _x('Google Analytics', 'options heading', 'anno'),
			'fields' => array(
				'anno_ga_id' => array(
					'label' => _x('Google Analytics ID', 'options label', 'anno'),
					'label_for' => 'anno-ga-id',
					'name' => 'ga_id',
					'type' => 'text',
					'help' => ' <span class="cfct-help">'._x('ex: UA-123456-12', 'help text for option input', 'anno').'</span>',
				),
			),
		),
		'annowf_settings' => array(
			'label' =>  _x('Workflow Options', 'options heading', 'anno'),
			'fields' => array(
				'workflow' => array(
					'label' => _x('Enable Workflow', 'options label', 'anno'),
					'name' => 'workflow_settings[workflow]',
					'type' => 'radio',
					'options' => $yn_options,
				),
				'author_reviewer' => array(
					'label' => _x('Allow article authors to see reviewers', 'options label', 'anno'),
					'name' => 'workflow_settings[author_reviewer]',
					'type' => 'radio',
					'options' => $yn_options,
				),
				'notifications' => array(
					'label' => _x('Enable workflow notifications', 'options label', 'anno'),
					'name' => 'workflow_settings[notifications]',
					'type' => 'radio',
					'options' => $yn_options,
				),
			),
		),
		'anno_journal' => array(
			'label' =>  _x('Journal Options', 'options heading', 'anno'),
			'fields' => array(
				'journal_name' => array(
					'label' => _x('Journal Name', 'options label', 'anno'),
					'name' => 'journal_name',
					'type' => 'text',
				),
				'journal_abbr' => array(
					'label' => _x('Journal Abbreviation', 'options label', 'anno'),
					'name' => 'journal_abbr',
					'type' => 'text',
				),
				'journal_id' => array(
					'label' => _x('Journal ID', 'options label', 'anno'),
					'name' => 'journal_id',
					'type' => 'text',
				),
				'journal_id_type' => array(
					'label' => _x('Journal ID Type', 'options label', 'anno'),
					'name' => 'journal_id_type',
					'type' => 'text',
				),
				'journal_issn' => array(
					'label' => _x('Journal ISSN', 'options label', 'anno'),
					'name' => 'journal_issn',
					'type' => 'text',
				),
				'publisher_name' => array(
					'label' => _x('Publisher Name', 'options label', 'anno'),
					'name' => 'publisher_name',
					'type' => 'text',
				),
				'publisher_location' => array(
					'label' => _x('Publisher Location', 'options label', 'anno'),
					'name' => 'publisher_location',
					'type' => 'text',
				),
				'publisher_issn' => array(
					'label' => _x('Publisher ISSN', 'options label', 'anno'),
					'name' => 'publisher_issn',
					'type' => 'text',
				),
			),
		),
		'anno_crossref' => array(
			'label' =>  _x('CrossRef Credentials', 'options heading', 'anno'),
			'fields' => array(
				'crossref_login' => array(
					'label' => _x('Login', 'options label', 'anno'),
					'name' => 'crossref_login',
					'type' => 'text',
				),
				'crossref_pass' => array(
					'label' => _x('Password', 'options label', 'anno'),
					'name' => 'crossref_pass',
					'type' => 'password',
				),
				'registrant_code' => array(
					'label' => _x('Registrant Code', 'options label', 'anno'),
					'name' => 'registrant_code',
					'type' => 'text',
				),
			),
		),
	);
	
	$settings = array_merge($settings, $anno_settings_bottom);
	
	return $settings;
}
add_filter('cfct_options', 'anno_settings');

function anno_sanitize_ga_id($new_value) {
	// Enforces a aa-a1234b-0 pattern
	if ($new_value == '' || (bool)preg_match('/^[a-zA-Z]{2,}-[a-zA-Z0-9]{2,}-[a-zA-Z0-9]{1,}$/', $new_value)) {
		$new_value = anno_sanitize_string($new_value);
	}
	else {
		$new_value = cfct_get_option('ga_id');
		if (function_exists('add_settings_error')) {
			add_settings_error('anno_ga_id', 'invalid_ga_id', _x('Invalid Google Analytics ID', 'error message', 'anno'));
		}
	}
	return $new_value;
}

add_action('sanitize_option_anno_ga_id', 'anno_sanitize_ga_id');

/**
 * Check to see if anno_home_post_type is set to article. If so, and we're on the
 * home page, intercept the default query and change the post type to article.
 * Hook @ 'pre_get_posts'.
 */
function anno_modify_home_query($query) {
	if (!is_admin()) {
		global $wp_the_query;
		// Check if this is the main loop (so we don't interfere with nav menus, etc)
		if ($query === $wp_the_query && $query->is_home) {
			$show_post_type = get_option('anno_home_post_type', 'article');
			if ($show_post_type == 'article') {
				$query->set('post_type', 'article');
			}
		}
	}
}
add_action('pre_get_posts', 'anno_modify_home_query');

/**
 * Register default option values
 */ 
function anno_defaults($defaults) {
	$defaults['anno_home_post_type'] = 'article';
	$defaults['anno_workflow_settings'] = array(
		'workflow' => 0,
		'author_reviewer' => 0,
		'notifications' => 0,
	);
	return $defaults;
}
add_filter('cfct_option_defaults', 'anno_defaults');

/**
 * Override the default cfct prefix if we've already name spaced our options.
 */ 
function anno_option_prefix($prefix) {
	return 'anno';
}
add_filter('cfct_option_prefix', 'anno_option_prefix', 10, 2);

/**
 * Determines whether or not an email address is valid
 * 
 * @param string $email email to check
 * @return bool true if it is a valid email, false otherwise
 */ 
function anno_is_valid_email($email) {
	return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Determines whether or not a username is valid
 * 
 * @param string $username username to check
 * @return bool true if it is a valid username, false otherwise
 */ 
function anno_is_valid_username($username) {
	return strcasecmp($username, sanitize_user($username, true) == 0);
}

/**
 * Returns an appropriate link for editing a given user.
 * Based on code found in WP Core 3.2
 * 
 * @param int $user_id The id of the user to get the url for
 * @return string edit user url
 */ 
function anno_edit_user_url($user_id) {
	if ( get_current_user_id() == $user_id ) {
		$edit_url = 'profile.php';
	}
	else {
		$edit_url = admin_url(esc_url( add_query_arg( 'wp_http_referer', urlencode(stripslashes($_SERVER['REQUEST_URI'])), "user-edit.php?user_id=$user_id" )));
	}
	return $edit_url;
	
}

/**
 * Function to limit front-end display of comments. 
 * Wrap this filter around comments_template();
 * 
 * @todo Update to WP_Comment_Query filter when WP updates core to use non-hardcoded queries.
 */
function anno_internal_comments_query($query) {
	$query = str_replace('WHERE', 'WHERE comment_type NOT IN (\'article_general\', \'article_review\') AND', $query);
	return $query;
}
add_filter('comment_feed_where', 'anno_internal_comments_query');


/**
 * Output Google Analytics Code if a GA ID is present
 */
function anno_ga_js() {
	$ga_id = cfct_get_option('ga_id');
	if (!empty($ga_id)) {
?>
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', '<?php echo esc_js($ga_id); ?>']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
<?php
	}
}
if (!is_admin()) {
	add_action('wp_print_scripts', 'anno_ga_js');
}

/**
 * Get a list of authors for a given post
 * 
 * @param int post_id post ID to retrieve users from
 * @return array Array of user IDs
 */ 
function anno_get_authors($post_id) {
	return array_unique(anno_get_post_users($post_id, 'author'));
}

/**
 * Get a list of reviewers for a given post
 * 
 * @param int post_id post ID to retrieve users from
 * @return array Array of user IDs
 */
function anno_get_reviewers($post_id) {
	return array_unique(anno_get_post_users($post_id, 'reviewer'));
}

/**
 * Gets all user of a certain role for a given post 
 *
 * @param int $post_id ID of the post to check
 * @param string $type the type/role of user to get. Accepts meta key or role.
 * @return array Array of reviewers (or empty array if none exist)
 */
function anno_get_post_users($post_id, $type) {
	$type = str_replace('-', '_', $type);
	if ($type == 'reviewer' || $type == 'author') {
		$type = '_anno_'.$type.'_order';
	}

	$users = get_post_meta($post_id, $type, true);

	if (!is_array($users)) {
		return array();
	}
	else {
		return $users;
	}
}

/**
 * Add author to meta co-author for more efficient author archive queries
 */ 
function anno_wp_insert_post($post_id, $post) {
	if (($post->post_type == 'article' || $post->post_type == 'post') && !in_array($post->post_status,  array('inherit', 'auto-draft'))) {
		
		$authors = get_post_meta($post_id, '_anno_author_order', true);
		if (!is_array($authors)) {
			update_post_meta($post_id, '_anno_author_order', array($post->post_author));
			add_post_meta($post_id, '_anno_author_'.$post->post_author, $post->post_author, true);
		}
		else if (!in_array($post->post_author, $authors)) {
			// Make sure the primary author is first
			array_unshift($authors, $post->post_author);
			update_post_meta($post_id, '_anno_author_order', array_unique($authors));
			add_post_meta($post_id, '_anno_author_'.$post->post_author, $post->post_author, true);
		}
	}
}
add_action('wp_insert_post', 'anno_wp_insert_post', 10, 2);

function anno_format_name($prefix, $first, $last, $suffix) {
	$name = $first.' '.$last;
	$name = ($prefix!='')?$prefix.' '.$name:$name;
	$name = ($suffix!='')?$name.', '.$suffix:$name;
	
	return $name;
}

/**
 * Sanitizes a string for insertion into DB
 * @param string $option The string to be sanitized
 * @return string Sanitized string
 */ 
function anno_sanitize_string($option) {
	$option = addslashes($option);
	$option = wp_filter_post_kses($option); // calls stripslashes then addslashes
	$option = stripslashes($option);
	$option = esc_html($option);
	
	return $option;
}

/**
 * Exhaustive search for a post ID.
 * 
 * @return int Post ID
 */ 
function anno_get_post_id() {
	global $post_id;
	
	$local_post_id = 0;
	
	if (empty($post_id)) {
		global $post;
		if (isset($post->ID)) {
			$local_post_id = $post->ID;
		}
		// Used in ajax requests where global aren't populated, attachments, etc...
		else if (isset($_POST['post'])) {
			$local_post_id = $_POST['post'];
		}
		else if (isset($_POST['post_ID'])) {
			$local_post_id = $_POST['post_ID'];
		}
		else if (isset($_POST['post_id'])) {
			$local_post_id = $_POST['post_id'];
		}
		else if (isset($_GET['post'])) {
			$local_post_id = $_GET['post'];
		}
		else if (isset($_GET['post_id'])) {
			$local_post_id = $_GET['post_id'];
		}
	}
	else {
		$local_post_id = $post_id;
	}
	return intval($local_post_id);
}

/**
 * Returns a user's display. First Name Last Name if either exist, otherwise just their login name.
 *
 * @param int|stdObj $user WP user object, or user ID
 * @return string A string displaying a users name.
 */
function anno_user_display($user) {
	if (is_numeric($user)) {
		$user = get_userdata(intval($user));
	}
	
	if (!empty($user->first_name) || !empty($user->last_name)) {
		$display = $user->first_name.' '.$user->last_name;
	}
	else {
		$display = $user->user_login;
	}
	return trim($display);
}

/**
 * Returns a user's email given their user object.
 *
 * @param stdObj $user WP user object
 * @return string The email of the given user
 */
function anno_user_email($user) {
	if (is_numeric($user)) {
		$user = get_userdata(intval($user));
	}
	return $user->user_email;
}

/**
 * Creates a new user, and sends that user an email. Returns a WP Error if the user was unable to be created.
 * 
 * @param string $username Username to create
 * @param string $email Email of user to create
 * @return int|WP_Error ID of new user, or, WP_Error 
 */ 
function anno_invite_contributor($user_login, $user_email, $extra = array()) {

	
	if (!current_user_can('create_users')) {
		wp_die(__('Cheatin&#8217; uh?'));
	}

	$current_user = wp_get_current_user();
	
	// wp_insert_user handles all other errors
	if (!anno_is_valid_email($user_email)) {
		return new WP_Error('invalid_email', _x('Invalid email address', 'error for creating new user', 'anno'));
	}
		
	// Create User
	$user_pass = wp_generate_password();	
	$user_login = esc_sql($user_login);
	$user_email = esc_sql($user_email);
	$role = 'contributor';
	$userdata = compact('user_login', 'user_pass', 'user_email', 'role');

	array_merge($extra, $userdata);

	$user_id = wp_insert_user($userdata);

	$blogname = get_bloginfo('name');
	
	// Send notifiction with PW, Username.	
	if (!is_wp_error($user_id)) {
		$subject = sprintf(_x('You have been invited to join %s', 'email subject %s represents blogname', 'anno'), $blogname);
		$message =  sprintf(_x(
'%s has created a user with your email address for %s.
Please us the following credentials to login and change your password:

Username: %s
Password: %s
%s', 'User creation email body. %s mapping: User who created this new user, blogname, username, password, profile url.', 'anno'),
		$current_user->display_name, $blogname, $user_login, $user_pass, esc_url(admin_url('profile.php')));
		
		wp_mail($user_email, $subject, $message);
	}

	return $user_id;
}


/**
 * Output general stats in dashboard widget
 *
 * @return void
 */
function anno_activity_information() {
	global $current_site, $avail_post_stati;
	$article_post_type = 'article';
	$status_text = array(
		'publish' => array(
			'i18n' 	=> __('Published', 'anno'),
			'class' => 'approved',
		),
		'pending' => array(
			'i18n' 	=> __('Pending', 'anno'),
			'class' => 'waiting',
		),
		'draft' => array(
			'i18n' 	=> __('Draft', 'anno'),
			'class' => 'waiting',
		),
		'trash' => array(
			'i18n' 	=> __('Trash', 'anno'),
			'class' => 'spam',
		),
	);
	
	$num_posts = wp_count_posts( $article_post_type, 'readable' );
	
	// Get the absolute total of $num_posts
	$total_records = array_sum( (array) $num_posts );

	// Subtract post types that are not included in the admin all list.
	foreach (get_post_stati(array('show_in_admin_all_list' => false)) as $state) {
		$total_records -= $num_posts->$state;
	}
	
	// Default
	$detailed_counts = array();
	
	$base_edit_link = add_query_arg(array('post_type' => $article_post_type), admin_url('edit.php'));
	
	// Only build detailed string if user is an editor or administrator
	if (current_user_can('editor') || current_user_can('administrator')) {
		foreach (get_post_stati(array('show_in_admin_status_list' => true), 'objects') as $status) {
			$status_slug = $status->name;
			
			// If this status is in our $status_text array
			if (!in_array($status_slug, array_keys($status_text)))
				continue;
			
			// If we don't have any, don't output...this is debatable
			if ( empty( $num_posts->$status_slug ) )
				continue;
			
			$detailed_counts[] = array(
				'status_slug' 	=> $status->name,
				'i18n' 			=> $status_text[$status_slug]['i18n'],
				'count' 		=> $num_posts->$status_slug,
				'url' 			=> add_query_arg(array('post_status' => $status_slug), $base_edit_link),
				'class' 		=> $status_text[$status_slug]['class'],
			);
		}
	}
	?>
	</table> <!-- /close up the other table -->
	
	<p id="article-dashboard-summary" class="sub"><?php _e('Article Summary', 'anno'); ?></p>
	
	<table>
		<tr>
			<td class="first b"><a href="<?php echo esc_url($base_edit_link); ?>"><?php echo number_format_i18n($total_records); ?></a></td>
			<td class="t"><a href="<?php echo esc_url($base_edit_link); ?>"><?php _e('Articles', 'anno'); ?></a></td>
		</tr>
		<?php
		foreach ($detailed_counts as $details) {
			?>
			<tr>
				<td class="first b"><a href="<?php echo esc_url($details['url']); ?>"><?php echo esc_html(number_format_i18n($details['count'])); ?></a></td>
				<td class="t"><a class="<?php echo esc_attr($details['class']); ?>" href="<?php echo esc_url($details['url']); ?>"><?php echo esc_html($details['i18n']); // already i18n'd ?></a></td>
			</tr>
			<?php
		}
		?>
	<?php 
}
add_action('right_now_content_table_end', 'anno_activity_information');

?>