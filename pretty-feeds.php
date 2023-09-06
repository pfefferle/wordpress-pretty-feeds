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
