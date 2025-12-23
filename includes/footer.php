    </div> <!-- Close container -->
</div> <!-- Close main-content -->
    
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
    </footer>
    
    
    <script src="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/js/main.js"></script>
    <?php if (isset($additionalJS)): ?>
        <script src="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/js/<?php echo $additionalJS; ?>"></script>
    <?php endif; ?>

    <!-- Mobile Drawer JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const mobileDrawer = document.getElementById('mobileDrawer');
            const mobileDrawerOverlay = document.getElementById('mobileDrawerOverlay');
            const mobileDrawerClose = document.getElementById('mobileDrawerClose');

            if (mobileMenuToggle && mobileDrawer && mobileDrawerOverlay) {
                // Open drawer
                mobileMenuToggle.addEventListener('click', function() {
                    mobileDrawer.classList.add('active');
                    mobileDrawerOverlay.classList.add('active');
                    document.body.style.overflow = 'hidden'; // Prevent background scrolling
                });

                // Close drawer function
                function closeDrawer() {
                    mobileDrawer.classList.remove('active');
                    mobileDrawerOverlay.classList.remove('active');
                    document.body.style.overflow = ''; // Restore scrolling
                }

                // Close drawer on close button click
                if (mobileDrawerClose) {
                    mobileDrawerClose.addEventListener('click', closeDrawer);
                }

                // Close drawer on overlay click
                mobileDrawerOverlay.addEventListener('click', closeDrawer);

                // Close drawer when clicking a link
                const drawerLinks = mobileDrawer.querySelectorAll('a');
                drawerLinks.forEach(link => {
                    link.addEventListener('click', closeDrawer);
                });

                // Close drawer on escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && mobileDrawer.classList.contains('active')) {
                        closeDrawer();
                    }
                });
            }
        });
    </script>

</body>
</html>
