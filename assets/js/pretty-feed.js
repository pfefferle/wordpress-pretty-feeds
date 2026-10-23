/**
 * Shows an RSS or Atom feed as a page in the browser.
 *
 * Browsers run this script when they get the feed as XML with an XHTML
 * script element in it. It replaces the XML with HTML, see
 * https://jakearchibald.com/2025/making-xml-human-readable-without-xslt/
 *
 * Translations are loaded the WordPress way: the JSON of the script is
 * passed in a script element with the id "pretty-feed-translations".
 */
( function () {
	const XHTML = 'http://www.w3.org/1999/xhtml';
	const ATOM = 'http://www.w3.org/2005/Atom';
	const DOMAIN = 'prettyfeeds';
	const i18n = window.wp && window.wp.i18n;
	const __ = i18n ? i18n.__ : ( text ) => text;
	const sprintf = i18n ? i18n.sprintf : ( text, ...args ) => text.replace( /%(?:(\d)\$)?s/g, ( match, index ) => args[ index ? index - 1 : 0 ] ?? '' );
	const feed = document.documentElement;
	let lang = '';

	// Before anything is translated.
	const translations = document.getElementById( 'pretty-feed-translations' );
	if ( i18n && translations ) {
		try {
			const data = JSON.parse( translations.textContent );
			const localeData = data.locale_data[ DOMAIN ] || data.locale_data.messages;
			localeData[ '' ].domain = DOMAIN;
			lang = ( localeData[ '' ].lang || '' ).replace( '_', '-' );
			i18n.setLocaleData( localeData, DOMAIN );
		} catch ( error ) {
			// Untranslated then.
		}
	}

	/**
	 * Creates an HTML element. Text is added as text, never as markup.
	 *
	 * @param {string}           name       The tag name.
	 * @param {Object}           attributes The attributes.
	 * @param {...(Node|string)} children   The children.
	 * @return {Element} The element.
	 */
	const el = ( name, attributes = {}, ...children ) => {
		const element = document.createElementNS( XHTML, name );

		for ( const [ key, value ] of Object.entries( attributes ) ) {
			element.setAttribute( key, value );
		}

		element.append( ...children.filter( ( child ) => null !== child ) );

		return element;
	};

	/**
	 * Parses markup of the plugin itself, e.g. a translation with a link.
	 *
	 * @param {string} html The markup.
	 * @return {Node[]} The nodes.
	 */
	const markup = ( html ) => [ ...new DOMParser().parseFromString( html, 'text/html' ).body.childNodes ];

	/**
	 * Returns a URL if it is a web address, e.g. not "javascript:".
	 *
	 * @param {string|null} url The URL.
	 * @return {string|null} The absolute URL, or null.
	 */
	const webUrl = ( url ) => {
		if ( ! url ) {
			return null;
		}

		try {
			const parsed = new URL( url, window.location.href );
			return [ 'http:', 'https:' ].includes( parsed.protocol ) ? parsed.href : null;
		} catch ( error ) {
			return null;
		}
	};

	/**
	 * A link that opens in a new tab, without href if the URL is not a web address.
	 *
	 * @param {Object}           attributes The attributes.
	 * @param {string}           url        The URL.
	 * @param {...(Node|string)} children   The children.
	 * @return {Element} The link.
	 */
	const link = ( attributes, url, ...children ) => {
		const href = webUrl( url );
		return el( 'a', { ...attributes, target: '_blank', ...( href ? { href } : {} ) }, ...children );
	};

	/**
	 * The child elements with a name.
	 *
	 * @param {Element|undefined} parent    The parent element.
	 * @param {string}            name      The local name.
	 * @param {string|null}       namespace The namespace, null for RSS.
	 * @return {Element[]} The elements.
	 */
	const children = ( parent, name, namespace = null ) =>
		parent ? [ ...parent.children ].filter( ( child ) => name === child.localName && namespace === child.namespaceURI ) : [];

	/**
	 * The text of the first child element with a name.
	 *
	 * @param {Element|undefined} parent    The parent element.
	 * @param {string}            name      The local name.
	 * @param {string|null}       namespace The namespace, null for RSS.
	 * @return {string} The text.
	 */
	const value = ( parent, name, namespace = null ) => children( parent, name, namespace )[ 0 ]?.textContent ?? '';

	/**
	 * The href of the first Atom link with a rel.
	 *
	 * @param {Element} parent The parent element.
	 * @param {string}  rel    The rel.
	 * @return {string} The href.
	 */
	const href = ( parent, rel ) =>
		children( parent, 'link', ATOM ).find( ( atomLink ) => rel === atomLink.getAttribute( 'rel' ) )?.getAttribute( 'href' ) ?? '';

	// Chrome ignores defer in XML documents and runs the script before the
	// rest of the document is parsed.
	document.addEventListener( 'DOMContentLoaded', () => {
		const channel = children( feed, 'channel' )[ 0 ];
		const info = channel
			? {
				title: value( channel, 'title' ),
				description: value( channel, 'description' ),
				link: value( channel, 'link' ),
				image: value( children( channel, 'image' )[ 0 ], 'url' ),
				items: children( channel, 'item' ).map( ( item ) => ( {
					title: value( item, 'title' ),
					link: value( item, 'link' ),
					date: value( item, 'pubDate' ),
				} ) ),
			}
			: {
				title: value( feed, 'title', ATOM ),
				description: value( feed, 'subtitle', ATOM ),
				link: href( feed, 'alternate' ),
				image: value( feed, 'icon', ATOM ),
				items: children( feed, 'entry', ATOM ).map( ( entry ) => ( {
					title: value( entry, 'title', ATOM ),
					link: href( entry, 'alternate' ),
					date: value( entry, 'published', ATOM ),
				} ) ),
			};
		const self = [ ...document.getElementsByTagNameNS( ATOM, 'link' ) ].find( ( atomLink ) => 'self' === atomLink.getAttribute( 'rel' ) );
		const selfUrl = webUrl( self?.getAttribute( 'href' ) ?? window.location.href );
		const image = webUrl( info.image );

		const style = `
			:root { color-scheme: light dark; --accent: #9a5b00; --intro: rgb(255 214 0 / 30%); --muted: #676767; }
			@media (prefers-color-scheme: dark) { :root { --accent: #f0b429; --intro: rgb(255 255 255 / 8%); --muted: #a7aaad; } }
			* { box-sizing: border-box; }
			svg { max-width: 100%; }
			a { color: var(--accent); }
			body { --gap: 5vw; margin: 0; font-family: system-ui; line-height: 1.7; }
			h1,h2,h3 { margin-block-start: 0; margin-block-end: 0; }
			.pb-5 { padding-bottom: calc(var(--gap) / 2); }
			.meta { color: var(--muted); }
			.container {
				display: grid;
				gap: var(--gap);
				max-width: 46rem;
				width: 95%;
				margin: auto;
			}
			.intro {
				background-color: var(--intro);
				margin-block-end: var(--gap);
				padding-block: calc(var(--gap) / 2);
			}
			.intro .container {
				gap: 1rem;
				grid-template-columns: 1fr auto;
				align-items: center;
			}
			.intro svg { width: 3rem; height: auto; justify-self: end; }
			@media (min-width: 40rem) {
				.intro .container {
				grid-template-columns:  4fr 1fr;
				}
				.intro svg { width: 5rem; }
			}
			.recent {
				padding-block-end: var(--gap);
			}
			header img {
				width: 5em;
				border-radius: 20%;
			}
		`;

		const icon = markup( `
			<svg xmlns="http://www.w3.org/2000/svg" id="RSSicon" viewBox="0 0 8 8" width="256" height="256">
				<title></title>
				<style type="text/css">
					.button {stroke: none; fill: orange;}
					.symbol {stroke: none; fill: white;}
				</style>
				<rect class="button" width="8" height="8" rx="1.5"/>
				<circle class="symbol" cx="2" cy="6" r="1"/>
				<path class="symbol" d="m 1,4 a 3,3 0 0 1 3,3 h 1 a 4,4 0 0 0 -4,-4 z"/>
				<path class="symbol" d="m 1,2 a 5,5 0 0 1 5,5 h 1 a 6,6 0 0 0 -6,-6 z"/>
			</svg>
		` ).find( ( node ) => 'svg' === node.localName );
		icon.querySelector( 'title' ).textContent = __( 'RSS feed icon', 'prettyfeeds' );

		const html = el(
			'html',
			lang ? { lang } : {},
			el(
				'head',
				{},
				el( 'title', {}, info.title + ' ' + __( 'Web Feed', 'prettyfeeds' ) ),
				el( 'meta', { name: 'viewport', content: 'width=device-width, initial-scale=1, maximum-scale=1' } ),
				el( 'style', { type: 'text/css' }, style )
			),
			el(
				'body',
				{},
				el(
					'nav',
					{ class: 'intro' },
					el(
						'div',
						{ class: 'container' },
						el(
							'div',
							{},
							el(
								'p',
								{},
								...markup(
									sprintf(
										/* translators: %1$s: opening link tag to subscribe to the feed; %2$s: closing link tag. */
										__( '<strong>Look at you, following along the old-school way.</strong> Subscribe by copy-pasting the URL into your feed reader, or by clicking %1$shere%2$s...', 'prettyfeeds' ),
										'<a class="subscribe">',
										'</a>'
									)
								)
							),
							el(
								'p',
								{},
								el(
									'small',
									{},
									...markup(
										sprintf(
											/* translators: %1$s: opening link tag to aboutfeeds.com; %2$s: closing link tag. */
											__( 'Visit %1$sAbout Feeds%2$s to get started with newsreaders and subscribing. It’s free.', 'prettyfeeds' ),
											'<a href="https://aboutfeeds.com">',
											'</a>'
										)
									)
								)
							)
						),
						icon
					)
				),
				el(
					'div',
					{ class: 'container' },
					el(
						'header',
						{},
						image ? el( 'img', { src: image, title: info.title } ) : null,
						el( 'h1', {}, info.title ),
						el( 'p', {}, info.description ),
						link( { class: 'head_link' }, info.link, __( 'Visit Website', 'prettyfeeds' ) + ' →' )
					),
					el(
						'section',
						{ class: 'recent' },
						el( 'h2', {}, __( 'Recent Items', 'prettyfeeds' ) ),
						...info.items.map( ( item ) =>
							el(
								'div',
								{ class: 'pb-5' },
								el( 'h3', {}, link( {}, item.link, item.title ) ),
								el( 'small', { class: 'meta' }, __( 'Published:', 'prettyfeeds' ) + ' ' + item.date )
							)
						)
					)
				)
			)
		);

		const subscribe = html.querySelector( '.subscribe' );
		if ( subscribe && selfUrl ) {
			subscribe.setAttribute( 'href', 'feed:' + selfUrl );
		}

		feed.replaceWith( html );
	} );
} )();
