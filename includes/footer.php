<?php if (!isset($assetBasePath)) { $assetBasePath = ''; } ?>
  </div>

  
  <?php include_once __DIR__ . '/toast.php'; ?>
  
  <script src="<?= site_url('assets/js/dashboard.js'); ?>"></script>
  <script src="<?= site_url('assets/js/modal-system.js'); ?>"></script>
  <script>
    function confirmLogout(logoutUrl) {
      const url = logoutUrl || '<?= site_url('logout.php'); ?>';
      if (typeof ModalSystem !== 'undefined' && ModalSystem.confirm) {
        ModalSystem.confirm(
          'Are you sure you want to log out of Civentral Caloocan Portal?',
          function() {
            if (typeof toast !== 'undefined') {
              toast.info('Logging out...', { title: 'Session Ending' });
            }
            setTimeout(function() {
              window.location.href = url;
            }, 300);
          },
          {
            title: 'Confirm Logout',
            confirmText: 'Yes, Logout',
            cancelText: 'Cancel',
            type: 'danger'
          }
        );
      } else if (confirm('Are you sure you want to log out?')) {
        window.location.href = url;
      }
    }
  </script>
</body>
</html>