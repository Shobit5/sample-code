<?php
add_action('admin_head', 'my_custom_fonts');
function my_custom_fonts() {
  echo '<style>.woocommerce #woocommerce_extensions{ display:none; }</style>';
}

/* Customer Default Address */
function get_customer_default_fields(){
	return $show_fields = array(
		'Customer Address' => array(
			'title' => __( 'Customer Address', 'woocommerce' ),
			'fields' => array(
				'company' => array(
						'label' => __( 'Company', 'woocommerce' ),
						'description' => ''
					),
				'street_address' => array(
						'label' => __( 'Address 1', 'woocommerce' ),
						'description' => ''
					),
				'street_address_2' => array(
						'label' => __( 'Address 2', 'woocommerce' ),
						'description' => ''
					),
				'city' => array(
						'label' => __( 'City', 'woocommerce' ),
						'description' => ''
					),
				'zipcode' => array(
						'label' => __( 'Postcode', 'woocommerce' ),
						'description' => ''
					),
				'state' => array(
						'label' => __( 'State/County', 'woocommerce' ),
						'description' => __( 'Country or state code', 'woocommerce' ),
					),
				'country' => array(
						'label' => __( 'Country', 'woocommerce' ),
						'description' => __( '2 letter Country code', 'woocommerce' ),
					),
				'phone' => array(
						'label' => __( 'Telephone', 'woocommerce' ),
						'description' => ''
					)
			)
		)
	);	
}
function customer_default_fields($user){
	if ( ! current_user_can( 'manage_woocommerce' ) )
		return;
	$show_fields = get_customer_default_fields();

	foreach( $show_fields as $fieldset ) :
		?>
		<h3><?php echo $fieldset['title']; ?></h3>
		<table class="form-table">
			<?php
			foreach( $fieldset['fields'] as $key => $field ) :
				?>
				<tr>
					<th><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
					<td>
						<input type="text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( get_user_meta( $user->ID, $key, true ) ); ?>" class="regular-text" /><br/>
						<span class="description"><?php echo wp_kses_post( $field['description'] ); ?></span>
					</td>
				</tr>
				<?php
			endforeach;
			?>
		</table>
		<?php
	endforeach;
}
add_action( 'show_user_profile', 'customer_default_fields', 10 );
add_action( 'edit_user_profile', 'customer_default_fields', 10 );

function save_customer_default_fields( $user_id ) {
	if ( ! current_user_can( 'manage_woocommerce' ) )
		return $columns;

 	$save_fields = get_customer_default_fields();
 	foreach( $save_fields as $fieldset )
 		foreach( $fieldset['fields'] as $key => $field )
 			if ( isset( $_POST[ $key ] ) )
 				update_user_meta( $user_id, $key, woocommerce_clean( $_POST[ $key ] ) );
}
add_action( 'personal_options_update', 'save_customer_default_fields' );
add_action( 'edit_user_profile_update', 'save_customer_default_fields' );
/* End Customer Default Address */


// Extra Fields of Products */
add_action( 'woocommerce_product_options_general_product_data', 'woo_add_custom_general_fields' );
function woo_add_custom_general_fields() {
	global $woocommerce, $post;
	$fields = array('text:_treasure_location', 'text:_condition', 'text:_end_date', 'textarea:_condition_description');
	
	echo '<div class="options_group">';
	foreach($fields as $val){
		list($type, $field)=explode(':', $val);

		if($type=='text' || $type=='textarea')
			woocommerce_fields($type, $field);
	}
	echo '</div>';
}


/* Shipping Method and Dimension of Products on Admin */
add_action( 'woocommerce_product_options_dimensions', 'woo_custom_shipping_fields' );
function woo_custom_shipping_fields(){
	global $post, $woocommerce;
	$shippings = get_post_meta($post->ID, '_shipping', true);
	if($load_shippings = $woocommerce->shipping->load_shipping_methods()){
		foreach($load_shippings as $key=>$method){
			if($method->enabled != 'yes'){
				unset($load_shippings[$key]);
			}
		}
		$shippings = $load_shippings;
	}
	
	foreach($shippings as $ship){
		$ship_price = $checked = '';
		if($ship->id == 'local_pickup'){
			if(get_post_meta($post->ID, '_shipping_buyer_pick_up', true)=='yes')
				$checked = ' checked="checked" ';
		}else{
			$ship1 = get_post_meta($post->ID, '_shipping', true);
			$ship_info = get_post_meta($post->ID, '_shipping_info', true);
			if(($ship->id == 'ups' || $ship->id == 'fedex' || $ship->id == 'usps') and $ship_info['shipping']=="shipping_calculated" and $ship_info['shipping_type']==$ship->id){
				$checked = ' checked="checked" '; 
			}elseif($ship->id == $ship1 and $ship->id == $ship_info['shipping']){
				$checked = ' checked="checked" ';
				$ship_price = $ship_info['shipping_price'];
			}
		}
		?> 
        <p><label for="ship_<?php echo $ship->id ?>"><?php echo $ship->title; ?></label>
		<input type="checkbox" <?php echo $checked; ?> value="<?php echo $ship->id; ?>" name="ship[]" id="ship_<?php echo $ship->id ?>" />
		  
        <?php if($ship->id=='package_shipping_method' || $ship->id=='USPS_shipping_method'){
			echo '<input type="text" value="'.$ship_price.'" name="'.$ship->id.'_price" style="width:40px;" />';
		} ?>
        </p>
		<?php
	 } 
}

