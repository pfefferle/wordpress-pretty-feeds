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
 * The plugin version.
 *
 * @var string
 */
const VERSION = '1.0.0';

/**
 * The handle of the script that shows the feed in the browser.
 *
 * @var string
 */
const SCRIPT = 'pretty-feed';

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
 * Include the script that shows the feed as a page in the browser.
 *
 * Translations are loaded the WordPress way: the JSON of the script is
 * passed in a script element with the id "pretty-feed-translations".
 *
 * @return void
 */
function feed_script() {
	$urls         = get_script_urls();
	$translations = load_script_textdomain( SCRIPT, 'prettyfeeds', plugin_dir_path( __FILE__ ) . 'languages' );

	if ( $translations ) {
		printf(
			"\t" . '<script xmlns="http://www.w3.org/1999/xhtml" type="application/json" id="pretty-feed-translations">%s</script>' . "\n",
			htmlspecialchars( $translations, ENT_XML1 | ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped for XML.
		);
	}

	foreach ( $urls as $src ) {
		printf(
			"\t" . '<script xmlns="http://www.w3.org/1999/xhtml" src="%s" defer=""/>' . "\n", // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- A feed has no wp_head().
			htmlspecialchars( $src, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped for XML.
		);
	}
}
add_action( 'rss2_head', __NAMESPACE__ . '\feed_script' );
add_action( 'atom_head', __NAMESPACE__ . '\feed_script' );

/**
 * The URLs of the script that shows the feed in the browser and of its
 * dependencies, in load order.
 *
 * The script is registered like any other, with wp-i18n for the
 * translations. A feed has no wp_head(), so the URLs are built from the
 * registry like WP_Scripts does.
 *
 * @return string[] The script URLs.
 */
function get_script_urls() {
	$scripts = wp_scripts();

	if ( ! $scripts->query( SCRIPT ) ) {
		wp_register_script(
			SCRIPT,
			plugins_url( 'assets/js/pretty-feed.js', __FILE__ ),
			array( 'wp-i18n' ),
			VERSION,
			array( 'strategy' => 'defer' )
		);
	}

	$scripts->all_deps( SCRIPT );
	$handles        = $scripts->to_do;
	$scripts->to_do = array();
	$urls           = array();

	foreach ( $handles as $handle ) {
		$script = $scripts->registered[ $handle ];

		// Aliases like "jquery" have no source of their own.
		if ( ! $script->src ) {
			continue;
		}

		$src = $script->src;

		if ( ! preg_match( '|^(https?:)?//|', $src ) && ! ( $scripts->content_url && 0 === strpos( $src, $scripts->content_url ) ) ) {
			$src = $scripts->base_url . $src;
		}

		if ( null !== $script->ver ) {
			$src = add_query_arg( 'ver', $script->ver ? $script->ver : $scripts->default_version, $src );
		}

		/** This filter is documented in wp-includes/class-wp-scripts.php */
		$urls[] = (string) apply_filters( 'script_loader_src', $src, $handle );
	}

	return $urls;
}

/**
 * Change the Content-Type of feeds, so that browsers show them and run the script.
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
		'%1$s<hr /><p>%2$s</p>',
		$content,
		$welcome_message
	);
}
add_filter( 'the_content_feed', __NAMESPACE__ . '\welcome_rss_readers' );
