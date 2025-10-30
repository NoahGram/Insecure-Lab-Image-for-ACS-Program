<?php
session_start();

function h($s) {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_admin() {
    return ($_SESSION['role'] ?? '') === 'admin';
}
