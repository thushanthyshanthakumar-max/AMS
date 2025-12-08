    </div> <!-- Close container -->
    
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
    </footer>
    
    <script src="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/js/main.js"></script>
    <?php if (isset($additionalJS)): ?>
        <script src="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/js/<?php echo $additionalJS; ?>"></script>
    <?php endif; ?>
</body>
</html>
