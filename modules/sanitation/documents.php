<?php
// ============================================================
// COLOR PALETTE USED ON THIS PAGE
// ============================================================
//   'brand-dark':   '#0B4F4A',
//   'brand-medium': '#14807A',
//   'brand-light':  '#E6F5F3',
//   'brand-border': '#B8E0DC',
// ============================================================

// ============================================================
// 1. PHP BACKEND - With Dependency Injection
// ============================================================
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Models/PermitDocument.php';
require_once __DIR__ . '/../../app/Models/Permit.php';
require_once __DIR__ . '/../../app/Models/Employee.php';
require_once __DIR__ . '/../../includes/data-mask.php';
require_once __DIR__ . '/../../includes/toast.php';

// Constants
const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 5;
const EXPIRY_WARNING_DAYS = 30;

// Initialize models using your existing Database singleton
$permitDocumentModel = new PermitDocument(Database::getInstance());
$permitModel = new Permit(Database::getInstance());
$employeeModel = new Employee(Database::getInstance());

// Get statistics from model (business logic moved to model)
$stats = $permitDocumentModel->getStats();

// Get expiring documents from model
$expiringDocuments = $permitDocumentModel->getExpiringSoon(EXPIRY_WARNING_DAYS);
$expiringCount = count($expiringDocuments);

// Pagination logic
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : DEFAULT_PAGE;
$limit = DEFAULT_LIMIT;
$offset = ($page - 1) * $limit;

// Get paginated documents from model
$documents = $permitDocumentModel->search([], $limit, $offset);
$totalDocuments = $stats['total'];
$totalPages = max(1, ceil($totalDocuments / $limit));

// Format documents for display (with N+1 problem solved)
$formattedDocuments = formatDocumentsForDisplay($documents, $employeeModel);

// Get permits for dropdown
$permits = $permitModel->all(['order' => 'created_at.desc']);
$formattedPermits = array_map(function($p) {
    return [
        'id' => (int)($p['id'] ?? 0),
        'permit_id' => $p['permit_id'] ?? '',
        'applicant' => $p['applicant'] ?? '',
        'status' => $p['status'] ?? 'pending'
    ];
}, $permits);

// Get current employee
$currentEmployeeId = (int)($_SESSION['user_id'] ?? 0);
$currentEmployee = $employeeModel->find($currentEmployeeId);
$currentEmployeeName = $currentEmployee ? ($currentEmployee['full_name'] ?? '') : '';

/**
 * Format documents for display - solves N+1 problem by loading all employees at once
 */
function formatDocumentsForDisplay(array $documents, Employee $employeeModel): array
{
    // Collect all uploader IDs
    $uploaderIds = array_unique(array_filter(array_map(function($d) {
        return !empty($d['uploaded_by']) ? (int)$d['uploaded_by'] : null;
    }, $documents)));
    
    // Load all employees in one query (if method exists)
    $employeeLookup = [];
    if (!empty($uploaderIds) && method_exists($employeeModel, 'findMultiple')) {
        $employees = $employeeModel->findMultiple($uploaderIds);
        foreach ($employees as $emp) {
            $employeeLookup[$emp['id']] = $emp['full_name'] ?? "Employee #{$emp['id']}";
        }
    }
    
    // Format each document
    return array_map(function($d) use ($employeeLookup) {
        return formatSingleDocument($d, $employeeLookup);
    }, $documents);
}

/**
 * Format a single document for display
 */
function formatSingleDocument(array $d, array $employeeLookup): array
{
    $uploadedById = (int)($d['uploaded_by'] ?? 0);
    $uploaderName = $employeeLookup[$uploadedById] ?? 'Unknown';
    
    // Format file size
    $fileSize = (int)($d['file_size'] ?? 0);
    $fileSizeFormatted = formatFileSize($fileSize);
    
    return [
        'id' => (int)($d['id'] ?? 0),
        'document_id' => $d['document_id'] ?? '',
        'permit_id' => (int)($d['permit_id'] ?? 0),
        'applicant' => $d['applicant'] ?? '',
        'document_type' => $d['document_type'] ?? '',
        'file_name' => $d['file_name'] ?? '',
        'file_size' => $fileSizeFormatted,
        'file_size_raw' => $fileSize,
        'file_type' => $d['file_type'] ?? '',
        'uploaded_by' => $uploaderName,
        'uploaded_by_id' => $uploadedById,
        'uploaded_at' => $d['uploaded_at'] ?? '',
        'status' => strtolower($d['status'] ?? 'pending'),
        'expiry_date' => $d['expiry_date'] ?? null,
        'qr_code' => $d['qr_code'] ?? null,
        'notes' => $d['notes'] ?? '',
        'verified' => (bool)($d['verified'] ?? false)
    ];
}

/**
 * Format file size to human-readable format
 */
