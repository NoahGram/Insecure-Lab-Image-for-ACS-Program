<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

ini_set('log_errors', '1');
ini_set('error_log', '/var/log/php_errors.log');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);