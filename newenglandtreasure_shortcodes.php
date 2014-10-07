<?php
class WD_SHORTCODES{
	function __construct() {
		add_shortcode( 'HEADER_USER_INFO', array($this, 'user_info') );
		add_shortcode( 'my_seller_wathclist', array($this, 'seller_wathclist') );
		/* Auction */
		add_shortcode( 'my_auction_wathclist', array($this, 'auctions_watchlist') );
		add_shortcode( 'my_auctions_bid', array($this, 'my_all_auctions_bids') );
		add_shortcode( 'my_win_auctions_bids', array($this, 'my_win_auctions_bids') );
		/* Seller Treasures */
		add_shortcode( 'my_active_listing', array($this, 'my_active_listing') );
		add_shortcode( 'ENDING_MYLISTING', array($this, 'ending_active_listing') );
		add_shortcode( 'my_sold_treasure', array($this, 'my_sold_treasure') );
		add_shortcode( 'my_unsold_treasure', array($this, 'my_unsold_treasure') );
		add_shortcode( 'my_relist_treasure', array($this, 'my_relist_treasure') );
		/* User Rating */
		add_shortcode( 'seller_rating_list', array($this, 'seller_rating_list') ); /* Single Seller Rating */
		add_shortcode( 'all_seller_rating_list', array($this, 'all_seller_rating_list') ); /*Sellers Rating */
		add_shortcode( 'pending_seller_rating_list', array($this, 'pending_seller_rating_list') );
		add_shortcode( 'user_rating_form', array($this, 'user_rating_form') ); /* user Rating Form */
		
		add_shortcode( 'HELP_SEARCH', array($this, 'help_search') );
		add_shortcode( 'SHOW_UPDATES', array($this, 'show_updates') );
		add_shortcode( 'MEMBER_BLOG', array($this, 'member_blog_post') );
		add_shortcode( 'MEMBER_POST', array($this, 'show_member_posts') );
		add_shortcode( 'CLOSE_Account', array($this, 'close_account') );
		//add_shortcode( 'ADDTOCART_CONFIRMATION', array($this, 'addtocart_confirmation_') );
		add_shortcode( 'SELLER_SEARCHING', array($this, 'wp_seller_searching') );
		/* My Store Setting */
		add_shortcode( 'MY_STORE_SUMMARY', array($this, 'wd_store_summary') );
		
	}
	
	function user_info() {
		if(!is_user_logged_in())	return;
		
		global $current_user; $home='';	
		$free_posts = free_listing();
		$month_posts = calculate_post_month();
		$rating = user_average_rating($current_user->ID);
		$home = shop_logo_image($current_user->ID);
		$t =(basename(get_permalink())=='my-account')?'Hello, ':'';
if(($free_posts-$month_posts)<=0){
$data = 0;
}
else{
$data = $free_posts-$month_posts;
}
		
		$return = '<div class="top-semi-buttom"><div class="top-semi-buttom-inner">
			<div class="header_usr_info">'.$t.'<span class="useridColor">'.ucwords($current_user->user_login).$home.'</span> / &nbsp;'.star_rating($rating).' / '.($data).'</div>
		  </div></div>';
		return $return;
	}
	
	function seller_wathclist(){
		global $current_user;
		$header = '<h3> My Watched Sellers List</h3>';
		$seller_watchlist = get_user_meta($current_user->ID, 'seller_watchlist', true);
		if ( !$seller_watchlist || count( $seller_watchlist ) <= 0 ) { 
			$output = '<p>No Seller on your watchlist</p>';
		}else{
			ob_start();
			require('templates/seller_watchlist.php');
			$output = ob_get_contents();
			ob_end_clean();
		} 
		return $header . $output;	
	}
	
	function auctions_watchlist(){
		ob_start();
		$obj=new IgniteWoo_Auctions;
		$obj->my_account_watchlist();	
		$output = ob_get_contents();
		ob_end_clean();
		return $output;	
	}
	
