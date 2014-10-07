<?php
class WD_ACTIONS{
	function __construct(){
		if(isset($_POST)){
			add_action( 'init', array($this, 'wd_init'), 1 );
			add_action( 'init', array($this, 'wd_init_100'), 100 );
		}
		if(isset($_REQUEST['action']) and isset($_REQUEST['pid'])){
			add_action( 'init', array($this, 'wd_action') );
		}

		add_action( 'wp_head', array($this, 'wd_head'), 1 );
		add_action( 'admin_init', array($this, 'blockusers_init') );
		add_action( 'get_header', array($this, 'user_restrictions') );
		add_action( 'wd_add_to_watched_sellers', array($this, 'add_to_watched_sellers') );
		
		add_action( 'woocommerce_new_order', array( &$this, 'action_on_create_order' ), 1, 1 );
		add_action( 'save_post', array( &$this, 'action_on_create_order' ), 1, 1 );
	}
	
	function wd_init(){
		if(isset($_POST['member_blog_post-submit']) and !empty($_POST['member_blog_post-submit'])){
			global $message;
			$message = $this->insert_member_post();
		}else
		if(isset($_POST['unsold_treasure-submit']) and !empty($_POST['unsold_treasure-submit'])){
			global $message;
			$message = $this->update_unsold_product_status();
		}else
		if(isset($_POST['seller_watchlist_submit']) and !empty($_POST['seller_watchlist_submit']) and !empty($_POST['seller_id'])){
			global $message;
			$message = $this->add_seller_watchlist_();	
		}else
		if(isset($_GET['remove_seller']) and !empty($_GET['remove_seller'])){
			global $message;
			$message = $this->remove_seller_watchlist_($_GET['remove_seller']);	
		}else
		if(isset($_POST['close_account-submit']) and !empty($_POST['close_account-submit'])){
			global $message;
			$message = $this->close_account_submit();
		}else
		if(isset($_POST['hide_order_history']) and !empty($_POST['hide_order_history'])){
			global $message;
			$message = $this->hide_order_history_submit();
		}else
		if( isset($_POST['submit_rating']) and !empty($_POST['submit_rating']) ){
			$this->submit_user_rating();	
		}else
		if( isset($_POST['delete_rating']) and !empty($_POST['delete_rating']) ){
			$this->_delete_user_rating();
		}else
		if( isset($_POST['store_edit_']) and !empty($_POST['store_edit_']) ){
			$this->edit_store_appearance();
		}
                else
		if( isset($_POST['cancel_plan']) and !empty($_POST['cancel_plan']) ){
			$this->cancel_store_plan();
		}
		
		if(! is_admin())
			add_filter( 'loop_shop_post_in', array($this, 'woocommerce_custom_filter') );
	}
	
	function wd_init_100(){
		if( isset($_POST['store_activate_submit']) and $_POST['store_activate_submit']=="Activate" ){
			$this->create_vendor_store();
		}
	}
	
	function wd_action(){
		if($_REQUEST['action']=='delete_treasure'){
			$this->delete_treasure_($_REQUEST['pid']);
		}elseif($_REQUEST['action']=='delete_auction'){
			$this->delete_auction_($_REQUEST['pid']);
		}
	}

	function blockusers_init() {
		if ( is_admin() && current_user_can( 'vendor' ) and !current_user_can( 'administrator' ) and !defined('DOING_AJAX')) {
			wp_redirect_( home_url() );
		}
	}
	
	function wd_head(){
		$this->html_header_function();
		$this->check_vendor_id();
		$this->check_expire();
	}
	
	function check_expire(){
		check_expire();
	}
	
	function html_header_function(){
		wp_register_style( 'wd_box_css', get_bloginfo('stylesheet_directory') . '/css/selectbox.css' );
		wp_enqueue_style( 'wd_box_css' );
		wp_enqueue_script( 'wd_scripts', get_bloginfo('stylesheet_directory') . '/js/jscript.js', array(), '99.9.0', true );
		wp_enqueue_script( 'wd_select_box_script', get_bloginfo('stylesheet_directory') . '/js/select_box.js', array(), '15', true );
	}
	
