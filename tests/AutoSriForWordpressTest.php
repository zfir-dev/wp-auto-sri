<?php

use PHPUnit\Framework\TestCase;

//
// 🧪 TESTS
// -------------------------------------------------------------

class AutoSriForWordpressTest extends TestCase {

    public function test_injects_sri_for_external_scripts() {

        $tag    = '<script src="https://cdn.example.com/app.js"></script>';
        $handle = 'my-script';
        $src    = 'https://cdn.example.com/app.js';

        $result = WP_Auto_SRI::inject_sri($tag, $handle, $src);

        echo "\n=== test_injects_sri_for_external_scripts ===\n";
        echo "Input:  $tag\n";
        echo "Output: $result\n";
        echo "==============================================\n\n";

        $this->assertStringContainsString('integrity="sha384-', $result);
        $this->assertStringContainsString('crossorigin="anonymous"', $result);
    }


    public function test_does_not_modify_internal_scripts() {

        $tag    = '<script src="https://example.com/app.js"></script>';
        $handle = 'my-script';
        $src    = 'https://example.com/app.js';

        $result = WP_Auto_SRI::inject_sri($tag, $handle, $src);

        echo "\n=== test_does_not_modify_internal_scripts ===\n";
        echo "Input:  $tag\n";
        echo "Output: $result\n";
        echo "==============================================\n\n";

        $this->assertEquals($tag, $result);
    }


    public function test_does_not_duplicate_integrity() {

        $tag    = '<script integrity="sha384-abc" src="https://cdn.com/app.js"></script>';
        $handle = 'my-script';
        $src    = 'https://cdn.com/app.js';

        $result = WP_Auto_SRI::inject_sri($tag, $handle, $src);

        echo "\n=== test_does_not_duplicate_integrity ===\n";
        echo "Input:  $tag\n";
        echo "Output: $result\n";
        echo "==========================================\n\n";

        $this->assertEquals($tag, $result);
    }


    public function test_style_tag_integrity() {

        $tag    = '<link rel="stylesheet" href="https://cdn.com/style.css">';
        $handle = 'my-style';
        $src    = 'https://cdn.com/style.css';
        $media  = 'all';

        $result = WP_Auto_SRI::inject_sri($tag, $handle, $src, $media);

        echo "\n=== test_style_tag_integrity ===\n";
        echo "Input:  $tag\n";
        echo "Output: $result\n";
        echo "==================================\n\n";

        $this->assertStringContainsString('integrity="sha384-', $result);
        $this->assertStringContainsString('crossorigin="anonymous"', $result);
    }
}
