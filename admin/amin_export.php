<?php
/**
 * Admin CSV Export
 * ?type=clients | applications | projects
 */
include("../config/database.php");
include("../includes/admin/helpers.php");

adminRequireLogin('admin/amin_export.php');

$type = $_GET['type'] ?? '';
$allowed = ['clients', 'applications', 'projects'];

if (!in_array($type, $allowed)) {
    http_response_code(400);
    exit('Invalid export type.');
}

// ── Helper: output CSV header + rows ───────────────────────
function sendCsv(string $filename, array $headers, array $rows): void
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    // UTF-8 BOM so Excel opens it correctly
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

// ── CLIENTS ─────────────────────────────────────────────────
if ($type === 'clients') {
    $result = mysqli_query($conn,
        "SELECT c.UserID,
                CONCAT(c.Client_FirstName,' ',IFNULL(CONCAT(c.Client_MI,'. '),''),c.Client_LastName) AS full_name,
                c.Client_Username, c.Client_Email, c.Client_ContactNumber,
                (SELECT COUNT(*) FROM Application a WHERE a.UserID = c.UserID) AS applications,
                (SELECT COUNT(*) FROM Application a WHERE a.UserID = c.UserID AND a.Status='Approved') AS approved,
                (SELECT COALESCE(SUM(a.ProposalBudget),0) FROM Application a WHERE a.UserID=c.UserID AND a.Status='Approved') AS approved_revenue
         FROM Client c
         ORDER BY c.UserID DESC"
    );

    $headers = ['ID','Full Name','Username','Email','Contact','Total Applications','Approved','Approved Revenue (PHP)'];
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = [
            $row['UserID'],
            $row['full_name'],
            $row['Client_Username'],
            $row['Client_Email'],
            $row['Client_ContactNumber'] ?: '—',
            $row['applications'],
            $row['approved'],
            number_format((float)$row['approved_revenue'], 2, '.', ''),
        ];
    }

    sendCsv('yosech_clients_' . date('Ymd') . '.csv', $headers, $rows);
}

// ── APPLICATIONS ─────────────────────────────────────────────
if ($type === 'applications') {
    $result = mysqli_query($conn,
        "SELECT a.ApplicationID, a.ApplicationType, a.Status,
                CONCAT(c.Client_FirstName,' ',c.Client_LastName) AS client_name,
                c.Client_Email,
                a.ProjectTitle, a.ProjectLocation,
                a.ProposalBudget, a.ProjectStartDate, a.ProjectEndDate,
                eo.Name AS EquipmentName, eo.Model AS EquipmentModel,
                a.RentalStartDate, a.RentalEndDate,
                a.NeedsOperator, a.SubmissionDate
         FROM Application a
         JOIN Client c ON a.UserID = c.UserID
         LEFT JOIN Equipment e ON a.EquipmentID = e.EquipmentID
         LEFT JOIN EquipmentOffering eo ON e.EquipmentOfferingID = eo.EquipmentOfferingID
         ORDER BY a.SubmissionDate DESC"
    );

    $headers = [
        'App ID','Type','Status','Client','Email',
        'Project Title','Location','Proposed Budget (PHP)','Project Start','Project End',
        'Equipment','Model','Rental Start','Rental End','Needs Operator','Submitted'
    ];
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = [
            $row['ApplicationID'],
            $row['ApplicationType'],
            $row['Status'],
            $row['client_name'],
            $row['Client_Email'],
            $row['ProjectTitle'] ?: '—',
            $row['ProjectLocation'] ?: '—',
            $row['ProposalBudget'] !== null ? number_format((float)$row['ProposalBudget'], 2, '.', '') : '—',
            $row['ProjectStartDate'] ?: '—',
            $row['ProjectEndDate'] ?: '—',
            $row['EquipmentName'] ?: '—',
            $row['EquipmentModel'] ?: '—',
            $row['RentalStartDate'] ?: '—',
            $row['RentalEndDate'] ?: '—',
            $row['NeedsOperator'] ? 'Yes' : 'No',
            $row['SubmissionDate'] ?: '—',
        ];
    }

    sendCsv('yosech_applications_' . date('Ymd') . '.csv', $headers, $rows);
}

// ── PROJECTS ─────────────────────────────────────────────────
if ($type === 'projects') {
    $result = mysqli_query($conn,
        "SELECT p.ProjectID, a.ProjectTitle, a.ProjectLocation, a.ApplicationType,
                CONCAT(c.Client_FirstName,' ',c.Client_LastName) AS client_name,
                c.Client_ContactNumber,
                p.ProjectStatus, p.ProjectPaymentStatus,
                p.ProposalBudget, p.StartDate, p.EndDate,
                p.ProposalStatus, p.ProposalDate
         FROM Project p
         JOIN Application a ON p.ApplicationID = a.ApplicationID
         JOIN Client c ON a.UserID = c.UserID
         ORDER BY p.ProjectID DESC"
    );

    $headers = [
        'Project ID','Title','Location','Type','Client','Contact',
        'Status','Payment Status','Budget (PHP)',
        'Start Date','End Date','Proposal Status','Proposal Date'
    ];
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = [
            $row['ProjectID'],
            $row['ProjectTitle'] ?: '—',
            $row['ProjectLocation'] ?: '—',
            $row['ApplicationType'],
            $row['client_name'],
            $row['Client_ContactNumber'] ?: '—',
            $row['ProjectStatus'],
            $row['ProjectPaymentStatus'],
            $row['ProposalBudget'] !== null ? number_format((float)$row['ProposalBudget'], 2, '.', '') : '—',
            $row['StartDate'] ?: '—',
            $row['EndDate'] ?: '—',
            $row['ProposalStatus'],
            $row['ProposalDate'] ?: '—',
        ];
    }

    sendCsv('yosech_projects_' . date('Ymd') . '.csv', $headers, $rows);
}
