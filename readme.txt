=== Reactions ===
Contributors: designwall
Tags: reactions, reaction, facebook
Requires at least: 3.0
Tested up to: 4.4.2
Stable tag: 1.0.0

== Description ==

== Key feature ==

== Documents and Support: ==

You can find [Documents](http://www.designwall.com/guide/dw-question-answer-plugin/) and more detailed information about DW Question and Answer plugin on [DesignWall.com](http://www.designwall.com/). 
We provide support both on support forum on WordPress.org and our [support page](http://www.designwall.com/question/) on DesignWall.

== Usage ==

1. Open `wp-content/themes/<Your theme folder>/`
2. You may place it in `archive.php`, `single.php`, `post.php` or `page.php` also.
3. Find `<?php while (have_posts()) : the_post(); ?>`
4. Add Anywhere Below it(The place you want Reactions to show): `<?php if (function_exists('dw_reactions')) { dw_reactions() } ?>`

- If you DO NOT want the reactions to appear in every post/page, DO NOT use the code above. Just type in `[reactions]` into the selected post/page and it will embed reactions into that post/page only.
- If you want to embed other post reactions user `[reactions id="1"]`, where 1 is the ID of the post/page ratings that you want to display.

== Changelog ==

= 1.0.0 =
* The first version of Reactions