<?php

use PHPUnit\Framework\TestCase;

class AutoSriAdminTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['__mock_options']   = [];
        $GLOBALS['__mock_user_meta'] = [];
        $GLOBALS['__mock_screen']    = 'plugins';
        foreach (['auto_sri_show_pro_link', 'auto_sri_pro_url'] as $hook) {
            remove_all_filters($hook);
        }
    }

    private function render($callback) {
        ob_start();
        call_user_func($callback);
        return ob_get_clean();
    }

    public function test_settings_page_has_no_exclusions_textarea() {
        $html = $this->render(['AutoSRI', 'settings_page_html']);

        $this->assertStringNotContainsString('<textarea', $html);
        $this->assertStringNotContainsString('auto_sri_exclusions', $html);
        $this->assertStringNotContainsString('<form', $html);
    }

    public function test_settings_page_advertises_pro() {
        $html = $this->render(['AutoSRI', 'settings_page_html']);

        $this->assertStringContainsString('Auto SRI Pro', $html);
        foreach (['Custom URLs', 'Scheduled re-checks and email alerts', 'Asset manager', 'Hash algorithm choice', 'WP-CLI commands'] as $feature) {
            $this->assertStringContainsString($feature, $html);
        }
        $this->assertStringContainsString('href="https://zfir.lemonsqueezy.com/"', $html);
        $this->assertStringContainsString('Get Auto SRI Pro', $html);
        $this->assertStringContainsString('not available in the free plugin', $html, 'teaser is labelled as marketing');
        $this->assertStringContainsString('action=auto_sri_dismiss_pro_link', $html);
    }

    public function test_pro_url_is_filterable() {
        add_filter('auto_sri_pro_url', function () { return 'https://example.test/buy'; });
        $this->assertStringContainsString('href="https://example.test/buy"', $this->render(['AutoSRI', 'render_pro_card']));
    }

    public function test_ads_hidden_when_pro_active() {
        add_filter('auto_sri_show_pro_link', '__return_false');

        $this->assertSame('', trim($this->render(['AutoSRI', 'render_pro_card'])));
        $links = AutoSRI::add_plugin_action_links([]);
        $this->assertCount(1, $links);
        $this->assertStringContainsString('Settings', $links[0]);
    }

    public function test_card_hidden_after_dismissal() {
        $GLOBALS['__mock_user_meta']['auto_sri_pro_link_dismissed'] = 1;
        $this->assertSame('', trim($this->render(['AutoSRI', 'render_pro_card'])));
    }

    public function test_plugin_row_upgrade_link() {
        $links = AutoSRI::add_plugin_action_links(['deactivate' => '<a>Deactivate</a>']);

        $this->assertStringContainsString('Settings', $links[0]);
        $this->assertStringContainsString('Upgrade to Pro', end($links));
        $this->assertStringContainsString('https://zfir.lemonsqueezy.com/', end($links));
    }

    public function test_legacy_notice_lists_saved_exclusions() {
        update_option('auto_sri_exclusions', "ads.example.com\nmy-dynamic-script.js\n");
        $html = $this->render(['AutoSRI', 'legacy_exclusions_notice']);

        $this->assertStringContainsString('no longer applied', $html);
        $this->assertStringContainsString('<code>ads.example.com</code>', $html);
        $this->assertStringContainsString('<code>my-dynamic-script.js</code>', $html);
        $this->assertStringContainsString('action=auto_sri_dismiss_legacy_notice', $html);
    }

    public function test_legacy_notice_conditions() {
        // Nothing saved.
        $this->assertSame('', trim($this->render(['AutoSRI', 'legacy_exclusions_notice'])));

        update_option('auto_sri_exclusions', "ads.example.com");

        // Other admin screens.
        $GLOBALS['__mock_screen'] = 'dashboard';
        $this->assertSame('', trim($this->render(['AutoSRI', 'legacy_exclusions_notice'])));

        // Dismissed.
        $GLOBALS['__mock_screen'] = 'settings_page_auto-sri';
        $GLOBALS['__mock_user_meta']['auto_sri_legacy_notice_dismissed'] = 1;
        $this->assertSame('', trim($this->render(['AutoSRI', 'legacy_exclusions_notice'])));

        // Pro licensed.
        $GLOBALS['__mock_user_meta'] = [];
        add_filter('auto_sri_show_pro_link', '__return_false');
        $this->assertSame('', trim($this->render(['AutoSRI', 'legacy_exclusions_notice'])));
    }
}
