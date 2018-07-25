<?php
session_start();
require('./../util/functions.php');
$db_handle = new Utility();
$db_handle2 = new Products();

setlocale(LC_MONETARY,"en_US"); // US national format (see : http://php.net/money_format)
############# add products to session #########################
if(isset($_POST["product_code"]))
{
	foreach($_POST as $key => $value){
		$new_product[$key] = filter_var($value, FILTER_SANITIZE_STRING); //create a new product array 
	}
	
	//we need to get product name and price from database.
	$pdetails = $db_handle2->getOneRecord("SELECT * FROM products WHERE code='" . $_POST["product_code"] . "'");
	$pimg = $db_handle2->getOneRecord("SELECT * FROM product_img WHERE product_id='" . $pdetails['_id'] . "'");
	$statement = $db_handle2->cartfetch("SELECT * FROM products WHERE code='" . $_POST["product_code"] . "'");
	

	while($statement->fetch()){ 
		$new_product["pid"] = $pdetails['_id']; 	
		$new_product["title"] = $pdetails['title']; //fetch product name from database
		$new_product["price"] = $pdetails['price'];  //fetch product price from database
		$new_product["currency"] = $pdetails['currency'];
		$new_product["product_qty"] = $_POST['quantity'];
		$new_product["description"] = $pdetails['description'];
		$new_product["size"] = $pdetails['size'];
		$new_product['image'] = $pimg['image_name'];
		
		if(isset($_SESSION["products"])){  //if session var already exist
			if(isset($_SESSION["products"][$new_product['product_code']])){ //check item exist in products array
				unset($_SESSION["products"][$new_product['product_code']]); //unset old item
			}			
		}
		
		$_SESSION["products"][$new_product['product_code']] = $new_product;	//update products with new item array	
	}
	
 	$total_items = count($_SESSION["products"]); //count total items
	die(json_encode(array('items'=>$total_items))); //output json 

}

################## list products in cart ###################
if(isset($_POST["load_cart"]) && $_POST["load_cart"]==1)
{

	if(isset($_SESSION["products"]) && count($_SESSION["products"])>0){ //if we have session variable
		$cart_box = '<ul class="cart-products-loaded">';
		$total = 0;
		foreach($_SESSION["products"] as $product){ //loop though items and prepare html content
			
			//set variables to use them in HTML content below
			$product_name = $product["title"]; 
			$product_price = $product["price"];
			$product_code = $product["product_code"];
			$product_qty = $product["product_qty"];
			$currency =  $product["currency"];
			$description = $product['description'];
			$size = $product['size'];

			
			$cart_box .=  "<li> $product_name (Qty : $product_qty  ) &mdash; $currency ".sprintf("%01.2f", ($product_price * $product_qty)). " <a href=\"#\" class=\"remove-item\" data-code=\"$product_code\">&times;</a></li>";
			$subtotal = ($product_price * $product_qty);
			$total = ($total + $subtotal);
		}
		$cart_box .= "</ul>";
		$cart_box .= '<div class="cart-products-total">Total : '.$currency.sprintf("%01.2f",$total).' <u><a href="mycart.php" title="Review Cart and Check-Out">Check-out</a></u></div>';
		die($cart_box); //exit and output content
	}else{
		die("Your Cart is empty"); //we have empty cart
	}
}

################# remove item from shopping cart ################
if(isset($_GET["remove_code"]) && isset($_SESSION["products"]))
{
	$product_code   = filter_var($_GET["remove_code"], FILTER_SANITIZE_STRING); //get the product code to remove

	if(isset($_SESSION["products"][$product_code]))
	{
		unset($_SESSION["products"][$product_code]);
	}
	
 	$total_items = count($_SESSION["products"]);
	die(json_encode(array('items'=>$total_items)));
}

if($_POST['update_cart'] == 1 && isset($_POST['pid']) && isset($_POST['new_quantity']) && isset($_POST['price'])){
	if(isset($_SESSION["products"])){  //if session var already exist
		// echo $_POST['pid'];
		$npid = preg_replace('/\s+/', '', $_POST['pid']);
		if(isset($_SESSION["products"][$npid])){ //check item exist in products array
			// unset($_SESSION["products"][$new_product['product_code']]); //unset old item
			$_SESSION["products"][$npid]['product_qty'] = $_POST['new_quantity'];
			$_SESSION["products"][$npid]['price'] = $_POST['price'];
		}else{
			echo "  problemg etting pduct ";
		}			
	}	
	die(json_encode($_SESSION["products"][$npid])); //output json 
}
