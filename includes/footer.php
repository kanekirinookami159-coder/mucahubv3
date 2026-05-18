<?php
  $footerImagePath = str_repeat('../', max(0, count(explode('/', trim(dirname($_SERVER['SCRIPT_NAME']), '/')))-1)) . 'assets/images/';
?>
<footer>
  <div class="footer">
    <p class="footer-school-name">Medicion Unida Christian Academy - MUCA HUB</p>
    <div class="footer-row">
      <a class="footer-link" href="https://www.google.com/maps/place/Medicion+Unida+Christian+Academy/@14.4428574,120.9171496,17z/data=!3m1!4b1!4m6!3m5!1s0x3397d297eef9c6ab:0x9c015522f1c64dbe!8m2!3d14.4428574!4d120.9197245!16s%2Fg%2F11hbvxy1ny?entry=ttu&g_ep=EgoyMDI2MDQyNy4wIKXMDSoASAFQAw%3D%3D" target="_blank" rel="noopener noreferrer">
        <span class="footer-icon"><img src="<?php echo $footerImagePath; ?>loc.png" alt="Location"></span>
        Visit us
      </a>
      <a class="footer-link" href="tel:09335558155">
        <span class="footer-icon"><img src="<?php echo $footerImagePath; ?>contact.png" alt="Phone"></span>
        046-426-1668
      </a>
      <a class="footer-link" href="mailto:mucahub2026@gmail.com" onclick="window.location.href='mailto:mucahub2026@gmail.com'; return false;">
        <span class="footer-icon"><img src="<?php echo $footerImagePath; ?>mail.png" alt="Email"></span>
        Email us
      </a>
      <a class="footer-link" href="https://www.facebook.com/share/1G2TKeDmKC/" target="_blank" rel="noopener noreferrer">
        <span class="footer-icon"><img src="<?php echo $footerImagePath; ?>fb.png" alt="Facebook"></span>
        Folow us
      </a>
    </div>
  </div>
</footer>