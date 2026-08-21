<?php
// modules/sanitation/verify_permit.php
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Models/Permit.php';
require_once __DIR__ . '/../../app/Models/PermitDocument.php';

$db = Database::getInstance();
$permitModel = new Permit($db);
$docModel = new PermitDocument($db);

$qrCode = isset($_GET['qr']) ? trim($_GET['qr']) : (isset($_GET['code']) ? trim($_GET['code']) : '');
$permitId = isset($_GET['permit_id']) ? (int)$_GET['permit_id'] : 0;
$docId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$document = null;
$permit = null;

if ($qrCode !== '') {
    $docs = $db->select('permit_documents', ['qr_code' => 'eq.' . $qrCode]);
    if (!empty($docs)) {
        $document = $docs[0];
        $permit = $permitModel->find((int)$document['permit_id']);
    }
} elseif ($docId > 0) {
    $document = $docModel->find($docId);
    if ($document) {
        $permit = $permitModel->find((int)$document['permit_id']);
    }
} elseif ($permitId > 0) {
    $permit = $permitModel->find($permitId);
    if ($permit) {
        $docs = $docModel->findByPermitId($permitId);
        foreach ($docs as $d) {
            if (($d['document_type'] ?? '') === 'sanitary_permit') {
                $document = $d;
                break;
            }
        }
    }
}

$found = ($permit !== null || $document !== null);

$businessName = $permit['business_name'] ?? ($permit['applicant'] ?? ($document['applicant'] ?? 'Commercial Establishment'));
$ownerName = $permit['owner_name'] ?? ($permit['applicant'] ?? 'Authorized Business Owner');
$permitNumber = $permit['permit_id'] ?? ($document['file_name'] ?? ('SP-' . date('Y') . '-' . str_pad((string)($permit['id'] ?? 1), 4, '0', STR_PAD_LEFT)));
$businessType = $permit['business_type'] ?? ($document['document_type'] ?? 'Food Establishment & Services');
$address = $permit['address'] ?? ($permit['barangay'] ?? 'City Municipality, Philippines');
$issuedDate = !empty($permit['approved_date']) ? date('F d, Y', strtotime($permit['approved_date'])) : date('F d, Y');
$expiryDate = !empty($permit['expiry_date']) ? date('F d, Y', strtotime($permit['expiry_date'])) : (!empty($document['expiry_date']) ? date('F d, Y', strtotime($document['expiry_date'])) : date('F d, Y', strtotime('+1 year')));
$isExpired = strtotime($expiryDate) < time();
$qrVal = $qrCode ?: ($document['qr_code'] ?? ('QR-SAN-' . date('Y') . '-' . str_pad((string)($permit['id'] ?? 1), 4, '0', STR_PAD_LEFT)));

$certUrl = 'permit_certificate.php?' . ($permit ? 'permit_id=' . $permit['id'] : 'qr=' . urlencode($qrVal));
$logoUrl = site_url('assets/images/logo.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permit Verification - <?php echo htmlspecialchars($businessName); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --brand-light: #EEF5FF;
            --brand-border: #B4D4FF;
            --brand-medium: #86B6F6;
            --brand-dark: #176B87;
            --brand-deep: #0d4f64;
        }
    </style>
