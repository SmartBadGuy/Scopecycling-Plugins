<?php
/**
 * Plugin Name: distributor

 
 
 
 
 
 **/

if(!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}
add_action( 'admin_menu', 'distributor_plugin_menu' );

function distributor_plugin_menu() {
	add_options_page( 'Manage distributor', 'Manage distributor', 'manage_options', 'oneguyandacat-manage-distributor', 'oneguyandacat_distributor_home' );
}


function oneguyandacat_distributor_home() {

    global $wpdb, $_wp_column_headers, $fields_distributor;

	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

        $refresh = false;
        if(isset($_GET['remove'])){
            global  $wpdb;
            $table = $wpdb->prefix . "distributor";
            $query = "DELETE FROM `".$table."` WHERE `id` = \"".$_GET['remove']."\"";
            $wpdb->query( $query );
            $refresh = true;
        }  
        if (isset($_POST['submit'])){
            global  $wpdb;
            // find list of events
                $table = $wpdb->prefix . "distributor";
                $query = 'REPLACE INTO `'.$table.'`';
                $keys = array();
                $values = array();
                foreach($fields_distributor as $field) {
                    $keys[] = $field[0];
                    $values[] = addslashes($_POST[$field[0]]);
                }
                    $query.='(`'.implode("`,`", $keys).'`)
                    values ("'.implode('","',$values).'")';
                $wpdb->query( $query );
            $refresh = true;
        }
        if ($refresh){
            echo '<script type="text/javascript">';
            echo '  window.location.href="'.$_SERVER['PHP_SELF'].'?page=oneguyandacat-manage-distributor"';
            echo '</script>';
            die;
        }
        $is_edit = false;
        if (isset($_GET['edit'])){
            global $wpdb;
            $table = $wpdb->prefix . "distributor";
            $distributor = $wpdb->get_results( "SELECT * FROM $table WHERE `id` = \"".$_GET['edit']."\"");
            $distributor = $distributor[0];
            $is_edit = true;
        }
        echo '<script type="text/javascript">';
        echo '  function showForm(){';
        echo '      jQuery("#form").show();return false;';
        echo '  }
            function checkForm(){ return true;}';
        echo '</script>';
        echo '
<div id="form" style="'.($is_edit?"":"display:none;").'">
<h2></h2>
    <form action="'.$_SERVER['PHP_SELF'].'?page=oneguyandacat-manage-distributor" onsubmit="return checkForm();" method="post">';
    foreach($fields_distributor as $field) {
        switch($field[2]) {
            case ID:
                echo '<input type="hidden" value="';
                break;
            case COUNTRY:
                echo '<br />';
                continue 2;
            case INPUT:
                echo '<input type="text" value="';

            default:
        }

        echo ($is_edit?$distributor->{$field[0]}:"").'" name="'.$field[0].'" placeholder="'.$field[1].'" />'."<br />";
    }
//        <input type="hidden" name="id" value="'.($is_edit?$distributor->id:"").'" />
//        <input placeholder="distributor naam" type="text" name="title" value="'.($is_edit?$distributor->title:"").'" /><br />
//        <input placeholder="front-value" type="text" name="front" value="'.($is_edit?$distributor->front:"").'" /><br />
//        <input placeholder="rear-value" type="text" name="rear" value="'.($is_edit?$distributor->rear:"").'" /><br />
        echo '<input type="submit" name="submit" value="Save" />
    </form>
</div>';


        $tb = new Link_List_table_distributor();
        $tb->prepare_items();
        $tb->display();
}
function wp_distributor_manager_admin_scripts() {
    wp_enqueue_script('media-upload');
    wp_enqueue_script('thickbox');
    wp_enqueue_script('jquery');
}

function wp_distributor_manager_admin_styles() {
wp_enqueue_style('thickbox');
}

add_action('admin_print_scripts', 'wp_distributor_manager_admin_scripts');
add_action('admin_print_styles', 'wp_distributor_manager_admin_styles');
global $ogac_db_version_distributor;
global $fields_distributor;
$ogac_db_version_distributor = "2.0";
define("ID",-1);
define("INPUT",0);
define("DROPOWN",1);
define("CHECKBOX",2);
define("COUNTRY",3);
$fields_distributor = array(
    array("id","", ID),
    array("title", "Location", INPUT),
    array("Oneway_Distribution", "Oneway_Distribution", INPUT),
	array("city", "City", INPUT),
    array("address", "Address", INPUT),
    array("zip", "Postcode", INPUT),
    array("country", "Country", INPUT),
    array("phone", "Phone", INPUT),
    array("website", "Website", INPUT),
    array("fax", "Fax", INPUT),
	array("email", "E-mail", INPUT),
);
class Link_List_table_distributor extends WP_List_Table {

