=== CaptchaFox for WordPress ===
Contributors: scorialabs
Tags: captcha, recaptcha, spam, anti-spam, gdpr
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.0
Stable tag: 1.10.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

CaptchaFox is an easy-to-use and GDPR compliant bot protection service. Protect your WordPess forms from malicious bot traffic!

== Description ==

CaptchaFox defends and safeguards your website against automated attacks such as account takeover, spam, scalping, and web scraping, while adhering to EU data protection regulations.

It utilizes a variety of data signals and challenges to verify the authenticity of the user without the use of cookies or trackers.
The challenges have low friction and are easily solvable for humans, but difficult for bots. With the one-click challenge, legitimate users don't even know they're being challenged.

This plugin comes with out-of-the-box support for popular form plugins, such as Contact Forms 7, WPForms, Woocommerce, Mailchimp, and more. Therefore, no developer experience is required.

To get started, login or create an account at [portal.captchafox.com](https://portal.captchafox.com/) and locate your keys. 
See the available [plans and pricing](https://captchafox.com/pricing) for more advanced features and additional support.

== Installation ==

1. Install the plugin via the WordPress.org plugin repository or upload the `captchafox-for-forms` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin on the **Plugins** page (**Plugins > Installed Plugins**).
3. Enter your Site Key and Secret Key in the **General** settings tab (**Settings > CaptchaFox**)  
4. Enable CaptchaFox for your installed form plugins (**Settings > CaptchaFox > Plugins**)  

== Supported Forms and Plugins ==

* WordPress Login
* WordPress Registration
* WordPress Password Reset
* WordPress Comments
* Elementor Forms
* Gravity Forms
* WP Forms Lite
* Contact Form 7
* Ninja Forms
* Ninja Forms Multi Step
* Woocommerce Checkout
* Woocommerce Login
* Woocommerce Registration
* Woocommerce Password Reset
* Mailchimp for WordPress
* Forminator
* BBPress New Topic
* BBPress Reply
* Otter Blocks
* Fluent Forms
* Avada Forms

== Frequently Asked Questions ==

= Where do I find my keys? =
You can find your site key and secret key in the [Portal](https://portal.captchafox.com).

= How to set the widget options programmatically? =

You can use the following filters to override the language, theme and mode:
- capf_language
- capf_theme
- capf_mode

__Example__
`
/**
* Filters the CaptchaFox language setting.
*
* @param string $language Language.
*/
function set_custom_language( $language ) {
  return 'YOUR_LANGUAGE_CODE'; // see https://docs.captchafox.com/language-codes for available codes
}

add_filter( 'capf_language', 'set_custom_language' );
`

= Where can I learn more about CaptchaFox? =
Visit the [website](https://captchafox.com/) to find more information about CaptchaFox.


== Screenshots ==
1. WordPress Login
2. WordPress Comment
3. Contact Form
4. General Settings
5. Manage Plugins

== Privacy Notices ==

CaptchaFox is compliant with various privacy laws, such as GDPR, CCPA, and others.

This plugin does **not** use cookies, track users, store personal data or communicate with external services that are not required for verification.

Once the plugin is activated, the CaptchaFox service may receive the user's IP address and browser vendor data while protecting enabled pages. However, CaptchaFox is designed to reduce the use of data and to dispose of it as soon as the verification request is processed.

To learn more, please read the privacy policy at [captchafox.com/privacy](https://captchafox.com/privacy).

== Changelog ==

= 1.10.0 =
* Support Gravity Forms

= 1.9.0 =
* Add filters to override options programmatically

= 1.8.0 =
* Support Elementor Forms

= 1.7.0 =
* Tested on WooCommerce 9.0
* Tested on WordPress 6.6
* Fix widget not appearing on WordPress register page

= 1.6.0 =
* Tested on WooCommerce 8.7
* Support Avada Forms

= 1.5.0 =
* Support Fluent Forms

= 1.4.0 =
* Tested on WooCommerce 8.6
* Tested on WordPress 6.5

= 1.3.0 =
* Tested on WooCommerce 8.5
* Support Ninja Forms Multi Step
* Add theme option

= 1.2.0 =
* Add support for Otter Blocks

= 1.1.0 =
* Add support for NinjaForms

= 1.0.0 =
* Plugin Created
