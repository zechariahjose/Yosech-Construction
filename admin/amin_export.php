<?php
/**
 * Admin CSV Export
 * ?type=clients | applications | projects
 */
include("../config/database.php");
include("../includes/admin/helpers.php");

adminRequireLogin('admin/amin_export.php');

$type = $_GET['type'] ?? '';
$allowed = ['clients', 'applications', 'projects', 'applications_excel'];

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

// ── APPLICATIONS EXCEL (.xlsx) — two sheets + charts ────────
if ($type === 'applications_excel') {

    // ── Fetch project applications ──────────────────────────
    $projResult = mysqli_query($conn,
        "SELECT a.ApplicationID, a.Status,
                CONCAT(c.Client_FirstName,' ',c.Client_LastName) AS client_name,
                a.ProjectTitle, a.ProjectLocation,
                a.ProposalBudget, a.ProjectStartDate, a.ProjectEndDate,
                a.SubmissionDate
         FROM Application a
         JOIN Client c ON a.UserID = c.UserID
         WHERE a.ApplicationType = 'New Project'
         ORDER BY a.SubmissionDate DESC"
    );
    $projRows = [];
    while ($r = mysqli_fetch_assoc($projResult)) $projRows[] = $r;

    // ── Fetch rental applications ───────────────────────────
    $rentResult = mysqli_query($conn,
        "SELECT a.ApplicationID, a.Status,
                CONCAT(c.Client_FirstName,' ',c.Client_LastName) AS client_name,
                eo.Name AS EquipmentName, eo.Model AS EquipmentModel,
                a.RentalStartDate, a.RentalEndDate,
                a.NeedsOperator, a.SubmissionDate
         FROM Application a
         JOIN Client c ON a.UserID = c.UserID
         LEFT JOIN Equipment e ON a.EquipmentID = e.EquipmentID
         LEFT JOIN EquipmentOffering eo ON e.EquipmentOfferingID = eo.EquipmentOfferingID
         WHERE a.ApplicationType = 'Equipment Rental'
         ORDER BY a.SubmissionDate DESC"
    );
    $rentRows = [];
    while ($r = mysqli_fetch_assoc($rentResult)) $rentRows[] = $r;

    // ── Summary counts for chart data ──────────────────────
    $summary = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT
            SUM(Status='Pending'  AND ApplicationType='New Project')       AS proj_pending,
            SUM(Status='Approved' AND ApplicationType='New Project')       AS proj_approved,
            SUM(Status='Rejected' AND ApplicationType='New Project')       AS proj_rejected,
            SUM(Status='Pending'  AND ApplicationType='Equipment Rental')  AS rent_pending,
            SUM(Status='Approved' AND ApplicationType='Equipment Rental')  AS rent_approved,
            SUM(Status='Rejected' AND ApplicationType='Equipment Rental')  AS rent_rejected
         FROM Application"
    ));

    // ── XML helper ──────────────────────────────────────────
    function xlCell(string $value, string $type = 'str', ?string $style = null): string {
        $s = $style !== null ? " s=\"{$style}\"" : '';
        $v = htmlspecialchars($value, ENT_XML1);
        if ($type === 'n') {
            return "<c{$s} t=\"n\"><v>{$v}</v></c>";
        }
        if ($type === 'str') {
            return "<c{$s} t=\"inlineStr\"><is><t>{$v}</t></is></c>";
        }
        return "<c{$s} t=\"inlineStr\"><is><t>{$v}</t></is></c>";
    }

    function xlRow(array $cells, string $rowNum): string {
        return "<row r=\"{$rowNum}\">" . implode('', $cells) . "</row>";
    }

    function xlHeader(array $values, string $rowNum): string {
        $cells = array_map(fn($v) => xlCell((string)$v, 'str', '1'), $values);
        return xlRow($cells, $rowNum);
    }

    // ── Build Sheet 1: Project Applications ────────────────
    $projSheet = '';
    $projSheet .= xlHeader(['App ID','Status','Client','Project Title','Location','Proposed Budget (PHP)','Project Start','Project End','Submitted'], '1');
    foreach ($projRows as $i => $r) {
        $rn = $i + 2;
        $cells = [
            xlCell((string)$r['ApplicationID'], 'n'),
            xlCell($r['Status']),
            xlCell($r['client_name']),
            xlCell($r['ProjectTitle'] ?: ''),
            xlCell($r['ProjectLocation'] ?: ''),
            xlCell($r['ProposalBudget'] !== null ? number_format((float)$r['ProposalBudget'], 2, '.', '') : '', 'n'),
            xlCell($r['ProjectStartDate'] ?: ''),
            xlCell($r['ProjectEndDate'] ?: ''),
            xlCell($r['SubmissionDate'] ?: ''),
        ];
        $projSheet .= xlRow($cells, (string)$rn);
    }

    // ── Build Sheet 2: Equipment Rental Applications ────────
    $rentSheet = '';
    $rentSheet .= xlHeader(['App ID','Status','Client','Equipment','Model','Rental Start','Rental End','Operator','Submitted'], '1');
    foreach ($rentRows as $i => $r) {
        $rn = $i + 2;
        $cells = [
            xlCell((string)$r['ApplicationID'], 'n'),
            xlCell($r['Status']),
            xlCell($r['client_name']),
            xlCell($r['EquipmentName'] ?: ''),
            xlCell($r['EquipmentModel'] ?: ''),
            xlCell($r['RentalStartDate'] ?: ''),
            xlCell($r['RentalEndDate'] ?: ''),
            xlCell($r['NeedsOperator'] ? 'Yes' : 'No'),
            xlCell($r['SubmissionDate'] ?: ''),
        ];
        $rentSheet .= xlRow($cells, (string)$rn);
    }

    // ── Build Sheet 3: Summary (chart source data) ──────────
    $sumSheet = '';
    $sumSheet .= xlHeader(['Category','Pending','Approved','Rejected'], '1');
    $sumSheet .= xlRow([
        xlCell('Project Applications'),
        xlCell((string)($summary['proj_pending']  ?? 0), 'n'),
        xlCell((string)($summary['proj_approved'] ?? 0), 'n'),
        xlCell((string)($summary['proj_rejected'] ?? 0), 'n'),
    ], '2');
    $sumSheet .= xlRow([
        xlCell('Equipment Rentals'),
        xlCell((string)($summary['rent_pending']  ?? 0), 'n'),
        xlCell((string)($summary['rent_approved'] ?? 0), 'n'),
        xlCell((string)($summary['rent_rejected'] ?? 0), 'n'),
    ], '3');

    // ── Assemble .xlsx (ZIP with XML parts) ─────────────────
    $date    = date('Ymd');
    $fname   = "yosech_history_{$date}.xlsx";
    $tmpFile = tempnam(sys_get_temp_dir(), 'ysc_xlsx_');

    $zip = new ZipArchive();
    $zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    // [Content_Types].xml
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml"  ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml"              ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml"     ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/worksheets/sheet2.xml"     ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/worksheets/sheet3.xml"     ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/charts/chart1.xml"         ContentType="application/vnd.openxmlformats-officedocument.drawingml.chart+xml"/>
  <Override PartName="/xl/charts/chart2.xml"         ContentType="application/vnd.openxmlformats-officedocument.drawingml.chart+xml"/>
  <Override PartName="/xl/drawings/drawing3.xml"     ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>
  <Override PartName="/xl/styles.xml"                ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>');

    // _rels/.rels
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

    // xl/workbook.xml
    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
          xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Project Applications" sheetId="1" r:id="rId1"/>
    <sheet name="Equipment Rentals"    sheetId="2" r:id="rId2"/>
    <sheet name="Summary &amp; Charts" sheetId="3" r:id="rId3"/>
  </sheets>
