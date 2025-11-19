<?php
// Vulnerability: Disable CSRF protection
function verify_csrf(): bool {
    return true; // Always return true, bypassing CSRF checks
}
