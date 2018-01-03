# Hide Post

Contributors: emanuelpoletto
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=3S9RPEFELV66Q
Tags: hide, post, simple, privacy, show, visibility
Requires at least: 3.0
Tested up to: 4.9.1
Requires PHP: 5.2.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Hide a post everywhere except when accessed directly.

## Description

Hide a post everywhere except when accessed directly.

Once installed and activated this plugin, all you have to do to turn any post "Visible" or "Hidden" is to check your option in the "Post Visibility" meta box on the edit post screen.

That's it :D

Initially, every post is "Visible" by default.

You can also change one or more posts visibility at a time using the quick edit WordPress native feature.

Just remember that a hidden post is still visible when accessed directly through its permalink. The purpose of this plugin is to hide posts from being listed or shown anywhere else but their single URLs.

For SEO purposes, **Hide Post** also adds a meta tag asking robots to not follow nor index hidden posts. It uses `wp_head` WordPress hook for adding this `<meta name="robots" content="noindex, nofollow">` to each hidden post `<head>`.

First, I've created this plugin just for my own use in one of my projects. Then I've thought maybe it would be helpfull for someone else, what led me to share it here. If you have any questions and/or suggestions, let me know in the [Support Forum].

If **Hide Post** helped you somehow, remember to leave some stars and optionally a [review here](https://wordpress.org/support/plugin/hide-post/reviews/) ;)

And if you've found a bug or are having some problems with it, I'll be glad to help you, mainly in the [Support Forum].

---

![Hiding Gif](https://media.giphy.com/media/V1NxC1YoNEHBe/giphy.gif)

## Installation

This section describes how to install the plugin and get it working.

1. Upload the plugin files to the `/wp-content/plugins/hide-post` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.

## Changelog

### 1.0.0
* First release.

[Support Forum]: https://wordpress.org/support/plugin/hide-post/