<?php
add_action('init', 'paypal_payment');
function paypal_payment(){
	if( !isset($_POST['end_treasure_payment']) || !is_array($_POST['end_listing_pid']) ) return;
	
	global $current_user;
	$paypal_email = 'rameshchander242@gmail.com';
	$return_url = site_url('/active-listings/?end_treasure=success');
	$cancel_url = site_url('/active-listings/?end_treasure=cancel');
	$notify_url = site_url('/active-listings/?end_treasure=notify');
	$item_name = 'End Test Item';
	$item_amount = end_listing_charges();
	

	$querystring = "?business=".urlencode($paypal_email)."&";
	$querystring .= "first_name=".urlencode($current_user->user_firstname)."&";
	$querystring .= "last_name=".urlencode($current_user->user_lastname)."&";
	$querystring .= "email=".urlencode($current_user->user_email)."&";
	$querystring .= "cmd=".urlencode('_cart')."&";
	$querystring .= "no_note=".urlencode('1')."&";
	$querystring .= "upload=".urlencode('1')."&";
	$querystring .= "currency_code=".urlencode(get_woocommerce_currency())."&";
	$querystring .= "charset=".urlencode('UTF-8')."&";
	$querystring .= "invoice=".urlencode('ET_'.time().$invoice)."&";
	$querystring .= "state=".urlencode($current_user->state)."&";
	$querystring .= "country=".urlencode($current_user->country)."&";
	$querystring .= "no_shipping=".urlencode('1')."&";
	$querystring .= "custom=".urlencode($_POST['describe_end_list'])."&";
	
	$i=1; $invoice='';
	foreach($_POST['end_listing_pid'] as $pid){
		$post = get_post($pid);
		$querystring .= "item_name_".$i."=".urlencode('End Treasure ('.$post->post_title.' )')."&";
		$querystring .= "quantity_".$i."=".urlencode('1')."&";
		$querystring .= "item_number_".$i."=".urlencode($post->ID)."&";
		$querystring .= "amount_".$i."=".urlencode($item_amount)."&";
		$i++;
		$invoice .= '_'.$post->ID;
	}
	
	//$querystring .= "image_ur=".urlencode(site_url('/wp-content/themes/tw_child/images/logo.jpg'))."&";
	$querystring .= "return=".urlencode(stripslashes($return_url))."&";
	$querystring .= "cancel_return=".urlencode(stripslashes($cancel_url))."&";
	$querystring .= "notify_url=".urlencode($notify_url);
	
	//echo $querystring; exit;
	
	wp_redirect_('https://www.sandbox.paypal.com/cgi-bin/webscr'.$querystring);
	exit();
}


if(isset($_GET['end_treasure']) and $_GET['end_treasure']=='notify' and isset($_POST["txn_id"]) || isset($_POST["txn_type"])){
	add_action('init', 'notify_end_treasure_payment');
}
function notify_end_treasure_payment(){
  if(isset($_POST["txn_id"]) || isset($_POST["txn_type"])){
	
	$headers = "MIME-Version: 1.0" . "\r\n";
	$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
	//$headers .= 'From: '.$aryDataInvoice['sender_name'].'<'.$aryDataInvoice['sender_email'].'>' . "\r\n";
	
	if ($_POST['payment_status']=='Completed') {
		submit_end_listing_($_POST['item_number']);
		
		$body = 'End Treasure Payment Successfully Done <br /><br />';
		$body .= '<b>Name :</b> &nbsp; '.$_POST['first_name'].$_POST['last_name'].'<br />';
		$body .= '<b>Email :</b> &nbsp; '.$_POST['payer_email'].'<br />';
		$body .= '<b>Item :</b> &nbsp; '.$_POST['item_name'].'<br />';
		$body .= '<b>Item ID :</b> &nbsp; '.$_POST['item_number'].'<br />';
		$body .= '<b>Payment :</b> &nbsp; '.$_POST['payment_gross'].'<br />';
		$body .= '<b>Payment Status :</b> &nbsp; '.$_POST['payment_status'].'<br />';
		$body .= '<b>Payment Date :</b> &nbsp; '.date('Y:m:d H:i:s', strtotime($_POST['payment_date'])).'<br />';
		$body .= '<b>Reason :</b> &nbsp; '.$_POST['custom'].'<br />';
		wp_mail( $_POST['payer_email'], "End Treasure Payment Succefully", $body, $headers ); 
		wp_mail( $_POST['receiver_email'], "End Treasure Payment Succefully", $body, $headers ); 
		exit;
	}else if (strcmp ($res, "INVALID") == 0) {
		wp_mail( $data['receiver_email'], "PAYPAL info", "Invalid Response<br />data = <pre>".print_r($_POST, true)."</pre>", $headers );
	}	
  }
  exit;
}
