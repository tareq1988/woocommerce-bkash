=== WooCommerce bKash ===
Contributors: tareq1988
Donate link: http://tareq.wedevs.com/donate/
Tags: bkash, gateway, woocommerce, bdt, bangladesh
Requires at least: 3.6
Tested up to: 4.5
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

bKash Payment gateway for WooCommerce

== Description ==

bKash Payment gateway for WooCommerce.

bKash Payment Gateway for WooCommerce

This is a very good and excellent plugin to shop online around Bangladesh using bKash payment gateway. If you(Store owner)'re using [WooCommerce](http://www.woothemes.com/woocommerce/) for your store, you should choose bKash for local payment for your shop.

= Using the Plugin =

* Download the plugin, Install and active it, as you're normally install and active other plugin too,

* Normally, you would not find any settings or options to use in the Dashboard, rather go to <strong>WooCommerce</strong> > <strong>Settings</strong> from your <strong>Dashboard</strong>, location URL would be exactly like below -

`
http://yoursite.ext/wp-admin/admin.php?page=wc-settings
`

* Now click on <strong>Checkout</strong> tab and you'll now see the <strong>bKash</strong> link, Click and enter the bKash setting page,

* Now, do <strong>Check</strong> the checkbox, if the <strong>Enable/Disable</strong> option is unchecked, This option must be checked to show this payment method in checkout page.

* Give a custom <strong>Title</strong> text by yourself or keep it as is in the text field,

* Give a <strong>Description</strong> as you did in the title or keep it as is,

* Write an <strong>Instructions</strong> on how do your customer pay you using their bKash account. It'll be showing in front of them when they select <strong>bKash Payment</strong> option and did complete their <strong>Checkout</strong> process.

### Generally the message would be -

>Send your payment directly to 01*** *** ***(Your merchant number here) via bKash. Please use your Order ID as the payment reference. Your order won't be shipped until the fund have cleared in our account.
>
>#### How to send payment:

>1. Dial *247#
>2. Select or Press 3 for "Payment" option
>3. Enter our bKash wallet number 01*** *** ***(Your merchant number here)
>4. Enter amount of fee that you ordered already
>5. Enter a reference, use your Order ID as reference
>6. Enter 1 as counter number
>7 Enter your bKash menu PIN to confirm payment
>8. You'll be getting a confirmation message in a while
>9. That's it! :)

* Write a custom <strong>Transaction Help Text</strong> or keep it as is. It'll be showing above the transaction confirmation box!

* Now you are in the main point -

Its time to take your bKash <strong>Merchant API Access</strong> Username and Password from your <strong>[bKash Account Manager](http://www.bkash.com/support/contact-us)</strong>. You should now call them and take the info right around. When you're getting the desired access info, now enter the Username into <strong>Merchant Username</strong> filed and Password into <strong>Merchant Password</strong> field in the bKash settings page. And don't forget to enter the <strong>Merchant mobile number</strong> into the last field. Otherwise your this plugin doesn't work!

* If everything sounds good, you're ready to sale your product using bKash! :)

= Contribute =

[Fork in Github](https://github.com/tareq1988/woocommerce-bkash)


= Author =

Brought to you by [Tareq Hasan](https://tareq.co) from [weDevs](http://wedevs.com)

== Installation ==


1. Upload the plugin folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

Nothing here yet

== Screenshots ==

1. Gateway Settings
2. Checkout page
3. Order received page
4. Order details page (pending order)

== Changelog ==

= 1.0 =
* Moved the transaction ID form to order received page and order details page.

= 0.1 =
* First release

== Upgrade Notice ==

Nothing here