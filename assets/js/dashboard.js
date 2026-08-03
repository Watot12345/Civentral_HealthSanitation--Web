// ============================================================
// 1. CALENDAR/CLOCK FUNCTION
// ============================================================
function updateClock() {
    const now = new Date();
    const options = { 
        weekday: 'short', 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit' 
    };
    const clockElement = document.getElementById('headerClock');
    if (clockElement) {
        clockElement.innerText = now.toLocaleDateString('en-US', options);
    }
}
setInterval(updateClock, 1000);
updateClock();

// ============================================================
// 2. GLOBAL VARIABLES & SAFE DECLARATIONS
// ============================================================
window.isCollapsed = window.isCollapsed || false;
var isCollapsed = window.isCollapsed;

window.ALL_DROPDOWN_IDS = window.ALL_DROPDOWN_IDS || [
    'systemOverviewDropdown',
    'analyticsDropdown',
    'reportsDropdown',
    'complianceDropdown',
    'healthCenterDropdown',
    'sanitationDropdown',
    'immunizationDropdown',
    'wastewaterDropdown',
    'surveillanceDropdown',
    'userMgmtDropdown',
    'sysLogsDropdown',
    'settingsDropdown'
];
var ALL_DROPDOWN_IDS = window.ALL_DROPDOWN_IDS;

window.ALL_CHEVRON_IDS = window.ALL_CHEVRON_IDS || [
    'systemOverviewChevron',
    'analyticsChevron',
    'reportsChevron',
    'complianceChevron',
    'healthCenterChevron',
    'sanitationChevron',
    'immunizationChevron',
    'wastewaterChevron',
    'surveillanceChevron',
    'userMgmtChevron',
    'sysLogsChevron',
    'settingsChevron'
];
var ALL_CHEVRON_IDS = window.ALL_CHEVRON_IDS;

// ============================================================
// 4. DROPDOWN TOGGLE
// ============================================================
window.toggleDropdown = function toggleDropdown(id, chevronId) {
    if (isCollapsed) return;

    const dropdown = document.getElementById(id);
    const chevron = document.getElementById(chevronId);

    // Close all other dropdowns
    ALL_DROPDOWN_IDS.forEach((d, i) => {
        if (d !== id) {
            const otherDropdown = document.getElementById(d);
            const otherChevron = document.getElementById(ALL_CHEVRON_IDS[i]);
            if (otherDropdown) {
                otherDropdown.classList.add('hidden');
            }
            if (otherChevron) {
                otherChevron.classList.remove('rotate-180');
                otherChevron.style.transform = 'rotate(0deg)';
            }
        }
    });

    // Toggle the clicked dropdown
    if (dropdown) {
        if (dropdown.classList.contains('hidden')) {
            dropdown.classList.remove('hidden');
            if (chevron) {
                chevron.classList.add('rotate-180');
                chevron.style.transform = 'rotate(180deg)';
            }
        } else {
            dropdown.classList.add('hidden');
            if (chevron) {
                chevron.classList.remove('rotate-180');
                chevron.style.transform = 'rotate(0deg)';
            }
        }
    }
}

// ============================================================
// 5. SIDEBAR COLLAPSE
// ============================================================
window.toggleSidebar = function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const arrow = document.getElementById('toggleArrow');

    const sideLabels = document.querySelectorAll('.sidebar-text');
    const dropdownButtons = document.querySelectorAll('.dropdown-btn');
    const dropdownRights = document.querySelectorAll('.dropdown-right');

    isCollapsed = !isCollapsed;

    if (isCollapsed) {
        // CLOSE ALL DROPDOWNS
        ALL_DROPDOWN_IDS.forEach(id => {
            const menu = document.getElementById(id);
            if (menu) {
                menu.classList.add('hidden');
            }
        });

        // Reset all chevrons
        document.querySelectorAll('.dropdown-chevron').forEach(chv => {
            chv.classList.remove('rotate-180');
            chv.style.transform = 'rotate(0deg)';
        });

        // COLLAPSE SIDEBAR
        sidebar.classList.remove('w-72');
        sidebar.classList.add('w-20');

        arrow.className = "fa-solid fa-chevron-right text-xs";

        // HIDE TEXT
        sideLabels.forEach(label => {
            label.classList.add('hidden');
        });

        // HIDE DROPDOWN ARROWS
        dropdownRights.forEach(right => {
            right.classList.add('hidden');
        });

        // CENTER ICONS
        dropdownButtons.forEach(btn => {
            btn.classList.remove('justify-between');
            btn.classList.add('justify-center');
        });

    } else {
        // EXPAND SIDEBAR
        sidebar.classList.remove('w-20');
        sidebar.classList.add('w-72');

        arrow.className = "fa-solid fa-chevron-left text-xs";

        // SHOW TEXT
        sideLabels.forEach(label => {
            label.classList.remove('hidden');
        });

        // SHOW DROPDOWN ARROWS
        dropdownRights.forEach(right => {
            right.classList.remove('hidden');
        });

        // RESTORE BUTTON STYLE
        dropdownButtons.forEach(btn => {
            btn.classList.remove('justify-center');
            btn.classList.add('justify-between');
        });

        // RESET CHEVRONS
        document.querySelectorAll('.dropdown-chevron').forEach(chv => {
            chv.classList.remove('rotate-180');
            chv.style.transform = 'rotate(0deg)';
        });
    }
}

// ============================================================
// 6. KEEP DROPDOWNS OPEN ON PAGE LOAD
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    const currentPath = window.location.pathname;

    ALL_DROPDOWN_IDS.forEach(id => {
        const dropdown = document.getElementById(id);
        if (dropdown) {
            const links = dropdown.querySelectorAll('a');
            let shouldOpen = false;
            links.forEach(link => {
                const href = link.getAttribute('href');
                if (href && currentPath.includes(href)) {
                    shouldOpen = true;
                }
            });

            if (shouldOpen) {
                // Open the dropdown
                dropdown.classList.remove('hidden');

                // The dropdown-btn is a direct previous sibling of the
                // dropdown panel (both live inside the same .space-y-1 wrapper),
                // so we grab it directly instead of using .closest() which
                // was matching the wrapper div itself, not the button.
                const parentBtn = dropdown.previousElementSibling;
                if (parentBtn && parentBtn.classList.contains('dropdown-btn')) {
                    const chevron = parentBtn.querySelector('.dropdown-chevron');
                    if (chevron) {
                        chevron.classList.add('rotate-180');
                        chevron.style.transform = 'rotate(180deg)';
                    }
                }
            }
        }
    });
});

