<?php
/**
 * Shared admin layout helpers and auth guard.
 */

if (!defined('BASE_URL')) {
    $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $appBase = preg_replace('#/(admin|manager)$#', '', $scriptPath);
    $appBase = rtrim($appBase, '/');
    if ($appBase === '') {
        $appBase = '/';
    }
    define('BASE_URL', $appBase);
}

function adminRequireLogin(string $redirectPath): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
        header('Location: ../login.php?redirect=' . urlencode($redirectPath));
        exit;
    }
}

function adminCurrentEmployee(mysqli $conn): ?array
{
    $employeeId = (int) ($_SESSION['user_id'] ?? 0);
    if ($employeeId <= 0) {
        return null;
    }

    $result = mysqli_query($conn, "SELECT * FROM Employee WHERE EmployeeID = {$employeeId} LIMIT 1");
    return mysqli_fetch_assoc($result) ?: null;
}

function adminProjectDisplayName(array $row): string
{
    if (!empty($row['ProjectTitle'])) {
        return $row['ProjectTitle'];
    }

    $desc = trim($row['Description'] ?? '');
    if ($desc !== '') {
        $firstLine = strtok($desc, "\n");
        return strlen($firstLine) > 60 ? substr($firstLine, 0, 57) . '…' : $firstLine;
    }

    return 'Project #' . ($row['ProjectID'] ?? '—');
}

function adminProjectStatusLabel(string $projectStatus, ?string $latestUpdateStatus = null): array
{
    if ($latestUpdateStatus === 'Pending') {
        return ['label' => 'Inspection Needed', 'class' => 'admin-badge-inspection'];
    }

    return match ($projectStatus) {
        'Ongoing' => ['label' => 'On Track', 'class' => 'admin-badge-track'],
        'On Hold' => ['label' => 'Delay', 'class' => 'admin-badge-delay'],
        'Completed' => ['label' => 'Completed', 'class' => 'admin-badge-complete'],
        'Cancelled' => ['label' => 'Cancelled', 'class' => 'admin-badge-cancelled'],
        default => ['label' => $projectStatus, 'class' => 'admin-badge-track'],
    };
}

function adminTimeAgo(?string $date): string
{
    if (empty($date)) {
        return '—';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return htmlspecialchars($date);
    }

    $diff = time() - $timestamp;
    if ($diff < 3600) {
        return max(1, (int) floor($diff / 60)) . ' min ago';
    }
    if ($diff < 86400) {
        return max(1, (int) floor($diff / 3600)) . ' hours ago';
    }
    if ($diff < 604800) {
        return max(1, (int) floor($diff / 86400)) . ' days ago';
    }

    return date('M j, Y', $timestamp);
}

function adminEquipmentUtilization(mysqli $conn): float
{
    $row = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total,
                SUM(CASE WHEN AvailabilityStatus = 'Rented' THEN 1 ELSE 0 END) AS rented
         FROM Equipment"
    ));

    $total = (int) ($row['total'] ?? 0);
    if ($total === 0) {
        return 0;
    }

    return round(((int) $row['rented'] / $total) * 100, 1);
}

function adminComplianceScore(mysqli $conn): int
{
    $row = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total,
                SUM(CASE WHEN ProjectStatus IN ('On Hold', 'Cancelled') THEN 1 ELSE 0 END) AS issues
         FROM Project"
    ));

    $total = (int) ($row['total'] ?? 0);
    if ($total === 0) {
        return 100;
    }

    $issues = (int) ($row['issues'] ?? 0);
    return max(0, min(100, 100 - (int) round(($issues / $total) * 100)));
}
