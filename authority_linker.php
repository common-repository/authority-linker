<?php
/*
Plugin Name: Authority Linker
Plugin URI: 
Description: The plugin (when activated) adds 2 - 4 links for each post or page automatically and randomly. 
Version: 1.0.0
Author: Pridglobe
Author URI: http://www.prideglobe.com
*/


/*


This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

//authority_linker
register_activation_hook( __FILE__,'wp_authority_linker_register' );
add_action('admin_menu','wp_authority_linker_menu');
add_filter('the_content','wp_authority_linker_inject_links');
add_action('add_meta_boxes','wp_authority_linker_register_meta_box');
add_action('save_post','wp_authority_linker_meta_data',10,2);
function wp_authority_linker_register_meta_box() 
{
	add_meta_box('wp_authority_linker_tick_meta_box','Authority Linker Options','wp_authority_linker_tick_meta_box','post','side');
	add_meta_box('wp_authority_linker_tick_meta_box','Authority Linker Options','wp_authority_linker_tick_meta_box','page','side');	
}
function wp_authority_linker_tick_meta_box($post)
{
	// Retrieve is allow to add link status
	$post_authority_linker_status	=	esc_html(get_post_meta($post->ID,'post_authority_linker_status',true));
?>
<!-- Display fields to enter authority linker status -->
<table>
  <tr>
    <td style="width: 100px">Allow to add Links</td>
    <td><input type="checkbox" name="post_authority_linker_status" value="1" <?php if($post_authority_linker_status == '1') echo 'checked="checked"'; ?> />
    </td>
  </tr>
</table>
<?php 
}
function wp_authority_linker_meta_data($post_id=false,$post=false) 
{
	// Check post type for posts or pages
	if($post->post_type == 'post' || $post->post_type == 'page') 
	{
		if(isset($_POST['post_authority_linker_status']))
		{
			$post_authority_linker_status	=	$_POST['post_authority_linker_status'];
		}
		else
		{
			$post_authority_linker_status	=	0;
		}
		// Store data in post meta table if present in post data		
		update_post_meta($post_id,'post_authority_linker_status',$post_authority_linker_status);			
	}
}
function wp_authority_linker_register() 
{
	//create table to store URL and its keywords	
	$sql	=	'CREATE TABLE IF NOT EXISTS `wp_authority_linker` (
				  `link_id` bigint(10) NOT NULL AUTO_INCREMENT,
				  `link` text NOT NULL,
				  `keywords` text NOT NULL,
				  PRIMARY KEY (`link_id`)
				) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;';
	
	 //$wpdb->query($sql);
	 
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
	//add mix link repacement count defualt value 4
	if(get_option('wp_authority_linker_max_links') === false) 
	{
		add_option('wp_authority_linker_max_links', "4");
	}//failure url
}	

function wp_authority_linker_menu() 
{	
	add_menu_page( 'Authority Linker', 'Authority Linker', 'manage_options', 'wp-authority-linker-settings-menu', 'wp_authority_linker_config_page', '', 55 ); 
}

function do_addlinks_actions($page,$post,$type) 
{  
	global $wpdb;
	$_POST = $post;
	/*echo '<pre>';
	print_r($_POST);
    echo '</pre>';*/
	// Save the settings //
	if(isset($_POST['Save'])) 
	{	
		$err_flg	=	0;
		// Get settings and do some validation 
		$table_name	= "wp_authority_linker";   
		if(trim($type) == 'add') 
		{		
			if($post['linkinfo']['al_link_url'] == '')
			{
				queue_message( __('Please enter Link', 'wp_authority_linker' ), 'error' );
				$err_flg	=	1;	
			}
			if($post['linkinfo']['al_keywords'] == '')
			{
				queue_message( __('Please enter Keywords', 'wp_authority_linker' ), 'error' );
				$err_flg	=	1;	
			}
			
			## URL check  
			if($post['linkinfo']['al_link_url'] != '')
			{				
				if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $post['linkinfo']['al_link_url'])) {	
					queue_message( __( 'Invalid URL', 'wp_authority_linker' ), 'error' );	
					$err_flg	=	1;		  
				}	
			}											
			##
			
			## Check keyword is exists or not	
			/*if($post['linkinfo']['al_keywords'] != '')
			{					
				$keywords_exists	=	'';
				$arr_keywords		=	explode(",",$post['linkinfo']['al_keywords']);
				//echo '<pre>';
				//print_r($arr_keywords);
    			//echo '</pre>'; die;
				foreach ($arr_keywords as $key => $value)
				{	
					$sql 			= 	"SELECT * FROM wp_authority_linker WHERE keywords LIKE '%".$value."%'";
					$results 		= 	$wpdb->get_results($sql);	
					
					if($results)
					{
						$keywords_exists	.= $value.'<br>';						
						$err_flg	=	1;
					}				 
				}	  	
				
				if($keywords_exists != '')
				{	
					queue_message( __('Following Keywords are already exists.<br>'.$keywords_exists, 'wp_authority_linker' ), 'error' );	
				}	
			}	*/
			##
			
			if($err_flg == 0)
			{
				//die;
				// Update database option and get response 
				$wpdb->insert($table_name, array(
					'link'=>$post['linkinfo']['al_link_url'], 
					'keywords'=>$post['linkinfo']['al_keywords']
				));
				$getLinkID = $wpdb->insert_id;
				if($getLinkID > 0) 
				{
					// Show update message 
					queue_message( __( 'New Link has been <strong>saved</strong> successfully.', 'wp_authority_linker' ), 'updated' );
					$_POST['linkinfo']['al_link_url'] = '';
					$_POST['linkinfo']['al_keywords'] = '';
				}
				else 
				{
				   queue_message( __( 'Error!Please Try Again', 'wp_authority_linker' ), 'error' );
				}
			}	
		}
		elseif(trim($type) == 'edit') 
		{
			if($post['linkinfo']['al_link_url'] == '')
			{
				queue_message( __('Please enter Link', 'wp_authority_linker' ), 'error' );
				$err_flg	=	1;	
			}
			if($post['linkinfo']['al_keywords'] == '')
			{
				queue_message( __('Please enter Keywords', 'wp_authority_linker' ), 'error' );
				$err_flg	=	1;	
			}
			
			## URL check  
			if($post['linkinfo']['al_link_url'] != '')
			{				
				if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $post['linkinfo']['al_link_url'])) {	
					queue_message( __( 'Invalid URL', 'wp_authority_linker' ), 'error' );	
					$err_flg	=	1;		  
				}	
			}											
			##
			/*
			## Check keyword is exists or not	
			if($post['linkinfo']['al_keywords'] != '')
			{					
				$keywords_exists	=	'';
				$arr_keywords		=	explode(",",$post['linkinfo']['al_keywords']);
				//echo '<pre>';
				//print_r($arr_keywords);
    			//echo '</pre>'; die;
				foreach ($arr_keywords as $key => $value)
				{	
					$sql 			= 	"SELECT * FROM wp_authority_linker WHERE link_id != ".$_POST['link_id']." AND keywords LIKE '%".$value."%'";
					$results 		= 	$wpdb->get_results($sql);	
					
					if($results)
					{
						$keywords_exists	.= $value.'<br>';						
						$err_flg	=	1;
					}				 
				}	  	
				
				if($keywords_exists != '')
				{	
					queue_message( __('Following Keywords are already exists.<br>'.$keywords_exists, 'wp_authority_linker' ), 'error' );	
				}	
			}	*/
			##
			
			if($err_flg == 0)
			{
				// Update database option and get response 
				$wpdb->update(
					$table_name,
					array(
						'link'=>$post['linkinfo']['al_link_url'], 
						'keywords'=>$post['linkinfo']['al_keywords']
					),
					array( 'link_id' => intval(trim($_POST['link_id'])) ) 
				);
			}	
			// Show update message 
			return queue_message( __( 'Link has been <strong>updated</strong> successfully.', 'wp_authority_linker' ), 'updated' );
		}
	} 
}
function wp_authority_linker_inject_links($content)
{ 
	$post_id = get_the_ID();
	$post_authority_linker_status = get_post_meta($post_id,'post_authority_linker_status',true);
	if($post_authority_linker_status == 1)
	{	
		$wp_authority_linker_max_links	=	get_option('wp_authority_linker_max_links');
		if($wp_authority_linker_max_links < 1 || $wp_authority_linker_max_links == '')
		{
			return $content;
		}
		## fetch all keywords - Start
		global $wpdb;

		$sql 				= 	"SELECT * FROM wp_authority_linker order by rand()";
		$results 			= 	$wpdb->get_results($sql);	
		$arr_link			=	array();
		$patterns_scram		=	array();
		$replacements_scram	=	array();
		foreach ($results as $result)
		{		
			$keywords		=	$result->keywords;	
			$link			=	$result->link;
			if(!in_array($link,$arr_link))
			{				   		
				$arr_keywords	=	explode(",",$keywords);	
				
				foreach ($arr_keywords as $key => $value)
				{   
					$keyword	=	ltrim($value);        
					$keyword 	=	rtrim($keyword);
					
					$regex 		= 	'/\b'.$keyword.'\b/i';
					$srch_cnt	=	preg_match_all($regex, $content,$match);					
					if($srch_cnt > 0)
					{					
						//Scramble replace						
						$replacement	=   base64_encode("<a href='".$link."' target='_blank'>$keyword</a>");
						//$content		=	preg_replace_callback($regex, $replacement, $content ,1);
						
						$callback 		= 	new wp_authority_linker_callback($replacement,$srch_cnt);
						$content 		= 	preg_replace_callback($regex, array($callback, 'callback'), $content);
						
						$patterns_scram[]		=	$replacement;
						$replacements_scram[]	=	"<a href='".$link."' target='_blank'>$keyword</a>";	
						
						$arr_link[]	=	$link;
						break;
					}//if kyword found
				}//foreach keyword
				if(count($arr_link) >= $wp_authority_linker_max_links)
				{
					break;
				}//if link count reaches to maximum then stop replacement
			}//if link is already added
		}//foreach each links	
		if(count($patterns_scram) > 0)
		{		
			/*echo $content;
			echo '<pre>';
			print_r($patterns_scram);
			print_r($replacements_scram);
			echo '</pre>';	*/
			$content  = str_replace($patterns_scram, $replacements_scram, $content);							
		}//if scrambled replacement is poresent then bring to origianl format
	}//if link addition applicable for post/page	
		
 	return $content;
}
function do_getLink_info( $id ) 
{ 
	 global $wpdb;
	 $table_name     = "wp_authority_linker";
	 $sql 		     = "SELECT * FROM $table_name WHERE `link_id`='".$id."'";
	 return $results = $wpdb->get_row($sql);
}
function do_dumpLink_actions()
{
	/*echo '<pre>';
	print_r($_FILES);
	echo '</pre>';*/	
	global $wpdb;  
	if ($_FILES[bulk_links_file][size] > 0) 
	{
		//get the csv file
		$file = $_FILES[bulk_links_file][tmp_name];
		$handle = fopen($file,"r");
		$table_name	= "wp_authority_linker";  
		//loop through the csv file and insert into database
		$import_count	=	0;
		$total_count	=	0;
		do 
		{
			if ($data[0]) 
			{				
				$err_flg	=	0;
				$post['linkinfo']['al_link_url']	=	$data[0];
				$post['linkinfo']['al_keywords']	=	$data[1];
				// Get settings and do some validation			 
				
				if($post['linkinfo']['al_link_url'] == '')
				{
					//queue_message( __('Please enter Link', 'wp_authority_linker' ), 'error' );
					$err_flg	=	1;	
				}
				if($post['linkinfo']['al_keywords'] == '')
				{
					//queue_message( __('Please enter Keywords', 'wp_authority_linker' ), 'error' );
					$err_flg	=	1;	
				}
				
				## URL check  
				if($post['linkinfo']['al_link_url'] != '')
				{				
					if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $post['linkinfo']['al_link_url'])) {	
						//queue_message( __( 'Invalid URL', 'wp_authority_linker' ), 'error' );	
						$err_flg	=	1;		  
					}	
				}			
				##				
				if($err_flg == 0)
				{
					//die;
					// Update database option and get response 
					$wpdb->insert($table_name, array(
						'link'=>$post['linkinfo']['al_link_url'], 
						'keywords'=>$post['linkinfo']['al_keywords']
					));
					$getLinkID = $wpdb->insert_id;
					if($getLinkID > 0) 
					{
						$import_count++;
					}
					else 
					{
					   queue_message( __( 'Error!Please Try Again', 'wp_authority_linker' ), 'error' );
					}
					
				}
				$total_count++;			
			}
		} while ($data = fgetcsv($handle,1000,",",'"'));
		if($total_count > 0)
		{
			return queue_message( __( "$import_count out of $total_count links has been <strong>added</strong> successfully. ", 'wp_authority_linker' ), 'updated' );
		}	
	}	
}
function do_updMaxLink_actions()
{	
	if($_POST['wp_authority_linker_max_links'] != '')
	{
		if(preg_match('/^\d+$/',$_POST['wp_authority_linker_max_links'])) 
		{
		  $wp_authority_linker_max_links	=	sanitize_text_field($_POST[wp_authority_linker_max_links]);
		  update_option('wp_authority_linker_max_links', $wp_authority_linker_max_links);	
		  return queue_message( __( "Max link count has been <strong>updated</strong> successfully. ", 'wp_authority_linker' ), 'updated' );
		} 
		else 
		{
		    return queue_message( __( "Max link count must be <strong>positive integer</strong> ", 'wp_authority_linker' ), 'error' );
		}
	}
	else
	{
		return queue_message( __( "Max link count can not be <strong>empty</strong>. ", 'wp_authority_linker' ), 'error' );
	}
}
function wp_authority_linker_config_page() 
{
	//links_tab
?>
	<div class="wrap">
		<div id="icon-themes" class="icon32"><br></div>
			<h2><?php _e( 'Links Keyword Section', 'wp_authority_linker' ); ?></h2>
				<div class="main-panel">
<?php
    $response = '';
	/** Get the TABS */
	$currentTab = $_GET['tab'] ? trim($_GET['tab']) : 'links_tab';
    if ( isset( $_POST['Save'] ) ) 
	{
	   if($_POST['add-link-submit'] == 'Y' && trim($_GET['tab']) == 'addnewlinks_tab') 
	   {   		
		   do_addlinks_actions($_GET['page'],$_POST,'add');
	       //WC_MercadoLibre::get_instance()->do_addvendorSupplier_actions( $_GET['page'],$_POST,'add' );
		   $currentTab = 'addnewlinks_tab';
	   }
	}//save link
	if ( isset( $_POST['Save'] ) ) 
	{
	   if($_POST['link-edit'] == 'Y' && trim($_GET['tab']) == 'editlinks_tab') 
	   {	   	   
	       do_addlinks_actions($_GET['page'],$_POST,'edit');
		   $currentTab = 'editlinks_tab';
	   }
	}//update link
	if ( isset( $_POST['Save'] ) ) 
	{
	   if($_POST['upload-link-submit'] == 'Y' && trim($_GET['tab']) == 'importlinks_tab') 
	   {				  
		   do_dumpLink_actions();       
		   $currentTab = 'importlinks_tab';
	   }
	}//upload links via csv	 
	if ( isset( $_POST['Save'] ) ) 
	{
	   if($_POST['max-link-update'] == 'Y' && trim($_GET['tab']) == 'links_setting') 
	   {				  
		   do_updMaxLink_actions();       
		   $currentTab = 'links_setting';
	   }
	}//upload links via csv	 
	//
	
	if ( isset( $_REQUEST['action'] ) ) 
	{
	   if($_REQUEST['action'] == 'edit' && trim($_REQUEST['tab']) == 'editlinks_tab') 
	   {   	 
	      $currentTab   = 'editlinks_tab';
		  $LinkInfo = do_getLink_info(intval(trim($_REQUEST['link_id'])));	  
	   } 
	   if($_POST['action'] == 'csvExport' && $currentTab == 'links_tab') { 
	   	   //die("csvExport");	
	       generate_csv( $_GET['page'],$_POST);
	   }
	}
	
	$tabNames  = array( 'links_tab' => 'Links', 'addnewlinks_tab' => 'Add New Link' ,'importlinks_tab' => 'Import Links','links_setting' => 'Setting','editlinks_tab' => 'Edit Link',);
    $tabs      = wp_authority_linker_admin_tabs( $currentTab , $tabNames);
	echo $tabs;
	$pagenow = $_GET['page'] ? trim($_GET['page']) : '';
	$action  = ''; 	
?>
	<div class="messages-container">
		<?php do_action( 'wp_authority_linker_admin_messages' ); ?>
	</div>
	<form method="post" name="wp_authority_linker_edit_link_section" action="<?php echo $_SERVER['REQUEST_URI']; ?>" enctype="multipart/form-data">
	<?php
	wp_nonce_field( "authority-linker-page" ); 
	/** Security nonce fields */
	wp_nonce_field('update-options');
	//wp_nonce_field( "woocommerce_mercadolibre-save_{$_GET['page']}", "woocommerce_mercadolibre-save_{$_GET['page']}", false );

	if($pagenow == 'wp-authority-linker-settings-menu')
	{
   		switch ($currentTab)
		{
      		case 'links_tab':
				echo "<BR />";
				displayLinkList();;
				if(trim($_REQUEST['action']) == 'delete')
				   do_action( 'woocommerce_mercadolibre_admin_messages' );
?>
         		<input type="hidden" name="page" value="woocommerce_mercadolibre_edit_vendor_supplier_section&tab=addnewsupplier_tab">
         <?php
      			break;
				
     		case 'addnewlinks_tab' :
	       		echo '<table class="form-table">';
         ?>
			 <tr>
				<td align="left" scope="row" width="15%">
					<label>Link (URL)</label>
				</td> 
				<td align="left" scope="row">
					<input type="text" name="linkinfo[al_link_url]" id="al_link_url" value="<?php echo $_POST['linkinfo']['al_link_url']; ?>" size="50"/>&nbsp; e.g. http://www.example.com 
				</td> 
			 </tr>
			 <tr>
				<td align="left" scope="row">
					<label>Keywords</label>
				</td>
				<td align="left" scope="row">
					<textarea name="linkinfo[al_keywords]" id="al_keywords" cols="55" rows="6"><?php echo $_POST['linkinfo']['al_keywords']; ?></textarea>&nbsp;Use Comma after each keyword (ABC,PQR,etc).
				</td> 
			 </tr>
			 </table>		 
			 <p class="submit" style="clear: both;">
				  <input type="submit" name="Save" id="Save" class="button-primary" value="Add Link" />
				  <input type="hidden" name="add-link-submit" value="Y" />
			 </p>
		 <?php    
      			break;
	  		case 'importlinks_tab' :
			echo '<table class="form-table">';
         ?>
			 <tr>
				<td align="left" scope="row" width="15%">
					<label>Uplaod CSV file</label>
				</td> 
				<td align="left" scope="row">
					<input type="file" name="bulk_links_file" id="bulk_links_file" />
				</td> 
			 </tr>	
			 <tr>
				<td align="left" scope="row" width="15%">
					<label>File format</label>
				</td> 
				<td align="left" scope="row">
					"http://www.facebook.com","Facebook, Social Network, Mark Zuckerberg"<br />
					"http://www.yahoo.com","yahoo, email service,free mail"
				</td> 
			 </tr>
			 </table>		 		 
			 <p class="submit" style="clear: both;">
				  <input type="submit" name="Save" id="Save" class="button-primary" value="Upload" />
				  <input type="hidden" name="upload-link-submit" value="Y" />
			 </p>
			 <?php 
				break;
			case 'links_setting' :
			//wp_authority_linker
					if(isset($_POST['wp_authority_linker_max_links']))
					{
						$wp_authority_linker_max_links	=	$_POST['wp_authority_linker_max_links'];
					}
					else
					{
						$wp_authority_linker_max_links	=	get_option('wp_authority_linker_max_links');
					}
			echo '<table class="form-table">';
         ?>
			 <tr>
				<td align="left" scope="row" width="15%">
					<label>Maximum Links</label>
				</td> 
				<td align="left" scope="row">
					<input type="text" name="wp_authority_linker_max_links" id="wp_authority_linker_max_links" value="<?php echo esc_html($wp_authority_linker_max_links); ?>" />
				</td> 
			 </tr>			 
			 </table>		 		 
			 <p class="submit" style="clear: both;">
				  <input type="submit" name="Save" id="Save" class="button-primary" value="Upload" />
				  <input type="hidden" name="max-link-update" value="Y" />
			 </p>
			 <?php 
				break;
	
			case 'editlinks_tab' :
	       echo '<table class="form-table">';
         ?>
		 <tr>
				<td align="left" scope="row" width="15%">
					<label>Link (URL)</label>
				</td> 
				<td align="left" scope="row">
					<input type="text" name="linkinfo[al_link_url]" id="al_link_url" value="<?php echo @$LinkInfo->link; ?>" size="50"/>&nbsp; e.g. http://www.example.com 
				</td> 
			 </tr>
			 <tr>
				<td align="left" scope="row">
					<label>Keywords</label>
				</td>
				<td align="left" scope="row">
					<textarea name="linkinfo[al_keywords]" id="al_keywords" cols="55" rows="6"><?php echo @$LinkInfo->keywords; ?></textarea>&nbsp;Use Comma after each keyword (ABC,PQR,etc).
				</td> 
			 </tr>
			 </table>
        
		 </table>        
		   <p class="submit" style="clear: both;">
			  <input type="submit" name="Save" id="Save" class="button-primary" value="Update Link" />
			  <input type="hidden" name="link-edit" value="Y" />
			  <input type="hidden" name="link_id" value="<?php echo @$LinkInfo->link_id; ?>" />
		   </p>
		 <?php    
      break;
   }
}
?>
</form>
<form id="frm_vendor_supplier_common" name="frm_vendor_supplier_common">
<input type="hidden" name="action" id="action">
<input type="hidden" name="supplier" id="supplier">
<input type="hidden" name="tab" id="tab">
</form>
</div>
</div>
<?php
}
/**
* Script to produce the Vendor Supplier TABS.
* 
*/
function wp_authority_linker_admin_tabs( $current = 'links' , $tabs) 
{
	//$output  = '<div id="icon-themes" class="icon32"><br></div>';
	$output .= '<h2 class="nav-tab-wrapper">';
	foreach( $tabs as $tab => $name ){
		$href	=	"?page=wp-authority-linker-settings-menu&tab=$tab";
		$class = ( $tab == $current ) ? ' nav-tab-active' : '';
		//$style = ( $tab == 'editlinks_tab' ) ? 'style="display:none;"' : '';
		if($tab == 'editlinks_tab')
		{
			$style	=	'style="display:none;"';
			$href	=	'';
		}
		if($current == 'editlinks_tab')
		{
			$style = '';
		}
			
		$output .= "<a class='nav-tab$class' href='$href' ".$style.">$name</a>";

	}
	$output .= '</h2>';
	return $output;
}

