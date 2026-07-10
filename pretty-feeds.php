<?php
/**
 * Plugin Name: Pretty Feeds
 * Plugin URI: https://github.com/pfefferle/wordpress-pretty-feeds
 * Description: Styles an RSS/Atom feed, making it friendly for humans viewers, and adds a link to aboutfeeds.com for new user onboarding.
 * Author: Matthias Pfefferle
 * Author URI: https://notiz.blog/
 * Version: 1.0.0
 * License: GPL-2.0-or-later
 * License URI: https://opensource.org/license/gpl-2-0/
 * Text Domain: prettyfeeds
 * Update URI: https://github.com/pfefferle/wordpress-pretty-feeds
 */

namespace Pretty_Feeds;

/**
 * The stylesheet "version".
 *
 * Used as the value of the stylesheet query argument so that browsers re-fetch
 * the stylesheet when the plugin (and therefore the markup or translations)
 * changes.
 *
 * @var string
 */
const STYLESHEET_VERSION = '4';

/**
 * Load the plugin translations.
 *
 * @return void
 */
function load_textdomain() {
	load_plugin_textdomain( 'prettyfeeds', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', __NAMESPACE__ . '\load_textdomain' );

/**
 * Return the URL the feed stylesheet is served from.
 *
 * The stylesheet is served through WordPress (instead of as a static file) so
 * that its labels can be translated. See {@see render_stylesheet()}.
 *
 * @return string The stylesheet URL.
 */
function stylesheet_url() {
	return add_query_arg( 'pretty-feed-stylesheet', STYLESHEET_VERSION, home_url( '/' ) );
}

/**
 * Include the XSL stylesheet processing instruction in the feed.
 *
 * @param string $feed The feed type.
 *
 * @return void
 */
function feed_stylesheet( $feed ) {
	if ( ! in_array( $feed, array( 'rss2', 'atom' ), true ) ) {
		return;
	}

	printf(
		"\n" . '<?xml-stylesheet href="%s" type="text/xsl" media="screen" ?>' . "\n",
		esc_url( stylesheet_url() )
	);
}
add_action( 'rss_tag_pre', __NAMESPACE__ . '\feed_stylesheet' );

/**
 * Serve the feed stylesheet.
 *
 * The stylesheet cannot be a static `.xsl` file because its labels have to be
 * run through gettext to be translatable. It is therefore rendered by PHP and
 * served from the URL returned by {@see stylesheet_url()}.
 *
 * @return void
 */
function render_stylesheet() {
	if ( ! isset( $_GET['pretty-feed-stylesheet'] ) ) {
		return;
	}

	header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ) );
	// Required to style feeds for users with Safari on iOS/Mac.
	header( 'X-Content-Type-Options: nosniff' );

	require plugin_dir_path( __FILE__ ) . 'pretty-feed-v4.php';

	exit;
}
add_action( 'parse_request', __NAMESPACE__ . '\render_stylesheet' );

/**
 * Change Content-Type for feeds to enable XSL-Transformation
 *
 * @param string $content_type The current Conten-Type
 * @param string $type         The Feed-Type
 *
 * @return string The new Content-Type
 */
function feed_content_type( $content_type, $type ) {
	if ( is_feed() && in_array( $type, array( 'rss2', 'atom' ), true ) ) {
		return 'text/xml';
	}

	return $content_type;
}
add_filter( 'feed_content_type', __NAMESPACE__ . '\feed_content_type', 99, 2 );

/**
 * Display a nice welcoming message to folks reading posts via RSS.
 *
 * Kudos Kev Quirk for the idea!
 * Even more kudos to Jeremy Herve for the original code!
 *
 * @see https://herve.bzh/thanking-my-rss-readers/
 *
 * @param string $content The current post content.
 *
 * @return string
 */
function welcome_rss_readers( $content ) {
	$welcome_messages = array(
		__( 'Thanks for reading this post via RSS. RSS is great, and you’re great for using it. ♥️', 'prettyfeeds' ),
		__( 'Congratulations on being an RSS reader! You are part of an elite group of people who know how to stay updated in style.', 'prettyfeeds' ),
		__( 'Hey there, RSS reader! You’re one of a special few, choosing this old-school yet awesome way of staying informed. Kudos!', 'prettyfeeds' ),
		__( 'You are a true RSS aficionado! While others are drowning in social media noise, you enjoy the simplicity and control of RSS. Congrats!', 'prettyfeeds' ),
		__( 'RSS readers like you are the unsung heroes of the internet. Keep up the good work!', 'prettyfeeds' ),
		__( 'You are a master of efficiency! By using RSS, you save time and avoid distractions. 👏', 'prettyfeeds' ),
		__( 'RSS readers like you are the secret sauce of the internet. Keep rocking and staying informed!', 'prettyfeeds' ),
		__( 'Hey there, RSS reader! You’re cool. Keep being awesome! 😎', 'prettyfeeds' ),
	);

	$welcome_message = $welcome_messages[ wp_rand( 0, count( $welcome_messages ) - 1 ) ];

	return sprintf(
		'%1$s<p>%2$s</p>',
		$content,
		$welcome_message
	);
}
add_filter( 'the_content_feed', __NAMESPACE__ . '\welcome_rss_readers' );