add_action( 'woocommerce_process_product_meta', 'woo_add_custom_general_fields_save' );
function woo_add_custom_general_fields_save( $post_id ){
	$fields = array('text:_treasure_location', 'text:_condition', 'text:_end_date', 'textarea:_condition_description');
	foreach($fields as $val){
		list($type, $field)=explode(':', $val);
		if(isset($_POST[$field])){
			update_post_meta( $post_id, $field, $_POST[$field] );	
		}
	}
	if($_POST['ship']){
		foreach($_POST['ship'] as $ship){
			if($ship == 'local_pickup'){
				update_post_meta($post_id, '_shipping_buyer_pick_up', 'yes'); 
			}elseif($ship == 'ups' || $ship == 'usps' || $ship == 'fedex'){
				update_post_meta($post_id, '_shipping', 'shipping_calculated'); 
				update_post_meta($post_id, '_shipping_info', array('shipping'=>'shipping_calculated', 'shipping_type'=>$ship) ); 
			}elseif($ship == 'package_shipping_method'){
				update_post_meta($post_id, '_shipping', 'package_shipping_method'); 
				update_post_meta($post_id, '_shipping_info', array('shipping'=>'package_shipping_method', 'shipping_price'=>$_POST['package_shipping_method_price']) ); 
			}elseif($ship == 'USPS_shipping_method'){
				update_post_meta($post_id, '_shipping', 'USPS_shipping_method'); 
				update_post_meta($post_id, '_shipping_info', array('shipping'=>'USPS_shipping_method', 'shipping_price'=>$_POST['USPS_shipping_method_price']) ); 
			}
			
		}
	}
}
/* End Shipping */

function woocommerce_fields($key='text', $field){
	$text = ucwords(trim(str_replace('_', ' ', $field)));
	
	if($key=='textarea'){
		woocommerce_wp_textarea_input(
			array(
			'id' => $field,
			'label' => __($text, 'woocommerce' ),
			'placeholder' => $text,
			)
		);
	}elseif($text == 'End Date'){
		woocommerce_wp_text_input(
			array(
			'id' => $field,
			'label' => __($text, 'woocommerce' ),
			'placeholder' => end(explode(' ',$text)),
			'class' => 'short datetimepicker',
			)
		);
	}else{
		woocommerce_wp_text_input(
			array(
			'id' => $field,
			'label' => __($text, 'woocommerce' ),
			'placeholder' => end(explode(' ',$text)),
			)
		);
	}
}

/* Payment Method of Products on Admin */
add_action( 'add_meta_boxes', 'wpp_meta_box_add' );  
function wpp_meta_box_add()  
{	
	add_meta_box( 'payments', 'Payments', 'wpp_payments_form', 'product', 'side', 'core' );  
	add_meta_box( '_is_owner', 'Owner Product', 'wd_is_owner', 'product', 'side', 'core' );  
}

function wpp_payments_form()  
{
	global $post, $woo;
	$postPayments = count( get_post_meta($post->ID, 'payments', true) ) ? get_post_meta($post->ID, 'payments', true) : array();
	$woo = new WC_Payment_Gateways();
	$payments = $woo->get_available_payment_gateways();
	foreach($payments as $pay){
		$checked = '';
		if( is_array( $postPayments ) && in_array($pay->id, $postPayments)) $checked = ' checked="yes" ';   
		echo '<input type="checkbox" '.$checked.' value="'.$pay->id.'" name="pays[]" id="payment_'.$pay->id.'" />
		<label for="payment_'.$pay->id.'">'.$pay->title.'</label><br />';
	}      
} 

add_action('save_post', 'wpp_meta_box_save', 10, 2 );
function wpp_meta_box_save( $post_id )  
{   
	if($_POST['post_type']=='product'){
		$payments = array();
		if($_POST['pays']){
			foreach($_POST['pays'] as $pay)
				$payments[] = $pay;
		}		
		update_post_meta($post_id, 'payments', $payments); 
	}
}
/* End Payment */

