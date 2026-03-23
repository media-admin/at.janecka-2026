<?php
/**
 * Partner Discount SDK
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Soflyy_Partner_Discount')) {

    class Soflyy_Partner_Discount {
        const VERSION = '1.0.5';
        private $partners;
        private $css_variables;
        private $filters;

        public function __construct($partners = [], $css_variables = [], $filters = []) {
            $this->partners = empty($partners) ? $this->get_default_partners() : $partners;
            $this->css_variables = $this->merge_css_variables($css_variables);
            $this->filters = empty($filters) ? $this->get_default_filters() : $filters;
        }

        public static function enqueue_assets() {
            $style_handle = 'partner-discount-ui-style';
            $script_handle = 'partner-discount-ui-script';

            $style_file = plugin_dir_path(__FILE__) . 'partner-discount-ui.css';
            $script_file = plugin_dir_path(__FILE__) . 'partner-discount-scripts.js';

            $style_url = plugin_dir_url(__FILE__) . 'partner-discount-ui.css';
            $script_url = plugin_dir_url(__FILE__) . 'partner-discount-scripts.js';

            if (file_exists($style_file)) {
                wp_enqueue_style($style_handle, $style_url, [], filemtime($style_file));
            }

            if (file_exists($script_file)) {
                wp_enqueue_script($script_handle, $script_url, [], filemtime($script_file), true);
            }
        }

        private function get_default_filters() {
            return [];
        }

        private function get_default_partners() {
            return [
                [
                    'name' => 'Partner name',
                    'desc' => 'Partner description',
                    'code' => 'Discount code',
                    'discount' => '0%',
                    'link' => 'https://example.com',
                    'image' => 'https://example.com/logo.svg',
                    'category' => ''
                ]
            ];
        }

        private function get_default_css_variables() {
            return [
                'primary-color' => '#00b3b6',        // Main accent color (buttons, links)
                'primary-color-hover' => '#009da0',  // Hover state for primary color
                'black' => '#000',                   // Primary text color
                'white' => '#fff',                   // Card backgrounds
                'gray' => '#757575',                 // Secondary text color
                'light-gray' => '#f5f5f7',          // Section background, code background
                'medium-gray' => '#79848e',          // Unused (kept for compatibility)
                'dark-gray' => '#333',               // Headings, dark text
                'border-gray' => '#979797',          // Discount badge border
                'text-gray' => '#585858',            // Discount badge text
                'hover-gray' => '#eaeaea',           // Code hover background
                'success-bg' => '#e6f7f7',            // Copied code background
                'btn-text-color' => '#fff',        // Button text color
                'btn-text-color-hover' => '#fff',  // Hover state for button text color
            ];
        }

        private function merge_css_variables($custom_variables) {
            $default_variables = $this->get_default_css_variables();
            return array_merge($default_variables, $custom_variables);
        }

        private function generate_css_variables_style() {
            $css_rules = [];
            foreach ($this->css_variables as $key => $value) {
                $css_rules[] = "--{$key}: {$value}";
            }
            return implode('; ', $css_rules);
        }

        private function is_percentage($input) {
            $input = trim($input);
            return preg_match('/^\d+(\.\d+)?%$/', $input) === 1;
        }

        public function render() {
            self::enqueue_assets();
            ob_start();
            $partners = $this->partners;
            $css_variables_style = $this->generate_css_variables_style();
            $filters = $this->filters;
            ?>
            <div class="soflyy_pd_sdk-section" style="<?php echo esc_attr($css_variables_style); ?>">
                <div class="soflyy_pd_sdk-container">
                    <div class="soflyy_pd_sdk-header">
                        <h1>Partner Discounts</h1>
                        <p>Exclusive discounts on premium WordPress tools and plugins for our users.</p>
                    </div>

                    <?php if (!empty($filters)): ?>
                        <div class="soflyy_pd_sdk-filters" aria-label="Filters">
                        <button class="soflyy_pd_sdk-filter-btn is-active" data-filter="*">All</button>
                        <?php foreach ($filters as $filter): ?>
                            <button class="soflyy_pd_sdk-filter-btn" data-filter="<?php echo esc_html($filter['slug']); ?>"><?php echo esc_html($filter['name']); ?></button>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="soflyy_pd_sdk-inner-wrap">
                        <div class="soflyy_pd_sdk-grid-container">
                            <?php foreach ($partners as $partner): ?>
                                <div class="soflyy_pd_sdk-grid-item" data-category="<?php if (isset($partner['category']))echo esc_html($partner['category']); ?>">
                                    <div class="soflyy_pd_sdk-partner-card">
                                        <?php if (!empty($partner['discount'])): ?>
                                            <div class="soflyy_pd_sdk-discount-badge"><?php echo esc_html($partner['discount']); ?> <?php if ($this->is_percentage($partner['discount'])):?>OFF<?php endif;?></div>
                                        <?php endif; ?>
                                        <div class="soflyy_pd_sdk-partner-top">
                                            <?php if (!empty($partner['image'])): ?>
                                            <div class="soflyy_pd_sdk-partner-logo">
                                                <img src="<?php echo esc_url($partner['image']); ?>" alt="<?php echo esc_attr($partner['name']); ?> logo">
                                            </div>
                                            <?php endif; ?>
                                            <div class="soflyy_pd_sdk-partner-info">
                                                <h3><?php echo esc_html($partner['name']); ?></h3>
                                                <p class="soflyy_pd_sdk-partner-desc"><?php echo esc_html($partner['desc']); ?></p>
                                            </div>
                                        </div>
                                        <div class="soflyy_pd_sdk-partner-bottom">
                                            <?php if ($this->is_percentage($partner['discount'])):?>
                                                <div class="soflyy_pd_sdk-partner-code">
                                                    <span>Code:</span>
                                                    <code data-original-text="<?php echo esc_attr($partner['code']); ?>"><?php echo esc_html($partner['code']); ?></code>
                                                </div>
                                            <?php else: ?>
                                                <div class="soflyy_pd_sdk-partner-code">
                                                    <div><?php echo $partner['code']; ?></div>
                                                </div>
                                            <?php endif; ?>
                                            <a class="soflyy_pd_sdk-claim-btn" href="<?php echo esc_url($partner['link']); ?>" target="_blank" rel="noopener">
                                                Claim
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17l9.2-9.2M17 17V7H7"/></svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>  
            <?php
            return ob_get_clean();
        }
    }

    function render_partner_discount_ui($partners = [], $css_variables = [], $filters = []) {
        $partner_ui = new Soflyy_Partner_Discount($partners, $css_variables, $filters);
        return $partner_ui->render();
    }
    
}