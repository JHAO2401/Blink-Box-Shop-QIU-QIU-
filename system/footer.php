<?php
$pageJs = $pageJs ?? [];
?>

<!-- footer  -->
<footer class="footer">
  <div class="container">
    <div class="footer-inner">

      <div class="footer-logo">
        <img src="/image/logo.png" alt="Qiu Shop">
        QIU QIU Shop
      </div>

      <div class="footer-copy">
        <?= date('Y') ?> ❤️🩷💛💚💙🩵💜🩶 QIU QIU Shop ❤️🩷💛💚💙🩵💜🩶 <?= date('Y') ?>.
      </div>

      <div class="footer-links-row">
        <a href="#">LUCK +999🍀</a>
        <a href="#">AURA +999🗿</a>
      </div>

    </div>
  </div>
</footer>

<script src="/js/index.js"></script>
<!-- load page-specific JS files -->
<?php 
foreach ($pageJs as $f): 
if ($f !== 'js/index.js'): 
?>
<script src="/<?= e($f) ?>"></script>

<?php 
endif; 
endforeach; 
?>
<div id="toast-wrap"></div>
</body>
</html>