	function check_vendor_id(){
		if(is_page_template('treasure-template.php')){
			$vendor = ign_get_user_vendor();
			if(empty($vendor->ID) and !is_page('my-store')){
				//wp_redirect_(site_url('my-store')); exit;
			}
		}
	}
	
	
	function user_restrictions(){
	  if(is_page() and is_singular()){
		global $post;
		if($user_rest = get_post_meta($post->ID, "_user_restriction", true)){
		  if($user_rest>0){
			if($user_rest==1 and is_user_logged_in()){
				wp_redirect_(site_url('/my-account/')); exit;
			}elseif(($user_rest==2 or $user_rest==3) and !is_user_logged_in()){
				wp_redirect_(site_url()); exit;
			}
		  }
		}
	  }
	}
	
	function add_to_watched_sellers(){
		global $product;
		if(empty($product)) return;
		if(is_owner_product($product->id)) return;
	
		if($vendor_p = ign_get_product_vendors($product->id)){
			$seller_id = $vendor_p[0]->admins[0]->ID;
			$seller = $vendor_p[0]->admins[0]->data->user_nicename;
		}else{
			$seller_id = $product->post->post_author;
			$seller =  get_the_author_meta( 'user_nicename', $seller_id );
		}
		$data = add_to_watched_seller_form($seller_id);
		$link = site_url('/seller/'.$seller.'/');
		
		$output = '<div class="payment_options">
			<p class="left"><strong>Seller ID : </strong> <a href="'.$link.'">'.ucwords($seller).'</a> </p>
			<div class="right">'.$data.'</div>
		</div>';
		echo $output;
	}
	
	
	function insert_member_post(){
		foreach($_POST as $pkey=>$pval){
			if(empty($pval)){
			  $message['reg_error'][] = "All Fields are Required";
			  return $message;
			}
		}
		$message=array();
		$user = get_userdatabylogin($_REQUEST['net_id']);
		$user_id = get_current_user_id();
		if(!empty($user) && $user->ID == $user_id){
			// Create post object
			$my_post = array(
			  'post_title'    => $_REQUEST['post_title'],
			  'post_content'  => $_REQUEST['post_content'],
			  'post_status'   => 'publish',
			  'post_author'   => $user->ID,
			);
			// Insert the post into the database
			wp_insert_post( $my_post );
			$message['success'][] = "post added successfully";
			$_SESSION['_message'] = $message;
		}else{
			$message['reg_error'][] = __('user does not exist.', 'frontendprofile');
		}
	
		if(empty($message['reg_error'])){
			wp_redirect( site_url().'/member-blog/' ); exit;
		}else{
			return $message;
		}
	}
	
	function update_unsold_product_status(){
		$unsold_relist_products=$_POST['check_to_relist'];
		if(!empty($unsold_relist_products)){
			foreach($unsold_relist_products as $product){
				update_post_meta($product,'_is_relist','yes');
				$message['success'][]='product added in relist listing successfully';
			}
		}
		return $message;
	}
	
	function add_seller_watchlist_(){
		if(isset($_POST['seller_id']) and !empty($_POST['seller_id'])){
			global $current_user;
			if(!$seller_watchlist = get_user_meta($current_user->ID, 'seller_watchlist', true))
				$seller_watchlist = array();
			foreach($seller_watchlist as $seller){
				if($_POST['seller_id'] == $seller['seller_id']){
					$message['reg_error'][] = "Already in Watchlist";	
				}
			}
			
			if(empty($message['reg_error'])){
				$seller_watchlist[] = array('seller_id'=>$_POST['seller_id'], 'from'=>date('d-m-Y'));
				update_user_meta($current_user->ID, 'seller_watchlist', $seller_watchlist);
				$message['success'][] = "Seller Add in Watchlist successfully";
				$_SESSION['_message'] = $message;
				wp_redirect( current_page_url() ); exit;
			}else{
				return $message;
			}
		}
	}
	
	function remove_seller_watchlist_($id){
		global $current_user;
		if($seller_watchlist = get_user_meta($current_user->ID, 'seller_watchlist', true)){
			foreach($seller_watchlist as $key=>$seller){
				if($id == $seller['seller_id']){
					$remove_key=$key;
				}
			}
			if(isset($remove_key) and $remove_key!=''){
				unset($seller_watchlist[$remove_key]);
				update_user_meta($current_user->ID, 'seller_watchlist', $seller_watchlist);
				$message['success'][] = "Seller Remove from Watchlist successfully";
				$_SESSION['_message'] = $message;
				wp_redirect( site_url('/my-watched-sellers/') ); exit;
			}else{
				$message['reg_error'][] = "Seller not in Your Watchlist";
				$_SESSION['_message'] = $message;
				wp_redirect( site_url('/my-watched-sellers/') ); exit;
			}
		}
		return false;
	}
	
