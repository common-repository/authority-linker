<?php
if( ! class_exists( 'WP_List_Table' ) ) {
    	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
class link_List_Table extends WP_List_Table {

    var $link_data = array();
		
    function __construct(){
    global $status, $page;

        parent::__construct( array(
            'singular'  => __( 'link', 'linklisttable' ),     //singular name of the listed records
            'plural'    => __( 'links', 'linklisttable' ),   //plural name of the listed records
            'ajax'      => false        //does this table support ajax?

    ) );

    add_action( 'admin_head', array( &$this, 'admin_header' ) );            

    }

   function admin_header() {
		$page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
		//if( 'my_list_test' != $page )
		//return;
		echo '<style type="text/css">';
		echo '.wp-list-table .column-link_id { width: 20%; }';
		echo '.wp-list-table .column-link { width: 40%; }';
		echo '.wp-list-table .column-keywords { width: 35%; }';
		//echo '.wp-list-table .column-companytype { width: 20%;}';
		echo '</style>';
   }

   function no_items() {
    _e( 'No Links found!!.' );
   }

   function column_default( $item, $column_name ) {
     switch( $column_name ) { 	
	 	 case 'link_id': 	 
        case 'link':
        case 'keywords':        
            return $item[ $column_name ];
        default:
            return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
     }
  }

	function get_sortable_columns() {
	  $sortable_columns = array(	  	
	  	'link_id'  => array('link_id',false),
		'link'  => array('link',false),
		'keywords' => array('keywords',false)
	  );
	  return $sortable_columns;
	}
	
	function get_columns(){
			$columns = array(
				'cb'        => '<input type="checkbox" />',		
				'link_id' => __( 'ID', 'linklisttable' ),		
				'link' => __( 'Links', 'linklisttable' ),
				'keywords'    => __( 'Keywords', 'linklisttable' )
			);
			 return $columns;
		}
	
	function usort_reorder( $a, $b ) {
	  // If no sort, default to title
	  $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'link_id';
	  // If no order, default to asc
	  $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
	  // Determine sort order
	  $result = strcmp( $a[$orderby], $b[$orderby] );
	  // Send final sort direction to usort
	  return ( $order === 'asc' ) ? $result : -$result;
	}
	
	function column_link_id($item){	 
	  /*$actions = array(
				'edit'      => sprintf('<a href="javascript:void(0);" onclick="javascript:doSupplierEdit(\'%s\',\'%s\',\'%s\',\'%s\');">Edit</a>',$_REQUEST['page'],$_REQUEST['tab'],'edit',$item['link_id']),
				'delete'    => sprintf('<a href="javascript:void(0);" onclick="javascript:confirmSupplierDelete(\'%s\',\'%s\',\'%s\',\'%s\');">Delete</a>',$_REQUEST['page'],$_REQUEST['tab'],'delete',$item['link_id']),
			);*/
	  $edit_url		=	$_SERVER['PHP_SELF']."?page=".$_REQUEST['page']."&tab=editlinks_tab&action=edit&link_id=".$item['link_id'];
	  $delete_url	=	$_SERVER['PHP_SELF']."?page=".$_REQUEST['page']."&tab=".$_REQUEST['tab']."&action=delete&link_id=".$item['link_id'];
	  		
	  $actions = array(
				'edit'      => sprintf('<a href="%s">Edit</a>',$edit_url),
				'delete'    => sprintf('<a href="%s" onclick="return confirm(\'Do you really want to delete this record?\')">Delete</a>',$delete_url),
			);		
	
	  return sprintf('%1$s %2$s', $item['link_id'], $this->row_actions($actions) );
	}
	
	function get_bulk_actions() {
	  $actions = array(
		'delete'    => 'Delete',
		'csvExport' => 'CSV Export'
	  );
	  return $actions;
	}
	
	function column_cb($item) {
			return sprintf(
				'<input type="checkbox" name="link_id[]" value="%s" />', $item['link_id']
			);    
		}
	
	function prepare_items() {
	  $columns  = $this->get_columns();
	 
	  $hidden   = array();
	  $sortable = $this->get_sortable_columns();
	  $this->_column_headers = array( $columns, $hidden, $sortable );
	  usort( $this->link_data, array( &$this, 'usort_reorder' ) );
	  
	  $per_page = 5;
	  $current_page = $this->get_pagenum();
	  $total_items = count( $this->link_data );
	
	  // only ncessary because we have sample data
	  $this->found_data = array_slice( $this->link_data,( ( $current_page-1 )* $per_page ), $per_page );
	
	  $this->set_pagination_args( array(
		'total_items' => $total_items,                  //WE have to calculate the total number of items
		'per_page'    => $per_page                     //WE have to determine how many items to show on a page
	  ) );
	  $this->items = $this->found_data;
	}
	
	/**
	 * Display the pagination.
	 *
	 * @since 3.1.0
	 * @access protected
	 */
	function pagination( $which ) {
		if ( empty( $this->_pagination_args ) )
			return;

		extract( $this->_pagination_args, EXTR_SKIP );

		$output = '<span class="displaying-num">' . sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

		$current = $this->get_pagenum();

		parse_str($_SERVER['REQUEST_URI'], $resReqUri);
		$resReqUri['tab'] = 'links_tab';
		$_SERVER['REQUEST_URI'] = urldecode(http_build_query($resReqUri));
		$_SERVER['REQUEST_URI'] = str_replace('admin_php','admin.php',$_SERVER['REQUEST_URI']); 

		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

		$current_url = remove_query_arg( array( 'hotkeys_highlight_last', 'hotkeys_highlight_first' ), $current_url );

		$page_links = array();

		$disable_first = $disable_last = '';
		if ( $current == 1 )
			$disable_first = ' disabled';
		if ( $current == $total_pages )
			$disable_last = ' disabled';

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'first-page' . $disable_first,
			esc_attr__( 'Go to the first page' ),
			esc_url( remove_query_arg( 'paged', $current_url ) ),
			'&laquo;'
		);

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'prev-page' . $disable_first,
			esc_attr__( 'Go to the previous page' ),
			esc_url( add_query_arg( 'paged', max( 1, $current-1 ), $current_url ) ),
			'&lsaquo;'
		);

		if ( 'bottom' == $which )
			$html_current_page = $current;
		else
			$html_current_page = sprintf( "<input class='current-page' title='%s' type='text' name='paged' value='%s' size='%d' />",
				esc_attr__( 'Current page' ),
				$current,
				strlen( $total_pages )
			);

		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
		$page_links[] = '<span class="paging-input">' . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . '</span>';

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'next-page' . $disable_last,
			esc_attr__( 'Go to the next page' ),
			esc_url( add_query_arg( 'paged', min( $total_pages, $current+1 ), $current_url ) ),
			'&rsaquo;'
		);

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'last-page' . $disable_last,
			esc_attr__( 'Go to the last page' ),
			esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
			'&raquo;'
		);

		$pagination_links_class = 'pagination-links';
		if ( ! empty( $infinite_scroll ) )
			$pagination_links_class = ' hide-if-js';
		$output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';

		if ( $total_pages )
			$page_class = $total_pages < 2 ? ' one-page' : '';
		else
			$page_class = ' no-pages';

		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

		echo $this->_pagination;
	}
} //class