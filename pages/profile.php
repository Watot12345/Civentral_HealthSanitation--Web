<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/paths.php';

if (empty($_SESSION['logged_in'])) {
    $_SESSION['flash_error'] = 'Access Denied: Please log in to access your profile.';
    header('Location: ' . site_url('login.php'));
    exit;
}

require_once __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$fullName = $_SESSION['full_name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$contact = $_SESSION['contact'] ?? ($_SESSION['phone'] ?? ($_SESSION['contact_number'] ?? ''));
$department = $_SESSION['department'] ?? $_SESSION['user_department'] ?? 'City Health Department';
$displayRole = $_SESSION['role'] ?? ($_SESSION['role_description'] ?? 'Employee');
$userStatus = $_SESSION['status'] ?? 'Active';
$employeeId = $_SESSION['employee_id'] ?? '';

// Fetch fresh employee record from database to ensure up-to-date profile display
if ($currentUserId > 0) {
    try {
        require_once __DIR__ . '/../app/Models/Employee.php';
        $employeeModel = new Employee();
        $freshUser = $employeeModel->find($currentUserId);
        if (!empty($freshUser)) {
            $fullName = $freshUser['full_name'] ?? $fullName;
            $email = $freshUser['email'] ?? $email;
            $contact = $freshUser['contact_number'] ?? ($freshUser['contact'] ?? ($freshUser['phone'] ?? $contact));
            $department = $freshUser['department'] ?? $department;
            $displayRole = $freshUser['role_description'] ?? ($freshUser['role'] ?? $displayRole);
            $userStatus = $freshUser['status'] ?? $userStatus;
            $employeeId = $freshUser['employee_id'] ?? $employeeId;

            $_SESSION['full_name'] = $fullName;
            $_SESSION['user_full_name'] = $fullName;
            $_SESSION['contact'] = $contact;
            $_SESSION['contact_number'] = $contact;
            $_SESSION['phone'] = $contact;
            $_SESSION['email'] = $email;
        }
    } catch (Throwable $e) {
        error_log('profile.php user refresh error: ' . $e->getMessage());
    }
}

// Remove leading 63 / +63 for clean local number display
$displayContact = preg_replace('/^\+?63\s*/', '', trim($contact));

$nameParts = explode(' ', trim($fullName));
$initials = '';
foreach ($nameParts as $part) {
    if (!empty($part)) {
        $initials .= strtoupper($part[0]);
    }
}
$initials = substr($initials, 0, 2);

$assignedStation = 'Main Health HQ (Caloocan)';
$dLower = strtolower($department);
if (str_contains($dLower, 'health center') || str_contains($dLower, 'medical')) {
    $assignedStation = 'District 1 Health Center';
} elseif (str_contains($dLower, 'sanitation')) {
    $assignedStation = 'Sanitation & Environmental Inspection Unit';
} elseif (str_contains($dLower, 'immunization') || str_contains($dLower, 'nutrition')) {
    $assignedStation = 'Maternal & Child Health Center';
} elseif (str_contains($dLower, 'waste')) {
    $assignedStation = 'Wastewater Treatment & Septic Services';
} elseif (str_contains($dLower, 'surveillance')) {
    $assignedStation = 'Epidemiological Surveillance Unit (CESU)';
} elseif (str_contains($dLower, 'admin')) {
    $assignedStation = 'City Administration HQ';
}

$pageTitle = 'My Profile';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

<div class="flex-1 flex min-h-0 bg-slate-50">
    <main class="flex-1 bg-slate-50 min-h-screen px-4 sm:px-6 py-6 overflow-y-auto">
        <div class="max-w-6xl mx-auto space-y-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-c3/80">Account</p>
                    <h1 class="text-2xl font-black text-slate-900">My Profile</h1>
                </div>
                <a href="<?= site_url('pages/dashboard.php') ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-xl hover:bg-slate-100 transition text-sm font-semibold">
                    <i class="fa-solid fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>

            <div id="profile" class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <section class="xl:col-span-1 bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="bg-gradient-to-r from-c3 to-c3d px-6 py-6 text-white">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-white/15 backdrop-blur-sm border border-white/25 flex items-center justify-center text-2xl font-black shadow-sm">
                            <?= htmlspecialchars($initials); ?>
                        </div>
                        <div>
                            <h2 class="text-lg font-black truncate"><?= htmlspecialchars($fullName); ?></h2>
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-200"><?= htmlspecialchars($displayRole); ?></p>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-4 text-sm">
                    <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
                        <span class="text-slate-500 font-semibold">Status</span>
                        <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-extrabold uppercase"><?= htmlspecialchars($userStatus); ?></span>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-start gap-3 p-3 bg-slate-50 rounded-xl">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center"><i class="fa-solid fa-envelope"></i></div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[10px] uppercase tracking-[0.15em] text-slate-400 font-bold">Email</p>
                                <p class="font-semibold text-slate-700 break-all"><?= htmlspecialchars($email); ?></p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 p-3 bg-slate-50 rounded-xl">
                            <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center"><i class="fa-solid fa-phone"></i></div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[10px] uppercase tracking-[0.15em] text-slate-400 font-bold">Contact</p>
                                <p class="font-semibold text-slate-700"><?= htmlspecialchars($displayContact ?: 'Not specified'); ?></p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 p-3 bg-slate-50 rounded-xl">
                            <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center"><i class="fa-solid fa-building"></i></div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[10px] uppercase tracking-[0.15em] text-slate-400 font-bold">Department</p>
                                <p class="font-semibold text-slate-700"><?= htmlspecialchars($department); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="xl:col-span-2 space-y-6">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
                    <div class="flex items-center justify-between gap-3 mb-5">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Profile</p>
                            <h2 class="text-xl font-black text-slate-900">Staff Details</h2>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                            <p class="text-[10px] uppercase tracking-[0.15em] text-slate-400 font-bold">Employee ID</p>
                            <p class="mt-2 font-bold text-slate-800"><?= htmlspecialchars($employeeId ?: ('EMP-' . str_pad((string)$currentUserId, 4, '0', STR_PAD_LEFT))); ?></p>
                        </div>

                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                            <p class="text-[10px] uppercase tracking-[0.15em] text-slate-400 font-bold">Assigned Station</p>
                            <p class="mt-2 font-bold text-slate-800"><?= htmlspecialchars($assignedStation); ?></p>
                        </div>

                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                            <p class="text-[10px] uppercase tracking-[0.15em] text-slate-400 font-bold">Role</p>
                            <p class="mt-2 font-bold text-slate-800"><?= htmlspecialchars($displayRole); ?></p>
                        </div>

                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                            <p class="text-[10px] uppercase tracking-[0.15em] text-slate-400 font-bold">Access Level</p>
                            <p class="mt-2 font-bold text-slate-800"><?= htmlspecialchars($userStatus === 'Active' ? 'Verified Access' : $userStatus); ?></p>
                        </div>
                    </div>
                </div>

                <div id="personal-settings" class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
                    <div class="flex items-center justify-between gap-3 mb-5">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Preferences</p>
                            <h2 class="text-xl font-black text-slate-900">Personal Settings</h2>
                        </div>
                    </div>

                    <form id="profileSettingsForm" class="space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="profileDisplayName" class="block text-xs font-bold uppercase tracking-[0.15em] text-slate-500 mb-2">Display Name</label>
                                <input id="profileDisplayName" type="text" value="<?= htmlspecialchars($fullName); ?>" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-c3/30 focus:border-c3 outline-none bg-white text-sm">
                            </div>

                            <div>
                                <label for="profileContact" class="block text-xs font-bold uppercase tracking-[0.15em] text-slate-500 mb-2">Contact Phone</label>
                                <div class="flex items-center rounded-xl border border-slate-200 focus-within:ring-2 focus-within:ring-c3/30 focus-within:border-c3 overflow-hidden bg-white shadow-sm">
                                    <span class="inline-flex items-center px-3.5 py-2.5 bg-slate-100 border-r border-slate-200 text-slate-700 font-bold text-sm select-none tracking-wide">+63</span>
                                    <input id="profileContact" type="tel" value="<?= htmlspecialchars($displayContact); ?>" placeholder="917 000 0000" class="w-full px-3 py-2.5 outline-none bg-transparent text-sm text-slate-800" maxlength="15">
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1">Country code preselected (+63). Enter mobile number without leading 0.</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="flex items-center justify-between p-3.5 bg-slate-50 rounded-xl border border-slate-200 cursor-pointer">
                                <div>
                                    <span class="font-bold text-slate-800">Enable Dark Mode</span>
                                    <p class="text-[11px] text-slate-500">Use the dark theme across the portal</p>
                                </div>
                                <input id="profileDarkModeToggle" type="checkbox" class="w-4 h-4 text-c3 rounded border-slate-300 focus:ring-c3">
                            </label>

                            <label class="flex items-center justify-between p-3.5 bg-slate-50 rounded-xl border border-slate-200 cursor-pointer">
                                <div>
                                    <span class="font-bold text-slate-800">Mask Citizen Confidential Data</span>
                                    <p class="text-[11px] text-slate-500">Hide sensitive names and contact data across tables</p>
                                </div>
                                <input id="profileMaskToggle" type="checkbox" class="w-4 h-4 text-c3 rounded border-slate-300 focus:ring-c3">
                            </label>
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="reset" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition text-sm font-semibold">Reset</button>
                            <button type="submit" class="px-4 py-2 bg-c3 text-white rounded-xl hover:bg-c3d transition text-sm font-semibold">Save Changes</button>
                        </div>
                    </form>
                </div>
                </section>
            </div>
        </div>
    </main>
</div>

<script>
    (function() {
        const savedTheme = localStorage.getItem('portal_theme') || 'light';
        const darkToggle = document.getElementById('profileDarkModeToggle');
        const maskToggle = document.getElementById('profileMaskToggle');

        if (darkToggle) {
            darkToggle.checked = savedTheme === 'dark' || (savedTheme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        }

        if (maskToggle) {
            maskToggle.checked = localStorage.getItem('data_mask_enabled') === '1';
        }

        darkToggle?.addEventListener('change', function() {
            const mode = this.checked ? 'dark' : 'light';
            localStorage.setItem('portal_theme', mode);
            document.documentElement.classList.toggle('dark', this.checked);
        });

        maskToggle?.addEventListener('change', function() {
            localStorage.setItem('data_mask_enabled', this.checked ? '1' : '0');
            if (typeof toggleDataMask === 'function') {
                toggleDataMask();
            }
        });

        const contactInput = document.getElementById('profileContact');
        contactInput?.addEventListener('input', function() {
            let val = this.value;
            if (val.startsWith('+63')) val = val.substring(3);
            else if (val.startsWith('63') && val.length > 10) val = val.substring(2);
            else if (val.startsWith('0')) val = val.substring(1);
            this.value = val.replace(/[^\d\s-]/g, '');
        });

        const form = document.getElementById('profileSettingsForm');
        form?.addEventListener('submit', async function(event) {
            event.preventDefault();

            const displayName = document.getElementById('profileDisplayName').value.trim();
            let rawContact = document.getElementById('profileContact').value.trim();
            rawContact = rawContact.replace(/^(\+?63\s*|0+)/, '').replace(/\D+/g, '');
            const contactPayload = rawContact ? ('+63' + rawContact) : '';
            const userId = <?= json_encode((string)($_SESSION['user_id'] ?? '')); ?>;

            if (!userId) {
                if (typeof toast !== 'undefined') {
                    toast.error('User session is missing. Please log in again.', { title: 'Profile' });
                }
                return;
            }

            try {
                const response = await fetch(`${window.SITE_URL}/api/employees.php?id=${encodeURIComponent(userId)}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({
                        full_name: displayName || 'User',
                        contact_number: contactPayload
                    })
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Unable to save profile changes.');
                }

                localStorage.setItem('user_display_name', displayName || 'User');
                if (rawContact) {
                    localStorage.setItem('user_contact', rawContact);
                }

                const nameEls = document.querySelectorAll('#headerUserFullName, #dropdownUserFullName');
                nameEls.forEach((el) => el.textContent = displayName || 'User');

                if (typeof toast !== 'undefined') {
                    toast.success('Profile settings saved successfully.', { title: 'Profile' });
                }

                setTimeout(() => {
                    window.location.reload();
                }, 400);
            } catch (error) {
                console.error('Profile save failed:', error);
                if (typeof toast !== 'undefined') {
                    toast.error(error.message || 'Unable to save profile changes.', { title: 'Profile' });
                }
            }
        });
    })();
</script>
