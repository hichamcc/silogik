=== FortressDB ===
Contributors: FortressDB
Donate link:
Tags: database tables charts forminator weforms gravity forms
Requires at least: 4.0
Tested up to: 5.9
Stable tag: 2.0.21
Requires PHP: 5.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
High-speed, secure database plugin for WordPress form data

== Description ==
## High-speed, secure database plugin 
FortressDB provides you with a safe and secure database to store sensitive information and files for your WordPress site. 

Watch FortressDB in action:

https://youtu.be/4uYi0833Kjw

## FortressDB is secure
FortressDB offers dedicated Google servers and high-level encryption to safely store your data and protect it from WordPress security vulnerabilities.

WordPress is designed for sharing information. This is great for Search Engine Optimization (SEO), but not so great for security, as files stored in wp-uploads can be found by Google and other search engines.

FortressDB removes this risk. Once you've installed the plugin, your sensitive data will be sent safely over SSL to our secure servers. Once there, only approved users will have access to your data. 

## FortressDB is fast
FortressDB is lightning fast. It was built for efficiency, using a modern database design to allow for handling even the most complex datasets at high speed. 

FortressDB plays a similar role to a Content Delivery Network (CDN), but instead of displaying rich media content, FortressDB's structure rapidly loads data and files only when needed. This means a smoother, quicker experience for your website users.

By default, most content within WordPress is stored as posts. This means WordPress sites often have bloated tables with lots of joins, resulting in slower performance. FortressDB helps you fight this bloat.

