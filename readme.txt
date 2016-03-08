=== DW Reactions ===
Contributors: designwall
Tags: reactions, reaction, facebook reactions, facebook, like, emotions, votes, voting, rating, wordpress reactions
Requires at least: 3.0
Tested up to: 4.4.2
Stable tag: 1.0.1
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

A simple plugin that helps you integrate reaction buttons into your WordPress site look like Facebook.

== Description ==

DW Reactions is a new handy feature for Facebook that allows people to express many more feelings on each blog post than just "Like" button. This plugin will help you do the same thing over on your WordPress site. All the expressive features of the plugin are listed below:

= Great User Experience =

We used the UX design of Facebook to let users have more and more great experience on both Desktop and Mobile.

= Flexible Usage =

The plugin allows you to easily display the reactions button and the reaction count above or below the post content according to your taste in the WordPress Admin Panel via Reactions Settings.

= Short-code & PHP Function supported =

Along with using option, you can quickly use the short-code [reaction] or PHP Function: dw_reactions() in order to display reactions button & counting for your WordPress theme.

= Contribute =

We will update the plugin regularly. If you have any suggestions for the product, please drop a line in the support forum. In addition, we are willing to welcome you translate the plugin into your native language here
[https://translate.wordpress.org/projects/wp-plugins/dw-reactions](https://translate.wordpress.org/projects/wp-plugins/dw-reactions)

== Documents and Support ==

We provide support both on support forum on WordPress.org and our [support page](http://www.designwall.com/question/) on DesignWall.

== Credit ==

Emotion icons by [EmojiOne](http://emojione.com/), License: [Creative Commons (CC BY 4.0)](https://creativecommons.org/licenses/by/4.0/)

== Installation ==

1. Upload `reactions` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Log In to your WordPress Dashboard and go to menu `Dashboard > Settings > Reactions`.

= Usage =

1. Open `wp-content/themes/<Your theme folder>/`.
2. You may place it in `archive.php`, `single.php`, `post.php` or `page.php` also.
3. Find `<?php while (have_posts()) : the_post(); ?>`.
4. Add anywhere below it (The place you want DW Reactions to show): `<?php if (function_exists('dw_reactions')) { dw_reactions() } ?>`.

- If you DO NOT want the reactions to appear in every post/page, DO NOT use the code above. Just type in `[reactions]` into the selected post/page and it will embed reactions into that post/page only.
- If you to use reactions button for specific post/page you can use this short code `[reactions id="1"]`, where 1 is the ID of the post/page.
- If you want to show reactions button you can use `[reactions count="false" button="true"]`.
- If you want to show reactions count you can use `[reactions count="true" button="false"]`.

== Changelog ==

= 1.0.1 =
* New: Anonymous can reactions

= 1.0.0 =
* First release