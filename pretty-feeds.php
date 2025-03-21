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
 * Include XSL-Stylesheets
 *
 * @return void
 */
function feed_stylesheet( $feed ) {
	if ( 'rss2' === $feed ) {
		printf( "\n" . '<?xml-stylesheet href="%s" type="text/xsl" media="screen" ?>' . "\n", esc_url( plugin_dir_url( __FILE__ ) . 'pretty-feed-v4.xsl' ) );
	}

	if ( 'atom' === $feed ) {
		printf( "\n" . '<?xml-stylesheet href="%s" type="text/xsl" media="screen" ?>' . "\n", esc_url( plugin_dir_url( __FILE__ ) . 'pretty-feed-v4.xsl' ) );
	}
}
add_action( 'rss_tag_pre', __NAMESPACE__ . '\feed_stylesheet' );

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
		'Thanks for reading this post via RSS. RSS is great, and you’re great for using it. ♥️',
		'Congratulations on being an RSS reader! You are part of an elite group of people who know how to stay updated in style.',
		'Hey there, RSS reader! You’re one of a special few, choosing this old-school yet awesome way of staying informed. Kudos!',
		'You are a true RSS aficionado! While others are drowning in social media noise, you enjoy the simplicity and control of RSS. Congrats!',
		'RSS readers like you are the unsung heroes of the internet. Keep up the good work!',
		'You are a master of efficiency! By using RSS, you save time and avoid distractions. 👏',
		'RSS readers like you are the secret sauce of the internet. Keep rocking and staying informed!',
		'Hey there, RSS reader! You’re cool. Keep being awesome! 😎',
	);

	$welcome_message = $welcome_messages[ wp_rand( 0, count( $welcome_messages ) - 1 ) ];

	return sprintf(
		'%1$s<p>%2$s</p>',
		$content,
		$welcome_message
	);
}
add_filter( 'the_content_feed', __NAMESPACE__ . '\welcome_rss_readers' );
