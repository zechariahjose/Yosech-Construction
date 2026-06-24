<?php

require_once __DIR__ . '/../admin/helpers.php';

function managerRequireLogin(string $redirectPath): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Manager') {
        header('Location: ../login.php?redirect=' . urlencode($redirectPath));
        exit;
    }
}

function managerPendingRentals(mysqli $conn): int
{
    $row = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total FROM Application
         WHERE ApplicationType = 'Equipment Rental' AND Status = 'Pending'"
    ));
    return (int) ($row['total'] ?? 0);
}

function managerPendingProjectApps(mysqli $conn): int
{
    $row = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total FROM Application
         WHERE ApplicationType = 'New Project' AND Status = 'Pending'"
    ));
    return (int) ($row['total'] ?? 0);
}

function managerPendingInspections(mysqli $conn): int
{
    $row = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total FROM Project_Update WHERE Status = 'Pending'"
    ));
    return (int) ($row['total'] ?? 0);
}

function managerProjectStatusLabel(string $projectStatus, ?string $latestUpdateStatus = null): array
{
    if ($latestUpdateStatus === 'Pending') {
        return ['label' => 'Inspection Needed', 'class' => 'admin-badge-inspection'];
    }

    return match ($projectStatus) {
        'Ongoing' => ['label' => 'On Track', 'class' => 'admin-badge-track'],
        'On Hold' => ['label' => 'Delayed', 'class' => 'admin-badge-delay'],
        'Completed' => ['label' => 'Completed', 'class' => 'admin-badge-approved'],
        'Cancelled' => ['label' => 'Cancelled', 'class' => 'admin-badge-rejected'],
        default => ['label' => $projectStatus, 'class' => 'admin-badge-track'],
    };
}

function managerLoadPreferences(): array
{
    return $_SESSION['pm_preferences'] ?? [
        'view_mode' => 'list',
        'notify_rentals' => true,
        'notify_on_hold' => true,
        'notify_inspections' => true,
    ];
}

function managerSavePreferences(array $prefs): void
{
    $_SESSION['pm_preferences'] = array_merge(managerLoadPreferences(), $prefs);
}
