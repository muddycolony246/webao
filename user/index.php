<?php
require_once '../inc/init.php'; 

global $loggedInUser;

if (!$loggedInUser) {
  $returnPage = isset($_GET['page']) ? esc($_GET['page']) : '';
  echo "<script>location.href='".ROOT_URL."auth?page=login'</script>";
  exit;
}


$page = 'profile';
if(isset($_GET['page'])) {
  $page = $_GET['page'];
}
?>
<?php include ROOT_PATH . 'public/template-parts/header.php'; ?>
<div class="container mt-5">
  <div class="row">
    <div class="col-md-9">
      <div class="main">
      <?php include "pages/$page.php"; ?>
      <?php include ROOT_PATH . 'inc/alert-message.php'; ?>
      </div>
    </div>
  </div>

</div>
<?php include ROOT_PATH . 'public/template-parts/footer.php'; ?>