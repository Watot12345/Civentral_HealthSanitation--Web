<?php
// modules/sanitation/permit_certificate.php
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Models/Permit.php';
require_once __DIR__ . '/../../app/Models/PermitDocument.php';
require_once __DIR__ . '/../../app/Models/Payment.php';

$db = Database::getInstance();
$permitModel = new Permit($db);
$docModel = new PermitDocument($db);
$paymentModel = new Payment($db);

$permitId = isset($_GET['permit_id']) ? (int)$_GET['permit_id'] : 0;
$docId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$qrCode = isset($_GET['qr']) ? trim($_GET['qr']) : '';

$permit = null;
$document = null;

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

if (!$permit && $permitId > 0) {
    $permit = $permitModel->find($permitId);
}

// Fallback or demo data if not found
$businessName = $permit['business_name'] ?? ($permit['applicant'] ?? 'Commercial Establishment');
$ownerName = $permit['owner_name'] ?? ($permit['applicant'] ?? 'Authorized Business Owner');
$permitNumber = $permit['permit_id'] ?? ('SP-' . date('Y') . '-' . str_pad((string)($permit['id'] ?? 1), 4, '0', STR_PAD_LEFT));
$businessType = $permit['business_type'] ?? 'Food Establishment & Services';
$address = $permit['address'] ?? ($permit['barangay'] ?? 'City Municipality, Philippines');
$fee = number_format((float)($permit['fee'] ?? 500), 2);
$issuedDate = !empty($permit['approved_date']) ? date('F d, Y', strtotime($permit['approved_date'])) : date('F d, Y');
$expiryDate = !empty($permit['expiry_date']) ? date('F d, Y', strtotime($permit['expiry_date'])) : date('F d, Y', strtotime('+1 year'));
$qrVal = $document['qr_code'] ?? ('QR-SAN-' . date('Y') . '-' . str_pad((string)($permit['id'] ?? 1), 4, '0', STR_PAD_LEFT));

