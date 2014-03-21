<?php
/**
 * Plugin name: Gigaom Quotes Shortcodes
 * Description: Provides consistent shortcodes for pullquotes and blockquotes.
 * Author: Gigaom Network
 * Author URI: http://gigaomnetwork.com/
 *
 * @author Stephen Page <stephen.page@gigaom.com>
 */

/**
 * Singleton - Lazy-loaded
 */
function go_quotes()
{
	global $go_quotes;

	if ( ! isset( $go_quotes ) || ! $go_quotes )
	{
		require_once __DIR__ . '/components/class-go-quotes.php';
		$go_quotes = new GO_Quotes;
	}//end if

	return $go_quotes;
}//end go_quotes

go_quotes();