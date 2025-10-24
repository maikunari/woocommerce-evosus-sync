=== WooCommerce Evosus Sync ===
Contributors: yourname
Tags: woocommerce, evosus, sync, integration, orders
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Sync WooCommerce orders and customers to Evosus Business Management Software.

== Description ==

WooCommerce Evosus Sync integrates your WooCommerce store with Evosus Business Management Software. 

**Key Features:**

* One-Click Order Sync from order edit screen
* Customer Duplicate Prevention
* SKU Validation and Mapping
* Bidirectional Cross-Reference
* Review Workflow for problem orders
* Stock Checking
* Automatic order notes

**Workflow:**

1. Order appears as "Pending" 
2. You review order details
3. Click "Add to Evosus" on order edit screen
4. Order syncs with customer and line items
5. Evosus order ID saved to WooCommerce
6. WooCommerce order number saved to Evosus PO field

**Requirements:**

* Active Evosus subscription (version 6.6.407+)
* Evosus Web API access
* WooCommerce 5.0+

== Installation ==

1. Upload plugin to `/wp-content/plugins/woocommerce-evosus-sync/`
2. Activate via 'Plugins' menu
3. Go to **Evosus Sync → Settings**
4. Enter API credentials
5. Configure Distribution Method ID
6. Start syncing!

Contact Evosus support for API credentials: http://support.evosus.com

== Frequently Asked Questions ==

= Do I need an Evosus account? =

Yes, requires active Evosus subscription and Web API access.

= How do I get API credentials? =

Contact Evosus support to request Web API access.

= What if a SKU doesn't match? =

Plugin flags order for review and suggests similar SKUs. Map correct SKU on order screen.

= Can I sync automatically? =

Yes, enable "Auto-Sync" in settings. However, manual review is recommended.

= What happens with existing customers? =

Plugin searches by email. If found, uses existing customer instead of creating duplicate.

== Changelog ==

= 1.0.0 =
* Initial release
* Customer sync with duplicate detection
* Order sync with line items
* SKU validation and mapping
* Bidirectional cross-referencing
* Admin dashboard
* Settings page

== Privacy ==

Plugin sends order/customer data to Evosus via REST API:
* Customer name, email, phone, address
* Order items (SKU, quantity, price)
* Order totals and payment info

No data sent to third parties except Evosus.