function displayLinkList()
{
	  global $wpdb;
	  
	  //echo "<pre>"; print_r($_REQUEST); echo "<pre>";die;
	  $table_name  = $wpdb->prefix . "authority_linker";
	  if(isset($_REQUEST['action'])) {
		 if($_REQUEST['action'] != '')
		 {
		   if(trim($_REQUEST['action']) == 'delete')
		   {
			  if(!is_array($_REQUEST['link_id'])) {
				  if(intval(trim($_REQUEST['link_id'])) > 0)
				  {
					//die("is first");
					$delsql  = "DELETE FROM $table_name WHERE $table_name.`link_id` = '".intval(trim($_REQUEST['link_id']))."'";
					$wpdb->query($delsql);
				  }	
			  }
			  else {
				  if(count($_REQUEST['link_id']) > 0)
				  {
						//die("is 2ND");
					$delsql  = "DELETE FROM $table_name WHERE $table_name.`link_id` IN (".implode(',',$_REQUEST['link_id']).")";
					$wpdb->query($delsql);
				  }
			  }
			  // Show update message 
			  queue_message( __( 'Link has been <strong>deleted</strong> successfully.', 'wp_authority_linker' ), 'updated' );
		   }
		 }
	  }
	  
	  $sql 		   = "SELECT * FROM $table_name ";
	  if(isset($_REQUEST['s'])) {
		 if(trim($_REQUEST['s']) != '') {
			$sql  .= "WHERE `link` LIKE '%".trim($_REQUEST['s'])."%' OR `keywords` LIKE '%".trim($_REQUEST['s'])."%' ";
		 }
	  }
	  $sql 		  .= "ORDER BY `link_id` DESC";
	  $results     = $wpdb->get_results($sql);
	  $recordsArr  = array();
	  if($wpdb->num_rows)
	  {
		foreach ($results as $result)
		{
			$recordsArr[] = array('link_id' => $result->link_id,'link' => $result->link, 'keywords' => $result->keywords);
		}
	  }    
	  $filename = dirname( __FILE__ ) . '/link_list_table.php';
	  require_once( $filename );
	  global $linkListTable;
	  $option = 'per_page';
	  $args = array(
			 'label' => 'Links',
			 'default' => 10,
			 'option' => 'links_per_page'
			 );
	  add_screen_option( $option, $args );
	  $linkListTable = new Link_List_Table();
	  $linkListTable->link_data = $recordsArr;
	  $linkListTable->prepare_items();
	  $linkListTable->search_box( 'search', 'search_id' );
	  $linkListTable->display();
}
/**
* Script to produce the CSV
* 
*/	
function generate_csv( $page,$post ) 
{ 
				 $sitename = sanitize_key( get_bloginfo( 'name' ) );
				 if ( ! empty( $sitename ) )
					$sitename .= '.';
				 $filename = $sitename . 'links.' . date( 'Y-m-d-H-i-s' ) . '.csv';
                 ob_end_clean();
				 header( 'Content-Description: File Transfer' );
				 header( 'Content-Disposition: attachment; filename=' . $filename );
				 header( 'Content-Type: text/csv; charset=' . get_option( 'blog_charset' ), true );
				 
				 $exclude_data = apply_filters( 'pp_eu_exclude_data', array() );
				 
				 global $wpdb;
				 
				 $fields       = array('ID', 'Links', 'keywords');
				 $dbfields     = array('link_id', 'link', 'keywords');
				 $headers = array();
				 
				 foreach ( $fields as $key => $field ) {
					if ( in_array( $field, $exclude_data ) )
						unset( $fields[$key] );
					else
						$headers[] = '"' . $field . '"';
				 }
			
				 echo implode( ',', $headers ) . "\n";
        	     $table_name     = $wpdb->prefix . "authority_linker";
			     $sql 		     = "SELECT * FROM $table_name ";
				 if(is_array($_REQUEST['link_id'])) {
				    if(count($_REQUEST['link_id']) > 0) {
					    $sql 	.= "WHERE $table_name.`link_id` IN (".implode(',',$_REQUEST['link_id']).") ";
						//$sql 	.= "WHERE $table_name.`link_id` IN (3,9) ";
					}
				 }
				 $sql .= "ORDER BY `link_id` ASC";
			     $results     = $wpdb->get_results($sql);
				 //echo "<pre>"; print_r($results); echo "<pre>"; die;
				 if($wpdb->num_rows)
				 {
				 	
					foreach ($results as $result)
					{
						
					  $data = array();
					  foreach ( $dbfields as $field ) {
							$value = isset( $result->{$field} ) ? $result->{$field} : '';
							$value = is_array( $value ) ? serialize( $value ) : $value;
							$data[] = '"' . str_replace( '"', '""', $value ) . '"';
					  }
					  echo implode( ',', $data ) . "\n"; 
					}
				 }
				 exit;
			 }			
			
function queue_message( $text, $type ) 
{
	$message = "<div class='message $type' id='dv_message'><p>$text</p></div>";
	//return $message;
	add_action( 'wp_authority_linker_admin_messages', create_function( '', 'echo "'. $message .'";' ) );
} 
class wp_authority_linker_callback 
{
    private $replacement;
	private $srch_cnt;
	private $match_cnt;
	private $rand_val;

    function __construct($replacement,$srch_cnt) 
	{
        $this->replacement 	= $replacement;
		$this->srch_cnt 	= $srch_cnt;
		$this->match_cnt 	= 0;
		if($this->srch_cnt > 1)
		{
			$this->rand_val	=	rand(1, $this->srch_cnt);
		}
		else
		{
			$this->rand_val	=	1;
		}
    }

    public function callback($matches) 
	{
		$this->match_cnt++;
		if($this->match_cnt == $this->rand_val)
		{
        	return $this->replacement;
    	}
		else
		{			
			return $matches[0];
		}
	}
}
?>