	/**
	 * Constructor, we override the parent to pass our own arguments
	 * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
	 */
	 function __construct() {
		 parent::__construct( array(
		'singular'=> 'wp_list_text_link', //Singular label
		'plural' => 'wp_list_test_links', //plural label, also this well be one of the table css class
		'ajax'	=> false //We won't support Ajax for this table
		));
	 }
         function extra_tablenav( $which ) {
            if ( $which == "top" ){
                    //The code that goes before the table is here
                    echo '<div class="wrap"><h2>distributor <a href="javascript:void(0);" onclick="javascript:showForm();" class="add-new-h2">Add New</a></h2>';
            }
            if ( $which == "bottom" ){
                    //The code that goes after the table is there
                    echo "</div>";
            }
         }
         function get_columns() {
            return $columns= array(
//                    'col_id'=>__('id'),
//                    'col_country'=>__('country'),
                    'col_title'=>__('title'),
                    'col_remove'=>__('delete')
//                    'col_link_description'=>__('Description'),
//                    'col_link_visible'=>__('Visible')
            );
         }
         public function get_sortable_columns() {
            return $sortable = array(
                    'col_country'=>'country',
                    'col_title'=>'title',
//                    'col_link_visible'=>'link_visible'
            );
        }
        function prepare_items() {
	global $wpdb, $_wp_column_headers, $fields_distributor;
	$screen = get_current_screen();
        $query = "SELECT * FROM ". $wpdb->prefix . "distributor";

        if (isset($_GET['country'])){
            $query.= " where `country` = '".$_GET['country']."'";
        }


	/* -- Pagination parameters -- */
        //Number of elements in your table?
        $totalitems_distributor = $wpdb->query($query); //return the total number of affected rows
        //How many to display per page?
        $perpage_distributor = 9999;
        //Which page is this?
        $paged_distributor = !empty($_GET["paged_distributor"]) ? mysql_real_escape_string($_GET["paged_distributor"]) : '';
        //Page Number
        if(empty($paged_distributor) || !is_numeric($paged_distributor) || $paged_distributor<=0 ){ $paged_distributor=1; }
        //How many pages do we have in total?
        $totalpages = ceil($totalitems_distributor/$perpage_distributor);
        //adjust the query to take pagination into account
	    if(!empty($paged_distributor) && !empty($perpage_distributor)){
		    $offset=($paged_distributor-1)*$perpage_distributor;
    		$query.=' LIMIT '.(int)$offset.','.(int)$perpage_distributor;
	    }

	/* -- Register the pagination -- */
		$this->set_pagination_args( array(
			"total_items" => $totalitems_distributor,
			"total_pages" => $totalpages,
			"per_page" => $perpage_distributor,
		) );
		//The pagination links are automatically built according to those parameters

	/* -- Register the Columns -- */
		$columns = $this->get_columns();
		$_wp_column_headers[$screen->id]=$columns;
                
	/* -- Fetch the items -- */
		$this->items = $wpdb->get_results($query);
        }
        function display_rows() {

                //Get the records registered in the prepare_items method
                $records = $this->items;
                    
                //Get the columns registered in the get_columns and get_sortable_columns methods
                list( $columns, $hidden ) = $this->get_column_info();
                //shit wordpress works for shit, so put this here and we do get the right results
                $columns = $this->get_columns();
                //Loop for each record
                if(!empty($records)){foreach($records as $rec){
                        //Open the line
                echo '<tr id="record_'.$rec->id.'">';
                        foreach ( $columns as $column_name => $column_display_name ) {

                                //Style attributes for each col
                                $class = "class='$column_name column-$column_name'";
                                $style = "";
                                if ( in_array( $column_name, $hidden ) ) $style = ' style="display:none;"';
                                $attributes = $class . $style;

                                //edit link
                                $editlink  = '/wp-admin/link.php?action=edit&link_id='.(int)$rec->id;

                                //Display the cell
                                switch ( $column_name ) {
                                        case "col_id":	echo '<td '.$attributes.'>'.stripslashes($rec->id).'</td>';	break;
                                        case "col_country": echo '<td '.$attributes.'>'.countryToString($rec->country).'</td>'; break;
                                        case "col_title": echo '<td '.$attributes.'>'.stripslashes($rec->title).'</td>'; break;
                                        case "col_remove": echo '<td '.$attributes.'><a href="?page=oneguyandacat-manage-distributor&edit='.$rec->id.'">Edit</a> - <a onclick="return confirm(\'Delete the record?\');" href="?page=oneguyandacat-manage-distributor&remove='.$rec->id.'">Delete</a></td>'; break;
//                                        case "col_link_description": echo '< td '.$attributes.'>'.$rec->link_description.'< /td>'; break;
//                                        case "col_link_visible": echo '< td '.$attributes.'>'.$rec->link_visible.'< /td>'; break;
                                }
                        }

                        //Close the line
                        echo'</tr>';
                }}
        }
}
?>