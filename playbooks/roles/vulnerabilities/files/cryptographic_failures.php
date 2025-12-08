<?php
    // Vulnerability: Cryptography Failures
    // Uses weak MD5 hashing instead of bcrypt
    $generate_hash = fn(string $input, bool $raw_hash): string => md5($input, $raw_hash);
    
    // Override password verification to accept plaintext and weak hashes (MD5)
    // This allows login with insecure password storage
    function verify_password_override(string $password, string $stored): bool {
        // Check if it's a bcrypt/argon2 hash (secure - shouldn't happen in vuln mode)
        if (strlen($stored) >= 60 && (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon2'))) {
            return password_verify($password, $stored);
        }
        // Check MD5 hash (32 hex chars)
        if (strlen($stored) === 32 && ctype_xdigit($stored)) {
            return hash_equals($stored, md5($password));
        }
        // Fallback: plaintext comparison (most insecure!)
        return hash_equals($stored, $password);
    }
?>
