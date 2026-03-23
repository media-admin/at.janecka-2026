<?php 
/**
 * Partner Discounts page
 * 
 * Handles the display and management of partner discount offers
 * within the admin interface.
 *
 * @author Prabch <prabch.soflyy@gmail.com>
 */

// Load the Partner Discount SDK for rendering discount UI components
require_once plugin_dir_path(__FILE__) . '../../classes/partner-discount-sdk/partner-discount-sdk.php';

class PMXI_Admin_Partners extends PMXI_Controller_Admin {
	
	public function init() {
		parent::init();
	}
	
	/**
	 * Display the partner discounts page
	 * 
	 * Renders the main partner discounts interface showing all available
	 * partner offers with discount codes and promotional information.
	 *
	 * @return void
	 */
	public function index() {
        echo '<div class="wrap">';
        echo render_partner_discount_ui($this->get_partners(), [], $this->get_filters());
        echo '</div>';
	}	

    /**
     * Get filter categories
     * 
     * Returns an array of predefined filter categories used to
     * group partners.
     * Each filter contains a display name and a corresponding slug identifier.
     * 
     * @return array Array of filter data with the following structure:
     *               - name: Display name of the filter category
     *               - slug: URL-friendly identifier for the filter
     */
    private function get_filters() {
        return [
              [
                'name' => 'Analytics',
                'slug' => 'analytics'
            ],
            [
                'name' => 'Builders',
                'slug' => 'builder'
            ],
            [
                'name' => 'Dev Tools',
                'slug' => 'dev-tools'
            ],
            [
                'name' => 'Marketing & Growth',
                'slug' => 'marketing-and-growth'
            ]
        ];
    }

    /**
     * Get partner discount data
     * 
     * Returns an array of partner discount information including
     * company details, discount codes, promotional links, and branding.
     * 
     * @return array Array of partner discount data with the following structure:
     *               - name: Partner company name
     *               - desc: Description of the partner's product/service
     *               - code: Discount/coupon code
     *               - discount: Percentage or amount of discount
     *               - link: Promotional/checkout URL
     *               - image: Partner logo/brand image URL
     */
    private function get_partners() {
        return [
            [
                'name' => 'AnalyticsWP',
                'desc' => 'This privacy-compliant WordPress analytics plugin gives detailed insights into user behavior beyond what traditional tools can provide, and has a dedicated integration for WooCommerce.',
                'code' => 'wpallimport2024',
                'discount' => '20%',
                'link' => 'https://analyticswp.com/pricing/?wt_coupon=wpallimport2024',
                'image' => 'https://www.wpallimport.com/wp-content/uploads/2025/05/analyticswp-logo.svg',
                'category' => 'analytics',
            ],
            [
                'name' => 'WPCodeBox',
                'desc' => 'Save code from inside Breakdance to WPCodebox in one click. Use cloud snippets to share across your sites and explore the Code Snippet Repository full of tested snippets.',
                'code' => 'KMWOV0WBKJ',
                'discount' => '20%',
                'link' => 'https://wpcodebox.com/',
                'image' => 'https://www.wpallimport.com/wp-content/uploads/2024/08/WPCodeBox-Logo-Small-Dark.png',
                'category' => 'dev-tools',
            ],
            [
                'name' => 'Solid Affiliate',
                'desc' => 'The all-in-one affiliate solution for high-performing WooCommerce stores. Trusted by 10,000+ store owners, it’s the professional way to scale referrals and rankings, all from your WordPress dashboard.',
                'code' => 'BREAKDANCEPARTNER',
                'discount' => '15%',
                'link' => 'https://solidaffiliate.com/pricing?_gl=1*1v5voof*_gcl_au*OTM3MDQ1Mjk3LjE3NTU3NzI2MTYuMTc2ODM2MDE2LjE3NjExMzI3MjguMTc2MTEzMjcyNw..',
                'image' => 'https://www.wpallimport.com/wp-content/uploads/2025/08/solid-affiliate.webp',
                'category' => 'marketing-and-growth',
            ],
            [
                'name' => 'WP Debug Toolkit',
                'desc' => 'The best WordPress error log viewer for WordPress. Easily centralize, filter, and understand your WordPress errors without touching FTP or SSH.',
                'code' => 'BD20',
                'discount' => '20%',
                'link' => 'https://wpdebugtoolkit.com/',
                'image' => 'https://www.wpallimport.com/wp-content/uploads/2025/10/wpdebugtoolkit.jpg',
                'category' => 'dev-tools',
            ],
            [
                'name' => 'Breakdance',
                'desc' => 'Created by the same team behind WP All Import, Breakdance is a modern visual site builder for WordPress that combines professional power with drag & drop ease of use.',
                'code' => 'WPAI',
                'discount' => '35%',
                'link' => 'https://breakdance.com/checkout?edd_action=add_to_cart&discount=WPAI&download_id=14&edd_options%5Bprice_id%5D=1',
                'image' => 'https://www.wpallimport.com/wp-content/uploads/2024/08/cropped-favicon.png',
                'category' => 'builder',
            ],
            [
                'name' => 'Oxygen',
                'desc' => 'Created by the same team behind WP All Import, Oxygen is the go-to WordPress website builder for highly advanced users & developers who love to code.',
                'code' => 'WPAI20',
                'discount' => '20%',
                'link' => 'https://oxygenbuilder.com/checkout/?edd_action=add_to_cart&download_id=4790638&discount=WPAI20',
                'image' => 'https://www.wpallimport.com/wp-content/uploads/2025/01/logo-minimal-black.png',
                'category' => 'builder',
            ],
            [
                'name' => 'Meta Box',
                'desc' => 'Meta Box is a WordPress custom fields plugin for flexible content management using custom post types and custom fields.',
                'code' => 'BREAKDANCE20',
                'discount' => '20%',
                'link' => 'https://metabox.io/pricing/',
                'image' => 'https://www.wpallimport.com/wp-content/uploads/2025/03/metabox-logo-square.png',
                'category' => 'dev-tools',
            ],
            [
                'name' => 'Elevated Discount Rules',
                'desc' => 'Create flexible and powerful discount rules for your WooCommerce Store. Boost your sales with bulk or store wide discounts.',
                'code' => 'All Extensions for Life for $199',
                'discount' => 'Special Launch Price',
                'link' => 'https://elevatedextensions.com/extensions/elevated-discount-rules/',
                'image' => 'https://www.wpallimport.com/wp-content/uploads/2025/10/elex_discount_i.svg',
                'category' => 'marketing-and-growth',
            ],
            [
                'name' => 'Elevated Instagram Feed',
                'desc' => 'Blow your visitors away showing a lightweight, fully customizable Instagram feed on any part of your site without any technical knowledge.',
                'code' => 'All Extensions for Life for $199',
                'discount' => 'Special Launch Price',
                'link' => 'https://elevatedextensions.com/extensions/elevated-instagram-feed/',
                'image' => 'https://www.wpallimport.com/wp-content/uploads/2025/10/elex_instagram_i.svg',
                'category' => 'marketing-and-growth',
            ],
            [
                'name' => 'Elevated AJAX Search',
                'desc' => 'Blazing fast, high-performing WooCommerce live search for your store. A powerful search engine that shows instant results and offers your customers a great shopping experience.',
                'code' => 'All Extensions for Life for $199',
                'discount' => 'Special Launch Price',
                'link' => 'https://elevatedextensions.com/extensions/elevated-ajax-search/',
                'image' => 'https://www.wpallimport.com/wp-content/uploads/2025/10/elex_search_i.svg',
                'category' => 'marketing-and-growth',
            ],
        ];
    }
}