	function close_account_submit(){
		$user_id = get_current_user_id();
		if($user = get_user_by( 'login', $_POST['net_id'] ) and $user->data->user_email==$_POST['net_email'] and $user->ID==$user_id){
			global $wpdb;
			update_user_meta( $user->ID, 'wd_disable_user', 1 );
			$wpdb->update( 'wp_users', array('user_login' => 'disable_'.str_replace('disable_','',$user->data->user_login) ),  array( 'ID' => $user->ID ) );
			$message['success'][] = "Your Account Closed Successfully";
		}else{
			$message['reg_error'][] = "MyNetAuctions-ID OR Email You Enter is Not Your";
		}
		$_SESSION['_message'] = $message;
		wp_redirect( site_url('/my-account/close-account/') ); exit;
	}
	
	function hide_order_history_submit(){
		if(isset($_POST['hide_history']) and !empty($_POST['hide_history'])){
			foreach($_POST['hide_history'] as $oid){
				update_post_meta($oid, '_hide_order_history', true);
			}
			$message['success'][] = "Order Delete Succefully";
		}else{
			$message['reg_error'][] = "Not selected any Order";
		}
		$_SESSION['_message'] = $message;
		wp_redirect( current_page_url() ); exit;
	}
	
	function woocommerce_custom_filter($filtered_posts) {
		global $wpdb;
		$where = $join = "";
		$matched_products = array();
		
		$join .= " INNER JOIN {$wpdb->postmeta} as vpm ON ($wpdb->posts.ID = vpm.post_id) ";
		$where .=" AND (vpm.meta_key = '_visibility' AND vpm.meta_value != 'hidden') ";
		
		$join .= " LEFT JOIN {$wpdb->postmeta} AS opm ON ($wpdb->posts.ID = opm.post_id and opm.meta_key = '_shop_our_brand') ";
		$where .= " AND ( opm.post_id IS NULL || (opm.meta_key = '_shop_our_brand' and opm.meta_value != 'yes') ) ";
		
		if ( isset( $_GET['product_tag'] ) ) {
			$tag = $_GET['product_tag'];
			$join .= " INNER join {$wpdb->term_relationships} as tr on ($wpdb->posts.ID = tr.object_id) 
				INNER join {$wpdb->term_taxonomy} as tt on (tt.term_taxonomy_id = tr.term_taxonomy_id) 
				INNER join {$wpdb->terms} as t on (t.term_id = tt.term_id)";
			$where .= " and tt.taxonomy = 'product_tag' and t.slug LIKE '%".$tag."%' ";
		}
		if ( isset( $_GET['maxprice'] ) && isset( $_GET['minprice'] ) ) {
			$join .= " INNER JOIN {$wpdb->postmeta} as mp ON ($wpdb->posts.ID = mp.post_id) ";
			if(!empty($_GET['minprice']))
				$where .=" AND (mp.meta_key = '_price' AND mp.meta_value >= ".$_GET['minprice'].") ";
			
			if(!empty($_GET['maxprice']))
				$where .=" AND (mp.meta_key = '_price' AND mp.meta_value <= ".$_GET['maxprice'].") ";
		}
		if(isset($_GET['timeleft_day']) || isset($_GET['timeleft_hour']) || isset($_GET['timeleft_min'])){
			global $wpdb;
			$day = !empty($_GET['timeleft_day'])?$_GET['timeleft_day']:'0';
			$hour = !empty($_GET['timeleft_hour'])?$_GET['timeleft_hour']:'0';
			$min = !empty($_GET['timeleft_min'])?$_GET['timeleft_min']:'0';
			$date = strtotime($day." day", strtotime(date('Y-m-d H:i:s'))); 
			$date = strtotime($hour." hours", $date); 
			$date = strtotime($min." mins", $date); 
			$join .= " INNER JOIN {$wpdb->postmeta} as tl ON ($wpdb->posts.ID = tl.post_id) ";
			$where .=" AND (tl.meta_key = '_end_date' AND UNIX_TIMESTAMP(tl.meta_value) > ".$date.") ";
		}
		if(!empty($join) and !empty($where)){
			$matched_products_query = $wpdb->get_results( "SELECT DISTINCT ID, post_parent, post_type FROM $wpdb->posts ".$join." WHERE post_type IN ( 'product', 'product_variation' ) AND post_status = 'publish' ".$where, OBJECT_K );
	
			if ( $matched_products_query ) {
				foreach ( $matched_products_query as $product ) {
					if ( $product->post_type == 'product' )
						$matched_products[] = $product->ID;
					if ( $product->post_parent > 0 && ! in_array( $product->post_parent, $matched_products ) )
						$matched_products[] = $product->post_parent;
				}
			}
	
			// Filter the id's
			if ( sizeof( $filtered_posts ) == 0) {
				$filtered_posts = $matched_products;
				$filtered_posts[] = 0;
			} else {
				$filtered_posts = array_intersect( $filtered_posts, $matched_products );
				$filtered_posts[] = 0;
			}
		}
		return (array) $filtered_posts;
	}
	