	function my_all_auctions_bids($att=''){
		ob_start();
		$obj=new IgniteWoo_Auctions;
		$obj->my_account_bids($att);	
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	
	function my_win_auctions_bids(){
		ob_start();
		$obj=new IgniteWoo_Auctions;
		$obj->my_account_won();	
		$output = ob_get_contents();
		ob_end_clean();
		return $output;	
	}
	
	function my_active_listing(){
          $current_user = wp_get_current_user();
                $args = array(
			'author'     =>  $current_user->ID,
			'post_type'  => 'product',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'meta_key' => '_end_date',
                        'orderby' => 'meta_value',
                        'order' => 'ASC'
		);
		$products = get_posts( $args );
		if($_GET['end_treasure']=='success')
			echo '<div class="success">Successfull End Treasure Listing</div>';
		elseif($_GET['end_treasure']=='cancel')
			echo '<div class="error">End Treasure Listing Not Success</div>';
		if(!empty($products)){
			ob_start();
			require('templates/seller_active_treasures.php');
			$output = ob_get_contents();
			ob_end_clean();
		}else { $output = 'No product found'; }
		return $output;	
	}
	
	function ending_active_listing(){
		ob_start();
		require('templates/seller_ending_active_treasures.php');
		$output = ob_get_contents();
		ob_end_clean();
		return $output;	
	}
	
	function my_sold_treasure(){
global $wpdb;
if(isset($_REQUEST['delete_treasure'])){
	$postid=$_REQUEST['delete_treasure'];
	$wpdb->query("DELETE FROM {$wpdb->prefix}woocommerce_order_items as order_items
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta_2 ON order_items.order_item_id = order_item_meta_2.order_item_id
			LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
			LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID = rel.object_ID
			LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
			LEFT JOIN {$wpdb->terms} AS term USING( term_id )
			WHERE 	posts.post_type 	= 'shop_order'
			AND 	posts.post_status 	= 'publish'
			AND 	tax.taxonomy		= 'shop_order_status'
			AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
			AND 	order_items.order_item_type = 'line_item'
			AND 	order_item_meta.meta_key = '_qty'
			AND 	order_item_meta_2.meta_key = '_product_id'
			and 	order_item_meta_2.meta_value in (".$postid.")");
	}
		if(isset($_GET['sold_order']) and !empty($_GET['sold_order']) and is_numeric($_GET['sold_order'])){
			$header = '<h2>My Sold Treasure Order</h2>
<p>Treasure Order Information</p>';
			$sold_order = $_GET['sold_order'];
			if(is_vendor_product_order($sold_order)){
				ob_start();
				require('templates/seller_sold_treasure_order.php');
				$output = ob_get_contents();
				ob_end_clean();
			}
			else { $output = 'Invalid Sold Order'; }
			return $header . $output;
		}
		elseif(isset($_GET['sold_order']) && empty($_GET['sold_order'])){
		$header = '<h2>My Sold Treasure Order</h2>';
			ob_start();
				require('templates/seller_sold_treasure_auction.php');
				$output = ob_get_contents();
				ob_end_clean();
				return $header . $output;
			}else{
			$header = '<h2>My Sold Treasures</h2>
<p>If a treasure is listed below, Click on the treasure to start the Transaction Process.</p>';
			$sold_products=$this->get_vendor_sold_unsold_products('sold');
			if(!empty($sold_products)){
				ob_start();
				require('templates/seller_sold_treasures.php');
				$output = ob_get_contents();
				ob_end_clean();
			}else { $output = 'No product found'; }
			return $header.$output;	
		}
	}
	
	function my_unsold_treasure(){
		$unsold_products=$this->get_vendor_sold_unsold_products('unsold');
		if(!empty($unsold_products)){
			ob_start();
			require('templates/seller_unsold_treasures.php');
			$output = ob_get_contents();
			ob_end_clean();
		}else { $output = 'No product found'; }
		return $output;	
	}
	
	function my_relist_treasure(){
		$relist_products=$this->get_vendor_sold_unsold_products('relist');
		if(!empty($relist_products)){
			ob_start();
			require('templates/seller_relist_treasures.php');
			$output = ob_get_contents();
			ob_end_clean();
		}else { $output = 'No product found'; }
		return $output;	
	}
	
	function seller_rating_list(){
		ob_start();
		require('templates/seller_rating_list.php');
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	
	function all_seller_rating_list(){
		ob_start();
		require('templates/all_seller_rating_list.php');
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	
	function pending_seller_rating_list(){
		ob_start();
		require('templates/pending_seller_rating_list.php');
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	
	function user_rating_form(){
		ob_start();
		require('templates/rating_form.php');
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	
	function help_search(){
		ob_start();
		require('templates/help_search.php');
		$output = ob_get_contents();
		ob_end_clean();
		return $output;	
	}
	
	function show_updates(){
		$args = array( 'category' => 73 );
		$myposts = get_posts( $args );
		foreach ( $myposts as $post ){
			setup_postdata( $post ); 
			$permalink = get_permalink( $post->ID );
			$title=get_the_title($post->ID); 
			$output.="<h3><a href='".$permalink."'>".$title."</a></h3>";
			$output.="<p>".$post->post_content."</p><br/>";
		} 
		wp_reset_postdata();
		return $output;	
	}
	
	
	function member_blog_post(){
		ob_start();
		require('templates/member_blog_post.php');
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	
	function show_member_posts(){
		$current_user = wp_get_current_user();
		$args = array( 'category' => '1' );
		$myposts = get_posts( $args );
		$output.="<table>";
		foreach ( $myposts as $post ) : setup_postdata( $post ); 
				$author_name = ucwords(get_the_author_meta( 'user_nicename', $post->post_author));
				$permalink = get_permalink( $post->ID );
				$output.="<tr><td style='width: 20%;'><a href='".$permalink."'>".$author_name.'<br/>'.$post->post_date."</a></td>";
				$output.="<td>".$post->post_content."</td></tr>";
		endforeach; 
		$output.="</table>";
		wp_reset_postdata();
		return $output;	
	}
	
	function close_account(){
		ob_start();
		require('templates/close_account.php');
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	
	function addtocart_confirmation_(){
		if(!isset($_SESSION['addtocart_confirmation']) || (isset($_SESSION['addtocart_confirmation']) and empty($_SESSION['addtocart_confirmation'])) ){
			wp_redirect_( site_url('/') ); exit;	
		}
		ob_start();
		require('templates/addtocart_confirmation.php');
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	
	function wp_seller_searching()
	{
		global $wpdb;
                $slr = $_GET['slr'];
		if(isset($_GET['slr'])){
			$select = "SELECT distinct ID, user_nicename FROM wp_users ";
			$where1 = " and (wp_users.user_login like '".$slr."' || wp_users.user_nicename like '".$slr."') ";
			
			$join = " INNER JOIN wp_usermeta AS mt1 ON (wp_users.ID = mt1.user_id) ";
			$where = " AND (mt1.meta_key = 'wp_capabilities' AND CAST(mt1.meta_value AS CHAR) LIKE '%vendor%') ";
			
			$join .= " LEFT JOIN wp_usermeta AS mt2 ON (wp_users.ID = mt2.user_id and mt2.meta_key = 'wd_disable_user') ";
			$where .= " AND ( mt2.user_id IS NULL || (mt2.meta_key = 'wd_disable_user' and mt2.meta_value = '0') ) ";
			
			$join .= " LEFT JOIN wp_usermeta AS mt3 ON (wp_users.ID = mt3.user_id and mt3.meta_key = 'verify_code') ";
			$where .= " AND ( mt3.user_id IS NULL ) ";
			
			$sql = $select . $join ." where 1=1 ". $where1. $where;
                         //echo $sql;
			if(!$results = $wpdb->get_results($sql)){
				$where2 = " and (wp_users.user_login like '%".$slr."%' || wp_users.user_nicename like '%".$slr."%') ";
				$sql = $select . $join ." where 1=1 ". $where2. $where;
				$results = $wpdb->get_results($sql);
			}
		}else{ $slr = ''; }
		
		$output = '<div class="seller-entry search-product" style="float:left;">';
		$output .= '<div class="clear">'.count($results).' Results found for "'.$slr.'"</div>';
		$output .= '<ul>';
		if (!empty($results)) {
		  foreach ($results as $author) {
			  /*
			  $user = new WP_User($author->ID);
			  $user->add_cap('vendor');
			  if(!$vendor = ign_get_user_vendor($author->ID)){
					create_store($author->ID);
				}
			  */
				$output .= '<li>
					<div class="auction_left"><a href="'.site_url('/seller/'.$author->user_nicename.'/').'">'.$author->user_nicename.'</a></div>';
					
				$vendor = ign_get_user_vendor($author->ID);	
				if(!empty($vendor->url))
					$output .= '<div class="auction_right"><a href="'.$vendor->url.'">'.$vendor->slug.'</a></div>';
				$output .= '</li>
				<li class="clear"></li>';
			}
		} else {
			$output .= '<tr><td><h2>Not Record Found</h2></td></tr>';
		}
		$output .= '</ul></div>';
		return $output;
	}
	
	function wd_store_summary(){
		ob_start();
		require('templates/store_summary.php');
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	
	
	
	function get_vendor_sold_unsold_products($type = NULL){
		global $wpdb;
		$products = get_vendor_products('hidden');
		if(empty($products)) return false;
		
		$prod_ = '-1';
		foreach($products as $product){
			$prod_ .= ', '.$product->ID;
		}
		
		$orders = $wpdb->get_results( "
			SELECT order_item_meta_2.meta_value as product_id, SUM( order_item_meta.meta_value ) as item_quantity, order_item_meta_2.order_item_id as order_item_id, order_items.order_id FROM {$wpdb->prefix}woocommerce_order_items as order_items
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta_2 ON order_items.order_item_id = order_item_meta_2.order_item_id
			LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
			LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID = rel.object_ID
			LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
			LEFT JOIN {$wpdb->terms} AS term USING( term_id )
			WHERE 	posts.post_type 	= 'shop_order'
			AND 	posts.post_status 	= 'publish'
			AND 	tax.taxonomy		= 'shop_order_status'
			AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold','pending' ) ) ) . "')
			AND 	order_items.order_item_type = 'line_item'
			AND 	order_item_meta.meta_key = '_qty'
			AND 	order_item_meta_2.meta_key = '_product_id'
			and 	order_item_meta_2.meta_value in (".$prod_.")
			GROUP BY order_item_meta_2.meta_value
		" );
//print_r($orders);
		$orders2=$wpdb->get_results("SELECT pid as product_id, quantity as item_quantity FROM wd_cart_list WHERE pid in (".$prod_.") group by product_id");
$timestamp=mktime(date('h')-4,date('i'),date('s'),date('m'),date('d'),date('Y'));
$date=date('Y-m-d h:i:s',$timestamp);
$orders4=$wpdb->get_results("SELECT wp_postmeta.post_id as product_id, 1 as item_quantity FROM wp_postmeta INNER JOIN wp_auction_bids ON wp_postmeta.post_id=wp_auction_bids.post_id WHERE wp_postmeta.meta_key='_end_date' AND wp_postmeta.meta_value<='".$date."' AND wp_auction_bids.post_id in (".$prod_.") GROUP BY wp_auction_bids.post_id");
//print_r($orders2);
$orders3=array_merge($orders,$orders2,$orders4);
//echo "<br>";
//print_r($orders3);
		$ids = array();
		$unsold = array();
		if($type == 'sold'){
if(!empty($orders3))
return $orders3;
//elseif(!empty($orders2))
//return $orders2;
else
return false;
		}elseif(!empty($products)){
			if(!empty($orders)){
				foreach ( $orders as $order )
					$ids[] = ( $order->product_id );
			}
			foreach ( $products as $key=>$product ) {
				if ( in_array( $product->ID, $ids )) {
					unset($products[$key]);	continue;
				}
				if($type=='relist' and !is_relist($product->ID)){
					unset($products[$key]); continue;
				}
				if($type=='unsold' and is_relist($product->ID)){
					unset($products[$key]);	continue;
				}
			}
			return $products;
		}
	}
}
new WD_SHORTCODES();
