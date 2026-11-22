<?php
if (!isset($base_url)) {
    $base_url = '';
}
?>
    <div class="site-footer" id="about">
        <div class="footer-inner">
            <div class="footer-top-columns">
                <div class="footer-col-left" id="help">
                    <div class="footer-heading">Tetap Terhubung</div>
                    <div class="footer-contact-p">Nomor Kontak: +6281229266070</div>
                    <div class="footer-contact-p">Instagram: @PP</div>
                    <div class="footer-contact-p">Email: PangkalanPangan@gmail.com</div>
                </div>
                <div class="footer-col-center">
                    <img src="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>assets/images/footer_logo_sprout.svg" alt="Logo Pangkalan Pangan Footer" class="footer-center-logo" />
                </div>
                <div class="footer-col-right">
                    <div class="footer-heading">Ambil peran</div>
                    <ul class="footer-link-list">
                        <li><a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>register.php?role=donor">Donatur pangan</a></li>
                        <li><a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>register.php?role=donor">Mitra penyaluran</a></li>
                    </ul>
                </div>
            </div>

            <hr class="footer-divider-line" />

            <div class="footer-bottom-copy">
                <img src="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>assets/images/footer_copyright.svg" alt="Hak Cipta Pangkalan Pangan" class="footer-copy-img" />
            </div>
        </div>
    </div>

</div>

<?php if (isset($extra_js) && is_array($extra_js)): ?>
<?php foreach ($extra_js as $js_file): ?>
<script type="text/javascript" src="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>assets/js/<?php echo htmlspecialchars($js_file, ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php endforeach; ?>
<?php endif; ?>
</body>
</html>
