<?php
// VULNERABILITY: Stored XSS in page content
// The page content from database is displayed without any sanitization
// Students can inject JavaScript by editing page content

// This function overrides the secure display to allow unescaped content
if (!function_exists('display_page_content_override')) {
    function display_page_content_override($content): string {
        // VULNERABLE: Returns content directly without escaping
        // Any HTML/JavaScript will be executed in the browser
        return $content;
    }
}
