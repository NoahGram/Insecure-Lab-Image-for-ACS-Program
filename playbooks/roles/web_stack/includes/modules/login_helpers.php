<?php

function is_account_locked(int $failed_attempts, ?int $last_failed): bool
    {
        $lockout_time = 15 * 60;
        $max_attempts = 5;
        return $failed_attempts >= $max_attempts && (time() - ($last_failed ?? 0)) < $lockout_time;
    }

function check_account_status(?array $row, string $username): array
{
    $result = [
        'ok' => false,
        'login_error' => 'Invalid username or password.' 
    ];

    if (empty($row)) {
        return $result; 
    }

    $failed_attempts = (int)$row['failed_attempts'];
    $last_failed = $row['last_failed'] ? strtotime($row['last_failed']) : 0;

    if (is_account_locked($failed_attempts, $last_failed)) {
        logAccountLocked($username);
        $result['login_error'] = 'Account temporarily locked. Try again later.';
        return $result;
    }

    $result['ok'] = true;
    $result['login_error'] = ''; 
    return $result;
}

function logAccountLocked(string $username): void {
    ActivityLogger::log(
        'account_locked',
        "Account locked due to too many failed login attempts",
        ActivityLogger::SECURITY,
        $username
    );
}  

function handle_successful_login(mysqli $conn, int $userId, string $username): void
{
    $stmt = $conn->prepare('UPDATE users SET failed_attempts=0, last_failed=NULL WHERE id=?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();

    ActivityLogger::logLogin($username);
}

function handle_failed_login(mysqli $conn, int $userId, string $username, array $row): string
{
    $failed_attempts = $row['failed_attempts'] + 1;

    $stmt = $conn->prepare('UPDATE users SET failed_attempts=?, last_failed=NOW() WHERE id=?');
    $stmt->bind_param('ii', $failed_attempts, $userId);
    $stmt->execute();
    $stmt->close();

    logFailedLogin($username, $failed_attempts);

    return 'Invalid username or password.';
}

function logFailedLogin(string $username, int $attempt_count): void {
    $severity = ($attempt_count >= 3) ? ActivityLogger::WARNING : ActivityLogger::INFO;
    ActivityLogger::log(
        'login_failed', 
        "Failed login attempt #{$attempt_count}", 
        $severity, 
        $username
    );
}