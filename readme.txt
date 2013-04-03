=== Metro Share ===
Contributors: ryanhellyer, kasparsd, metronet
Donate link: http://konstruktors.com/
Tags: share, icons, metronet
Requires at least: 3.5
Stable tag: 0.5

Super fast and super customizable social sharing.

== Description ==

Super fast and super customizable social sharing

== Installation ==

Simply install, activate and visit the "Metro Share" settings page.

By default, the plugin displays the sharing icons via the_content(), however
you may wish to display it elsewhere. If this is the case, please install
the "Metro Share Remover" plugin and add the code <code><?php do_action( 'metroshare' ); ?></code>
wherever you wish to display the sharing icons.

The plugin is provided with CSS by default, but you can unhook this and add your own. For an example of this, check out the "Metro Share Styles" plugin.

== Frequently Asked Questions ==

* Q. I want new icons, how do I do that?
* A. Dequeue the existing CSS and replace it with new CSS. Or try the "Metro Share Styles" plugin.

* Q. Why is this better than other sharing plugins?
* A. It's easier to apply custom styles and features lazy loads it's scripts so that it won't bog down your page loads unnecessarily.

* Q. Why don't you add an administration page to let us customise the icons?
* A. This plugin is intended for use by developers. The plugin is intended to be as extensible. Most changes you might like to make can be achieved via a few lines in a short custom plugin (or in your theme).

== Changelog ==

= 0.5.1 (13/1/2013) =
* Documentation update

= 0.5 (13/12/2012) =
* Updated CSS to use WordPress coding standards
* Added support for adding the content automatically
* Added _icons to end of some functions to make their names clearer
* Removed class of .tabs due to clashes with off the shelf scripts
* Removed unncessary CSS
* Added plugin upgrade block for security purposes

= 0.4 =
* Initial plugin creation

== Credits ==

* <a href="http://metronet.no/">Metronet</a> - Norwegian WordPress developers<br />
* <a href="http://www.dss.dep.no/">DSS</a> - Norwegian Government Administration Services<br />
