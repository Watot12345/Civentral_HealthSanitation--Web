import sys

with open(r'd:\xampp\htdocs\Civentral_HealthSanitation--Web\includes\sidebar.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Find and replace the maintenance + service_requests links with merged link
search = "modules/services/maintenance.php"
if search not in content:
    print("NOT FOUND"); sys.exit(1)

# Locate start of maintenance link
start = content.find("          <a href=\"<?= site_url('modules/services/maintenance.php')")
# Locate end of service_requests link (end of </a>)
end_marker = "</a>"
# find the service_requests block after maintenance block
sr_start = content.find("          <a href=\"<?= site_url('modules/services/service_requests.php')", start)
end = content.find("</a>", sr_start) + len(end_marker)

old = content[start:end]
print("OLD BLOCK FOUND, length:", len(old))

new = """          <a href="<?= site_url('modules/services/services_management.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'services_management.php') !== false || strpos($currentPath, 'maintenance.php') !== false || strpos($currentPath, 'service_requests.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-list-check text-[10px] opacity-50"></i> 
            <span>Service Requests &amp; Maintenance</span>
          </a>"""

content = content[:start] + new + content[end:]

with open(r'd:\xampp\htdocs\Civentral_HealthSanitation--Web\includes\sidebar.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("DONE - sidebar updated")
