    </div><!-- /admin-body -->
  </main><!-- /admin-main -->

</div><!-- /admin-layout -->

<!-- SIDEBAR TOGGLE SCRIPT -->
<script>
(function() {
  const toggle   = document.getElementById('sidebarToggle');
  const sidebar  = document.getElementById('adminSidebar');
  const overlay  = document.getElementById('sidebarOverlay');

  function openSidebar() {
    sidebar.classList.add('open');
    overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
  }
  function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('show');
    document.body.style.overflow = '';
  }

  if (toggle) toggle.addEventListener('click', openSidebar);
  if (overlay) overlay.addEventListener('click', closeSidebar);
})();
</script>
<?= $extraScripts ?? '' ?>
</body>
</html>
