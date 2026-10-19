            </main>
            <footer class="main-footer">
                <div class="footer-content">
                    <div class="footer-info">
                        <div class="footer-library">
                            <h4><?php echo htmlspecialchars(getSetting('library_name', 'Nexus Library')); ?></h4>
                            <p><?php echo htmlspecialchars(getSetting('library_address', 'Library Address')); ?></p>
                        </div>
                        <div class="footer-contact">
                            <h4>Contact Us</h4>
                            <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars(getSetting('library_phone', 'N/A')); ?></p>
                            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars(getSetting('library_email', 'N/A')); ?></p>
                        </div>
                    </div>
                    <div class="footer-bottom">
                        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(getSetting('library_name', 'Nexus Library')); ?>. All rights reserved.</p>
                        <div class="footer-links">
                            <a href="#">Privacy Policy</a>
                            <a href="#">Terms of Service</a>
                            <a href="#">Help</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <div class="modal-overlay" id="modalOverlay"></div>
    
    <div class="toast-container" id="toastContainer"></div>

    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    <?php if (isset($extra_js)): ?>
        <?php echo $extra_js; ?>
    <?php endif; ?>
</body>
</html>