</head>
<body class="bg-[#F8FAFC] min-h-screen py-6 px-4 font-sans text-slate-800 antialiased flex flex-col justify-between">

    <div class="max-w-md mx-auto w-full">
        <!-- Top Official Header -->
        <div class="text-center mb-5">
            <div class="inline-flex items-center justify-center p-2 rounded-2xl bg-white border border-[#B4D4FF] shadow-sm mb-2">
                <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="CivCentral Logo" class="h-12 w-auto object-contain">
            </div>
            <h3 class="text-[11px] font-extrabold text-slate-500 uppercase tracking-widest">Republic of the Philippines</h3>
            <h2 class="text-sm font-black text-slate-900 uppercase">City Health Department</h2>
            <p class="text-[11px] font-bold text-[#176B87] uppercase tracking-wider">Official Sanitary Permit Registry</p>
        </div>

        <?php if ($found): ?>
            <!-- Main Verification Card -->
            <div class="bg-white rounded-3xl shadow-lg border border-[#B4D4FF] overflow-hidden mb-4">
                
                <!-- Status Banner -->
                <div class="<?php echo $isExpired ? 'bg-rose-600' : 'bg-[#176B87]'; ?> px-6 py-4 text-white text-center">
                    <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/20 mb-1">
                        <i class="fa-solid <?php echo $isExpired ? 'fa-triangle-exclamation' : 'fa-check'; ?> text-xl text-white"></i>
                    </div>
                    <h4 class="text-base font-black uppercase tracking-wide">
                        <?php echo $isExpired ? 'Permit Expired' : 'Official Verified Permit'; ?>
                    </h4>
                    <p class="text-[11px] text-white/80">Authenticated in the Municipal Health Database</p>
                </div>

                <!-- Establishment Details -->
                <div class="p-6 space-y-4">
                    <div class="text-center pb-3 border-b border-slate-100">
                        <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Establishment Name</span>
                        <h3 class="text-xl font-black text-slate-900 mt-0.5"><?php echo htmlspecialchars($businessName); ?></h3>
                        <p class="text-xs text-slate-500 font-medium"><?php echo htmlspecialchars($businessType); ?></p>
                    </div>

                    <div class="space-y-2.5 text-xs">
                        <div class="flex justify-between py-1 border-b border-slate-100">
                            <span class="text-slate-500 font-semibold">Permit Number:</span>
                            <span class="font-mono font-bold text-[#176B87]"><?php echo htmlspecialchars($permitNumber); ?></span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100">
                            <span class="text-slate-500 font-semibold">Business Owner:</span>
                            <span class="font-bold text-slate-800"><?php echo htmlspecialchars($ownerName); ?></span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100">
                            <span class="text-slate-500 font-semibold">Address:</span>
                            <span class="font-medium text-slate-700 text-right max-w-[200px]"><?php echo htmlspecialchars($address); ?></span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100">
                            <span class="text-slate-500 font-semibold">Date Issued:</span>
                            <span class="font-semibold text-slate-800"><?php echo htmlspecialchars($issuedDate); ?></span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100">
                            <span class="text-slate-500 font-semibold">Valid Until:</span>
                            <span class="font-bold <?php echo $isExpired ? 'text-rose-600' : 'text-[#176B87]'; ?>"><?php echo htmlspecialchars($expiryDate); ?></span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-slate-500 font-semibold">QR Code ID:</span>
                            <span class="font-mono text-slate-600 font-semibold"><?php echo htmlspecialchars($qrVal); ?></span>
                        </div>
                    </div>

                    <!-- Direct Action Buttons -->
                    <div class="pt-3 space-y-2">
                        <a href="<?php echo htmlspecialchars($certUrl); ?>" target="_blank" class="w-full py-3.5 bg-[#176B87] hover:bg-[#0d4f64] text-white rounded-2xl font-bold text-sm shadow-md flex items-center justify-center gap-2 transition active:scale-[0.98]">
                            <i class="fa-solid fa-file-pdf text-lg"></i> Download / View Sanitation Permit
                        </a>
                        <button onclick="window.print()" class="w-full py-2.5 bg-[#EEF5FF] hover:bg-[#B4D4FF]/40 text-[#176B87] border border-[#B4D4FF] rounded-xl font-bold text-xs flex items-center justify-center gap-2 transition">
                            <i class="fa-solid fa-print"></i> Print Verification Slip
                        </button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Invalid / Not Found Card -->
            <div class="bg-white rounded-3xl shadow-lg border border-slate-200 p-8 text-center mb-4">
                <div class="w-16 h-16 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-3">
                    <i class="fa-solid fa-triangle-exclamation text-3xl"></i>
                </div>
                <h4 class="text-lg font-black text-slate-900 mb-1">Permit Not Found</h4>
                <p class="text-xs text-slate-500 mb-4">The scanned QR code (<?php echo htmlspecialchars($qrCode); ?>) could not be verified in the active registry.</p>
                <a href="/capstone/" class="px-5 py-2.5 bg-[#176B87] text-white rounded-xl text-xs font-bold inline-block">Return to Homepage</a>
            </div>
        <?php endif; ?>

        <div class="text-center text-[10px] text-slate-400 space-y-1">
            <p>CivCentral Sanitation Management System</p>
            <p>Official Public Verification Portal • City Health Office</p>
        </div>
    </div>

</body>
</html>