$host = ($_SERVER['HTTP_HOST'] ?? 'localhost');
if ($host === 'localhost' || $host === '127.0.0.1') {
    $host = '192.168.0.105';
}
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
$verificationUrl = $protocol . '://' . $host . '/capstone/modules/sanitation/verify_permit.php?qr=' . urlencode($qrVal);
$qrImgUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . urlencode($verificationUrl);
$logoUrl = site_url('assets/images/logo.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sanitary Permit Certificate - <?php echo htmlspecialchars($permitNumber); ?></title>
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
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; margin: 0 !important; }
            .certificate-container { 
                box-shadow: none !important; 
                border: 6px double #176B87 !important; 
                margin: 0 !important; 
                width: 100% !important; 
                max-width: 100% !important; 
                padding: 24px !important;
            }
        }
        @page {
            size: A4 portrait;
            margin: 8mm;
        }
        .cert-frame {
            border: 8px double #176B87;
            outline: 2px solid #86B6F6;
            outline-offset: -12px;
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen py-8 px-4 font-sans text-slate-800 antialiased">

    <!-- Top Action Bar (Hidden on Print) -->
    <div class="no-print max-w-4xl mx-auto mb-6 flex items-center justify-between bg-white p-4 rounded-2xl shadow-sm border border-[#B4D4FF]">
        <div class="flex items-center gap-3">
            <a href="javascript:history.back()" class="p-2 text-slate-600 hover:text-[#176B87] hover:bg-[#EEF5FF] rounded-xl transition text-sm font-semibold flex items-center gap-1.5">
                <i class="fa-solid fa-arrow-left"></i> Back
            </a>
            <span class="text-slate-300">|</span>
            <div class="flex items-center gap-2">
                <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="CivCentral Logo" class="h-6 w-auto object-contain">
                <span class="text-sm font-bold text-slate-700">Permit #<span class="font-mono text-[#176B87]"><?php echo htmlspecialchars($permitNumber); ?></span></span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="downloadQRImage()" class="px-4 py-2 bg-[#EEF5FF] text-[#176B87] border border-[#B4D4FF] hover:bg-[#B4D4FF]/30 rounded-xl text-sm font-bold transition flex items-center gap-2">
                <i class="fa-solid fa-qrcode"></i> Save QR Image
            </button>
            <button onclick="window.print()" class="px-5 py-2 bg-[#176B87] hover:bg-[#0d4f64] text-white rounded-xl text-sm font-bold shadow-sm transition flex items-center gap-2">
                <i class="fa-solid fa-print"></i> Print / Save as PDF
            </button>
        </div>
    </div>

    <!-- Official Certificate Frame -->
    <div class="certificate-container cert-frame max-w-4xl mx-auto bg-white p-10 md:p-14 rounded-3xl shadow-xl relative overflow-hidden">
        
        <!-- Watermark with Official Logo -->
        <div class="absolute inset-0 flex items-center justify-center opacity-[0.04] pointer-events-none">
            <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Watermark" class="w-[500px] h-auto object-contain">
        </div>

        <!-- Official Header -->
        <div class="text-center relative z-10 border-b-2 border-[#B4D4FF] pb-6 mb-8">
            <div class="flex items-center justify-between max-w-2xl mx-auto mb-3">
                <div class="w-18 h-18 rounded-2xl bg-[#EEF5FF] border border-[#B4D4FF] p-2 flex items-center justify-center shadow-xs">
                    <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="CivCentral Logo" class="h-14 w-auto object-contain">
                </div>
                <div class="text-center flex-1 px-4">
                    <h3 class="text-[11px] font-extrabold tracking-widest text-slate-500 uppercase">Republic of the Philippines</h3>
                    <h2 class="text-base font-black tracking-wider text-slate-900 uppercase">City Health Department</h2>
                    <p class="text-xs font-bold text-[#176B87] tracking-wider uppercase">Environmental Sanitation Division</p>
                </div>
                <div class="w-18 h-18 rounded-2xl bg-[#EEF5FF] border border-[#B4D4FF] flex items-center justify-center shadow-xs">
                    <i class="fa-solid fa-certificate text-3xl text-[#176B87]"></i>
                </div>
            </div>
            <div class="mt-4">
                <h1 class="text-2xl md:text-3xl font-black text-[#176B87] tracking-wider uppercase font-serif">SANITARY PERMIT TO OPERATE</h1>
                <p class="text-[11px] font-medium text-slate-500 tracking-wide mt-1">Issued pursuant to Presidential Decree No. 856 (Code on Sanitation of the Philippines) & City Ordinance No. 0386</p>
            </div>
        </div>

        <!-- Certificate Body -->
        <div class="relative z-10 space-y-6 text-center">
            
            <p class="text-xs uppercase font-bold text-slate-400 tracking-widest">This is to certify that</p>
            
            <!-- Establishment Name Box -->
            <div class="py-2 border-b-2 border-[#176B87] max-w-xl mx-auto">
                <h2 class="text-2xl md:text-3xl font-black text-slate-900 uppercase tracking-tight"><?php echo htmlspecialchars($businessName); ?></h2>
            </div>

            <!-- Establishment Info Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl mx-auto text-left bg-[#EEF5FF]/60 p-5 rounded-2xl border border-[#B4D4FF]/80 text-xs">
                <div>
                    <span class="text-slate-400 font-bold uppercase tracking-wider block text-[10px]">Owner / Operator:</span>
                    <span class="text-slate-800 font-extrabold text-sm"><?php echo htmlspecialchars($ownerName); ?></span>
                </div>
                <div>
                    <span class="text-slate-400 font-bold uppercase tracking-wider block text-[10px]">Line of Business:</span>
                    <span class="text-slate-800 font-extrabold text-sm"><?php echo htmlspecialchars($businessType); ?></span>
                </div>
                <div class="md:col-span-2">
                    <span class="text-slate-400 font-bold uppercase tracking-wider block text-[10px]">Establishment Address:</span>
                    <span class="text-slate-800 font-extrabold text-sm"><?php echo htmlspecialchars($address); ?></span>
                </div>
            </div>

            <!-- Compliance Text -->
            <p class="text-xs text-slate-600 max-w-2xl mx-auto leading-relaxed pt-2">
                Has fully complied with the environmental health rules and sanitary regulations of the City Health Department and is hereby granted this <strong>Sanitary Permit to Operate</strong> in accordance with national laws.
            </p>

            <!-- Metadata & QR Code Section -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center max-w-2xl mx-auto pt-4 pb-4 border-y border-[#B4D4FF]/70">
                <div class="text-left space-y-1.5 text-xs">
                    <div>
                        <span class="text-slate-400 font-semibold block text-[10px]">PERMIT NUMBER:</span>
                        <span class="font-mono font-black text-base text-[#176B87]"><?php echo htmlspecialchars($permitNumber); ?></span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-semibold block text-[10px]">DATE ISSUED:</span>
                        <span class="font-bold text-slate-800"><?php echo htmlspecialchars($issuedDate); ?></span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-semibold block text-[10px]">EXPIRATION DATE:</span>
                        <span class="font-bold text-rose-600"><?php echo htmlspecialchars($expiryDate); ?></span>
                    </div>
                </div>

                <!-- Central Authentic QR Code -->
                <div class="text-center flex flex-col items-center justify-center">
                    <div class="p-2.5 bg-white rounded-2xl border-2 border-[#176B87] shadow-sm inline-block">
                        <img id="qrPermitImg" src="<?php echo htmlspecialchars($qrImgUrl); ?>" alt="Sanitary Permit QR" class="w-28 h-28 object-contain">
                    </div>
                    <span class="text-[9px] font-mono text-[#176B87] font-bold mt-1.5"><?php echo htmlspecialchars($qrVal); ?></span>
                    <span class="text-[8px] text-[#176B87] font-bold uppercase tracking-wider mt-0.5"><i class="fa-solid fa-circle-check text-emerald-600"></i> Verified Official</span>
                </div>

                <div class="text-right space-y-1.5 text-xs">
                    <div>
                        <span class="text-slate-400 font-semibold block text-[10px]">FEE PAID:</span>
                        <span class="font-black text-slate-900">₱<?php echo htmlspecialchars($fee); ?></span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-semibold block text-[10px]">STATUS:</span>
                        <span class="inline-block px-2 py-0.5 bg-[#EEF5FF] text-[#176B87] border border-[#B4D4FF] font-black rounded-full text-[10px]">ACTIVE & VALID</span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-semibold block text-[10px]">ISSUING AUTHORITY:</span>
                        <span class="font-bold text-slate-700">City Sanitation Office</span>
                    </div>
                </div>
            </div>

            <!-- Official Signatories -->
            <div class="grid grid-cols-2 gap-12 max-w-xl mx-auto pt-6 text-center">
                <div>
                    <div class="border-b border-slate-900 pb-1 mb-1 font-bold text-xs text-slate-900 uppercase">
                        ENGR. PEDRO GARCIA
                    </div>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wide">Chief, Sanitation Division</p>
                </div>
                <div>
                    <div class="border-b border-slate-900 pb-1 mb-1 font-bold text-xs text-slate-900 uppercase">
                        DR. CARLO RAMOS, MD, MPH
                    </div>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wide">City Health Officer</p>
                </div>
            </div>

            <!-- Footer Notice -->
            <div class="pt-4 text-[9px] text-slate-400 uppercase tracking-wider">
                <p>Note: This permit must be posted in a conspicuous place in the establishment and is valid for one (1) year unless revoked for non-compliance.</p>
            </div>
        </div>
    </div>

    <script>
        async function downloadQRImage() {
            const qrImg = document.getElementById('qrPermitImg');
            try {
                const res = await fetch(qrImg.src);
                const blob = await res.blob();
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = '<?php echo htmlspecialchars($permitNumber); ?>_QRCode.png';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            } catch (e) {
                window.open(qrImg.src, '_blank');
            }
        }
    </script>
</body>
</html>
