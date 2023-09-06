<?xml version="1.0" encoding="utf-8"?>
<!--

# Pretty Feed

Styles an RSS/Atom feed, making it friendly for humans viewers, and adds a link
to aboutfeeds.com for new user onboarding. See it in action:

   https://interconnected.org/home/feed


## How to use

1. Download this XML stylesheet from the following URL and host it on your own
   domain (this is a limitation of XSL in browsers):

   https://github.com/genmon/aboutfeeds/blob/main/tools/pretty-feed-v3.xsl

2. Include the XSL at the top of the RSS/Atom feed, like:

```
<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet href="/PATH-TO-YOUR-STYLES/pretty-feed-v3.xsl" type="text/xsl"?>
```

3. Serve the feed with the following HTTP headers:

```
Content-Type: application/xml; charset=utf-8  # not application/rss+xml
x-content-type-options: nosniff
```

(These headers are required to style feeds for users with Safari on iOS/Mac.)



## Limitations

- Styling the feed *prevents* the browser from automatically opening a
  newsreader application. This is a trade off, but it's a benefit to new users
  who won't have a newsreader installed, and they are saved from seeing or
  downloaded obscure XML content. For existing newsreader users, they will know
  to copy-and-paste the feed URL, and they get the benefit of an in-browser feed
  preview.
- Feed styling, for all browsers, is only available to site owners who control
  their own platform. The need to add both XML and HTTP headers makes this a
  limited solution.


## Credits

pretty-feed is based on work by lepture.com:

   https://lepture.com/en/2019/rss-style-with-xsl

and is maintained by aboutfeeds.com:

   https://github.com/genmon/aboutfeeds

This specific is a modified version of Dave Rupert's feed:

   https://daverupert.com/feed/

## Feedback

This file is in BETA. Please test and contribute to the discussion:

   https://github.com/pfefferle/wordpress-pretty-feeds/issues