The FortressDB database design was created specifically to handle complex data at speed. In FortressDB database joins are not required for data retrieval, meaning our plugin can manage the complexities of many millions of rows of data instantly. Watch our Million Rows Demo](https://fortressdb.com/#millionrows) to see for yourself!

## FortressDB protects your users' privacy 
FortressDB’s most obvious benefit for privacy is that it’s completely secure. Sensitive data submitted via your website is sent directly over SSL to the FortressDB servers. We chose Google to host our servers in large part for their security reputation, so you can be confident that your data is safe.

Privacy isn’t just about security though. In addition to being secure, FortressDB also ensures that only users with the correct permissions can access the data you hold, meaning you have complete control over has the power to read, write or delete your stored data. 

FortressDB matches native WordPress user roles for this precise purpose. This keeps sensitive data safe and guarantees it can only be accessed by people who are logged in with the correct user role.


## FortressDB offers a choice of server locations
We have secure servers in three different locations: USA, UK and Europe. When you create an account, you choose which location to use. 

This flexibility is beneficial for companies that are subject to GDPR and similar privacy laws.


## FortressDB has pre-built integrations with popular form plugins
FortressDB has integrations with the following popular WordPress form plugins, making it easy to secure your data and protect your website's users:

- weForms
- Forminator
- Gravity Forms

We are working on adding more integrations. If there's a form plugin you'd like us to support, let us know by [submitting a ticket](https://help.fortressdb.com/submit-a-ticket/). 

Learn more about [FortressDB integrations on our website](https://fortressdb.com/form-plugins/).

 
== Installation ==
### Automatic Installation (Recommended)
1. Login to WordPress
2. Open Plugins > Add New
3. Search for FortressDB
4. Activate FortressDB from your Plugins page
5. Create a FortressDB account to connect your data—it's free. You will need an email address and a password. You will also need to select where you want to store your secure data: USA, Europe or UK.

### Manual Installation
1. Upload the FortressDB plugin to the /wp-content/plugins/ directory
2. Activate the plugin through the Plugins menu in WordPress
3. Create a FortressDB account to connect your data, it's free. You will need an email address and a password. You will also need to select where you want to store your secure data: USA, Europe or UK.
 
[Find more information about installing FortressDB on our website](https://help.fortressdb.com/knowledge-base/installing-fortressdb/).

== Frequently Asked Questions ==
= Is there a free version? =
Yes, of course! We encourage you to try out FortressDB using the free version. The free version may provide you with everything you need, and there's no need to upgrade if that's the case: we're happy to support the open source community.

But if you need more storage, have multiple websites to support or want additional security measures then one of our paid subscription plans may be better for you. We offer Bronze, Silver and Gold packages, so there's a price to suit everyone. [Find out more on our Pricing page](https://fortressdb.com/pricing/).
 
= Is FortressDB Gutenberg friendly? =
Yes, and we have our own Gutenberg blocks!

We love using Gutenberg, it's the present and the future of WordPress. To make it easy for you to use our plugin with Gutenberg, have created three types of blocks: a data table block, a Google Maps block, and a chart block. [See examples on the FortressDB website](https://fortressdb.com/blocks/).

Want more blocks? [Tell us what other blocks that you want us to create](https://help.fortressdb.com/submit-a-ticket/).
 
= Who has access to the data? =
You have absolute control over who can access the data you hold. For each data table, you specify which user roles can interact with the data, and whether they should be able to create, read, update or delete.

To keep things simple, the user roles in our plugin match the built-in WordPress user roles. Our plugin understands non-standard roles too, like the customer or shop manager user roles for websites with WooCommerce shops.

Additionally, you can choose a "Not Logged In" role for data that isn't sensitive, or for files that you are happy for anyone to read.
 
= Where are your servers located? =
We have 3 different locations; Europe, USA and the UK. When you create an account you choose which location to use. This is beneficial for companies subject to GDPR and similar laws.

= How do I create a data table? =
We designed FortressDB so that it's simple and easy to use. You add fields in much the same way you would if you were using a form plugin.

You have the power to easily create and edit data tables and manage user roles directly from the FortressDB plugin settings in your WordPress admin area.

We handle all the technical bits behind the scenes for you, like creating the data table on our servers and connecting it to your WordPress website. 

== Screenshots ==
1. Example of a FortressDB data table Gutenberg block
2. Data table editor in FortressDB
3. FortressDB Data Table CRUD Editor
4. Example FortressDB chart block for Gutenberg

== Changelog ==

= 2.0.21 =

* Fixed bug in multi-file upload for Forminator
* Tested against WordPress 5.9

= 2.0.20 =
* Support for multi-site
* Manage Billing 
* General improvements

= 2.0.19 =
* General maintenance
* Fixed bug with new version of Forminator 

= 2.0.18 =
* Added search and pagination functionality to chart and map blocks
* General fixes and speed improvements
= 2.0.17 =
Release Date: April 29th, 2021
* Added new block - Custom Data - create custom directories etc
* Search history tab - added to secure forms and custom data so admin can see what users have searched for
* Secure Forms - enabled fields tab in editor so users can re-order fields or deselect server encryption for specific fields
* General fixes and speed improvements
= 2.0.16 =
Release Date: January 26th, 2021
* Integration with Gravity forms
* Added support in plugin
* Improved error messages - greater clarity / detailed description
= 2.0.15 =
Release Date: November 24th, 2020
* Fixed bug with multi-select checkbox form fields
= 2.0.14 =
Release Date: October 13th, 2020
* Updates for weForms release
= 2.0.12 =
* General tidy up and minor but fixing
* Preparation for weForms plugin
= 2.0.9 =
* Added blocks for secure forms
* Fixed minor bug
= 2.0.8 =
* Added multi-files feature for WeForms and Forminator addons
* Added forced encryption to addons API
* Small improvements in UI
= 2.0.7 =
* Fixed method of getting short accessToken for addons feature
= 2.0.6 =
* Added check for WordPress UserId on de-authenticate
* Added 401 errorCode Handler
= 2.0.2 =
Release Date: April 30th, 2020
* Add default wide align for blocks
* Group chart block settings to 2 columns
* Fixed File field markup
* Fixed table row select
* Fixed select menu position, style for fields and picklist values
* Add WeForms plugin integration
= 2.0.1 =
Release Date: April 24th, 2020
* Fixed API reconnection if access token has expired
= 2.0.0 =
Release Date: April 16th, 2020
* Fixed transition using
= 1.0.0 =
Release Date: March 18th, 2020
* Our first major release of the FortressDB plugin
== Upgrade Notice ==

