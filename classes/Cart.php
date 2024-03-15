<?php

  class Cart {

    public $id;
    public $user_id;
    public $client_id;

    public function __construct($id, $user_id, $client_id){
      $this->id = $id > 0 ? $id : 0;
      $this->user_id = $user_id;
      $this->client_id = $client_id;
    }
  }

  class CartItem {

    public $id;
    public $cart_id;
    public $product_id;
    public $quantity;

    public function __construct($id, $cart_id, $product_id, $quantity){
      $this->id = $id > 0 ? $id : 0;
      $this->cart_id = $cart_id;
      $this->product_id = $product_id;
      $this->quantity = $quantity;
    }
  }

  class Order {

    public $id;
    public $user_id;
    public $status;

    public function __construct($id, $user_id, $status){
      $this->id = $id > 0 ? $id : 0;
      $this->user_id = $user_id;
      $this->status = $status;
    }
  }

  class CartItemManager extends DBManager {

    public function __construct(){
      parent::__construct();
      $this->columns = array( 'id', 'cart_id', 'product_id', 'quantity' );
      $this->tableName = 'cart_item';
    }


  }

  class OrderManager extends DBManager {
    public function __construct(){
      parent::__construct();
      $this->columns = array( 'id', 'user_id', 'status' );
      $this->tableName = 'Orders';
    }

    public function updateStatus($orderId, $status){
      $query = "UPDATE orders SET status = '$status', updated_at = CURRENT_TIMESTAMP() WHERE id = $orderId"; 
      $result = $this->db->query($query);
      return $result;
    }

    public function getOrdersOfUser($userId, $status){
      $query = "CALL user_orders ($userId, '$status')"; 
      //var_dump($query); die;
      $result = $this->db->query($query);
      //var_dump($result); die;
      return $result;
    }

    public function getEmailAndName($orderId){
      $result = $this->db->query("CALL get_order_email ($orderId)");
      //var_dump($result); die;
      return $result[0];
    }

    public function createOrderFromCart($cartId, $userId){
      $orderId = $this->create(new Order(0, $userId, 'pending'));
      $this->db->query("CALL cart_to_order ($cartId, $orderId)");
      return $orderId;
    }

    public function getOrderTotal($orderId) {
      $result = $this->db->query("CALL order_total ($orderId)");
      //var_dump($result); die;
      return $result;
    }

    public function getOrderItems($orderId){
      $result = $this->db->query("CALL order_items ($orderId)");
      //var_dump($result); die;
      return $result;
    }

    public function getUserAddress($userId){
      $result = $this->db->query("SELECT street, city, cap FROM address WHERE user_id = $userId");
      //var_dump($result); die;
      return $result ? $result[0] : null;
    }
    
    public function getAllOrders($status){
      $result = $this->db->query("CALL all_orders ('$status')");
      //var_dump($result); die;
      return $result;
    }

  }

  

  class CartManager extends DBManager {

    private $userId;
    private $clientId;
    private $cartItemMgr;

    public function __construct(){
      parent::__construct();

      //global $loggedInUser;

      $this->userId = isset($_SESSION['user']) ? unserialize($_SESSION['user'])->id : 0;
      // $this->clientId = isset($_COOKIE['client_id']) ? $_COOKIE['client_id'] : random_string();
      $this->clientId = isset($_SESSION['client_id']) ? $_SESSION['client_id'] : random_string();

      $_SESSION['client_id'] = $this->clientId;
      //var_dump($this->clientId, $_SESSION);

      $this->columns = array( 'id', 'user_id', 'client_id' );
      $this->tableName = 'cart';

      $this->cartItemMgr = new CartItemManager();
    }

    private function quantityInCart($productId, $cartId) {
      $quantity = 0;
      $results = $this->db->query("SELECT quantity FROM cart_item WHERE cart_id = '$cartId' AND product_id = '$productId'");
      if(count($results) > 0) {
        $quantity = (int)$results[0]['quantity'];
      }
      return $quantity;
    }

    private function incrementByOne($productId, $cartId, $quantityInCart){
      $quantityInCart++;
      $this->db->query("UPDATE cart_item SET quantity = $quantityInCart WHERE cart_id = '$cartId' AND product_id = '$productId'");
    }

    private function decrementOne($productId, $cartId, $quantityInCart){
      $quantityInCart--;
      $this->db->query("UPDATE cart_item SET quantity = $quantityInCart WHERE cart_id = '$cartId' AND product_id = '$productId'");
    }

    private function createItem($productId, $cartId){
      $item_id = $this->cartItemMgr->create(new CartItem(0, $cartId, $productId, 1));
      //var_dump($item_id); die;
      return $item_id;
    }

    // private function clearCart($cartId){
    //   if($this->userId) {
    //     $this->db->query('DELETE cart, cart_item FROM cart INNER JOIN cart_item ON cart.id = cart_item.cart_id WHERE cart.user_id = ' . $this->userId . ' AND cart.id NOT IN ('.$cartId.')');
    //   } else if ($this->clientIp) {
    //     $this->db->query("DELETE cart, cart_item FROM cart INNER JOIN cart_item ON cart.id = cart_item.cart_id WHERE cart.client_id = '" . $this->clientIp . "' AND cart.id NOT IN ('.$cartId.')");
    //   }
    // }

    public function isEmptyCart($cartId){
      $results = $this->db->query("SELECT 1 FROM cart_item WHERE cart_id = '$cartId'"); 
      return count($results) == 0;
    }

    private function createCart(){

      $client_id = $this->userId > 0 ? '' : $this->clientId;
      $cart_id = $this->create(new Cart(0, $this->userId, $client_id)); 
      return $cart_id;
    }

    private function removeItem($productId, $cartId){
      return $this->db->query("DELETE FROM cart_item WHERE cart_id = '$cartId' AND product_id = '$productId'");
    }

    private function clearUserCart() {
      if($this->userId) {
        $this->db->query('DELETE cart, cart_item FROM cart INNER JOIN cart_item ON cart.id = cart_item.cart_id WHERE cart.user_id = ' . $this->userId );
      }
    }

    public function mergeCarts(){

      $oldUserCart = $this->db->query("SELECT id FROM cart where user_id = $this->userId");
      $oldClientCart = $this->db->query("SELECT id FROM cart where client_id = '$this->clientId'");
      //var_dump($oldUserCart, $oldClientCart, $this->userId, $this->clientId); die;
      if (count($oldClientCart) > 0 AND count($oldUserCart) == 0){
        $result = $this->db->query("UPDATE cart SET user_id = $this->userId, client_id = '' WHERE client_id = '$this->clientId'");
      }

      else if (count($oldClientCart) > 0 AND count($oldUserCart) > 0 ) {

        $userCartId = $oldUserCart[0]['id'];
        $userCartItems = $this->getCartItems($userCartId);

        $clientCartId = $oldClientCart[0]['id'];
        $clientCartItems = $this->getCartItems($clientCartId);
        

        foreach($clientCartItems as $clientItem){
          
          $isAlreadyInCart = false;
          $clientProductId = $clientItem['product_id'];

          foreach($userCartItems as $userItem){
            if ($userItem['product_id'] == $clientProductId){
              $isAlreadyInCart = true;
              $newQuantity = $userItem['quantity'] + $clientItem['quantity'];
              $this->db->query("UPDATE cart_item SET quantity = $newQuantity  WHERE cart_id = $userCartId AND product_id = $clientProductId");
              $this->db->query("DELETE FROM cart_item WHERE cart_id = $clientCartId AND product_id = $clientProductId");
              break;
            }
          }

          if (!$isAlreadyInCart) {
            $this->db->query("UPDATE cart_item SET cart_id = $userCartId  WHERE cart_id = $clientCartId AND product_id = $clientProductId");
          }
        }

        $result = $this->db->query("DELETE FROM cart WHERE id = $clientCartId");
      }

      unset($_SESSION['client_id']);
      return $result;
    }


    public function addToCart($productId, $cartId) {
      // $this->clearCart($cartId);
      
      // if(!$cartId) {
      //   $cartId = $this->createCart();
      // }

      $quantityInCart = $this->quantityInCart($productId, $cartId);

      if ($quantityInCart > 0){
        $this->incrementByOne($productId, $cartId, $quantityInCart);
      } else {
        $this->createItem($productId, $cartId);
      }
    }

    public function removeFromCart($productId, $cartId) {

      $quantityInCart = $this->quantityInCart($productId, $cartId);

      if ($quantityInCart > 1){
        $this->decrementOne($productId, $cartId, $quantityInCart);
      } else {
        $this->removeItem($productId, $cartId);
      }
    }

    public function getCurrentCartId(){
      $cartId = 0;

      if (!$this->userId) {
        //var_dump($this->clientId, $_SESSION['client_id']); die;
        $result = $this->db->query("SELECT id FROM cart WHERE client_id = '$this->clientId'"); 
        if (count($result) == 0) {
          $cartId = $this->createCart();
        } else {
          $cartId = $result[0]['id'];
        }
      } else {
        $result = $this->db->query("SELECT id FROM cart WHERE user_id = $this->userId");
        if (count($result) == 0) {
          $cartId = $this->createCart();
        } else {
          $cartId = $result[0]['id'];
        }
      }
        
      return $cartId;
    }

    public function getCartTotal($cartId) {
      return $this->db->query("CALL cart_total ($cartId)");
    }

    public function getCartItems($cartId){
      return $this->db->query("CALL cart_items ($cartId)");
    }


  }