	function submit_user_rating(){
		global $message;
		if( !(isset($_POST['submit_rating']) and !empty($_POST['submit_rating'])) )
			return;
	
		if(empty($_POST['buyer_id']))
			$message['reg_error'][] = "You Required Login for Rating";
		if(empty($_POST['seller_id']))
			$message['reg_error'][] = "Please Select Seller Which you want Rating.";
	
		if((empty($_POST['ship_rating']) || empty($_POST['ship_fees_rating']) || empty($_POST['quality_rating']) || empty($_POST['transacion_rating']) || empty($_POST['packaging_rating'])) and empty($message)){
			$message['reg_error'][] = "All Star Ratings are Required";
		}
		if(isset($message['reg_error']) and !empty($message['reg_error']))	return;
		
	
		global $wpdb;
		$select_row = $wpdb->get_row('select rating_id from '.$wpdb->prefix . 'user_rating where seller_id="'.$_POST['seller_id'].'" and buyer_id="'.$_POST['buyer_id'].'" and order_id="'.$_POST['order_id'].'"');
		if(isset($select_row) and !empty($select_row)){
			$message['reg_error'][] = "You Already Rate this Seller on Your Order";
			return;
		}
		
		if(empty($select_row)){
			$insert_data = array(
				'seller_id' => $_POST['seller_id'], 
				'buyer_id' => $_POST['buyer_id'], 
				'order_id' => $_POST['order_id'], 
				'ship_rating' => $_POST['ship_rating'], 
				'ship_fees_rating' => $_POST['ship_fees_rating'], 
				'quality_rating' => $_POST['quality_rating'], 
				'transacion_rating' => $_POST['transacion_rating'], 
				'packaging_rating' => $_POST['packaging_rating'], 
				'rating_dom' => date('Y-m-d h:i:s', current_time('timestamp'))
			);	
			$wpdb->insert( $wpdb->prefix . "user_rating", $insert_data );
			$message['success'][] = "Rating Successfully Done";
			$_SESSION['_message'] = $message;
			wp_redirect_(site_url('/pending-ratings/'));
		}
		return;
	}
	
	function _delete_user_rating(){
		global $message;
		if(empty($_POST['ratings'])){
			$message['reg_error'][] = "Not any Record Select to Delete";
			return;
		}
		global $wpdb;
		$i = 0;
		foreach($_POST['ratings'] as $rating_id){
			$del_arr = array( 'rating_id' => $rating_id );
			if($wpdb->delete( $wpdb->prefix . "user_rating", $del_arr))
				$i++;
		}
		$message['success'][] = $i ." Records Deleted Successfully.";
		$_SESSION['_message'] = $message;
		wp_redirect_( current_page_url() ); exit;
	}
	
	function delete_treasure_($pid){
		$post = array( 'ID' => $pid, 'post_status'=>'draft' );
		wp_update_post( $post );
		$message['success'][] = "Treasure Delete Successfully";
		$_SESSION['_message'] = $message;
		wp_redirect_($_SERVER['HTTP_REFERER']);
	}
	
