<?php
if (!is_admin() && !isset($_GET['et_fb']) && !isset($_GET['et_pb_preview']) && defined('CSP_NONCE')) {
    function generate_pub_csp() {
        // ajust rules and URLs as needed, they are usually specifically tailored to each site
        $csp = "upgrade-insecure-requests; " .
            "base-uri 'self'; " .
            "default-src 'none'; " .
            "script-src 'nonce-" . CSP_NONCE . "' 'unsafe-inline' 'strict-dynamic' " .
                "https://c0.wp.com https://calendar.google.com https://static.xx.fbcdn.net; " .
            "script-src-attr 'nonce-" . CSP_NONCE . "' 'self' 'unsafe-inline'; " .
            "script-src-elem 'nonce-" . CSP_NONCE . "' 'self' " .
                "https://www.clarity.ms https://scripts.clarity.ms https://www.googletagmanager.com; " .
            "object-src 'none'; " .
            "style-src 'nonce-" . CSP_NONCE . "' 'self' 'unsafe-inline' https://fonts.gstatic.com https://fonts.googleapis.com; " .
            "style-src-elem 'nonce-" . CSP_NONCE . "' 'self' 'unsafe-inline' " .
                " https://s0.wp.com https://c0.wp.com https://i0.wp.com https://platform.twitter.com/widgets.js; " .
            "style-src-attr 'nonce-" . CSP_NONCE . "' 'self' 'unsafe-inline'  " .
                "https://c0.wp.com data:; " .
            "font-src 'self'  " .
                "https://fonts.gstatic.com https://s0.wp.com https://c0.wp.com data: ; " .
            "img-src 'self'  " .
                "https://accept_my_own.pictures.domain.tld https://pixel.wp.com https://cdn-cookieyes.com data: ; " .
            "connect-src 'self'  " .
                "https://connect.facebook.net https://cdn-cookieyes.com https://cdn.trustindex.io; " .
            "form-action 'self'  " .
                "https://e.abla.io; " .
            "frame-ancestors 'self'; " .
            "frame-src 'self'  " .
                "https://calendar.google.com https://td.doubleclick.net https://www.googletagmanager.com; "; // "report-uri /csp-reports;"
        header("Content-Security-Policy: " . $csp); //Content-Security-Policy-Report-Only
    }
    add_action('send_headers', 'generate_pub_csp');

function add_csp_nonce_to_tags($buffer) {
    if (!is_admin() && defined('CSP_NONCE') && !empty($buffer)) {
        // Use DOMDocument for parse et modify HTML
        $dom = new DOMDocument;

        // Load HTML with removal of errors due to malformed tags
        @$dom->loadHTML(mb_convert_encoding($buffer, 'HTML-ENTITIES', 'UTF-8'));

        // List of tags to which the nonce need to be added
        $tags = ['link', 'script', 'style'];

        // Browse document elements
        foreach ($tags as $tag) {
            $elements = $dom->getElementsByTagName($tag);
            foreach ($elements as $element) {
                // Delete any existing nonce, start clean
                $element->removeAttribute('nonce');

                // Create a new tag with the nonce first
                $newElement = $dom->createElement($tag);
                $newElement->setAttribute('nonce', CSP_NONCE);

                // Copy other attributes
                foreach ($element->attributes as $attr) {
                    if ($attr->name !== 'nonce') {
                        $newElement->setAttribute($attr->name, $attr->value);
                    }
                }

                // Copy the content of the element
                foreach ($element->childNodes as $child) {
                    $newElement->appendChild($dom->importNode($child, true));
                }

                // Replace the old component with the new one
                $element->parentNode->replaceChild($newElement, $element);
            }
        }

        // Retrieve the modified HTML
        $html = $dom->saveHTML();

        // Fix self-closing tags
        $buffer = preg_replace('/(<(link|script|style)([^>]*?))(>|(\/>))/', '$1>', $html);
    }

    return $buffer;
}

function enable_output_buffering() {
    ob_start('add_csp_nonce_to_tags');
}
add_action('template_redirect', 'enable_output_buffering');

} else {
    // Handle the case where the constant is not defined
    // It shouldn't if the file containing this definition is correctly prepended
    error_log('The constant CSP_NONCE is not defined. Please check out your prepended file');
}
?>