</workbook>');

    // xl/_rels/workbook.xml.rels
    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet3.xml"/>
  <Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"   Target="styles.xml"/>
</Relationships>');

    // xl/styles.xml — style 0=normal, 1=bold header
    $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts>
    <font><sz val="11"/></font>
    <font><b/><sz val="11"/></font>
  </fonts>
  <fills>
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF4472C4"/></patternFill></fill>
  </fills>
  <borders><border><left/><right/><top/><bottom/><diagonal/></border></borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs>
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0"><alignment horizontal="center"/></xf>
  </cellXfs>
</styleSheet>');

    // xl/worksheets/sheet1.xml — Project Applications
    $zip->addFromString('xl/worksheets/sheet1.xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetView><selection activeCell="A1"/></sheetView>
  <sheetData>' . $projSheet . '</sheetData>
</worksheet>');

    // xl/worksheets/sheet2.xml — Equipment Rentals
    $zip->addFromString('xl/worksheets/sheet2.xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetView><selection activeCell="A1"/></sheetView>
  <sheetData>' . $rentSheet . '</sheetData>
</worksheet>');

    // xl/worksheets/sheet3.xml — Summary + drawing reference
    $zip->addFromString('xl/worksheets/sheet3.xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
           xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheetView><selection activeCell="A1"/></sheetView>
  <sheetData>' . $sumSheet . '</sheetData>
  <drawing r:id="rId1"/>
</worksheet>');

    // xl/worksheets/_rels/sheet3.xml.rels
    $zip->addFromString('xl/worksheets/_rels/sheet3.xml.rels',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing3.xml"/>
</Relationships>');

    // ── Chart 1: Project Applications status (bar chart) ────
    $chart1 = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<c:chartSpace xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart"
              xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
              xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <c:chart>
    <c:title><c:tx><c:rich><a:bodyPr/><a:p><a:r><a:t>Project Applications by Status</a:t></a:r></a:p></c:rich></c:tx><c:overlay val="0"/></c:title>
    <c:autoTitleDeleted val="0"/>
    <c:plotArea>
      <c:barChart>
        <c:barDir val="col"/>
        <c:grouping val="clustered"/>
        <c:ser>
          <c:idx val="0"/><c:order val="0"/>
          <c:tx><c:strRef><c:f>\'Summary &amp; Charts\'!$B$1</c:f></c:strRef></c:tx>
          <c:cat><c:strRef><c:f>\'Summary &amp; Charts\'!$A$2:$A$3</c:f></c:strRef></c:cat>
          <c:val><c:numRef><c:f>\'Summary &amp; Charts\'!$B$2:$B$3</c:f></c:numRef></c:val>
        </c:ser>
        <c:ser>
          <c:idx val="1"/><c:order val="1"/>
          <c:tx><c:strRef><c:f>\'Summary &amp; Charts\'!$C$1</c:f></c:strRef></c:tx>
          <c:cat><c:strRef><c:f>\'Summary &amp; Charts\'!$A$2:$A$3</c:f></c:strRef></c:cat>
          <c:val><c:numRef><c:f>\'Summary &amp; Charts\'!$C$2:$C$3</c:f></c:numRef></c:val>
        </c:ser>
        <c:ser>
          <c:idx val="2"/><c:order val="2"/>
          <c:tx><c:strRef><c:f>\'Summary &amp; Charts\'!$D$1</c:f></c:strRef></c:tx>
          <c:cat><c:strRef><c:f>\'Summary &amp; Charts\'!$A$2:$A$3</c:f></c:strRef></c:cat>
          <c:val><c:numRef><c:f>\'Summary &amp; Charts\'!$D$2:$D$3</c:f></c:numRef></c:val>
        </c:ser>
        <c:axId val="1"/><c:axId val="2"/>
      </c:barChart>
      <c:catAx><c:axId val="1"/><c:scaling><c:orientation val="minMax"/></c:scaling><c:delete val="0"/><c:axPos val="b"/><c:crossAx val="2"/></c:catAx>
      <c:valAx><c:axId val="2"/><c:scaling><c:orientation val="minMax"/></c:scaling><c:delete val="0"/><c:axPos val="l"/><c:crossAx val="1"/></c:valAx>
    </c:plotArea>
    <c:legend><c:legendPos val="b"/></c:legend>
  </c:chart>
</c:chartSpace>';

    // ── Chart 2: Pie chart — overall approval rate ───────────
    $totalApproved = (int)($summary['proj_approved'] ?? 0) + (int)($summary['rent_approved'] ?? 0);
    $totalRejected = (int)($summary['proj_rejected'] ?? 0) + (int)($summary['rent_rejected'] ?? 0);
    $totalPending  = (int)($summary['proj_pending']  ?? 0) + (int)($summary['rent_pending']  ?? 0);

    $chart2 = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<c:chartSpace xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart"
              xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">
  <c:chart>
    <c:title><c:tx><c:rich><a:bodyPr/><a:p><a:r><a:t>Overall Application Status</a:t></a:r></a:p></c:rich></c:tx><c:overlay val="0"/></c:title>
    <c:autoTitleDeleted val="0"/>
    <c:plotArea>
      <c:pieChart>
        <c:ser>
          <c:idx val="0"/><c:order val="0"/>
          <c:cat>
            <c:strRef>
              <c:f>\'Summary &amp; Charts\'!$B$1:$D$1</c:f>
              <c:strCache><c:ptCount val="3"/>
                <c:pt idx="0"><c:v>Pending</c:v></c:pt>
                <c:pt idx="1"><c:v>Approved</c:v></c:pt>
                <c:pt idx="2"><c:v>Rejected</c:v></c:pt>
              </c:strCache>
            </c:strRef>
          </c:cat>
          <c:val>
            <c:numRef>
              <c:f>Sheet3!$A$10:$A$12</c:f>
              <c:numCache><c:ptCount val="3"/>
                <c:pt idx="0"><c:v>' . $totalPending  . '</c:v></c:pt>
                <c:pt idx="1"><c:v>' . $totalApproved . '</c:v></c:pt>
                <c:pt idx="2"><c:v>' . $totalRejected . '</c:v></c:pt>
              </c:numCache>
            </c:numRef>
          </c:val>
        </c:ser>
        <c:firstSliceAng val="0"/>
      </c:pieChart>
    </c:plotArea>
    <c:legend><c:legendPos val="r"/></c:legend>
  </c:chart>
</c:chartSpace>';

    $zip->addFromString('xl/charts/chart1.xml', $chart1);
    $zip->addFromString('xl/charts/chart2.xml', $chart2);

    // xl/drawings/drawing3.xml — positions both charts on sheet3
    $zip->addFromString('xl/drawings/drawing3.xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing"
           xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
           xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <xdr:twoCellAnchor>
    <xdr:from><xdr:col>0</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>4</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:from>
    <xdr:to>  <xdr:col>7</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>22</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:to>
    <xdr:graphicFrame macro=""><xdr:nvGraphicFramePr>
      <xdr:cNvPr id="2" name="Chart 1"/>
      <xdr:cNvGraphicFramePr/>
    </xdr:nvGraphicFramePr>
    <xdr:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/></xdr:xfrm>
    <a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/chart">
      <c:chart xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart" r:id="rId1"/>
    </a:graphicData></a:graphic>
    </xdr:graphicFrame><xdr:clientData/>
  </xdr:twoCellAnchor>
  <xdr:twoCellAnchor>
    <xdr:from><xdr:col>8</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>4</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:from>
    <xdr:to>  <xdr:col>15</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>22</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:to>
    <xdr:graphicFrame macro=""><xdr:nvGraphicFramePr>
      <xdr:cNvPr id="3" name="Chart 2"/>
      <xdr:cNvGraphicFramePr/>
    </xdr:nvGraphicFramePr>
    <xdr:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/></xdr:xfrm>
    <a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/chart">
      <c:chart xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart" r:id="rId2"/>
    </a:graphicData></a:graphic>
    </xdr:graphicFrame><xdr:clientData/>
  </xdr:twoCellAnchor>
</xdr:wsDr>');

    // xl/drawings/_rels/drawing3.xml.rels
    $zip->addFromString('xl/drawings/_rels/drawing3.xml.rels',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/chart" Target="../charts/chart1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/chart" Target="../charts/chart2.xml"/>
</Relationships>');

    $zip->close();

    // Stream the file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    header('Content-Length: ' . filesize($tmpFile));
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($tmpFile);
    unlink($tmpFile);
    exit;
}
