=== Quick Adsense – Ad Manager & AdSense Ads ===
Contributors: namithjawahar
Tags: adsense, ads, ad manager, ads.txt, advertising
Requires at least: 6.7
Requires PHP: 7.4
Tested up to: 7.1
Stable tag: 2.10.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Free, lightweight ad manager: place AdSense or any ad code in posts, widgets, header & footer. No upsells. Your settings are always backed up.

== Description ==

**Quick Adsense** is a free, lightweight ad manager for WordPress. Paste your Google AdSense code, or the code from any other ad network, and choose where it appears: inside posts, in sidebar widgets, or in the header and footer of your site. There is no Pro version, no upsell and no revenue sharing.

It has a simple settings page, and it does not overwhelm you with complicated options. It can also place ads at random positions within a post, which is a good way to try different ad formats and placements.

= Features =

1. 100% free. No Pro version, no upsells, and no author revenue share on your AdSense or other ads.
1. Ads in the post body: up to 10 ad slots, each with its own code, alignment and margin.
1. Ads in sidebar widgets: up to 10 widget slots. Each widget appears in Appearance - Widgets once its code is entered.
1. Header and footer code boxes, for AdSense Auto ads, verification snippets and tracking code.
1. Automatic positions: beginning of post, middle of post, end of post, after the "more" tag, before the last paragraph, after chosen paragraphs (3 options) and after images.
1. Markers: insert a short tag into a post to place a chosen ad, a random ad, or to switch ads off for that post.
1. Support for any ad code, not only AdSense. Display up to 10 ads on a page.
1. Country targeting: limit ads to specific countries.
1. Device targeting: limit ads to specific devices.
1. Visitor targeting: limit ads to specific types or sources of visitors.
1. Local ad stats: a quick way to judge how each ad performs, stored in your own database.
1. ads.txt helper: check for, and add, the AdSense line when the file is writable.
1. Settings backups and one-click restore.