-->
<xsl:stylesheet version="3.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/"
				xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd">
	<xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
	<xsl:template match="/">
		<html xmlns="http://www.w3.org/1999/xhtml">
			<head>
				<title><xsl:value-of select="/rss/channel/title"/> Web Feed</title>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
				<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"/>
				<style type="text/css">
					* { box-sizing: border-box; }
					svg { max-width: 100%; }
					body { --gap: 5vw; margin: 0; font-family: system-ui; line-height: 1.7; }
					h1,h2,h3 { margin-block-start: 0; margin-block-end: 0; }
					.pb-5 { padding-bottom: calc(var(--gap) / 2); }
					.meta { color: #676767; }
					.container {
						display: grid;
						gap: var(--gap);
						max-width: 46rem;
						width: 95%;
						margin: auto;
					}
					.intro {
						background-color: #fff5b1;
						margin-block-end: var(--gap);
						padding-block: calc(var(--gap) / 2);
					}
					.intro .container {
						gap: 1rem;
						grid-template-columns:  4fr 2fr;
						align-items: top;
					}
					@media (min-width: 40rem) {
						.intro .container {
						grid-template-columns:  4fr 1fr;
						align-items: center;
						}
					}
					.recent {
						padding-block-end: var(--gap);
					}
					header img {
						width: 5em;
						border-radius: 20%;
					}
				</style>
			</head>
			<body>
				<nav class="intro">
					<div class="container">
						<div>
							<p><strong>Yahaha, you found me!</strong> This is my RSS feed. You can <strong>Subscribe</strong> by copy-pasting the URL into your RSS feed reader or by clicking <a>
									<xsl:attribute name="href">
										<xsl:value-of select="concat('feed:', //atom:link[@rel='self']/@href)"/>
									</xsl:attribute>
									here</a>...
							</p>
							<p><small>
								...or subscribe via
								<input type="button" onclick="subtome()" value="SubToMe" />
							</small></p>
							<p><small>
								Visit <a href="https://aboutfeeds.com">About Feeds</a> to get started with newsreaders and subscribing. It’s free.
							</small></p>
						</div>
						<svg xmlns="http://www.w3.org/2000/svg" id="RSSicon" viewBox="0 0 8 8" width="256" height="256">
							<title>RSS feed icon</title>
							<style type="text/css">
								.button {stroke: none; fill: orange;}
								.symbol {stroke: none; fill: white;}
							</style>
							<rect class="button" width="8" height="8" rx="1.5"/>
							<circle class="symbol" cx="2" cy="6" r="1"/>
							<path class="symbol" d="m 1,4 a 3,3 0 0 1 3,3 h 1 a 4,4 0 0 0 -4,-4 z"/>
							<path class="symbol" d="m 1,2 a 5,5 0 0 1 5,5 h 1 a 6,6 0 0 0 -6,-6 z"/>
						</svg>
					</div>
				</nav>

				<div class="container">
					<header>
						<!-- RSS -->
						<xsl:if test="/rss/channel">
							<xsl:if test="/rss/channel/image">
								<img>
									<xsl:attribute name="src">
										<xsl:value-of select="/rss/channel/image/url"/>
									</xsl:attribute>
									<xsl:attribute name="title">
										<xsl:value-of select="/rss/channel/title"/>
									</xsl:attribute>
								</img>
							</xsl:if>
							<h1>
								<xsl:value-of select="/rss/channel/title"/>
							</h1>
							<p>
								<xsl:value-of select="/rss/channel/description"/>
							</p>
							<a class="head_link" target="_blank">
							<xsl:attribute name="href">
								<xsl:value-of select="/rss/channel/link"/>
							</xsl:attribute>
							Visit Website &#x2192;
							</a>
						</xsl:if>

						<!-- Atom -->
						<xsl:if test="/atom:feed">
						<header>
							<xsl:if test="/atom:feed/atom:icon">
								<img>
									<xsl:attribute name="src">
										<xsl:value-of select="/atom:feed/atom:icon"/>
									</xsl:attribute>
									<xsl:attribute name="title">
										<xsl:value-of select="/atom:feed/atom:title"/>
									</xsl:attribute>
								</img>
							</xsl:if>
							<h1>
								<xsl:value-of select="/atom:feed/atom:title"/>
							</h1>
							<p>
								<xsl:value-of select="/atom:feed/atom:subtitle"/>
							</p>
							<a class="head_link" target="_blank">
							<xsl:attribute name="href">
								<xsl:value-of select="/atom:feed/atom:link[@rel='alternate']/@href"/>
							</xsl:attribute>
							Visit Website &#x2192;
							</a>
						</header>
						</xsl:if>
					</header>

					<section class="recent">
						<h2>Recent Items</h2>

						<!-- RSS -->
						<xsl:for-each select="/rss/channel/item">
						<div class="pb-5">
							<h3>
							<a target="_blank">
								<xsl:attribute name="href">
									<xsl:value-of select="link"/>
								</xsl:attribute>
								<xsl:value-of select="title"/>
							</a>
							</h3>
							<small class="meta">
							Published: <xsl:value-of select="pubDate" />
							</small>
						</div>
						</xsl:for-each>

						<!-- Atom -->
						<xsl:for-each select="/atom:feed/atom:entry">
						<div class="pb-5">
							<h3>
							<a target="_blank">
								<xsl:attribute name="href">
									<xsl:value-of select="atom:link[@rel='alternate']/@href"/>
								</xsl:attribute>
								<xsl:value-of select="atom:title"/>
							</a>
							</h3>
							<small class="meta">
							Published: <xsl:value-of select="atom:published" />
							</small>
						</div>
						</xsl:for-each>
					</section>
				</div>
				<script>
					function subtome() {
						(function(){var z=document.createElement('script');z.src='https://www.subtome.com/load.js';document.body.appendChild(z);})()
					}
				</script>
			</body>
		</html>
	</xsl:template>
</xsl:stylesheet>
