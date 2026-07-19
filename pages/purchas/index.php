<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'view_purchas') && 
    !hasPermission($conn, $userId, 'create_purchas') && 
    !hasPermission($conn, $userId, 'edit_purchas') && 
    !hasPermission($conn, $userId, 'delete_purchas')) {
    header("Location: ../dashboard");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'invoice') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        die("Invalid ID");
    }
    
    // Fetch purchase record
    $pQuery = "SELECT p.*, pr.product_name, pr.product_code, l.lots_code, s.name as supplier_name, s.id as supplier_code, s.phone as supplier_phone, s.email as supplier_email, w.warehouse_name, uom.code as uom_code
               FROM purchasing p
               JOIN product pr ON p.product_id = pr.id
               JOIN lots l ON p.lot_id = l.id
               JOIN suppliers s ON p.supplier_id = s.id
               JOIN warehouses w ON p.warehouse_id = w.id
               LEFT JOIN unit_of_measure uom ON p.uom_id = uom.id
               WHERE p.id = $id LIMIT 1";
               
    $pRes = mysqli_query($conn, $pQuery);
    if (!$pRes || mysqli_num_rows($pRes) === 0) {
        die("Purchase transaction not found");
    }
    $p = mysqli_fetch_assoc($pRes);
    
    // Fetch grades
    $gradesQuery = "SELECT peg.*, pe.element_code, pe.element_name, pe.symbol 
                    FROM purchasing_element_grade peg
                    JOIN product_element pe ON peg.product_element_id = pe.id
                    WHERE peg.purchasing_id = $id";
    $gradesResult = mysqli_query($conn, $gradesQuery);
    $grades = [];
    if ($gradesResult) {
        while ($gRow = mysqli_fetch_assoc($gradesResult)) {
            $grades[] = $gRow;
        }
    }
    
    // Converted details
    $qty = (float)$p['quantity_kg'];
    $uom = $p['uom_code'] ?: 'kg';
    $purchaseValUsd = $p['purchase_value_usd'] !== null ? (float)$p['purchase_value_usd'] : 0;
    $purchaseValRwf = $p['purchase_value_rwf'] !== null ? (float)$p['purchase_value_rwf'] : 0;
    $exchangeRate = $p['exchange_rate'] !== null ? (float)$p['exchange_rate'] : 0;
    $netPaidUsd = $p['net_paid_supplier_usd'] !== null ? (float)$p['net_paid_supplier_usd'] : 0;

    // Find primary element grade from database composition
    $compQuery = "SELECT product_element_id FROM product_element_composition WHERE product_id = " . (int)$p['product_id'] . " AND is_primary_grade = 1 LIMIT 1";
    $compRes = mysqli_query($conn, $compQuery);
    $primary_elem_id = 0;
    if ($compRes && mysqli_num_rows($compRes) > 0) {
        $compRow = mysqli_fetch_assoc($compRes);
        $primary_elem_id = (int)$compRow['product_element_id'];
    }

    $primaryGradePct = 0.0;
    $primaryElementSymbol = '';
    $primaryElementName = '';
    foreach ($grades as $g) {
        if ((int)$g['product_element_id'] === $primary_elem_id) {
            $primaryGradePct = (float)$g['grade_pct'];
            $primaryElementSymbol = $g['symbol'];
            $primaryElementName = $g['element_name'];
            break;
        }
    }
    // Fallback if no primary grade is set in composition
    if ($primaryGradePct === 0.0 && !empty($grades)) {
        foreach ($grades as $g) {
            $sym = strtoupper($g['symbol']);
            if ($sym === 'SN' || $sym === 'TA' || $sym === 'WO3' || $sym === 'TA2O5') {
                $primaryGradePct = (float)$g['grade_pct'];
                $primaryElementSymbol = $g['symbol'];
                $primaryElementName = $g['element_name'];
                break;
            }
        }
        if ($primaryGradePct === 0.0) {
            $primaryGradePct = (float)$grades[0]['grade_pct'];
            $primaryElementSymbol = $grades[0]['symbol'];
            $primaryElementName = $grades[0]['element_name'];
        }
    }

    $product_code = strtoupper($p['product_code']);
    $product_name = strtolower($p['product_name']);

    $is_tantalum = (strpos($product_name, 'coltan') !== false || strpos($product_name, 'tantalum') !== false || strpos($product_code, 'TA') !== false);
    $is_wolframite = (strpos($product_name, 'wolframite') !== false || strpos($product_name, 'tungsten') !== false || strpos($product_code, 'W') !== false);
    $is_tin = !$is_tantalum && !$is_wolframite;
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Invoice - <?php echo htmlspecialchars($p['purchase_no']); ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');
        
        :root {
            --text-main: #0f172a;
            --text-muted: #475569;
            --border-color: #cbd5e1;
            --primary: #0f172a;
            --accent: #ec4f25;
            --grid-bg: #f8fafc;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            color: var(--text-main);
            background-color: #f1f5f9;
            margin: 0;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .no-print-bar {
            width: 100%;
            max-width: 800px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 500;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            background-color: #ffffff;
            color: var(--text-main);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn:hover {
            background-color: #f8fafc;
        }
        .btn-primary {
            background-color: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }
        .btn-primary:hover {
            background-color: #1e293b;
            border-color: #1e293b;
        }

        .page-container {
            background-color: #ffffff;
            width: 100%;
            max-width: 800px;
            min-height: 1000px;
            padding: 80px 60px 40px 60px;
            box-sizing: border-box;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            position: relative;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
        }

        .invoice-body-wrapper {
            flex: 1 0 auto;
        }

        .divHeader {
            margin-bottom: 40px;
            display: flex;
            justify-content: flex-start;
        }
        
        .header-logo {
            height: 50px;
            width: auto;
        }

        .divFooter {
            margin-top: auto;
            border-top: 0.75pt solid var(--accent);
            padding-top: 15px;
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .footer-left {
            text-align: left;
        }
        .footer-left .company-name {
            font-weight: 700;
            font-size: 9.5px;
            color: var(--text-main);
            margin-bottom: 4px;
        }
        .footer-right {
            text-align: right;
        }
        .footer-right .footer-web {
            color: #0000ff;
            text-decoration: underline;
            font-weight: 500;
        }

        .voucher-title-section {
            text-align: center;
            margin-bottom: 30px;
        }
        .voucher-title-section h1 {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 5px 0;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: var(--text-main);
        }
        .voucher-subtitle {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
        }

        .voucher-meta-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            font-size: 12px;
            line-height: 1.6;
        }
        .voucher-meta-left {
            text-align: left;
        }
        .voucher-meta-right {
            text-align: right;
        }

        .excel-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            margin-bottom: 35px;
            font-size: 11px;
        }
        .excel-grid th, .excel-grid td {
            border: 1px solid var(--border-color);
            padding: 8px 10px;
            text-align: left;
        }
        .excel-grid th {
            background-color: var(--grid-bg);
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            font-size: 10px;
        }
        .excel-grid td.num-cell {
            text-align: right;
            font-family: 'Century Gothic', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        .excel-grid td.label-cell {
            font-weight: 600;
            background-color: var(--grid-bg);
            width: 30%;
        }
        .excel-grid-exact {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px;
            font-size: 11px;
            color: #000;
        }
        .excel-grid-exact th, .excel-grid-exact td {
            border: 1px solid #000000;
            padding: 4px 6px;
            text-align: left;
        }
        .excel-grid-exact td.num-cell {
            text-align: right;
        }

        .excel-signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 40px;
            margin-bottom: 20px;
            font-size: 11px;
        }
        .sig-box {
            border: 1px solid var(--border-color);
            padding: 15px;
            background-color: #f8fafc;
            border-radius: 4px;
        }
        .sig-title {
            font-weight: 700;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 6px;
            color: var(--text-main);
        }
        .sig-field {
            margin-bottom: 12px;
            color: var(--text-muted);
        }
        .sig-line {
            display: inline-block;
            width: 100%;
            border-bottom: 1px dotted var(--text-muted);
            margin-top: 4px;
            height: 15px;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 90pt 40pt 90pt 40pt;
            }
            
            body {
                background-color: #ffffff;
                color: #000000;
                padding: 0;
            }

            .no-print-bar {
                display: none !important;
            }

            .page-container {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                max-width: 100% !important;
                min-height: auto !important;
            }

            .divHeader {
                position: fixed;
                top: -70pt;
                left: 0;
                right: 0;
                height: 50pt;
                margin: 0;
            }

            .divFooter {
                position: fixed;
                bottom: -70pt;
                left: 0;
                right: 0;
                height: 50pt;
                margin: 0;
                border-top: 0.75pt solid var(--accent);
                padding-top: 10pt;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .excel-grid th {
                background-color: #f8fafc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .excel-grid td.label-cell {
                background-color: #f8fafc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .excel-grid tr.total-row td {
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .sig-box {
                background-color: #f8fafc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    </head>
    <body>

    <div class="no-print-bar">
        <button onclick="window.close()" class="btn">
            <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: none; stroke: currentColor; stroke-width: 2; vertical-align: middle; margin-right: 4px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            Close Tab
        </button>
        <button onclick="downloadPDF()" class="btn btn-primary">
            <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: none; stroke: currentColor; stroke-width: 2; vertical-align: middle; margin-right: 4px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
            Download PDF
        </button>
    </div>

    <div class="page-container">
        <!-- HEADER -->
        <div class="divHeader">
            <img src="../../src/logo/ineza_logo.png" alt="INEZA Logo" class="header-logo">
        </div>

        <div class="invoice-body-wrapper">
            <!-- MAIN CONTENT -->
            <?php if ($is_tin): ?>
                <!-- TIN VOUCHER (Sn02) - EXACT EXCEL MATCH -->
                <div style="margin-bottom: 15px; font-size: 11px;">
                    <div style="font-weight: 700; font-size: 13px; margin-bottom: 15px;">INEZA AFRICA MINING Ltd</div>
                    <div style="font-weight: 700; font-size: 12px; margin-bottom: 12px; margin-left: 60px;">PAYMENT VOUCHER: <?php echo htmlspecialchars($p['purchase_no']); ?></div>
                    
                    <table style="font-size: 11px; margin-bottom: 8px; border-collapse: collapse;">
                        <tr>
                            <td style="padding-right: 15px; vertical-align: top;">Date:</td>
                            <td style="vertical-align: top; padding-right: 40px;"><?php echo htmlspecialchars($p['purchase_date']); ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 700; padding-right: 15px; vertical-align: top;">Supplier:</td>
                            <td style="font-weight: 700; font-size: 10px; vertical-align: top;"><?php echo htmlspecialchars($p['supplier_name']); ?></td>
                        </tr>
                    </table>
                </div>

                <table class="excel-grid-exact">
                    <colgroup>
                        <col style="width: 35%;">
                        <col style="width: 20%;">
                        <col style="width: 22.5%;">
                        <col style="width: 22.5%;">
                    </colgroup>
                    <tbody>
                        <tr>
                            <td></td>
                            <td></td>
                            <td style="font-weight: 700; text-align: left;">Fluc</td>
                            <td style="font-weight: 700; text-align: left;">USD</td>
                        </tr>
                        <tr>
                            <td>RATE</td>
                            <td></td>
                            <td class="num-cell" style="text-align: left;"><?php echo number_format($exchangeRate, 0); ?></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>QUANTITY</td>
                            <td></td>
                            <td></td>
                            <td class="num-cell"><?php echo number_format($qty, 2); ?></td>
                        </tr>
                        <tr>
                            <td>GRADE</td>
                            <td></td>
                            <td></td>
                            <td class="num-cell"><?php echo number_format($primaryGradePct, 2); ?></td>
                        </tr>
                        <tr>
                            <td>LME</td>
                            <td class="num-cell" style="text-align: left;"><?php echo number_format($p['lme_price'] !== null ? (float)$p['lme_price'] : 0.0, 2); ?></td>
                            <td class="num-cell" style="text-align: left;"><?php echo number_format($p['fluc'] !== null ? (float)$p['fluc'] : 0.0, 2); ?></td>
                            <td class="num-cell"><?php echo number_format($p['lme_paid'] !== null ? (float)$p['lme_paid'] : 0.0, 2); ?></td>
                        </tr>
                        <tr>
                            <td>TC</td>
                            <td></td>
                            <td></td>
                            <td class="num-cell"><?php echo number_format($p['tc_charges'] !== null ? (float)$p['tc_charges'] : 0.0, 2); ?></td>
                        </tr>
                        <tr>
                            <td>P.U</td>
                            <td></td>
                            <td></td>
                            <td class="num-cell"><?php echo number_format($p['price_per_kg_usd'] !== null ? (float)$p['price_per_kg_usd'] : 0.0, 2); ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 700;">P.T</td>
                            <td></td>
                            <td></td>
                            <td class="num-cell" style="font-weight: 700;"><?php echo number_format($purchaseValUsd, 2); ?></td>
                        </tr>
                        <tr>
                            <td>3% RRA (TC=800)</td>
                            <td></td>
                            <td></td>
                            <td class="num-cell"><?php echo number_format((float)$p['tax_rra'], 2); ?></td>
                        </tr>
                        <tr>
                            <td>RMA (50 FRW/KG)</td>
                            <td class="num-cell" style="text-align: left;">50</td>
                            <td class="num-cell" style="text-align: left;"><?php echo number_format($qty * 50, 2); ?></td>
                            <td class="num-cell"><?php echo number_format((float)$p['tax_rma'], 2); ?></td>
                        </tr>
                        <tr>
                            <td>INKOMANE (20FRW/KG)</td>
                            <td class="num-cell" style="text-align: left;">20</td>
                            <td class="num-cell" style="text-align: left;"><?php echo number_format($qty * 20, 2); ?></td>
                            <td class="num-cell"><?php echo number_format((float)$p['tax_inkomane'], 2); ?></td>
                        </tr>
                        <tr>
                            <td>Prod fees</td>
                            <td class="num-cell" style="text-align: left;"><?php echo number_format((float)$p['production_charges_per_kg'] * $exchangeRate, 2); ?></td>
                            <td class="num-cell" style="text-align: left;"><?php echo number_format((float)$p['production_charges'] * $exchangeRate, 2); ?></td>
                            <td class="num-cell"><?php echo number_format((float)$p['production_charges'], 2); ?></td>
                        </tr>
                        <tr style="height: 10px;"><td colspan="4" style="border: none;"></td></tr>
                        <tr style="background-color: #ffff00;">
                            <td style="font-weight: 700;">A PAYER</td>
                            <td></td>
                            <td></td>
                            <td class="num-cell" style="font-weight: 700;"><?php echo number_format($netPaidUsd, 2); ?></td>
                        </tr>
                        <tr style="height: 10px;"><td colspan="4" style="border: none;"></td></tr>
                        <tr>
                            <td>ADVANCE PAID (SEE ATTACHED DOCUMENT)</td>
                            <td></td>
                            <td></td>
                            <td class="num-cell">0.00</td>
                        </tr>
                        <tr style="height: 10px;"><td colspan="4" style="border: none;"></td></tr>
                        <tr style="background-color: #ffc000;">
                            <td style="font-weight: 700;">NET TO BE PAID</td>
                            <td></td>
                            <td></td>
                            <td class="num-cell" style="font-weight: 700;"><?php echo number_format($netPaidUsd, 2); ?></td>
                        </tr>
                        <tr style="background-color: #92d050;">
                            <td></td>
                            <td></td>
                            <td style="font-weight: 700; text-align: center;">IN FRW</td>
                            <td class="num-cell" style="font-weight: 700;"><?php echo number_format($netPaidUsd * $exchangeRate, 2); ?></td>
                        </tr>
                    </tbody>
                </table>

            <?php elseif ($is_tantalum): ?>
                <!-- TANTALITE VOUCHER (Ta205) - EXACT EXCEL MATCH -->
                <div style="margin-bottom: 15px; font-size: 11px;">
                    <div style="font-weight: 700; font-size: 13px; margin-bottom: 4px;">INEZA AFRICAN MINING Ltd</div>
                    <div style="font-weight: 700; font-size: 11px; margin-bottom: 2px;">Adress:</div>
                    <div style="font-size: 11px; margin-bottom: 12px;">Tel:</div>
                    <div style="font-weight: 700; font-size: 12px; margin-bottom: 12px; margin-left: 60px;">PAYMENT VOUCHER: <?php echo htmlspecialchars($p['purchase_no']); ?></div>
                    
                    <table style="font-size: 11px; margin-bottom: 8px; border-collapse: collapse;">
                        <tr>
                            <td style="font-weight: 700; padding-right: 15px; vertical-align: top;">Date:</td>
                            <td style="vertical-align: top; padding-right: 40px;"><?php echo htmlspecialchars($p['purchase_date']); ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 700; padding-right: 15px; vertical-align: top;">Supplier: <?php echo htmlspecialchars($p['supplier_name']); ?></td>
                            <td></td>
                        </tr>
                    </table>
                </div>

                <table class="excel-grid-exact">
                    <colgroup>
                        <col style="width: 40%;">
                        <col style="width: 20%;">
                        <col style="width: 20%;">
                        <col style="width: 20%;">
                    </colgroup>
                    <tbody>
                        <tr>
                            <td></td>
                            <td></td>
                            <td style="font-weight: 700; text-align: left;">Fluc</td>
                            <td style="font-weight: 700; text-align: left;">USD</td>
                        </tr>
                        <tr>
                            <td>RATE</td>
                            <td></td>
                            <td class="num-cell" style="text-align: left;"><?php echo number_format($exchangeRate, 0); ?></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>QUANTITY</td>
                            <td></td>
                            <td></td>
                            <td class="num-cell"><?php echo number_format($qty, 2); ?></td>
                        </tr>
                        <tr>
                            <td>GRADE</td>
                            <td></td>
                            <td></td>
                            <td class="num-cell"><?php echo number_format($primaryGradePct, 2); ?></td>
                        </tr>
                        <tr>
                            <td>PRICE</td>
                            <td></td>
                            <td class="num-cell" style="font-weight: 700; text-align: left;"><?php echo number_format($p['price_per_ta_unit'] !== null ? (float)$p['price_per_ta_unit'] : 0.0, 2); ?></td>
                            <td class="num-cell" style="font-weight: 700;"><?php echo number_format($p['price_per_ta_unit'] !== null ? (float)$p['price_per_ta_unit'] : 0.0, 2); ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 700;">P.T</td>
                            <td></td>
                            <td></td>
                            <td class="num-cell" style="font-weight: 700;"><?php echo number_format($purchaseValUsd, 2); ?></td>
                        </tr>
                        <tr>
                            <td>3% RRA</td>
                            <td></td>
                            <td></td>
                            <td class="num-cell"><?php echo number_format((float)$p['tax_rra'], 2); ?></td>
                        </tr>
                        <tr>
                            <td>RMA (125 FRW/KG)</td>
                            <td class="num-cell" style="text-align: left;">125</td>
                            <td class="num-cell" style="text-align: left;"><?php echo number_format($qty * 125, 2); ?></td>
                            <td class="num-cell"><?php echo number_format((float)$p['tax_rma'], 2); ?></td>
                        </tr>
                        <tr>
                            <td>INKOMANE (40FRW/KG)</td>
                            <td class="num-cell" style="text-align: left;">40</td>
                            <td class="num-cell" style="text-align: left;"><?php echo number_format($qty * 40, 2); ?></td>
                            <td class="num-cell"><?php echo number_format((float)$p['tax_inkomane'], 2); ?></td>
                        </tr>
                        <tr>
                            <td>Prod fees</td>
                            <td class="num-cell" style="text-align: left;"><?php echo number_format((float)$p['production_charges_per_kg'] * $exchangeRate, 2); ?></td>
                            <td class="num-cell" style="text-align: left;"><?php echo number_format((float)$p['production_charges'] * $exchangeRate, 2); ?></td>
                            <td class="num-cell"><?php echo number_format((float)$p['production_charges'], 2); ?></td>
                        </tr>
                        <tr style="height: 10px;"><td colspan="4" style="border: none;"></td></tr>
                        <tr style="background-color: #ffff00;">
                            <td style="font-weight: 700;">A PAYER</td>
                            <td></td>
                            <td></td>
                            <td class="num-cell" style="font-weight: 700;"><?php echo number_format($netPaidUsd, 2); ?></td>
                        </tr>
                        <tr style="height: 10px;"><td colspan="4" style="border: none;"></td></tr>
                        <tr>
                            <td>ADVANCE PAID (SEE ATTACHED DOCUMENT)</td>
                            <td></td>
                            <td></td>
                            <td class="num-cell">0.00</td>
                        </tr>
                        <tr style="height: 10px;"><td colspan="4" style="border: none;"></td></tr>
                        <tr style="background-color: #ffc000;">
                            <td style="font-weight: 700;">NET TO NE PAID</td>
                            <td></td>
                            <td></td>
                            <td class="num-cell" style="font-weight: 700;"><?php echo number_format($netPaidUsd, 2); ?></td>
                        </tr>
                        <tr style="background-color: #92d050;">
                            <td></td>
                            <td></td>
                            <td style="font-weight: 700; text-align: center;">IN FRW</td>
                            <td class="num-cell" style="font-weight: 700;"><?php echo number_format($netPaidUsd * $exchangeRate, 2); ?></td>
                        </tr>
                    </tbody>
                </table>

            <?php else: ?>
                <!-- WOLFRAMITE VOUCHER (W03) - EXACT EXCEL MATCH -->
                <div style="text-align: center; margin-bottom: 20px;">
                    <h1 style="font-size: 20px; font-weight: 700; color: #000; margin: 0; text-transform: uppercase;">PAYMENT VOUCHER FOR INEZA AFRICAN MINING</h1>
                </div>
                
                <table style="width: 100%; font-size: 11px; margin-bottom: 15px; border-collapse: collapse;">
                    <tr>
                        <td style="font-weight: 700; width: 120px;">To:</td>
                        <td style="font-weight: 700;"><?php echo htmlspecialchars($p['supplier_name']); ?></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 700;">Exchange rate</td>
                        <td style="font-weight: 700;"><?php echo number_format($exchangeRate, 2); ?></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 700;">RMB Price/MTU</td>
                        <td style="font-weight: 700;"><?php echo number_format($p['lme_price'] !== null ? (float)$p['lme_price'] : 0.0, 2); ?></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 700;">Lot Number :</td>
                        <td></td>
                        <td></td>
                        <td style="font-weight: 700; text-align: center;"><?php echo htmlspecialchars($p['lots_code']); ?></td>
                    </tr>
                </table>

                <table class="excel-grid-exact" style="margin-bottom: 15px;">
                    <thead>
                        <tr>
                            <th style="font-weight: 700; text-align: center;">DESCRIPTION OF GOODS</th>
                            <th style="font-weight: 700; text-align: center;">LOT NO.</th>
                            <th style="font-weight: 700; text-align: center;">Gross weight(MT)</th>
                            <th style="font-weight: 700; text-align: center;">Moisture     （%）</th>
                            <th style="font-weight: 700; text-align: center;">NET DRY WEIGHT       (MT)</th>
                            <th style="font-weight: 700; text-align: center;">WO3 (%)</th>
                            <th style="font-weight: 700; text-align: center;">UNIT PRICE  (USD/DMTU)</th>
                            <th style="font-weight: 700; text-align: center;">Price USD/Kg</th>
                            <th style="font-weight: 700; text-align: center;">TOTAL PRICE （USD)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="text-align: center;">Tungsten Concentrate/wolframite</td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($p['lots_code']); ?></td>
                            <td style="text-align: center;"><?php echo number_format($qty / 1000.0, 3); ?></td>
                            <td style="text-align: center;"></td>
                            <td style="text-align: center; background-color: #ffff00; font-weight: 700;"><?php echo number_format($qty / 1000.0, 3); ?></td>
                            <td style="text-align: center; background-color: #ffff00; font-weight: 700;"><?php echo number_format($primaryGradePct, 2); ?></td>
                            <td style="text-align: center; background-color: #ffff00; font-weight: 700;"><?php echo number_format($p['lme_paid'] !== null ? (float)$p['lme_paid'] : 0.0, 2); ?></td>
                            <td style="text-align: center;"><?php echo number_format($p['price_per_kg_usd'] !== null ? (float)$p['price_per_kg_usd'] : 0.0, 2); ?></td>
                            <td style="text-align: center; font-weight: 700;"><?php echo number_format($purchaseValUsd, 2); ?></td>
                        </tr>
                    </tbody>
                </table>

                <div style="display: flex; justify-content: flex-end; margin-bottom: 25px;">
                    <table class="excel-grid-exact" style="width: 40%; margin: 0;">
                        <tbody>
                            <tr>
                                <td style="font-weight: 700;">Amount Rwf</td>
                                <td class="num-cell" style="font-weight: 500;"><?php echo number_format($purchaseValRwf, 2); ?></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 700;">RMA</td>
                                <td class="num-cell" style="text-align: left;"><?php echo number_format($p['tax_rma'] !== null ? (float)$p['tax_rma'] * $exchangeRate : 0.0, 2); ?></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 700;">Transport fees</td>
                                <td class="num-cell" style="text-align: left;"><?php echo number_format($p['production_charges'] !== null ? (float)$p['production_charges'] * $exchangeRate : 0.0, 2); ?></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 700;">Inkomane</td>
                                <td class="num-cell" style="text-align: left;"><?php echo number_format($p['tax_inkomane'] !== null ? (float)$p['tax_inkomane'] * $exchangeRate : 0.0, 2); ?></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 700;">RRA Tax 3%</td>
                                <td class="num-cell" style="text-align: left;"><?php echo number_format($p['tax_rra'] !== null ? (float)$p['tax_rra'] * $exchangeRate : 0.0, 2); ?></td>
                            </tr>
                            <tr style="height: 6px;"><td colspan="2" style="border: none;"></td></tr>
                            <tr>
                                <td style="font-weight: 700;">Balance Rwf</td>
                                <td class="num-cell" style="font-weight: 700; text-align: left;"><?php echo number_format($netPaidUsd * $exchangeRate, 2); ?></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 700;">Exchange rate</td>
                                <td class="num-cell" style="text-align: left;"><?php echo number_format($exchangeRate, 2); ?></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 700;">Balance USD</td>
                                <td class="num-cell" style="font-weight: 700; text-align: left;"><?php echo number_format($netPaidUsd, 2); ?></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 700;">Advance payment UDS</td>
                                <td class="num-cell"></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 700;">Amount to be paid USD</td>
                                <td class="num-cell" style="font-weight: 700; text-align: left;"><?php echo number_format($netPaidUsd, 2); ?></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 700;">Amount to be paid Rwf</td>
                                <td class="num-cell" style="font-weight: 700; text-align: left;"><?php echo number_format($netPaidUsd * $exchangeRate, 2); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

            <!-- SIGNATURES (EXACT EXCEL MATCH) -->
            <div style="margin-top: 30px; margin-bottom: 20px; font-size: 11px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="vertical-align: top; width: 33%; padding-right: 15px;">
                            <div style="font-weight: 400; margin-bottom: 8px;">PREPARED BY:</div>
                            <div style="margin-bottom: 6px;">Name: <span style="display: inline-block; width: 65%; border-bottom: 1px solid #000;"></span></div>
                            <div>Signature: <span style="display: inline-block; width: 55%; border-bottom: 1px solid #000;"></span></div>
                        </td>
                        <td style="vertical-align: top; width: 33%; padding-right: 15px;">
                            <div style="font-weight: 400; margin-bottom: 8px;">APPROUVED BY:</div>
                            <div style="margin-bottom: 6px;">Name: <span style="display: inline-block; width: 65%; border-bottom: 1px solid #000;"></span></div>
                            <div>Signature: <span style="display: inline-block; width: 55%; border-bottom: 1px solid #000;"></span></div>
                        </td>
                        <td style="vertical-align: top; width: 33%;">
                            <div style="font-weight: 400; margin-bottom: 8px;">RECEIVED BY:</div>
                            <div style="margin-bottom: 6px;">Name: <span style="display: inline-block; width: 65%; border-bottom: 1px solid #000;"></span></div>
                            <div>Signature: <span style="display: inline-block; width: 55%; border-bottom: 1px solid #000;"></span></div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="divFooter">
            <div class="footer-left">
                <div class="company-name">INEZA AFRICAN MINING LTD</div>
                <div class="company-sub">Company Number : 123054396</div>
                <div class="company-address">Adress : Plot N. 8425, Kigarama, Gahanga</div>
                <div class="company-address">Industrial area, Kicukiro</div>
            </div>
            <div class="footer-right">
                <div class="contact-item">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;+250 784 886 562</div>
                <div class="contact-item">info@inezaafricanmincie.com</div>
                <div class="contact-item"><a href="http://www.inezaafricanmincie.com" class="footer-web" target="_blank">www.inezaafricanmincie.com</a></div>
            </div>
        </div>

    </div>

    <script>
        // Set theme matching parent window
        if (window.opener) {
            var theme = window.opener.document.documentElement.getAttribute('data-theme');
            if (theme) {
                document.documentElement.setAttribute('data-theme', theme);
            }
        }

        function downloadPDF() {
            var element = document.querySelector('.page-container');
            
            // Temporarily adjust styles for clean PDF rendering
            var originalBoxShadow = element.style.boxShadow;
            var originalBorderRadius = element.style.borderRadius;
            var originalPadding = element.style.padding;
            
            element.style.boxShadow = 'none';
            element.style.borderRadius = '0';
            element.style.padding = '40px'; 
            
            var opt = {
                margin:       [0, 0, 0, 0],
                filename:     'Purchase_Invoice_<?php echo htmlspecialchars($p['purchase_no']); ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { 
                    scale: 2, 
                    useCORS: true,
                    logging: false,
                    letterRendering: true
                },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            html2pdf().from(element).set(opt).toPdf().get('pdf').then(function (pdf) {
                element.style.boxShadow = originalBoxShadow;
                element.style.borderRadius = originalBorderRadius;
                element.style.padding = originalPadding;
            }).save();
        }
    </script>
    </body>
    </html>
    <?php
    exit();
}

if (empty($_SESSION['purchas_token'])) {
    $_SESSION['purchas_token'] = bin2hex(random_bytes(32));
}

$canCreate = hasPermission($conn, $userId, 'create_purchas');
$canEdit = hasPermission($conn, $userId, 'edit_purchas');
$canDelete = hasPermission($conn, $userId, 'delete_purchas');

// Load dropdown options
$suppliers = [];
$supQuery = mysqli_query($conn, "SELECT id, name FROM suppliers WHERE is_active = 1 ORDER BY name ASC");
if ($supQuery) {
    while ($row = mysqli_fetch_assoc($supQuery)) {
        $suppliers[] = $row;
    }
}

$warehouses = [];
$whQuery = mysqli_query($conn, "SELECT id, warehouse_name FROM warehouses WHERE is_active = 1 ORDER BY warehouse_name ASC");
if ($whQuery) {
    while ($row = mysqli_fetch_assoc($whQuery)) {
        $warehouses[] = $row;
    }
}

$lots = [];
$lotQuery = mysqli_query($conn, "SELECT id, lots_code FROM lots WHERE closing_date IS NULL ORDER BY lots_code ASC");
if ($lotQuery) {
    while ($row = mysqli_fetch_assoc($lotQuery)) {
        $lots[] = $row;
    }
}

$products = [];
$prodQuery = mysqli_query($conn, "SELECT id, product_code, product_name, uom_id FROM product WHERE is_active = 1 ORDER BY product_name ASC");
if ($prodQuery) {
    while ($row = mysqli_fetch_assoc($prodQuery)) {
        $products[] = $row;
    }
}

$uoms = [];
$uomQuery = mysqli_query($conn, "SELECT id, code, name FROM unit_of_measure WHERE is_active = 1 ORDER BY code ASC");
if ($uomQuery) {
    while ($row = mysqli_fetch_assoc($uomQuery)) {
        $uoms[] = $row;
    }
}

// Fetch purchases data for first render
$purchasesData = [];
$query = "SELECT p.*, pr.product_name, pr.product_code, l.lots_code, s.name as supplier_name, w.warehouse_name, uom.code as uom_code
          FROM purchasing p
          JOIN product pr ON p.product_id = pr.id
          JOIN lots l ON p.lot_id = l.id
          JOIN suppliers s ON p.supplier_id = s.id
          JOIN warehouses w ON p.warehouse_id = w.id
          LEFT JOIN unit_of_measure uom ON p.uom_id = uom.id
          ORDER BY p.purchase_date DESC, p.id DESC";

$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $pId = (int)$row['id'];
        
        // Fetch grades
        $gradesQuery = "SELECT peg.*, pe.element_code, pe.element_name, pe.symbol 
                        FROM purchasing_element_grade peg
                        JOIN product_element pe ON peg.product_element_id = pe.id
                        WHERE peg.purchasing_id = $pId";
        $gradesResult = mysqli_query($conn, $gradesQuery);
        $grades = [];
        if ($gradesResult) {
            while ($gRow = mysqli_fetch_assoc($gradesResult)) {
                $grades[] = [
                    'product_element_id' => (int)$gRow['product_element_id'],
                    'element_code' => $gRow['element_code'],
                    'element_name' => $gRow['element_name'],
                    'symbol' => $gRow['symbol'],
                    'grade_pct' => (float)$gRow['grade_pct'],
                    'notes' => $gRow['notes']
                ];
            }
        }

        // Find primary element
        $primaryElementId = null;
        $compQuery = "SELECT product_element_id FROM product_element_composition WHERE product_id = {$row['product_id']} AND is_primary_grade = 1 LIMIT 1";
        $compResult = mysqli_query($conn, $compQuery);
        if ($compResult && mysqli_num_rows($compResult) > 0) {
            $primaryElementId = (int)mysqli_fetch_assoc($compResult)['product_element_id'];
        }

        $row['id'] = $pId;
        $row['lot_id'] = (int)$row['lot_id'];
        $row['product_id'] = (int)$row['product_id'];
        $row['supplier_id'] = (int)$row['supplier_id'];
        $row['warehouse_id'] = (int)$row['warehouse_id'];
        $row['uom_id'] = (int)$row['uom_id'];
        $row['quantity_kg'] = (float)$row['quantity_kg'];
        $row['price_per_kg_rwf'] = $row['price_per_kg_rwf'] !== null ? (float)$row['price_per_kg_rwf'] : null;
        $row['purchase_value_rwf'] = $row['purchase_value_rwf'] !== null ? (float)$row['purchase_value_rwf'] : null;
        $row['exchange_rate'] = $row['exchange_rate'] !== null ? (float)$row['exchange_rate'] : null;
        $row['purchase_value_usd'] = $row['purchase_value_usd'] !== null ? (float)$row['purchase_value_usd'] : null;
        $row['net_paid_supplier_usd'] = $row['net_paid_supplier_usd'] !== null ? (float)$row['net_paid_supplier_usd'] : null;
        $row['charges_per_kg'] = $row['charges_per_kg'] !== null ? (float)$row['charges_per_kg'] : null;
        $row['price_per_ta_unit'] = $row['price_per_ta_unit'] !== null ? (float)$row['price_per_ta_unit'] : null;
        $row['price_per_kg_usd'] = $row['price_per_kg_usd'] !== null ? (float)$row['price_per_kg_usd'] : null;
        $row['lme_price'] = $row['lme_price'] !== null ? (float)$row['lme_price'] : null;
        $row['tc_charges'] = $row['tc_charges'] !== null ? (float)$row['tc_charges'] : null;
        $row['fluc'] = $row['fluc'] !== null ? (float)$row['fluc'] : null;
        $row['lme_paid'] = $row['lme_paid'] !== null ? (float)$row['lme_paid'] : null;
        $row['tax_rra'] = $row['tax_rra'] !== null ? (float)$row['tax_rra'] : null;
        $row['tax_rma'] = $row['tax_rma'] !== null ? (float)$row['tax_rma'] : null;
        $row['tax_inkomane'] = $row['tax_inkomane'] !== null ? (float)$row['tax_inkomane'] : null;
        $row['production_charges'] = $row['production_charges'] !== null ? (float)$row['production_charges'] : null;
        
        $row['grades'] = $grades;
        $row['primary_element_id'] = $primaryElementId;
        
        $purchasesData[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Purchases & Logistics</title>
<meta name="description" content="Manage and record mining product purchases, element grading, and supplier pricing metrics.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/lots.css">
<link rel="stylesheet" href="../../src/css/purchas.css">
<script>
  (function() {
    var savedTheme = localStorage.getItem('theme');
    var currentTheme = savedTheme || 'light';
    document.documentElement.setAttribute('data-theme', currentTheme);
  })();
</script>
</head>
<body>

<?php include '../include/sidebar.php'; ?>

<div class="main">

  <?php $page_title = "Mining Purchases"; include '../include/navbar.php'; ?>

  <div class="content" id="purchasContent">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
          Purchases Registry
        </h1>
        <div class="page-sub">Record mineral purchase receipts, element compositions, and price metrics</div>
      </div>
      <div class="page-actions">
        <button class="btn-sm" id="refreshBtn">
          <svg class="btn-icon" viewBox="0 0 24 24"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          Refresh List
        </button>
        <?php if ($canCreate): ?>
          <a href="record.php" class="btn-sm btn-primary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
            <svg class="btn-icon" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Record Purchase
          </a>
          <a href="pay_supplier.php" class="btn-sm" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
            <svg class="btn-icon" viewBox="0 0 24 24"><path d="M12 2l7 4v6c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6l7-4z"/><path d="M9 12l2 2 4-4"/></svg>
            Pay Supplier
          </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card" id="card-total-purchases">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
          </div>
          <span class="stat-trend trend-blue">Total</span>
        </div>
        <div class="stat-val" id="stat-total">0</div>
        <div class="stat-label">Transactions</div>
      </div>

      <div class="stat-card" id="card-total-qty">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
          </div>
          <span class="stat-trend trend-up">Quantity</span>
        </div>
        <div class="stat-val" id="stat-qty">0 kg</div>
        <div class="stat-label">Accumulated Weight</div>
      </div>

      <div class="stat-card" id="card-total-value">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--orange-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--orange)"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <span class="stat-trend trend-orange">Value</span>
        </div>
        <div class="stat-val" id="stat-value">$0.00</div>
        <div class="stat-label">Total Outlay (USD)</div>
      </div>
    </div>

    <!-- Purchases Table Card -->
    <div class="purchas-grid">
      <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
          <div class="card-title">Recorded Receipts</div>
          <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <input type="text" id="searchInput" class="form-control" placeholder="Search purchases..." style="max-width: 200px; padding: 5px 8px; font-size: 12px; margin: 0;">
            <select id="statusFilter" class="form-control" style="max-width: 130px; padding: 5px 8px; font-size: 12px; margin: 0;">
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="confirmed">Confirmed</option>
              <option value="received">Received</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
        </div>

        <div id="alertPlaceholder"></div>

        <div class="table-container">
          <table class="data-table" id="purchasesTable">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>Purchase No</th>
                <th>Lot</th>
                <th>Product</th>
                <th>Supplier</th>
                <th>Negociant</th>
                <th>Warehouse</th>
                <th>Quantity</th>
                <th>Total Value</th>
                <th>Status</th>
                <th>Date</th>
                <th style="width: 120px; text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody id="purchasesList">
              <tr>
                <td colspan="12" class="table-empty">Loading purchases data...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="bottom-spacer"></div>
  </div>
</div>

<!-- Modal: Confirm Delete -->
<div class="confirm-modal-overlay" id="confirmOverlay" style="display: none;">
  <div class="confirm-modal">
    <div class="confirm-title">
      <svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0zM12 9v4M12 17h.01"/></svg>
      Delete Purchase Record
    </div>
    <div class="confirm-body" id="confirmBody">
      Are you sure you want to delete this purchase receipt? This action is permanent and will revert any inventory changes.
    </div>
    <div class="confirm-footer">
      <button class="btn-sm" id="confirmCancelBtn">Cancel</button>
      <button class="btn-sm btn-primary" id="confirmDeleteBtn" style="background:var(--red); border-color:var(--red);">Delete</button>
    </div>
  </div>
</div>

<!-- Modal: Purchase Details View -->
<div class="confirm-modal-overlay" id="detailModalOverlay" style="display: none;">
  <div class="confirm-modal" style="max-width: 650px; width: 90%;">
    <div class="confirm-title" style="display: flex; justify-content: space-between; align-items: center;">
      <span style="display: flex; align-items: center; gap: 8px;">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        Purchase Transaction Details
      </span>
      <button class="purchas-modal-close" id="detailCloseBtn" title="Close" style="padding: 2px;">
        <svg viewBox="0 0 24 24" style="width:16px; height:16px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="confirm-body" id="detailContent" style="max-height: 70vh; overflow-y: auto; text-align: left; padding: 20px 0;">
      <!-- Details populated via JS -->
    </div>
  </div>
</div>

<!-- Modal: Change Purchase Status -->
<div class="status-modal-overlay" id="statusModalOverlay">
  <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['purchas_token']); ?>">
  <div class="status-modal">
    <div class="status-modal-header">
      <span class="modal-title-text">
        <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Change Purchase Status
      </span>
      <button class="purchas-modal-close" id="statusCloseBtn" title="Close">
        <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="status-modal-body">
      <div class="status-current">
        <span class="status-current-label">Current Status:</span>
        <span id="statusCurrentPill"></span>
      </div>
      <div class="status-options-grid">
        <label class="status-option" data-status="pending">
          <input type="radio" name="new_status" value="pending">
          <div class="status-option-icon icon-pending">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          </div>
          <span class="status-option-label">Pending</span>
          <span class="status-option-desc">Initial entry, not yet confirmed</span>
        </label>
        <label class="status-option" data-status="confirmed">
          <input type="radio" name="new_status" value="confirmed">
          <div class="status-option-icon icon-confirmed">
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <span class="status-option-label">Confirmed</span>
          <span class="status-option-desc">Verified and approved</span>
        </label>
        <label class="status-option" data-status="received">
          <input type="radio" name="new_status" value="received">
          <div class="status-option-icon icon-received">
            <svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
          </div>
          <span class="status-option-label">Received</span>
          <span class="status-option-desc">Goods delivered to warehouse</span>
        </label>
        <label class="status-option" data-status="cancelled">
          <input type="radio" name="new_status" value="cancelled">
          <div class="status-option-icon icon-cancelled">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          </div>
          <span class="status-option-label">Cancelled</span>
          <span class="status-option-desc">Voided or reversed</span>
        </label>
      </div>
    </div>
    <div class="status-modal-footer">
      <button class="btn-sm" id="statusCancelBtn">Cancel</button>
      <button class="btn-sm btn-primary" id="statusSaveBtn">Update Status</button>
    </div>
  </div>
</div>

<script>
  window.initialPurchasesData = <?php echo json_encode($purchasesData); ?>;
</script>
<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script src="../../src/js/purchas.js"></script>
</body>
</html>
