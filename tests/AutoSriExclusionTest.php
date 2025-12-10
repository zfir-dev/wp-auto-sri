<?php

use PHPUnit\Framework\TestCase;

class AutoSriExclusionTest extends TestCase {

    protected function setUp(): void {
        // Reset options before each test
        $GLOBALS['__mock_options'] = [];
    }

    public function test_hardcoded_exclusion() {
        // defined in class-auto-sri.php
        $url = 'https://www.google.com/recaptcha/api.js';
        $this->assertTrue(AutoSRI::is_excluded($url), 'Google reCAPTCHA should be excluded by default');
    }

    public function test_user_defined_exclusion() {
        // Add a user exclusion
        update_option('auto_sri_exclusions', "cdn.example.com\nexample.org");

        $url1 = 'https://cdn.example.com/script.js';
        $url2 = 'https://example.org/style.css';
        $url3 = 'https://other.com/script.js';

        $this->assertTrue(AutoSRI::is_excluded($url1), 'URL in user exclusions should be excluded');
        $this->assertTrue(AutoSRI::is_excluded($url2), 'URL in user exclusions should be excluded');
        $this->assertFalse(AutoSRI::is_excluded($url3), 'URL NOT in user exclusions should NOT be excluded');
    }

    public function test_user_defined_exclusion_partial_match() {
        update_option('auto_sri_exclusions', "bad-script.js");

        $url = 'https://cdn.com/js/bad-script.js';
        $this->assertTrue(AutoSRI::is_excluded($url), 'Partial match in user exclusions should work');
    }

    public function test_rewrite_output_respects_user_exclusion() {
        update_option('auto_sri_exclusions', "exclude-me.js");

        $html = '
        <html>
        <head>
            <script src="https://cdn.com/include-me.js"></script>
            <script src="https://cdn.com/exclude-me.js"></script>
        </head>
        </html>';

        $result = AutoSRI::rewrite_output($html);

        // include-me.js should have integrity
        $this->assertStringContainsString('integrity="sha384-', $result); 
        // But we need to be careful, as assertStringContainsString checks the whole string.
        // Let's check specifically for the include-me.js tag having integrity
        $this->assertMatchesRegularExpression('#<script[^>]*src="[^"]*include-me\.js"[^>]*integrity=#', $result);

        // exclude-me.js should NOT have integrity
        // equivalent to: regex match for exclude-me.js WITHOUT integrity
        $this->assertMatchesRegularExpression('#<script[^>]*src="[^"]*exclude-me\.js"[^>]*>#', $result);
        $this->assertDoesNotMatchRegularExpression('#<script[^>]*src="[^"]*exclude-me\.js"[^>]*integrity=#', $result);
    }
}
