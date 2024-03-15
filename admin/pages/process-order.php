<?php

  if (! defined('ROOT_URL')) {
    die;
  }

  if (!isset($_GET['id'])){
    echo "<script>location.href='".ROOT_URL."admin?page=orders-list&msg=not_found';</script>";
    exit;
  }

  $orderId = esc($_GET['id']);

  $orderMgr = new OrderManager();
  $orderItems = $orderMgr->getOrderItems($orderId);
  $orderTotal = $orderMgr->getOrderTotal($orderId)[0];
  $address = $orderMgr->getUserAddress($orderTotal['user_id']);
  $email = $orderMgr->getEmailAndName($orderId)['email'];
  $first_name = $orderMgr->getEmailAndName($orderId)['first_name'];
  $status = $orderItems[0]['order_status'];


  if (count($orderItems) == 0) {
    echo "<script>location.href='".ROOT_URL."admin?page=orders-list&msg=order_empty';</script>";
    exit;
  }

  if ($status == 'pending' AND isset($_POST['ship_order'])){

    
    $status = 'shipped';
    $orderMgr->updateStatus($orderId, $status);
    
    $br = "\r\n";
    $to = $email;
    $subject = "SPEDITO ORDINE N. " . $orderId;
    $txt = "<h2>L'ordine è stato spedito!</h2>" ;

    $headers = "From: ".SITE_NAME . $br ;
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    mail($to,$subject,$txt,$headers);

    $alertMsg = 'order_shipped';
  }
  $count = 0;
?>

<a href="<?php echo ROOT_URL . 'admin?page=orders-list'; ?>" class="back underline">&laquo; Lista Ordini</a>

<h1 cass="mb-4">Ordine #<?php echo esc_html($orderId); ?></h1>

  <table class="table table-bordered">
    <tr>
      <th class="big-screen">#</th>
      <th>Prodotto</th>
      <th>Quantità</th>
      <th class="big-screen">Prezzo Unitario</th>
      <th>Prezzo</th>
    </tr>
  <?php foreach ($orderItems as $item) : $count++; ?>
  
    <tr>
      <td class="big-screen"><?php echo $count; ?></td>
      <td class="big-screen"><?php echo esc_html($item['product_name']); ?></td>
      <td><?php echo esc_html($item['quantity']); ?></td>
      <td><?php echo esc_html($item['single_price']); ?> €</td>
      <td><?php echo esc_html($item['total_price']); ?> €</td>
    </tr>
  <?php endforeach; $count=0; ?>
  <tr> 
    <th colspan="100%">
      <h4 class="inline">Totale <?php echo $orderTotal['total']; ?> €</h4>
      <?php if ($status == 'pending') : ?>
      <form method="post" class="inline right">
        <input onclick="return confirm('Confermi spedizione ordine n. #<?php echo esc_html($orderId); ?> ?');" name="ship_order" type="submit" class="btn btn-primary m-0" value="Spedisci Ordine">
      </form>
      <?php else : ?>
        <h4 class="inline right"><span class="badge badge-secondary badge-pill">Ordine spedito</span></h4>
      <?php endif; ?>
    </th>
  </tr>
</table>

<hr class="m-3">

<?php if ($address) : ?>
  <h4>Dettagli Cliente</h4>

  <ul class="list-group">
    <li class="list-group-item">
      <strong>Nominativo: </strong><br>
      <?php echo esc_html($first_name); ?>
    </li>
    <li class="list-group-item">
      <strong>Email: </strong><br>
      <?php echo esc_html($email); ?>
    </li>
    <li class="list-group-item">
      <strong>Indirizzo: </strong><br>
      <?php echo esc_html($address['street']); ?> - <?php echo esc_html($address['city']); ?> (<?php echo esc_html($address['cap']); ?>)
    </li>
  </ul>
<?php endif; ?>

