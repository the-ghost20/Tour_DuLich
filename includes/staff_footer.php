    </div><!-- /staff-body -->
  </main><!-- /staff-main -->

</div><!-- /staff-layout -->

<script>
(function() {
  const toggle  = document.getElementById('staffSidebarToggle');
  const sidebar = document.getElementById('staffSidebar');
  const overlay = document.getElementById('staffSidebarOverlay');
  if (!toggle || !sidebar || !overlay) return;
  function openNav() {
    sidebar.classList.add('open');
    overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
  }
  function closeNav() {
    sidebar.classList.remove('open');
    overlay.classList.remove('show');
    document.body.style.overflow = '';
  }
  toggle.addEventListener('click', openNav);
  overlay.addEventListener('click', closeNav);
})();
</script>
<?= $extraScripts ?? '' ?>
</body>
</html>
