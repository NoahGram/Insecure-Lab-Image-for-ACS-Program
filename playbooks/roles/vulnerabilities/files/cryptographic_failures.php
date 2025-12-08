<?php
    // Vulnerability: Cryptography Failures
    $generate_hash = fn(string $input, bool $raw_hash): string => md5($input, $raw_hash);
?>