	function delete_auction_($pid){
		global $current_user, $wpdb;
		$sql = 'delete from wp_auction_bids where post_id = "' . $pid . '" and user_id="'.$current_user->ID.'"';
		$wpdb->query( $sql );
		$message['success'][] = "Auction Delete Successfully";
		$_SESSION['_message'] = $message;
		wp_redirect_($_SERVER['HTTP_REFERER']);
	}
	
	function action_on_create_order( $order_id ) {
		global $post;
		if ( isset( $_POST ) ) {
			if ( is_int( wp_is_post_revision( $order_id ) ) ) return;
			if( is_int( wp_is_post_autosave( $order_id ) ) ) return;
			if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $order_id;
			if ( !current_user_can( 'edit_post', $order_id )) return $order_id;
			if ( isset( $post->post_type ) && ( $post->post_type != 'shop_order' ) ) return $order_id;
		}

		$order = new WC_Order( $order_id );
		if ( !$order )
			return $order_id;
		$author_id = -1;
		foreach( $order->get_items() as $item ) {
			$_product = $order->get_product_from_item( $item );
			if ( $_product && $_product->exists() )
				$author_id = $_product->post->post_author;
		}
		update_post_meta( $order_id, '_product_seller', $author_id );
	}
	
	function edit_store_appearance(){
	
         global $ignitewoo_vendors, $current_user;
		if ( isset($_POST['store_name']) && !empty($_POST['store_name']) ) {
			$title = strip_tags( $_POST[ 'store_name' ] );
                        $slug=str_replace(" ","-",strtolower($title));
			if ( 'yes' != $ignitewoo_vendors->settings['allow_vendor_html' ] ) 
				$desc = strip_tags( $_POST[ 'store_description'] );
			else
				$desc = $_POST[ 'store_description'];
				
			if ( !taxonomy_exists( $ignitewoo_vendors->token ) )
			$ignitewoo_vendors->register_vendors_taxonomy();
			
			$term=wp_update_term($_POST['store_id'],$ignitewoo_vendors->token,array('name'=>$title ,'slug'=>$slug,'description'=> $desc ));
			
		
			if ( empty( $error ) ) { 
				update_user_meta( $current_user->ID, 'product_vendor', $_POST['store_id'] );
				$vendor_data = array();
				$vendor_data['admins'] = array( $current_user->ID );
				$vendor_data['paypal_email'] = '';
				$vendor_data['commission'] = $ignitewoo_vendors->settings['default_commission'];
				$vendor_data['commission_for'] = $ignitewoo_vendors->settings['default_commission_for'];
				if ( isset( $_FILES['store_logo'] ) and !empty($_FILES['store_logo']['name']) ){
					require_once(ABSPATH . "wp-admin" . '/includes/image.php');
					require_once(ABSPATH . "wp-admin" . '/includes/file.php');
					require_once(ABSPATH . "wp-admin" . '/includes/media.php');
					$attach_id = media_handle_upload( 'store_logo', 0);
					update_woocommerce_term_meta( $_POST['store_id'], 'thumbnail_id', $attach_id );
				}
				
				if ( isset( $_FILES['store_bgimage'] ) and !empty($_FILES['store_bgimage']['name']) ){
					require_once(ABSPATH . "wp-admin" . '/includes/image.php');
					require_once(ABSPATH . "wp-admin" . '/includes/file.php');
					require_once(ABSPATH . "wp-admin" . '/includes/media.php');
					$attach_id = media_handle_upload( 'store_bgimage', 0);
					$sb = wp_get_attachment_image_src( $attach_id );
					$vendor_data['store_bgimage'] = $sb[0];
				}
				$vendor_data['store_bgcolor'] = $_POST[ 'store_bgcolor'];
				$vendor_data['store_plan'] = $_POST[ 'plan'];
                                
				$vendor_data['store_level'] = $_POST[ 'level'];
                                
				$store_level = store_level();
				$vendor_data['store_price'] = number_format($store_level[$_POST['level']][$_POST['plan']], 2, '.', '');
                                
				update_option( $ignitewoo_vendors->token . '_' . $_POST['store_id'], $vendor_data );
				$url = remove_query_arg( 'store_setup' ); 
				//wp_redirect_( site_url('/my-store/?overview=store') );
				//exit;
			}
			if ( !empty( $error ) && !empty( $new_store ) ) { 
				$err = '<div class="error">';
				  foreach( $new_store->errors as $key => $msg ) { 
					foreach( $msg as $k => $m ) { 
						$err .= str_replace( 'term', 'store', $m );
					}
				  } 
			   $err .= '</div>';
			   $message['reg_error'][] = $err;
			}
		}
        }
	function create_vendor_store(){
			global $message;
		if( !(isset($_POST['level']) and !empty($_POST['level']) and isset($_POST['plan']) and !empty($_POST['plan']) and isset($_POST['store_name']) and !empty($_POST['store_name']) and isset($_POST['step']) and !empty($_POST['step'])) ){
			$message['reg_error'][] ='All Fields are Required';
			return;
		}
		global $ignitewoo_vendors, $current_user;
		if ( isset($_POST['store_name']) ) {
			$title = strip_tags( $_POST[ 'store_name' ] );
			if ( 'yes' != $ignitewoo_vendors->settings['allow_vendor_html' ] ) 
				$desc = strip_tags( $_POST[ 'store_description'] );
			else
				$desc = $_POST[ 'store_description'];
				
			if ( !taxonomy_exists( $ignitewoo_vendors->token ) )
			$ignitewoo_vendors->register_vendors_taxonomy();
			
			$new_store = wp_insert_term(
				$title, 
				$ignitewoo_vendors->token, // the taxonomy
				array( 'description'=> $desc )
			);
			$error = is_wp_error( $new_store );
			if($error == 1){
				$message['reg_error'][] ='Store Name Already Exists';
				return;
			}
			if ( empty( $error ) ) { 
				update_user_meta( $current_user->ID, 'product_vendor', $new_store['term_id'] );
				$vendor_data = array();
				$vendor_data['admins'] = array( $current_user->ID );
				$vendor_data['paypal_email'] = '';
				$vendor_data['commission'] = $ignitewoo_vendors->settings['default_commission'];
				$vendor_data['commission_for'] = $ignitewoo_vendors->settings['default_commission_for'];
				if ( isset( $_FILES['store_logo'] ) and !empty($_FILES['store_logo']['name']) ){
					require_once(ABSPATH . "wp-admin" . '/includes/image.php');
					require_once(ABSPATH . "wp-admin" . '/includes/file.php');
					require_once(ABSPATH . "wp-admin" . '/includes/media.php');
					$attach_id = media_handle_upload( 'store_logo', 0);
					update_woocommerce_term_meta( $new_store['term_id'], 'thumbnail_id', $attach_id );
				}
				
				if ( isset( $_FILES['store_bgimage'] ) and !empty($_FILES['store_bgimage']['name']) ){
					require_once(ABSPATH . "wp-admin" . '/includes/image.php');
					require_once(ABSPATH . "wp-admin" . '/includes/file.php');
					require_once(ABSPATH . "wp-admin" . '/includes/media.php');
					$attach_id = media_handle_upload( 'store_bgimage', 0);
					$sb = wp_get_attachment_image_src( $attach_id );
					$vendor_data['store_bgimage'] = $sb[0];
				}
				$vendor_data['store_bgcolor'] = $_POST[ 'store_bgcolor'];
				$vendor_data['store_plan'] = $_POST[ 'plan'];
				$vendor_data['store_level'] = $_POST[ 'level'];
				$store_level = store_level();
				$vendor_data['store_price'] = number_format($store_level[$_POST['level']][$_POST['plan']], 2, '.', '');
				update_option( $ignitewoo_vendors->token . '_' . $new_store['term_id'], $vendor_data );
				$url = remove_query_arg( 'store_setup' ); 
				wp_redirect_( site_url('/my-store/?overview=store') );
				exit;
			}
			if ( !empty( $error ) && !empty( $new_store ) ) { 
				$err = '<div class="error">';
				  foreach( $new_store->errors as $key => $msg ) { 
					foreach( $msg as $k => $m ) { 
						$err .= str_replace( 'term', 'store', $m );
					}
				  } 
			   $err .= '</div>';
			   $message['reg_error'][] = $err;
			}
		}
	
	}
}
new WD_ACTIONS();
