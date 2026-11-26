<?php

use PHPUnit\Framework\TestCase;

class AutoSriHooksTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['__mock_options'] = [];
        foreach (['auto_sri_hash_algorithm', 'auto_sri_pre_get_hash', 'auto_sri_hash_computed', 'auto_sri_is_excluded', 'auto_sri_enabled', 'auto_sri_rewrite_html', 'auto_sri_builtin_exclusions'] as $hook) {
            remove_all_filters($hook);
        }
    }

    public function test_default_algorithm_is_sha384() {
        $this->assertSame('sha384', AutoSRI::get_hash_algorithm());
        $this->assertStringStartsWith('sha384-', AutoSRI::get_sri_hash('https://cdn.example.com/a.js'));
    }

    public function test_algorithm_filter_changes_hash() {
        add_filter('auto_sri_hash_algorithm', function () { return 'sha512'; });

        $sri = AutoSRI::get_sri_hash('https://cdn.example.com/a.js');
        $expected = 'sha512-' . base64_encode(hash('sha512', "console.log('auto-sri');", true));

        $this->assertSame($expected, $sri);
    }

    public function test_invalid_algorithm_falls_back_to_sha384() {
        add_filter('auto_sri_hash_algorithm', function () { return 'md5'; });
        $this->assertSame('sha384', AutoSRI::get_hash_algorithm());
    }

    public function test_cache_is_recomputed_when_algorithm_changes() {
        $url = 'https://cdn.example.com/b.js';
        $this->assertStringStartsWith('sha384-', AutoSRI::get_sri_hash($url));

        add_filter('auto_sri_hash_algorithm', function () { return 'sha256'; });
        $this->assertStringStartsWith('sha256-', AutoSRI::get_sri_hash($url));
    }

    public function test_pre_get_hash_short_circuits() {
        add_filter('auto_sri_pre_get_hash', function ($pre, $url) {
            return 'sha384-pinned';
        }, 10, 2);

        $this->assertSame('sha384-pinned', AutoSRI::get_sri_hash('https://cdn.example.com/c.js'));
    }

    public function test_pre_get_hash_false_skips_sri() {
        add_filter('auto_sri_pre_get_hash', function () { return false; });

        $tag = '<script src="https://cdn.example.com/d.js"></script>';
        $this->assertSame($tag, AutoSRI::inject_sri($tag, 'd', 'https://cdn.example.com/d.js'));
    }

    public function test_hash_computed_action_fires_once() {
        $calls = [];
        add_action('auto_sri_hash_computed', function ($url, $sri, $algorithm) use (&$calls) {
            $calls[] = [$url, $sri, $algorithm];
        }, 10, 3);

        AutoSRI::get_sri_hash('https://cdn.example.com/e.js');
        AutoSRI::get_sri_hash('https://cdn.example.com/e.js'); // cached, no second call

        $this->assertCount(1, $calls);
        $this->assertSame('https://cdn.example.com/e.js', $calls[0][0]);
        $this->assertSame('sha384', $calls[0][2]);
    }

    public function test_is_excluded_filter() {
        add_filter('auto_sri_is_excluded', function ($excluded, $url) {
            return $excluded || strpos($url, 'skip-me') !== false;
        }, 10, 2);

        $this->assertTrue(AutoSRI::is_excluded('https://cdn.example.com/skip-me.js'));
        $this->assertFalse(AutoSRI::is_excluded('https://cdn.example.com/keep-me.js'));
        $this->assertTrue(AutoSRI::is_excluded('https://www.google.com/recaptcha/api.js'));
    }

    public function test_enabled_filter_switches_sri_off() {
        $tag = '<script src="https://cdn.example.com/off.js"></script>';
        add_filter('auto_sri_enabled', function () { return false; });

        $this->assertFalse(AutoSRI::is_enabled());
        $this->assertSame($tag, AutoSRI::inject_sri($tag, 'off', 'https://cdn.example.com/off.js'));
    }

    public function test_rewrite_html_filter_skips_output_buffer() {
        $level = ob_get_level();
        add_filter('auto_sri_rewrite_html', function () { return false; });
        AutoSRI::start_buffer();
        $this->assertSame($level, ob_get_level());

        remove_all_filters('auto_sri_rewrite_html');
        AutoSRI::start_buffer();
        $this->assertSame($level + 1, ob_get_level());
        ob_end_clean();
    }

    public function test_builtin_exclusions_are_filterable() {
        $this->assertContains('fonts.googleapis.com', AutoSRI::get_builtin_exclusions());
        $this->assertTrue(AutoSRI::is_excluded('https://FONTS.googleapis.com/css?family=Roboto'));

        add_filter('auto_sri_builtin_exclusions', function ($patterns) {
            return array_values(array_diff($patterns, ['fonts.googleapis.com']));
        });
        $this->assertFalse(AutoSRI::is_excluded('https://fonts.googleapis.com/css?family=Roboto'));
        $this->assertTrue(AutoSRI::is_excluded('https://www.google.com/recaptcha/api.js'));
    }
}