function formatFileSize(int $bytes): string
{
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }
    return $bytes . ' B';
}

/**
 * Get status CSS class
 */
function getStatusClass(string $status): string
{
    $classes = [
        'verified' => 'bg-emerald-100 text-emerald-700',
        'pending' => 'bg-amber-100 text-amber-700',
        'expired' => 'bg-rose-100 text-rose-700'
    ];
    return $classes[$status] ?? $classes['pending'];
}

/**
 * Calculate days left until expiry
 */
function getDaysLeft(?string $expiryDate): ?int
{
    if (empty($expiryDate)) {
        return null;
    }
    return (int)round((strtotime($expiryDate) - time()) / 86400);
}

$title = 'Documents';
?>

<!-- Rest of HTML remains mostly the same but using the refactored functions -->
<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Documents</h2>
            <p class="text-sm text-slate-500 mt-0.5">Upload, manage, and verify digital permits & documents</p>
        </div>
        <div class="flex gap-3">
            <button onclick="openModal('uploadDocumentModal')"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-upload text-xs"></i> Upload Document
            </button>
        </div>
    </div>

    <!-- KPI Cards - Using stats from model -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <!-- Total Documents -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-file-lines text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo $stats['total']; ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Documents</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">📄 All documents</span>
                    <span class="text-[10px] text-slate-400"><?php echo $stats['verified']; ?> verified</span>
                </div>
            </div>
        </div>

        <!-- Verified -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                        <i class="fa-solid fa-check-circle text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-emerald-600"><?php echo $stats['verified']; ?></p>
                        <p class="text-xs font-medium text-slate-500">Verified</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Approved</span>
                    <span class="text-[10px] text-slate-400">Authenticated</span>
                </div>
            </div>
        </div>

        <!-- Pending -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <i class="fa-solid fa-clock text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-amber-600"><?php echo $stats['pending']; ?></p>
                        <p class="text-xs font-medium text-slate-500">Pending</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">⏳ Awaiting</span>
                    <span class="text-[10px] text-slate-400">Needs review</span>
                </div>
            </div>
        </div>

        <!-- Expired -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-rose-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-rose-500 to-rose-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-rose-200">
                        <i class="fa-solid fa-calendar-xmark text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-rose-600"><?php echo $stats['expired']; ?></p>
                        <p class="text-xs font-medium text-slate-500">Expired</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[10px] font-bold">📅 Overdue</span>
                    <span class="text-[10px] text-slate-400">Needs renewal</span>
                </div>
            </div>
        </div>

        <!-- QR Codes -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-brand-light rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-brand-dark to-brand-medium rounded-xl flex items-center justify-center text-white shadow-lg shadow-brand-light">
                        <i class="fa-solid fa-qrcode text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-brand-dark"><?php echo $stats['has_qr']; ?></p>
                        <p class="text-xs font-medium text-slate-500">QR Codes</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-brand-light text-brand-dark rounded-full text-[10px] font-bold">📱 Generated</span>
                    <span class="text-[10px] text-slate-400">Digital verification</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Expiry Alerts -->
    <?php if ($expiringCount > 0): ?>
    <div class="relative overflow-hidden bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-4 flex items-center justify-between">
        <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-30"></div>
        <div class="relative flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-clock text-amber-500 text-lg"></i>
            </div>
            <div>
                <p class="text-sm font-semibold text-amber-700">
                    ⏰ <span class="font-bold"><?php echo $expiringCount; ?></span> document(s) expiring within <?php echo EXPIRY_WARNING_DAYS; ?> days
                </p>
                <div class="flex flex-wrap gap-2 mt-1">
                    <?php foreach (array_slice($expiringDocuments, 0, 3) as $doc): ?>
                        <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-xs font-medium">
                            <?php echo htmlspecialchars($doc['document_type'] ?? 'Document'); ?> 
                            (<?php echo htmlspecialchars($doc['applicant'] ?? 'Unknown'); ?>)
                        </span>
                    <?php endforeach; ?>
                    <?php if ($expiringCount > 3): ?>
                        <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-xs font-medium">
                            +<?php echo $expiringCount - 3; ?> more
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <button onclick="document.getElementById('filterStatus').value='expiring_soon'; filterDocuments();" 
                class="relative px-4 py-2 text-xs font-semibold text-amber-700 hover:text-amber-900 bg-white/60 rounded-lg hover:bg-white transition border border-amber-200">
            View All
        </button>
    </div>
    <?php endif; ?>

    <!-- Search & Filter -->
    <div class="bg-white rounded-xl shadow-xs p-4 border border-slate-200 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text"
                       id="searchDocument"
                       placeholder="Search by document ID, applicant, or file name..."
                       class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
            </div>
            <div class="flex gap-2 flex-wrap">
                <select id="filterStatus" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="verified">Verified</option>
                    <option value="pending">Pending</option>
                    <option value="expired">Expired</option>
                    <option value="expiring_soon">Expiring Soon</option>
                </select>
                <select id="filterType" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Types</option>
                    <option value="business_permit">Business Permit</option>
                    <option value="sanitary_permit">Sanitary Permit</option>
                    <option value="fire_safety">Fire Safety</option>
                    <option value="zoning_clearance">Zoning Clearance</option>
                    <option value="environmental_compliance">Environmental Compliance</option>
                    <option value="building_permit">Building Permit</option>
                    <option value="tax_clearance">Tax Clearance</option>
                    <option value="other">Other</option>
                </select>
                <button onclick="resetFilters()" title="Reset filters"
                        class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Documents Table -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Doc ID</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Applicant / Permit</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Document Type</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">File</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">QR Code</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Expiry</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="documentTableBody">
                    <?php foreach ($formattedDocuments as $document): ?>
                    <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors document-row <?php echo $document['status'] === 'expired' ? 'bg-rose-50/50' : ''; ?>"
                        data-applicant="<?php echo htmlspecialchars(strtolower($document['applicant'])); ?>"
                        data-type="<?php echo htmlspecialchars(strtolower($document['document_type'])); ?>"
                        data-status="<?php echo htmlspecialchars($document['status']); ?>"
                        data-id="<?php echo htmlspecialchars($document['document_id']); ?>">
                        <td class="px-4 py-3 font-mono text-xs text-brand-dark font-semibold">
                            <?php echo htmlspecialchars($document['document_id']); ?>
                        </td>
                        <td class="px-4 py-3">
                            <div>
                                <p class="font-semibold text-slate-800 text-sm maskable" 
                                   data-masked="<?php echo htmlspecialchars(maskName($document['applicant'])); ?>"
                                   data-real="<?php echo htmlspecialchars($document['applicant']); ?>">
                                    <?php echo htmlspecialchars($document['applicant']); ?>
                                </p>
                                <p class="text-xs text-slate-400"><?php echo $document['permit_id']; ?></p>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-xs">
                            <?php echo htmlspecialchars($document['document_type']); ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-file-pdf text-rose-500"></i>
                                <span class="text-xs text-slate-600 truncate max-w-[100px]">
                                    <?php echo htmlspecialchars($document['file_name']); ?>
                                </span>
                            </div>
                            <span class="text-[10px] text-slate-400"><?php echo $document['file_size']; ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($document['qr_code']): ?>
                                <span class="px-2 py-1 bg-brand-light/60 rounded text-xs font-mono text-brand-dark border border-brand-border">
                                    <i class="fa-solid fa-qrcode mr-1"></i>
                                    <?php echo htmlspecialchars($document['qr_code']); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-xs text-slate-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo getStatusClass($document['status']); ?>">
                                <?php echo ucfirst($document['status']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-500 text-xs">
                            <?php if ($document['expiry_date']): 
                                $daysLeft = getDaysLeft($document['expiry_date']);
                            ?>
                                <span class="<?php echo $daysLeft <= EXPIRY_WARNING_DAYS ? 'text-rose-600 font-bold' : 'text-slate-500'; ?>">
                                    <?php echo date('M d, Y', strtotime($document['expiry_date'])); ?>
                                </span>
                                <?php if ($document['status'] !== 'expired'): ?>
                                    <span class="block text-[10px] <?php echo $daysLeft <= EXPIRY_WARNING_DAYS ? 'text-rose-500' : 'text-slate-400'; ?>">
                                        <?php echo $daysLeft . ' days left'; ?>
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-slate-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <button onclick="viewDocument(<?php echo $document['id']; ?>)"
                                        class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View">
                                    <i class="fa-solid fa-eye text-sm"></i>
                                </button>
                                <button onclick="downloadDocument('<?php echo htmlspecialchars($document['file_name'], ENT_QUOTES); ?>')"
                                        class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Download">
                                    <i class="fa-solid fa-download text-sm"></i>
                                </button>
                                <?php if ($document['qr_code']): ?>
                                    <button onclick="viewQR('<?php echo htmlspecialchars($document['qr_code'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($document['applicant'], ENT_QUOTES); ?>')"
                                            class="p-1.5 text-brand-dark hover:bg-brand-light rounded-lg transition" title="QR Code">
                                        <i class="fa-solid fa-qrcode text-sm"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if ($document['status'] === 'pending'): ?>
                                    <button onclick="verifyDocument(<?php echo $document['id']; ?>)"
                                            class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Verify">
                                        <i class="fa-solid fa-check text-sm"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <!-- Empty state -->
    <div id="emptyState" class="hidden flex-col items-center justify-center py-14 text-center">
        <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
            <i class="fa-solid fa-folder-open text-slate-400 text-2xl"></i>
        </div>
        <p class="text-base font-bold text-slate-700 mb-1">No Documents Found</p>
        <p class="text-sm text-slate-500 mb-4">There are no documents to display at this time</p>
        <button onclick="openModal('uploadDocumentModal')" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
            <i class="fa-solid fa-upload mr-2"></i>Upload First Document
        </button>
    </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-slate-50">
            <p class="text-xs text-slate-500">
                Showing <span class="font-semibold text-slate-700"><?php echo $offset + 1; ?></span> to
                <span class="font-semibold text-slate-700"><?php echo min($offset + $limit, $totalDocuments); ?></span> of
                <span class="font-semibold text-slate-700"><?php echo $totalDocuments; ?></span> documents
            </p>
            <div class="flex gap-1">
                <button onclick="changePage(<?php echo $page - 1; ?>)"
                        class="px-3 py-1.5 rounded-lg text-sm <?php echo $page <= 1 ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'; ?>"
                        <?php echo $page <= 1 ? 'disabled' : ''; ?>>
                    <i class="fa-solid fa-chevron-left text-xs"></i>
                </button>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <button onclick="changePage(<?php echo $i; ?>)"
                            class="px-3 py-1.5 rounded-lg text-sm font-medium <?php echo $i === $page ? 'bg-brand-dark text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'; ?>">
                        <?php echo $i; ?>
                    </button>
                <?php endfor; ?>
                <button onclick="changePage(<?php echo $page + 1; ?>)"
                        class="px-3 py-1.5 rounded-lg text-sm <?php echo $page >= $totalPages ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'; ?>"
                        <?php echo $page >= $totalPages ? 'disabled' : ''; ?>>
                    <i class="fa-solid fa-chevron-right text-xs"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const DOCUMENTS = <?php echo json_encode(array_column($documents, null, 'id'), JSON_PRETTY_PRINT); ?>;

    // ============================================================
    // MODAL FUNCTIONS - Using ModalSystem
    // ============================================================
    window.openModal = function(id) {
        const modal = document.getElementById(id);
        if (!modal) {
            console.warn('Modal not found:', id);
            return;
        }
        
        if (typeof ModalSystem !== 'undefined') {
            ModalSystem.open(id);
        } else {
            // Fallback if ModalSystem not loaded
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }
    };

    window.closeModal = function(id) {
        const modal = document.getElementById(id);
        if (!modal) {
            console.warn('Modal not found:', id);
            return;
        }
        
        if (typeof ModalSystem !== 'undefined') {
            ModalSystem.close(id);
        } else {
            // Fallback if ModalSystem not loaded
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }
    };

    // ============================================================
    // FILE HANDLING
    // ============================================================
    function handleFileSelect(file) {
        const fileSelected = document.getElementById('fileSelected');
        const fileName = document.getElementById('fileNameDisplay');
        const fileSize = document.getElementById('fileSizeDisplay');
        
        if (file) {
            fileName.textContent = file.name;
            fileSize.textContent = (file.size / 1024).toFixed(1) + ' KB';
            fileSelected.classList.remove('hidden');
        } else {
            fileSelected.classList.add('hidden');
        }
    }

    // ============================================================
    // REFRESH DOCUMENT LIST
    // ============================================================
    async function refreshDocumentList(page = 1, limit = 5) {
        const apiUrl = '<?php echo site_url('api/permit_documents.php'); ?>?page=' + page + '&limit=' + limit;
        try {
            const response = await fetch(apiUrl);
            const result = await response.json();
            
            if (!response.ok) {
                const errMsg = result && result.message ? result.message : 'HTTP error: ' + response.status;
                throw new Error(errMsg);
            }
            
            if (!result || !result.success) {
                throw new Error(result && result.message ? result.message : 'Refresh failed');
            }
            
            const data = result.data;
            if (!Array.isArray(data)) {
                console.error('Unexpected data format', result);
                return;
            }
            
            // Show loading state on table
            const tbody = document.getElementById('documentTableBody');
            tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center"><i class="fa-solid fa-spinner fa-spin text-2xl text-brand-medium"></i><p class="text-sm text-slate-500 mt-2">Loading documents...</p></td></tr>';
            
            // Small delay for UX
            await new Promise(resolve => setTimeout(resolve, 400));
            
            // Rebuild table body
            tbody.innerHTML = '';
            data.forEach(doc => {
                const unmasked = doc.applicant || '';
                // Generate masked version: first letter + asterisks for remaining chars
                const masked = unmasked.split(' ').map(function(p) {
                    if (!p) return '';
                    return p.charAt(0) + '*'.repeat(Math.max(0, p.length - 1));
                }).join(' ');
                
                // Format file size (inline - same logic as PHP formatFileSize)
                var fs = parseInt(doc.file_size) || 0;
                var fileSizeDisplay;
                if (fs >= 1048576) {
                    fileSizeDisplay = (fs / 1048576).toFixed(1) + ' MB';
                } else if (fs >= 1024) {
                    fileSizeDisplay = (fs / 1024).toFixed(1) + ' KB';
                } else {
                    fileSizeDisplay = fs + ' B';
                }
                
                // Get status class (inline - same logic as PHP getStatusClass)
                var statusClass;
                if (doc.status === 'verified') {
                    statusClass = 'bg-emerald-100 text-emerald-700';
                } else if (doc.status === 'expired') {
                    statusClass = 'bg-rose-100 text-rose-700';
                } else {
                    statusClass = 'bg-amber-100 text-amber-700';
                }
                
                const row = document.createElement('tr');
                row.className = 'border-b border-slate-100 hover:bg-brand-light/40 transition document-row ' + (doc.status === 'expired' ? 'bg-rose-50/50' : '');
                row.dataset.applicant = unmasked.toLowerCase();
                row.dataset.type = doc.document_type?.toLowerCase() || '';
                row.dataset.status = doc.status || '';
                row.dataset.id = doc.id;
                row.innerHTML = `
                    <td class="px-4 py-3 font-mono text-xs text-brand-dark font-semibold">${escHtml(doc.document_id)}</td>
                    <td class="px-4 py-3">
                        <div>
                            <p class="font-semibold text-slate-800 text-sm maskable" data-masked="${escHtml(masked)}" data-real="${escHtml(unmasked)}">${escHtml(unmasked)}</p>
                            <p class="text-xs text-slate-500">${escHtml(doc.permit_id)}</p>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-slate-600 text-xs">${escHtml(doc.document_type)}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-file-pdf text-rose-500"></i>
                            <span class="text-xs text-slate-600 truncate max-w-[100px]">${escHtml(doc.file_name)}</span>
                        </div>
                        <span class="text-[10px] text-slate-400">${fileSizeDisplay}</span>
                    </td>
                    <td class="px-4 py-3">
                        ${doc.qr_code ? `<span class="px-2 py-1 bg-brand-light/60 rounded text-xs font-mono text-brand-dark border border-brand-border"><i class="fa-solid fa-qrcode mr-1"></i>${escHtml(doc.qr_code)}</span>` : '<span class="text-xs text-slate-400">—</span>'}
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold ${statusClass}">${doc.status.charAt(0).toUpperCase() + doc.status.slice(1)}</span>
                    </td>
                    <td class="px-4 py-3">
                        ${doc.expiry_date ? `<span class="${(new Date(doc.expiry_date) - Date.now())/86400000 <= <?php echo EXPIRY_WARNING_DAYS; ?> ? 'text-rose-600 font-bold' : 'text-slate-500'}">${new Date(doc.expiry_date).toLocaleDateString()}</span>` : '<span class="text-slate-400">—</span>'}
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <button onclick="viewDocument(${doc.id})" class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View"><i class="fa-solid fa-eye text-sm"></i></button>
                            <button onclick="downloadDocument('${doc.file_name}')" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Download"><i class="fa-solid fa-download text-sm"></i></button>
                            ${doc.qr_code ? `<button onclick="viewQR('${doc.qr_code}', '${doc.applicant}')" class="p-1.5 text-brand-dark hover:bg-brand-light rounded-lg transition" title="QR Code"><i class="fa-solid fa-qrcode text-sm"></i></button>` : ''}
                            ${doc.status === 'pending' ? `<button onclick="verifyDocument(${doc.id})" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Verify"><i class="fa-solid fa-check text-sm"></i></button>` : ''}
                        </div>
                    </td>`;
                tbody.appendChild(row);
                DOCUMENTS[doc.id] = doc;
            });
            // Update empty state visibility
            const emptyState = document.getElementById('emptyState');
            if (data.length === 0) {
                emptyState.style.display = 'flex';
            } else {
                emptyState.style.display = 'none';
            }
        } catch (err) {
            console.error('Failed to refresh documents:', err);
            toast.error(err.message, { title: 'Refresh Error', duration: 5000 });
        }
    }

    // ============================================================
    // UPLOAD DOCUMENT
    // ============================================================
    async function saveUploadedDocument(event) {
        event.preventDefault();
        
        const form = event.target;
        
        // Get selected permit and document type
        const permitSelect = form.querySelector('select');
        const docTypeSelect = form.querySelectorAll('select')[1];
        const fileInput = form.querySelector('input[type="file"]');
        const notesTextarea = form.querySelector('textarea');
        const applicantInput = form.querySelector('input[name="applicant"]');
        
        // Validation
        if (!permitSelect.value) {
            toast.error('Please select a permit', { title: 'Validation Error', duration: 3000 });
            return;
        }
        if (!docTypeSelect.value) {
            toast.error('Please select a document type', { title: 'Validation Error', duration: 3000 });
            return;
        }
        if (!fileInput.files[0]) {
            toast.error('Please select a file to upload', { title: 'Validation Error', duration: 3000 });
            return;
        }
        if (!applicantInput || !applicantInput.value.trim()) {
            toast.error('Please enter applicant name', { title: 'Validation Error', duration: 3000 });
            return;
        }
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        const originalIcon = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Uploading...';
        submitBtn.disabled = true;
        
        // Show loading overlay
        const loadingOverlay = document.getElementById('uploadLoadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.classList.remove('hidden');
        }
        
        try {
            const file = fileInput.files[0];
            const uploadData = {
                permit_id: parseInt(permitSelect.value),
                document_type: docTypeSelect.value,
                file_name: file.name,
                file_path: '/uploads/' + file.name,
                file_size: file.size,
                file_type: file.name.split('.').pop(),
                mime_type: file.type,
                uploaded_by: <?php echo $currentEmployeeId; ?>,
                applicant: applicantInput.value.trim(),
                notes: notesTextarea.value || '',
                status: 'pending'
            };
            
            console.log('Uploading document:', uploadData);
            
            // Submit to API
            const apiUrl = '<?php echo site_url('api/permit_documents.php'); ?>';
            console.log('API URL:', apiUrl);
            
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(uploadData)
            });
            
            console.log('Response status:', response.status);
            const responseText = await response.text();
            console.log('Response text:', responseText);
            
            let result;
            if (responseText && responseText.trim() !== '') {
                try {
                    result = JSON.parse(responseText);
                } catch (e) {
                    console.error('Failed to parse JSON:', e, 'Response:', responseText);
                }
            }
            
            if (!response.ok) {
                const errMsg = result && result.message ? result.message : `HTTP error! status: ${response.status}`;
                const errDetails = result && result.data && Array.isArray(result.data) ? ': ' + result.data.join(', ') : '';
                throw new Error(errMsg + errDetails);
            }
            
            if (result && result.success) {
    closeModal('uploadDocumentModal');
    form.reset();
    await refreshDocumentList();
}
        } catch (error) {
            console.error('Upload error:', error);
            toast.error('Error uploading document: ' + error.message, { title: 'Error', duration: 4000 });
        } finally {
            // Reset button
            submitBtn.textContent = originalText;
            submitBtn.innerHTML = originalIcon;
            submitBtn.disabled = false;
            
            // Hide loading overlay
            if (loadingOverlay) {
                loadingOverlay.classList.add('hidden');
            }
        }
    }

    // ============================================================
    // VIEW DOCUMENT
    // ============================================================
    function viewDocument(id) {
        openModal('viewDocumentModal');
        const d = DOCUMENTS[id];
        if (!d) return;

        setTimeout(() => {
            const statusColors = {
                verified: 'bg-emerald-100 text-emerald-700',
                pending: 'bg-amber-100 text-amber-700',
                expired: 'bg-rose-100 text-rose-700'
            };

            document.getElementById('documentDetailsContent').innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                        <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-2xl flex-shrink-0">
                            ${d.applicant.charAt(0)}
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-slate-900">${d.document_type}</h4>
                            <p class="text-sm text-slate-500">${d.document_id} • ${d.applicant}</p>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${statusColors[d.status] || statusColors.pending}">
                                ${d.status.toUpperCase()}
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><p class="text-xs text-slate-400 font-semibold">Permit ID</p><p class="text-sm text-slate-800">${d.permit_id}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">File Name</p><p class="text-sm text-slate-800">${d.file_name}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">File Size</p><p class="text-sm text-slate-800">${d.file_size}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Uploaded By</p><p class="text-sm text-slate-800">${d.uploaded_by}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Uploaded At</p><p class="text-sm text-slate-800">${new Date(d.uploaded_at).toLocaleString()}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Expiry Date</p><p class="text-sm text-slate-800">${d.expiry_date ? new Date(d.expiry_date).toLocaleDateString() : '—'}</p></div>
                    </div>
                    ${d.notes ? `<div class="bg-slate-50 rounded-xl p-4 border border-slate-200"><h5 class="text-sm font-bold text-slate-700 mb-2">Notes</h5><p class="text-sm text-slate-800">${d.notes}</p></div>` : ''}
                    ${d.qr_code ? `<div class="bg-brand-light/40 rounded-xl p-4 border border-brand-border text-center"><i class="fa-solid fa-qrcode text-2xl text-brand-dark block mb-2"></i><p class="text-sm font-semibold text-slate-700">QR Code: ${d.qr_code}</p><button onclick="closeModal('viewDocumentModal'); viewQR('${d.qr_code}', '${d.applicant}')" class="mt-2 px-4 py-1.5 bg-brand-dark text-white rounded-lg text-xs hover:bg-brand-medium transition"><i class="fa-solid fa-qrcode mr-1"></i> View QR</button></div>` : ''}
                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                        <button onclick="closeModal('viewDocumentModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                        <button onclick="downloadDocument('${d.file_name}')" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold"><i class="fa-solid fa-download mr-1.5"></i> Download</button>
                    </div>
                </div>
            `;
        }, 300);
    }

    // ============================================================
    // DOWNLOAD DOCUMENT
    // ============================================================
    function downloadDocument(fileName) {
        toast.info('Downloading: ' + fileName, { title: 'Download', duration: 3000 });
    }

    // ============================================================
    // VERIFY DOCUMENT
    // ============================================================
    function verifyDocument(id) {
        if (!confirm('Verify this document?')) return;
        const d = DOCUMENTS[id];
        if (!d) return;
        
        d.status = 'verified';
        if (!d.qr_code) {
            d.qr_code = 'QR-' + String(id).padStart(3, '0') + '-' + String(Math.floor(Math.random() * 900) + 100);
        }
        updateDocumentRow(d);
        toast.success('Document ' + d.document_id + ' verified!', { title: 'Verified', duration: 3000 });
    }

    function updateDocumentRow(d) {
        const rows = document.querySelectorAll('.document-row');
        rows.forEach(row => {
            const docId = row.querySelector('.font-mono.text-xs.text-brand-dark.font-semibold')?.textContent;
            if (docId === d.document_id) {
                // Update status
                const statusBadge = row.querySelector('.px-2.py-1.rounded-full');
                const statusColors = {
                    verified: 'bg-emerald-100 text-emerald-700',
                    pending: 'bg-amber-100 text-amber-700',
                    expired: 'bg-rose-100 text-rose-700'
                };
                statusBadge.className = `px-2 py-1 rounded-full text-xs font-semibold ${statusColors[d.status] || statusColors.pending}`;
                statusBadge.textContent = d.status.charAt(0).toUpperCase() + d.status.slice(1);
                
                // Update QR
                const qrCell = row.querySelector('.px-4.py-3 .px-2.py-1');
                if (qrCell) {
                    if (d.qr_code) {
                        qrCell.className = 'px-2 py-1 bg-brand-light/60 rounded text-xs font-mono text-brand-dark border border-brand-border';
                        qrCell.innerHTML = `<i class="fa-solid fa-qrcode mr-1"></i>${d.qr_code}`;
                    }
                }
            }
        });
    }

    // ============================================================
    // QR CODE VIEWER
    // ============================================================
    function viewQR(qrCode, applicant) {
        document.getElementById('qrCodeId').textContent = qrCode;
        document.getElementById('qrApplicant').textContent = applicant;
        openModal('qrViewerModal');
    }

    function downloadQR() {
        toast.success('QR Code downloaded successfully!', { title: 'Downloaded', duration: 3000 });
    }


    // ============================================================
    // SEARCH & FILTER
    // ============================================================
    document.getElementById('searchDocument').addEventListener('input', filterDocuments);
    document.getElementById('filterStatus').addEventListener('change', filterDocuments);
    document.getElementById('filterType').addEventListener('change', filterDocuments);

    function filterDocuments() {
        const search = document.getElementById('searchDocument').value.toLowerCase();
        const status = document.getElementById('filterStatus').value;
        const type = document.getElementById('filterType').value.toLowerCase();
        let visibleCount = 0;

        document.querySelectorAll('.document-row').forEach(row => {
            const applicant = row.dataset.applicant;
            const rowType = row.dataset.type;
            const rowStatus = row.dataset.status;
            const docId = row.dataset.id.toLowerCase();

            let matchesStatus = true;
            if (status === 'expiring_soon') {
                const expiryCell = row.querySelector('.px-4.py-3.text-slate-500.text-xs span');
                if (expiryCell) {
                    const daysText = expiryCell.textContent.match(/\d+/);
                    const daysLeft = daysText ? parseInt(daysText[0]) : 999;
                    matchesStatus = daysLeft <= 30 && rowStatus !== 'expired';
                } else {
                    matchesStatus = false;
                }
            } else {
                matchesStatus = !status || rowStatus === status;
            }

            const matchesSearch = applicant.includes(search) || docId.includes(search);
            const matchesType = !type || rowType === type;
            const isVisible = matchesSearch && matchesStatus && matchesType;

            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        document.getElementById('emptyState').style.display = visibleCount === 0 ? 'flex' : 'none';
    }

    function resetFilters() {
        document.getElementById('searchDocument').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterType').value = '';
        document.querySelectorAll('.document-row').forEach(row => row.style.display = '');
        document.getElementById('emptyState').style.display = 'none';
    }

    function changePage(page) {
        if (page < 1 || page > <?php echo $totalPages; ?>) return;
        window.location.href = '?page=' + page;
    }

    // ============================================================
    // HTML ESCAPE HELPER (for dynamic content)
    // ============================================================
    function escHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ============================================================
    // INITIALIZATION
    // ============================================================
    
    // Check if documents exist on page load
    function checkEmptyState() {
        const hasDocuments = document.querySelectorAll('.document-row').length > 0;
        const emptyState = document.getElementById('emptyState');
        
        if (!hasDocuments && emptyState) {
            emptyState.style.display = 'flex';
        } else if (emptyState) {
            emptyState.style.display = 'none';
        }
    }
    
    // Run on page load
    document.addEventListener('DOMContentLoaded', function() {
        checkEmptyState();
    });
    
    // Run after filter changes
    const originalFilterDocuments = window.filterDocuments;
    window.filterDocuments = function() {
        if (originalFilterDocuments) {
            originalFilterDocuments();
        }
        // Delay check to allow DOM to update
        setTimeout(checkEmptyState, 100);
    };
</script>

    <!-- ============================================================ -->
    <!-- MODALS -->
    <!-- ============================================================ -->
    
    <!-- Upload Document Modal -->
    <div id="uploadDocumentModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl z-10">
                <h3 class="font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-upload text-brand-medium"></i> Upload Document
                </h3>
                <button onclick="closeModal('uploadDocumentModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="p-6 relative">
                <!-- Loading Overlay -->
                <div id="uploadLoadingOverlay" class="hidden absolute inset-0 bg-white/80 backdrop-blur-sm z-20 flex items-center justify-center rounded-2xl">
                    <div class="text-center">
                        <i class="fa-solid fa-spinner fa-spin text-4xl text-brand-medium mb-3"></i>
                        <p class="text-sm font-semibold text-slate-700">Uploading document...</p>
                        <p class="text-xs text-slate-500 mt-1">Please wait</p>
                    </div>
                </div>
                
                <form onsubmit="saveUploadedDocument(event)">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Applicant Name <span class="text-rose-500">*</span></label>
                            <input type="text" 
                                   name="applicant" 
                                   placeholder="Enter applicant name" 
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm"
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Select Permit</label>
                            <select class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm">
                                <option value="">Choose a permit...</option>
                                <?php foreach ($formattedPermits as $permit): ?>
                                <option value="<?php echo $permit['id']; ?>">
                                    <?php echo htmlspecialchars($permit['permit_id'] . ' - ' . $permit['applicant']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Document Type</label>
                            <select class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm">
                                <option value="">Select document type...</option>
                                <option value="business_permit">Business Permit</option>
                                <option value="sanitary_permit">Sanitary Permit</option>
                                <option value="fire_safety">Fire Safety</option>
                                <option value="zoning_clearance">Zoning Clearance</option>
                                <option value="environmental_compliance">Environmental Compliance</option>
                                <option value="building_permit">Building Permit</option>
                                <option value="tax_clearance"> Tax Clearance</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Upload File <span class="text-rose-500">*</span></label>
                            <div class="border-2 border-dashed border-slate-200 rounded-lg p-6 text-center hover:border-brand-medium transition">
                                <i class="fa-solid fa-cloud-arrow-up text-3xl text-slate-400 mb-2"></i>
                                <p class="text-sm text-slate-600">Drag and drop your file here or</p>
                                <input type="file" class="mt-2 text-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Notes (Optional)</label>
                            <textarea rows="3" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-4 mt-4 border-t border-slate-200">
                        <button type="button" onclick="closeModal('uploadDocumentModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                            <i class="fa-solid fa-upload mr-2"></i>Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Document Modal -->
    <div id="viewDocumentModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl z-10">
                <h3 class="font-bold text-slate-900">Document Details</h3>
                <button onclick="closeModal('viewDocumentModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div id="documentDetailsContent" class="p-6">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- QR Code Viewer Modal -->
    <div id="qrViewerModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                <h3 class="font-bold text-slate-900">QR Code</h3>
                <button onclick="closeModal('qrViewerModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="p-6 text-center">
                <div class="w-48 h-48 bg-brand-light/40 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-qrcode text-8xl text-brand-dark"></i>
                </div>
                <p class="text-sm font-semibold text-slate-700 mb-1">QR Code ID</p>
                <p id="qrCodeId" class="text-lg font-mono text-brand-dark font-bold mb-4"></p>
                <p class="text-sm font-semibold text-slate-700 mb-1">Applicant</p>
                <p id="qrApplicant" class="text-base text-slate-600 mb-6"></p>
                <button onclick="downloadQR()" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-download mr-2"></i>Download QR Code
                </button>
            </div>
        </div>
    </div>

<?php include_once '../../includes/footer.php'; ?>