Sign up for a [Google AdSense account](https://adsense.google.com/) to earn from your site, if you don't have one.

= Your ads are safe =

An update should never cost you your ad codes. Quick Adsense saves a copy of its settings automatically before every plugin update and before every restore, and you can make a backup yourself at any time. From the Backups tab of the Quick Adsense page you can restore a backup with one click, or download it as a JSON file. The five most recent backups are kept.

Every release is tested against 20 real site configurations, with a wide range of saved ad codes, before it ships. The tests check that what your visitors see, and what is stored in your database, stays the same.

= Markers =

Markers are short HTML comments that you put in a post to control its ads. They are invisible to readers.

* `<!--Ads1-->` to `<!--Ads10-->` place ad number 1 to 10 at that spot.
* `<!--RndAds-->` places a randomly chosen ad at that spot.
* `<!--NoAds-->` turns off all ads in the post.
* `<!--OffDef-->` turns off the automatic positions, so that only the markers you placed show ads.
* `<!--OffWidget-->` turns off the sidebar widget ads for that post.
* `<!--OffBegin-->`, `<!--OffMiddle-->`, `<!--OffEnd-->`, `<!--OffAfMore-->` and `<!--OffBfLastPara-->` turn off the automatic ad at the beginning, middle or end of the post, after the "more" tag, or before the last paragraph.
* `<!--OffAds-->` is an older marker that still works and turns off the ads in the post.

= Quick Adsense and WP QUADS =

WP QUADS - Quick AdSense Reloaded is a separate plugin. It began as a fork of Quick Adsense and is developed by a different team. This is the original Quick Adsense. The two plugins store their settings separately, so installing or removing one does not change the other.

= Requirements =

Quick Adsense 2.10 needs WordPress 6.7 or newer. Sites on older versions of WordPress keep using version 2.9.4.

[Installation](https://wordpress.org/plugins/quick-adsense/#installation) | [Screen Shots](https://wordpress.org/plugins/quick-adsense/#screenshots) | [Plugin Homepage](https://quickadsense.com/)

== Screenshots ==

1. Screen shot showing the additional Quicktag Buttons added to the HTML Edit Post SubPanel.
2. The Settings page from Quick Adsense plugin. There are many ways to configure your Ads display, specifically or randomly as you wish.

== Installation ==

1. Unzip the downloaded plugin and upload the folder "quick-adsense" to the "/wp-content/plugins/" directory.
1. Go to the plugin menu in WordPress and activate the plugin.
1. Click the "Quick Adsense" item in the admin menu and configure the options. Paste the Ads codes to the respective text box.
1. Optionally, insert the trigger Tags into a post via the additional Quicktag Buttons added to the HTML Edit Post SubPanel.

== Frequently Asked Questions ==

= How do markers work? =

A marker is an HTML comment such as `<!--Ads1-->` or `<!--RndAds-->` that you type into a post. When the post is shown, Quick Adsense replaces it with the ad you chose, or removes it if no ad is available. Markers that start with "Off", and `<!--NoAds-->`, switch ads off instead. See the Markers section of the description for the full list.

= Should I use AdSense Auto ads or manual placement? =

Both work. For Auto ads, paste the code Google gives you into the header code box on the Header / Footer Codes tab, and Google decides where ads appear. For manual placement, paste each ad unit into one of the slots on the Ads on Post Body tab or the Sidebar Widget tab, and choose where each one appears. You can use them together, but Google's own policies apply to the number of ads on a page.

= How do I set up ads.txt? =

The Settings tab can check your ads.txt file for your AdSense line and add it for you. This works when WordPress is able to write to the ads.txt file in your site's root folder. If the file is not writable, the plugin shows you the line to add by hand.

= Do ads work with caching plugins? =

Yes. Ads are inserted on the server into the post content, so a page cache stores the page together with its ads. After you change your settings, clear your page cache so that visitors see the change.

= Does it work with the block editor? =

Yes. Add a marker to a post with a Custom HTML block, or from the Code editor. The Quicktag buttons are part of the classic editor, so they only appear there. The automatic positions work in every editor, with no markers needed.

= What about privacy? =

Quick Adsense sends no telemetry or site data to its authors, and the ad statistics stay in your own database. The ad and tracking code you paste in runs in your visitors' browsers and may contact third parties, so you are responsible for choosing it, disclosing it and getting any consent it needs. See the Privacy section below.

= How do backups and restore work? =

Quick Adsense saves a copy of its settings before every plugin update and before every restore. You can also create a backup at any time. They are listed in the Backups tab of the Quick Adsense page, where you can restore or download them. The five most recent backups are kept. A restore first saves your current settings, then puts the chosen backup back exactly as it was. Widget placements are only restored if you tick "Also restore widget placements". Restoring needs an administrator account that is allowed to edit unfiltered HTML.

= Is it really free? =

Yes, 100%. There is no Pro version, no locked feature and no upsell, and the plugin takes no share of your ad revenue.

Please send feedback through the [Quick Adsense support forum](https://wordpress.org/support/plugin/quick-adsense/) for any bug you find or suggestion you would like to add.

== Changelog ==
= 2.10.0 =
* New: automatic settings backups and one-click restore.
* Updated listing.
* Requires WordPress 6.7+.
* Fixed PHP warnings on sites with incomplete settings; ad output is unchanged.

= 2.9.4 =
* Fix: the release package now includes the bundled Mobile Detect and GeoIP libraries again. Without them, 2.9.1 and 2.9.2 could fail with a fatal error on activation.

= 2.9.3 =
* Maintenance: the version number now comes from the release tag. No change to plugin behaviour.

= 2.9.2 =
* Maintenance: release and deployment pipeline fixes. No change to plugin behaviour.

= 2.9.1 =
* Modernized plugin security and WordPress compatibility while preserving existing HTML, JavaScript and script-based ad codes.
* Fixed escaped markup in administration labels and restored selectable widget ad units.
* Added automated compatibility tests, release packaging, FAIR metadata publishing and WordPress Playground demos.

= 2.6 =
* Vi Integration Removed
* Plugin Ownership Change
* Bring plugin opto date with latest Wordpress

= 2.4 =
* GDPR consent management module added
* Updated Privacy policy

= 2.3.2 =
* Bug fixes
* 3 option fields have been removed
* The API limiter added
* QuickTag issues have been fixed

= 2.3 =
* Updated ads.txt modification logic
* UI fixes

= 2.2 =
* Fixed functionality issues

= 2.1 =
* Optimized UI
* Brought plugin uptodate with latest wordpress standards
* Removed all legacy code
* Legacy Tinymce API replaced with current API
* Legacy Widget API replaced with current API

= 2.0.1 =
* Bug Fix: Fixed a bug which caused a JS error in specific use cases.
* Date: 19-December-2017

= 2.0 =
* The number of individual database calls has been reduced from 80 to just 1, also almost all of the legacy code has been altered and code base re-structured to be better understandable.
* Info update.
* Date: 14-September-2017

= 1.9.3 =
* Info update.
* Date: 29-August-2017

= 1.9.2 =
* Update the depreciated wp code function for AdsWidget in the plugin.
* New Feature - Enable user to insert Ads after Image's outer div wp-caption & not just right below the image.
* New Feature - Enable user to insert Ads before the last paragraph of the post.
* New Feature - Option to disable ads at particular positions, such as Beginning, Middle & End of post, ads on Sidebar, ads after 'more' tag and ads before last paragraph.
* Date: 8-May-2013

= 1.9.1 =
* Bug Fix - Fix the Quicktag Buttons display error on the HTML Edit Post SubPanel.
* Bug Fix - Fix the conflict with Jetpack by WordPress.com & some other plugins.
* Info update.
* Date: 11-January-2013

= 1.9 =
* New functionality added - Hide Ads when user is logged in to Wordpress, giving you option to exclude ads view by you from the total ads impression.
* Date: 12-June-2011

= 1.8.4 =
* Bug Fix - Fix the Quicktag Buttons display error on the HTML Edit Post SubPanel, make it compatible with WP 3.1 onward.
* Date: 31-Mar-2011

= 1.8.3 =
* Bug Fix - Remove the ads from Feed, Ads shouldn't be there in the first place.
* Date: 16-Jan-2011

= 1.8.2 =
* New functionality - Default Ads positioning: Assign Ads right after the &lt;!--more--&gt; tag.
* Date: 03-Dec-2010

= 1.8.1 =
* New functionality - Default Ads positioning: Assign Ads to Middle of post.
* Date: 22-Nov-2010

= 1.8 =
* Text correction: Change the words "must be identical" to "must not be identical".
* New functionality - Default Ads positioning after certain paragraphs, make it up to the total of three.
* Also, option to display ads at End of Post if the above function is selected & fewer paragraphs are found.
* Date: 20-Nov-2010

= 1.7 =
* Fix the error message problem when the plugin is first activated. Error message: "The plugin generated 'n' characters of unexpected output during activation ..."
* Date: 13-Aug-2010

= 1.6 =
* Fix for the problem on Setting page stop loading & the disappearing of "Save Setting" button in some cases.
* Fix the AdsWidget div tag's class name. Now you can use CSS to style the AdsWidget.
* Extend the number of AdsWidget (Sidebar Ads) from 3 to 10, as requested by users. Now, AdsWidgets are automatically appear in Appearance-&gt;Widgets page only when the code is available.
* Remove the &lt;!--OffAds--&gt; option as this function doesn't work well in certain theme. However, if you are using this function in previous version, you can continue to use it.
* Minor code modifications.
* Date: 10-Apr-2010

= 1.5.1 =
* v1.5 has been tested up to WP2.9, update the compatible version notice.
* Date: 04-Jan-2010

= 1.5 =
* New functionality - Default Ads positioning: Assign Ads after certain paragraphs & assign Ads after certain images.
* You can now disable AdsWidget (Sidebar Ads) on Homepage. 
* Insert a &lt;!--OffDef--&gt; to temporary disable the default positioned Ads, so that you can insert the specific Ads or Random ad tags in the post.
* You may now display up to 10 ads on a page.
* Minor bug fix & code modifications.
* Date: 09-Dec-2009

= 1.4 =
* Fix for plugin user Roles. Now, only Administrator is allowed to read & modified the plugin.
* Quicktag Buttons for Ads1-Ads10 are now automatically shown only when the Ads codes are available. You will have the option to hide the RndAds, NoAds & OffAds Quicktag Buttons.
* Minor code modification on the AdsWidget portions.
* Update plugin screenshots & add a Paypal donation button.
* Date: 14-Nov-2009

= 1.3 =
* Code modification: Option to place all possible Ads on Homepage, Categories, Archives and Tags page. Previously, only one Ads (the one assigned to the Beginning of Post) is displayed.
* Code update to take advantage of some WP functions for options page, also for forward compatibility in future version.
* Code update to make it compatible with WordpressMU, tested on WordpressMU 2.7.1
* Some minor page & wording modification.
* Date: 13-Oct-2009

= 1.2 =
* Re-fix the frist time default options loading value.
* Date: 03-Oct-2009

= 1.1 =
* Minor fix for the removal of &lt;p&gt;&lt;/p&gt; tag wrapped around Ads code.
* Default options value will now load when the frist time plugin is activated, not every time during plugin reactivation.
* Change the clearing DIV height at the end of post from 1px to 0px to hide this transparent line.
* Date: 02-Oct-2009

= 1.0 =
* Initial public release. 
* Date: 21-Sep-2009

== Upgrade Notice ==

= 2.10.0 =
Requires WordPress 6.7 or newer. Adds automatic settings backups and one-click restore.

== Privacy ==

Quick Adsense does not send telemetry or site data to its authors. Optional ad click and impression statistics are stored locally in the WordPress database for up to 30 days.

The administrator-provided ad and tracking snippets execute in visitors' browsers and may contact the third-party providers selected by the site owner. Those providers may collect data under their own terms and privacy policies. Site owners are responsible for choosing, configuring, disclosing, and obtaining any consent required for those snippets.
