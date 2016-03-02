=== Reactions ===
Contributors: designwall
Tags: reactions, reaction, facebook
Requires at least: 3.0
Tested up to: 4.4.2
Stable tag: 1.0.0

== Description ==

== Key feature ==

== Documents and Support: ==

We provide support both on support forum on WordPress.org and our [support page](http://www.designwall.com/question/) on DesignWall.

== Usage ==

1. Open `wp-content/themes/<Your theme folder>/`.
2. You may place it in `archive.php`, `single.php`, `post.php` or `page.php` also.
3. Find `<?php while (have_posts()) : the_post(); ?>`.
4. Add anywhere below it (The place you want Reactions to show): `<?php if (function_exists('dw_reactions')) { dw_reactions() } ?>`.

- If you DO NOT want the reactions to appear in every post/page, DO NOT use the code above. Just type in `[reactions]` into the selected post/page and it will embed reactions into that post/page only.
- If you to use reactions button for specific post/page you can use this short code `[reactions id="1"]`, where 1 is the ID of the post/page.
- If you want to show reactions button you can use `[reactions count="false" button="true"]`.
- If you want to show reactions count you can use `[reactions count="true" button="false"]`.

== Changelog ==

= 1.0.0 =
* The first version of Reactions