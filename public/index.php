<?php
require_once '../inc/init.php'; 
$page = isset($_GET["page"]) ? $_GET["page"] : 'homepage';
?>
<?php include ROOT_PATH . 'public/template-parts/header.php'; ?>
<div class="container mt-5">
  <div class="row">
    <div class="col-lg-9">
      <div class="main">
      <?php include "pages/$page.php"; ?>
      </div>
    </div>
    
  </div>

</div>
<?php include ROOT_PATH . 'public/template-parts/footer.php'; ?>