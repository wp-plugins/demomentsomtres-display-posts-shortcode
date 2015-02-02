=== DeMomentSomTres Display Posts Shortcode ===
Contributors: marcqueralt
Tags: shortcode, pages, posts, page, query, display, list, multisite
Requires at least: 3.0
Tested up to: 4.0
Stable tag: trunk

Display a listing of posts using the [display-posts] shortcode allowing multiple network instances.

== Description ==

Based on development by Bill Erickson (http://www.billerickson.net/shortcode-to-display-posts/). We have added support to multisite in order to be capable of reading any other blog in the network.

The *DeMomentSomTres Display Posts Shortcode* was written to allow users to easily display listings of posts without knowing PHP or editing template files and extendend to take the maximum profit from a network install with multiple blogs.

= Usage =
Add the shortcode in a post or page, and use the arguments to query based on tag, category, post type, and many other possibilities (see the Arguments). I've also added some extra options to display something more than just the title: include_date, include_excerpt, and image_size.

Add the parameter blog_id to change the network instance number.

See the [WordPress Codex](http://codex.wordpress.org/Class_Reference/WP_Query) for information on using the arguments.

The parameter metaorderby allows to order based on a metafield or customfield value. The parameter metaorderbynum does the same but considering the values as numbers.

= History & Raison d'être =
A customer of us needed a multisite website to implement multiple languages and she was using [DeMomentSomTres Language Plugin](http://demomentsomtres.com/english/wordpress-plugins/demomentsomtres-language/). Although they could have many blogs they didn't want to keep 3 blogs informed. However they wanted to show the blog in all the subsites. So we build this plugin allowing to show blog content from other sites in the multisite installation.

== Installation ==
This plugin can be installed as any other WordPress plugin. 

= Requirements =

* Uses [DeMomentSomTresTools Plugin](http://demomentsomtres.com/english/wordpress-plugins/demomentsomtres-tools/).

== Changelog ==

= v2.1.2 =
* Non multisite compatibility

= v2.1.1 =
* Compatibility bug in 2.1 solved

= v2.1 =
* Empty query message

= v1.1 =
* metaorderby attribute added
* metaorderbynum attibute added
* administration page
* open using javascript

= v1.0 =
* Initial version based on 2.2 by Bill Erickson.
* Network parameter added.