/* Product Owner */
function wd_is_owner(){
	global $post;
	$select = (!$_GET['post'] || get_post_meta( $post->ID, '_is_owner_product', true )=="yes")?'checked="checked"':'';
	echo '<div class="form-field">
		<label for="owner_prod">Is Owner Product</label>
        <input style="width:auto; margin:0 0 0 10px;" type="checkbox" name="_is_owner_product" id="owner_prod" value="yes" '.$select.' />
	</div>';
}
add_action('save_post', 'wd_is_owner_save', 10, 2 );
function wd_is_owner_save( $post_id )  
{   
	if($_POST['post_type']=='product'){	
		update_post_meta($post_id, '_is_owner_product', $_POST['_is_owner_product']); 
	}
}


/* Product Category Price Field */
function woocommerce_add_category_treasure_price() {
	global $woocommerce;
	echo '<div class="form-field">
		<label for="treasure_price">Treaure Price</label>
        <input type="text" name="treasure_price" id="treasure_price" value="" style="width:100px;" />
	</div>';
}
add_action( 'product_cat_add_form_fields', 'woocommerce_add_category_treasure_price' );

function woocommerce_edit_category_treasure_price( $term, $taxonomy ) {
	global $woocommerce;
	$treasure_price	= get_woocommerce_term_meta( $term->term_id, 'treasure_price', true );
	echo '<tr class="form-field">
		<th scope="row" valign="top"><label>Treaure Price</label></th>
		<td><input type="text" name="treasure_price" id="treasure_price" value="'. $treasure_price .'" style="width:100px;" />	</td>
	</tr>';
}
add_action( 'product_cat_edit_form_fields', 'woocommerce_edit_category_treasure_price', 10,2 );

function woocommerce_save_category_treasure_price( $term_id, $tt_id, $taxonomy ) {
	if ( isset( $_POST['treasure_price'] ) )
		update_woocommerce_term_meta( $term_id, 'treasure_price', esc_attr( $_POST['treasure_price'] ) );
}
add_action( 'created_term', 'woocommerce_save_category_treasure_price', 10,3 );
add_action( 'edit_term', 'woocommerce_save_category_treasure_price', 10,3 );



/* Page Restriction By user Type */
add_action("admin_init", "admin_init");
function admin_init(){  add_meta_box("user_restriction", "User Restriction", "user_restriction", "page", "side", "core"); }
function user_restriction(){
  global $post;
  $selected = 'selected_';
  $selected .= ($ur = get_post_meta($post->ID, "_user_restriction", true))?$ur:0;
  $$selected = 'checked="checked"';
  echo '<style> .first{ width:48%; float:left; } .last{width:48%; float:right; }</style>';
  echo '<p><strong>Who can see this page</strong></p>';
  echo '<div class="first"><input type="radio" name="_user_restriction" value="0" '.$selected_0.' id="selected_0" /><label for="selected_0">For All</label></div>';
  echo '<div class="last"><input type="radio" name="_user_restriction" value="1" '.$selected_1.' id="selected_1" /><label for="selected_1">Guest Only</label></div>';
  echo '<div class="first"><input type="radio" name="_user_restriction" value="2" '.$selected_2.' id="selected_2" /><label for="selected_2">Users Only</label></div>';
  echo '<div class="clear"></div>';
}
add_action('save_post', 'save_details');
function save_details(){
  global $post;
  update_post_meta($post->ID, "_user_restriction", $_POST["_user_restriction"]);
}



/* Vendor Store */
function custom_vendor_fields(){
	global $ignitewoo_vendors;
	add_action( $ignitewoo_vendors->token . '_add_form_fields' , '_add_vendor_fields', 0 );
	add_action( $ignitewoo_vendors->token . '_edit_form_fields' , '_edit_vendor_fields');
}
custom_vendor_fields();
function _add_vendor_fields(){
	echo '<div class="form-field">
		<label for="vendor_google_email">Google email address</label>
		<input type="text" name="vendor_data[google_email]" id="vendor_google_email" value="" /><br/>
		<span class="description">The Google email address of the vendor where their profits will be delivered.</span>
	</div>';
}
function _edit_vendor_fields($vendor){
	global $woocommerce, $ignitewoo_vendors;
	$vendor_id = $vendor->term_id;
	$vendor_data = get_option( $ignitewoo_vendors->token . '_' . $vendor_id );
	$google_email = '';
	if( $vendor_data['google_email'] || strlen( $vendor_data['google_email'] ) > 0 || $vendor_data['google_email'] != '' ) {
		$google_email = $vendor_data['google_email'];
	}
	echo '<tr class="form-field">
		<th scope="row" valign="top"><label for="vendor_google_email">Google email address</label></th>
		<td>
			<input type="text" name="vendor_data[google_email]" id="vendor_google_email" value="'.esc_attr( $google_email ).'" /><br/>
			<span class="description">Vendor Google email address for commission payments</span>
		</td>
	</tr>';
}
