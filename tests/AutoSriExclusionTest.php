<?php

use PHPUnit\Framework\TestCase;

class AutoSriExclusionTest extends TestCase {

    protected function setUp(): void {
        // Reset options and filters before each test
        $GLOBALS['__mock_options'] = [];
        remove_all_filters('auto_sri_excluded_urls');
    }

    public function test_hardcoded_exclusion() {
        // defined in class-auto-sri.php
        $url = 'https://www.google.com/recaptcha/api.js';
        $this->assertTrue(AutoSRI::is_excluded($url), 'Google reCAPTCHA should be excluded by default');
    }

    public function test_legacy_option_is_no_longer_applied() {
        // Saved by the settings textarea before 2.4. Kept in the database but not applied.
        update_option('auto_sri_exclusions', "cdn.example.com\nexample.org");

        $this->assertFalse(AutoSRI::is_excluded('https://cdn.example.com/script.js'));
        $this->assertSame("cdn.example.com\nexample.org", get_option('auto_sri_exclusions'), 'option is left untouched');
        $this->assertSame(['cdn.example.com', 'example.org'], AutoSRI::get_legacy_exclusions());
    }

    public function test_excluded_urls_filter() {
        add_filter('auto_sri_excluded_urls', function ($patterns) {
            return array_merge($patterns, ['cdn.example.com', 'example.org']);
        });

        $this->assertTrue(AutoSRI::is_excluded('https://cdn.example.com/script.js'));
        $this->assertTrue(AutoSRI::is_excluded('https://example.org/style.css'));
        $this->assertFalse(AutoSRI::is_excluded('https://other.com/script.js'));
    }

    public function test_excluded_urls_filter_partial_match() {
        add_filter('auto_sri_excluded_urls', function () { return ['bad-script.js']; });

        $this->assertTrue(AutoSRI::is_excluded('https://cdn.com/js/bad-script.js'));
    }

    public function test_excluded_urls_filter_ignores_junk() {
        add_filter('auto_sri_excluded_urls', function () { return ['', '  ', ['nested'], null, ' ok.js ']; });

        $this->assertSame(['ok.js'], AutoSRI::get_excluded_urls());

        remove_all_filters('auto_sri_excluded_urls');
        add_filter('auto_sri_excluded_urls', function () { return 'not-an-array'; });
        $this->assertSame([], AutoSRI::get_excluded_urls());
    }

    public function test_rewrite_output_respects_excluded_urls() {
        add_filter('auto_sri_excluded_urls', function () { return ['exclude-me.js']; });

        $html = '
        <html>
        <head>
            <script src="https://cdn.com/include-me.js"></script>
            <script src="https://cdn.com/exclude-me.js"></script>
        </head>
        </html>';

        $result = AutoSRI::rewrite_output($html);

        $this->assertMatchesRegularExpression('#<script[^>]*src="[^"]*include-me\.js"[^>]*integrity=#', $result);
        $this->assertMatchesRegularExpression('#<script[^>]*src="[^"]*exclude-me\.js"[^>]*>#', $result);
        $this->assertDoesNotMatchRegularExpression('#<script[^>]*src="[^"]*exclude-me\.js"[^>]*integrity=#', $result);
    }
}
