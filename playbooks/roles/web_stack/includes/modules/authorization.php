<?php
function is_admin()
{
    return ($_SESSION['role'] ?? '') === 'admin';
}