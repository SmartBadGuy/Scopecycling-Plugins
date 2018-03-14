<?php
/**
 * Plugin Name: Dealers
 * Plugin URI: http://oneguyandacat.com
 * Description: for managing Dealers
 * Version: 0
 * Author: Name Of The Plugin Author
 * Author URI: http://oneguyandacat.com
 * License: none, it's free
 **/

if(!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}
add_action( 'admin_menu', 'dealers_plugin_menu' );

function dealers_plugin_menu() {
	add_options_page( 'Manage dealers', 'Manage dealers', 'manage_options', 'oneguyandacat-manage-dealers', 'oneguyandacat_dealers_home' );
}


function oneguyandacat_dealers_home() {

    global $wpdb, $_wp_column_headers, $fields;

	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

        $refresh = false;
        if(isset($_GET['remove'])){
            global  $wpdb;
            $table = $wpdb->prefix . "dealers";
            $query = "DELETE FROM `".$table."` WHERE `id` = \"".$_GET['remove']."\"";
            $wpdb->query( $query );
            $refresh = true;
        }  
        if (isset($_POST['submit'])){
            global  $wpdb;
            // find list of events
                $table = $wpdb->prefix . "dealers";
                $query = 'REPLACE INTO `'.$table.'`';
                $keys = array();
                $values = array();
                foreach($fields as $field) {
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
            echo '  window.location.href="'.$_SERVER['PHP_SELF'].'?page=oneguyandacat-manage-dealers"';
            echo '</script>';
            die;
        }
        $is_edit = false;
        if (isset($_GET['edit'])){
            global $wpdb;
            $table = $wpdb->prefix . "dealers";
            $dealer = $wpdb->get_results( "SELECT * FROM $table WHERE `id` = \"".$_GET['edit']."\"");
            $dealer = $dealer[0];
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
<h2>'.($is_edit?"Edit ".countryToString($dealer->country)." -> ".stripslashes($dealer->title):"Add Specification").'</h2>
    <form action="'.$_SERVER['PHP_SELF'].'?page=oneguyandacat-manage-dealers" onsubmit="return checkForm();" method="post">';
    foreach($fields as $field) {
        switch($field[2]) {
            case ID:
                echo '<input type="hidden" value="';
                break;
            case COUNTRY:
                echo getDealerCountryList(($is_edit?$dealer->{$field[0]}:""))."<br />";
                continue 2;
            case INPUT:
                echo '<input type="text" value="';

            default:
        }

        echo ($is_edit?$dealer->{$field[0]}:"").'" name="'.$field[0].'" placeholder="'.$field[1].'" />'."<br />";
    }
//        <input type="hidden" name="id" value="'.($is_edit?$dealer->id:"").'" />
//        <input placeholder="Dealer naam" type="text" name="title" value="'.($is_edit?$dealer->title:"").'" /><br />
//        <input placeholder="front-value" type="text" name="front" value="'.($is_edit?$dealer->front:"").'" /><br />
//        <input placeholder="rear-value" type="text" name="rear" value="'.($is_edit?$dealer->rear:"").'" /><br />
        echo '<input type="submit" name="submit" value="Save" />
    </form>
</div>';


        $tb = new Link_List_table_Dealers();
        $tb->prepare_items();
        $tb->display();
}
//wp_preload_dialogs( array( 'plugins' => 'wpdialogs,wplink,wpfullscreen' ) );
function wp_dealers_manager_admin_scripts() {
    wp_enqueue_script('media-upload');
    wp_enqueue_script('thickbox');
    wp_enqueue_script('jquery');
}

function wp_dealers_manager_admin_styles() {
wp_enqueue_style('thickbox');
}

add_action('admin_print_scripts', 'wp_dealers_manager_admin_scripts');
add_action('admin_print_styles', 'wp_dealers_manager_admin_styles');
global $ogac_db_version;
global $fields;
$ogac_db_version = "1.0";
define("ID",-1);
define("INPUT",0);
define("DROPOWN",1);
define("CHECKBOX",2);
define("COUNTRY",3);
$fields = array(
    array("id","", ID),
    array("title", "Dealer Name", INPUT),
    array("address", "Address", INPUT),
    array("city", "city", INPUT),
    array("zip", "Postcode", INPUT),
    array("country", "Country", COUNTRY),
    array("phone", "Phone", INPUT),
    array("website", "Website", INPUT),
    array("email", "E-mail", INPUT),
    array("long", "Longitude",INPUT),
    array("lat", "Latitude", INPUT),
);
function ogac_install() {
   global $wpdb;
   global $ogac_db_version;
    global $fields;

   $table_name = $wpdb->prefix . "dealers";
      
   $sql = "CREATE TABLE $table_name (`id` int(11) NOT NULL AUTO_INCREMENT";
    foreach($fields as $field) {
        switch($field[2]) {
            case INPUT:
                $sql.= "`".$field[0]."` VARCHAR(255) DEFAULT '' NOT NULL,";
                break;
            case COUNTRY:
                $sql.= "`".$field[0]."` VARCHAR(5) DEFAULT '' NOT NULL,";
                break;
        }
    }

    $sql.= " PRIMARY KEY `id` (`id`));";
   
   require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

   add_option( "ogac_db_version", $ogac_db_version );
   
}

register_activation_hook( __FILE__, 'ogac_install' );

class Link_List_table_Dealers extends WP_List_Table {

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
                    echo '<div class="wrap"><h2>Dealers <a href="javascript:void(0);" onclick="javascript:showForm();" class="add-new-h2">Add New</a></h2>';
            }
            if ( $which == "bottom" ){
                    //The code that goes after the table is there
                    echo "</div>";
            }
         }
         function get_columns() {
            return $columns= array(
//                    'col_id'=>__('id'),
                    'col_country'=>__('country'),
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
	global $wpdb, $_wp_column_headers, $fields;
	$screen = get_current_screen();
        $query = "SELECT * FROM ". $wpdb->prefix . "dealers";

        if (isset($_GET['country'])){
            $query.= " where `country` = '".$_GET['country']."'";
        }


	/* -- Pagination parameters -- */
        //Number of elements in your table?
        $totalitems = $wpdb->query($query); //return the total number of affected rows
        //How many to display per page?
        $perpage = 500;
        //Which page is this?
        $paged = !empty($_GET["paged"]) ? mysql_real_escape_string($_GET["paged"]) : '';
        //Page Number
        if(empty($paged) || !is_numeric($paged) || $paged<=0 ){ $paged=1; }
        //How many pages do we have in total?
        $totalpages = ceil($totalitems/$perpage);
        //adjust the query to take pagination into account
	    if(!empty($paged) && !empty($perpage)){
		    $offset=($paged-1)*$perpage;
    		$query.=' LIMIT '.(int)$offset.','.(int)$perpage;
	    }

	/* -- Register the pagination -- */
		$this->set_pagination_args( array(
			"total_items" => $totalitems,
			"total_pages" => $totalpages,
			"per_page" => $perpage,
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
                                        case "col_remove": echo '<td '.$attributes.'><a href="?page=oneguyandacat-manage-dealers&edit='.$rec->id.'">Edit</a> - <a onclick="return confirm(\'Delete the record?\');" href="?page=oneguyandacat-manage-dealers&remove='.$rec->id.'">Delete</a></td>'; break;
//                                        case "col_link_description": echo '< td '.$attributes.'>'.$rec->link_description.'< /td>'; break;
//                                        case "col_link_visible": echo '< td '.$attributes.'>'.$rec->link_visible.'< /td>'; break;
                                }
                        }

                        //Close the line
                        echo'</tr>';
                }}
        }
}
function countryToString($type) {
    $countries = array
    (
        'AF' => 'Afghanistan',
        'AX' => 'Aland Islands',
        'AL' => 'Albania',
        'DZ' => 'Algeria',
        'AS' => 'American Samoa',
        'AD' => 'Andorra',
        'AO' => 'Angola',
        'AI' => 'Anguilla',
        'AQ' => 'Antarctica',
        'AG' => 'Antigua And Barbuda',
        'AR' => 'Argentina',
        'AM' => 'Armenia',
        'AW' => 'Aruba',
        'AU' => 'Australia',
        'AT' => 'Austria',
        'AZ' => 'Azerbaijan',
        'BS' => 'Bahamas',
        'BH' => 'Bahrain',
        'BD' => 'Bangladesh',
        'BB' => 'Barbados',
        'BY' => 'Belarus',
        'BE' => 'Belgium',
        'BZ' => 'Belize',
        'BJ' => 'Benin',
        'BM' => 'Bermuda',
        'BT' => 'Bhutan',
        'BO' => 'Bolivia',
        'BA' => 'Bosnia And Herzegovina',
        'BW' => 'Botswana',
        'BV' => 'Bouvet Island',
        'BR' => 'Brazil',
        'IO' => 'British Indian Ocean Territory',
        'BN' => 'Brunei Darussalam',
        'BG' => 'Bulgaria',
        'BF' => 'Burkina Faso',
        'BI' => 'Burundi',
        'KH' => 'Cambodia',
        'CM' => 'Cameroon',
        'CA' => 'Canada',
        'CV' => 'Cape Verde',
        'KY' => 'Cayman Islands',
        'CF' => 'Central African Republic',
        'TD' => 'Chad',
        'CL' => 'Chile',
        'CN' => 'China',
        'CX' => 'Christmas Island',
        'CC' => 'Cocos (Keeling) Islands',
        'CO' => 'Colombia',
        'KM' => 'Comoros',
        'CG' => 'Congo',
        'CD' => 'Congo, Democratic Republic',
        'CK' => 'Cook Islands',
        'CR' => 'Costa Rica',
        'CI' => 'Cote D\'Ivoire',
        'HR' => 'Croatia',
        'CU' => 'Cuba',
        'CY' => 'Cyprus',
        'CZ' => 'Czech Republic',
        'DK' => 'Denmark',
        'DJ' => 'Djibouti',
        'DM' => 'Dominica',
        'DO' => 'Dominican Republic',
        'EC' => 'Ecuador',
        'EG' => 'Egypt',
        'SV' => 'El Salvador',
        'GQ' => 'Equatorial Guinea',
        'ER' => 'Eritrea',
        'EE' => 'Estonia',
        'ET' => 'Ethiopia',
        'FK' => 'Falkland Islands (Malvinas)',
        'FO' => 'Faroe Islands',
        'FJ' => 'Fiji',
        'FI' => 'Finland',
        'FR' => 'France',
        'GF' => 'French Guiana',
        'PF' => 'French Polynesia',
        'TF' => 'French Southern Territories',
        'GA' => 'Gabon',
        'GM' => 'Gambia',
        'GE' => 'Georgia',
        'DE' => 'Germany',
        'GH' => 'Ghana',
        'GI' => 'Gibraltar',
        'GR' => 'Greece',
        'GL' => 'Greenland',
        'GD' => 'Grenada',
        'GP' => 'Guadeloupe',
        'GU' => 'Guam',
        'GT' => 'Guatemala',
        'GG' => 'Guernsey',
        'GN' => 'Guinea',
        'GW' => 'Guinea-Bissau',
        'GY' => 'Guyana',
        'HT' => 'Haiti',
        'HM' => 'Heard Island & Mcdonald Islands',
        'VA' => 'Holy See (Vatican City State)',
        'HN' => 'Honduras',
        'HK' => 'Hong Kong',
        'HU' => 'Hungary',
        'IS' => 'Iceland',
        'IN' => 'India',
        'ID' => 'Indonesia',
        'IR' => 'Iran, Islamic Republic Of',
        'IQ' => 'Iraq',
        'IE' => 'Ireland',
        'IM' => 'Isle Of Man',
        'IL' => 'Israel',
        'IT' => 'Italy',
        'JM' => 'Jamaica',
        'JP' => 'Japan',
        'JE' => 'Jersey',
        'JO' => 'Jordan',
        'KZ' => 'Kazakhstan',
        'KE' => 'Kenya',
        'KI' => 'Kiribati',
        'KR' => 'Korea',
        'KW' => 'Kuwait',
        'KG' => 'Kyrgyzstan',
        'LA' => 'Lao People\'s Democratic Republic',
        'LV' => 'Latvia',
        'LB' => 'Lebanon',
        'LS' => 'Lesotho',
        'LR' => 'Liberia',
        'LY' => 'Libyan Arab Jamahiriya',
        'LI' => 'Liechtenstein',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'MO' => 'Macao',
        'MK' => 'Macedonia',
        'MG' => 'Madagascar',
        'MW' => 'Malawi',
        'MY' => 'Malaysia',
        'MV' => 'Maldives',
        'ML' => 'Mali',
        'MT' => 'Malta',
        'MH' => 'Marshall Islands',
        'MQ' => 'Martinique',
        'MR' => 'Mauritania',
        'MU' => 'Mauritius',
        'YT' => 'Mayotte',
        'MX' => 'Mexico',
        'FM' => 'Micronesia, Federated States Of',
        'MD' => 'Moldova',
        'MC' => 'Monaco',
        'MN' => 'Mongolia',
        'ME' => 'Montenegro',
        'MS' => 'Montserrat',
        'MA' => 'Morocco',
        'MZ' => 'Mozambique',
        'MM' => 'Myanmar',
        'NA' => 'Namibia',
        'NR' => 'Nauru',
        'NP' => 'Nepal',
        'NL' => 'Netherlands',
        'AN' => 'Netherlands Antilles',
        'NC' => 'New Caledonia',
        'NZ' => 'New Zealand',
        'NI' => 'Nicaragua',
        'NE' => 'Niger',
        'NG' => 'Nigeria',
        'NU' => 'Niue',
        'NF' => 'Norfolk Island',
        'MP' => 'Northern Mariana Islands',
        'NO' => 'Norway',
        'OM' => 'Oman',
        'PK' => 'Pakistan',
        'PW' => 'Palau',
        'PS' => 'Palestinian Territory, Occupied',
        'PA' => 'Panama',
        'PG' => 'Papua New Guinea',
        'PY' => 'Paraguay',
        'PE' => 'Peru',
        'PH' => 'Philippines',
        'PN' => 'Pitcairn',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'PR' => 'Puerto Rico',
        'QA' => 'Qatar',
        'RE' => 'Reunion',
        'RO' => 'Romania',
        'RU' => 'Russian Federation',
        'RW' => 'Rwanda',
        'BL' => 'Saint Barthelemy',
        'SH' => 'Saint Helena',
        'KN' => 'Saint Kitts And Nevis',
        'LC' => 'Saint Lucia',
        'MF' => 'Saint Martin',
        'PM' => 'Saint Pierre And Miquelon',
        'VC' => 'Saint Vincent And Grenadines',
        'WS' => 'Samoa',
        'SM' => 'San Marino',
        'ST' => 'Sao Tome And Principe',
        'SA' => 'Saudi Arabia',
        'SN' => 'Senegal',
        'RS' => 'Serbia',
        'SC' => 'Seychelles',
        'SL' => 'Sierra Leone',
        'SG' => 'Singapore',
        'SK' => 'Slovakia',
        'SI' => 'Slovenia',
        'SB' => 'Solomon Islands',
        'SO' => 'Somalia',
        'ZA' => 'South Africa',
        'GS' => 'South Georgia And Sandwich Isl.',
        'ES' => 'Spain',
        'LK' => 'Sri Lanka',
        'SD' => 'Sudan',
        'SR' => 'Suriname',
        'SJ' => 'Svalbard And Jan Mayen',
        'SZ' => 'Swaziland',
        'SE' => 'Sweden',
        'CH' => 'Switzerland',
        'SY' => 'Syrian Arab Republic',
        'TW' => 'Taiwan',
        'TJ' => 'Tajikistan',
        'TZ' => 'Tanzania',
        'TH' => 'Thailand',
        'TL' => 'Timor-Leste',
        'TG' => 'Togo',
        'TK' => 'Tokelau',
        'TO' => 'Tonga',
        'TT' => 'Trinidad And Tobago',
        'TN' => 'Tunisia',
        'TR' => 'Turkey',
        'TM' => 'Turkmenistan',
        'TC' => 'Turks And Caicos Islands',
        'TV' => 'Tuvalu',
        'UG' => 'Uganda',
        'UA' => 'Ukraine',
        'AE' => 'United Arab Emirates',
        'GB' => 'United Kingdom',
        'US' => 'United States',
        'UM' => 'United States Outlying Islands',
        'UY' => 'Uruguay',
        'UZ' => 'Uzbekistan',
        'VU' => 'Vanuatu',
        'VE' => 'Venezuela',
        'VN' => 'Viet Nam',
        'VG' => 'Virgin Islands, British',
        'VI' => 'Virgin Islands, U.S.',
        'WF' => 'Wallis And Futuna',
        'EH' => 'Western Sahara',
        'YE' => 'Yemen',
        'ZM' => 'Zambia',
        'ZW' => 'Zimbabwe',
    );

    return $countries[$type];

}
function getDealerCountryList($va = "", $filterCountries = array()) {

    $countries = array
    (
        'AF' => 'Afghanistan',
        'AX' => 'Aland Islands',
        'AL' => 'Albania',
        'DZ' => 'Algeria',
        'AS' => 'American Samoa',
        'AD' => 'Andorra',
        'AO' => 'Angola',
        'AI' => 'Anguilla',
        'AQ' => 'Antarctica',
        'AG' => 'Antigua And Barbuda',
        'AR' => 'Argentina',
        'AM' => 'Armenia',
        'AW' => 'Aruba',
        'AU' => 'Australia',
        'AT' => 'Austria',
        'AZ' => 'Azerbaijan',
        'BS' => 'Bahamas',
        'BH' => 'Bahrain',
        'BD' => 'Bangladesh',
        'BB' => 'Barbados',
        'BY' => 'Belarus',
        'BE' => 'Belgium',
        'BZ' => 'Belize',
        'BJ' => 'Benin',
        'BM' => 'Bermuda',
        'BT' => 'Bhutan',
        'BO' => 'Bolivia',
        'BA' => 'Bosnia And Herzegovina',
        'BW' => 'Botswana',
        'BV' => 'Bouvet Island',
        'BR' => 'Brazil',
        'IO' => 'British Indian Ocean Territory',
        'BN' => 'Brunei Darussalam',
        'BG' => 'Bulgaria',
        'BF' => 'Burkina Faso',
        'BI' => 'Burundi',
        'KH' => 'Cambodia',
        'CM' => 'Cameroon',
        'CA' => 'Canada',
        'CV' => 'Cape Verde',
        'KY' => 'Cayman Islands',
        'CF' => 'Central African Republic',
        'TD' => 'Chad',
        'CL' => 'Chile',
        'CN' => 'China',
        'CX' => 'Christmas Island',
        'CC' => 'Cocos (Keeling) Islands',
        'CO' => 'Colombia',
        'KM' => 'Comoros',
        'CG' => 'Congo',
        'CD' => 'Congo, Democratic Republic',
        'CK' => 'Cook Islands',
        'CR' => 'Costa Rica',
        'CI' => 'Cote D\'Ivoire',
        'HR' => 'Croatia',
        'CU' => 'Cuba',
        'CY' => 'Cyprus',
        'CZ' => 'Czech Republic',
        'DK' => 'Denmark',
        'DJ' => 'Djibouti',
        'DM' => 'Dominica',
        'DO' => 'Dominican Republic',
        'EC' => 'Ecuador',
        'EG' => 'Egypt',
        'SV' => 'El Salvador',
        'GQ' => 'Equatorial Guinea',
        'ER' => 'Eritrea',
        'EE' => 'Estonia',
        'ET' => 'Ethiopia',
        'FK' => 'Falkland Islands (Malvinas)',
        'FO' => 'Faroe Islands',
        'FJ' => 'Fiji',
        'FI' => 'Finland',
        'FR' => 'France',
        'GF' => 'French Guiana',
        'PF' => 'French Polynesia',
        'TF' => 'French Southern Territories',
        'GA' => 'Gabon',
        'GM' => 'Gambia',
        'GE' => 'Georgia',
        'DE' => 'Germany',
        'GH' => 'Ghana',
        'GI' => 'Gibraltar',
        'GR' => 'Greece',
        'GL' => 'Greenland',
        'GD' => 'Grenada',
        'GP' => 'Guadeloupe',
        'GU' => 'Guam',
        'GT' => 'Guatemala',
        'GG' => 'Guernsey',
        'GN' => 'Guinea',
        'GW' => 'Guinea-Bissau',
        'GY' => 'Guyana',
        'HT' => 'Haiti',
        'HM' => 'Heard Island & Mcdonald Islands',
        'VA' => 'Holy See (Vatican City State)',
        'HN' => 'Honduras',
        'HK' => 'Hong Kong',
        'HU' => 'Hungary',
        'IS' => 'Iceland',
        'IN' => 'India',
        'ID' => 'Indonesia',
        'IR' => 'Iran, Islamic Republic Of',
        'IQ' => 'Iraq',
        'IE' => 'Ireland',
        'IM' => 'Isle Of Man',
        'IL' => 'Israel',
        'IT' => 'Italy',
        'JM' => 'Jamaica',
        'JP' => 'Japan',
        'JE' => 'Jersey',
        'JO' => 'Jordan',
        'KZ' => 'Kazakhstan',
        'KE' => 'Kenya',
        'KI' => 'Kiribati',
        'KR' => 'Korea',
        'KW' => 'Kuwait',
        'KG' => 'Kyrgyzstan',
        'LA' => 'Lao People\'s Democratic Republic',
        'LV' => 'Latvia',
        'LB' => 'Lebanon',
        'LS' => 'Lesotho',
        'LR' => 'Liberia',
        'LY' => 'Libyan Arab Jamahiriya',
        'LI' => 'Liechtenstein',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'MO' => 'Macao',
        'MK' => 'Macedonia',
        'MG' => 'Madagascar',
        'MW' => 'Malawi',
        'MY' => 'Malaysia',
        'MV' => 'Maldives',
        'ML' => 'Mali',
        'MT' => 'Malta',
        'MH' => 'Marshall Islands',
        'MQ' => 'Martinique',
        'MR' => 'Mauritania',
        'MU' => 'Mauritius',
        'YT' => 'Mayotte',
        'MX' => 'Mexico',
        'FM' => 'Micronesia, Federated States Of',
        'MD' => 'Moldova',
        'MC' => 'Monaco',
        'MN' => 'Mongolia',
        'ME' => 'Montenegro',
        'MS' => 'Montserrat',
        'MA' => 'Morocco',
        'MZ' => 'Mozambique',
        'MM' => 'Myanmar',
        'NA' => 'Namibia',
        'NR' => 'Nauru',
        'NP' => 'Nepal',
        'NL' => 'Netherlands',
        'AN' => 'Netherlands Antilles',
        'NC' => 'New Caledonia',
        'NZ' => 'New Zealand',
        'NI' => 'Nicaragua',
        'NE' => 'Niger',
        'NG' => 'Nigeria',
        'NU' => 'Niue',
        'NF' => 'Norfolk Island',
        'MP' => 'Northern Mariana Islands',
        'NO' => 'Norway',
        'OM' => 'Oman',
        'PK' => 'Pakistan',
        'PW' => 'Palau',
        'PS' => 'Palestinian Territory, Occupied',
        'PA' => 'Panama',
        'PG' => 'Papua New Guinea',
        'PY' => 'Paraguay',
        'PE' => 'Peru',
        'PH' => 'Philippines',
        'PN' => 'Pitcairn',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'PR' => 'Puerto Rico',
        'QA' => 'Qatar',
        'RE' => 'Reunion',
        'RO' => 'Romania',
        'RU' => 'Russian Federation',
        'RW' => 'Rwanda',
        'BL' => 'Saint Barthelemy',
        'SH' => 'Saint Helena',
        'KN' => 'Saint Kitts And Nevis',
        'LC' => 'Saint Lucia',
        'MF' => 'Saint Martin',
        'PM' => 'Saint Pierre And Miquelon',
        'VC' => 'Saint Vincent And Grenadines',
        'WS' => 'Samoa',
        'SM' => 'San Marino',
        'ST' => 'Sao Tome And Principe',
        'SA' => 'Saudi Arabia',
        'SN' => 'Senegal',
        'RS' => 'Serbia',
        'SC' => 'Seychelles',
        'SL' => 'Sierra Leone',
        'SG' => 'Singapore',
        'SK' => 'Slovakia',
        'SI' => 'Slovenia',
        'SB' => 'Solomon Islands',
        'SO' => 'Somalia',
        'ZA' => 'South Africa',
        'GS' => 'South Georgia And Sandwich Isl.',
        'ES' => 'Spain',
        'LK' => 'Sri Lanka',
        'SD' => 'Sudan',
        'SR' => 'Suriname',
        'SJ' => 'Svalbard And Jan Mayen',
        'SZ' => 'Swaziland',
        'SE' => 'Sweden',
        'CH' => 'Switzerland',
        'SY' => 'Syrian Arab Republic',
        'TW' => 'Taiwan',
        'TJ' => 'Tajikistan',
        'TZ' => 'Tanzania',
        'TH' => 'Thailand',
        'TL' => 'Timor-Leste',
        'TG' => 'Togo',
        'TK' => 'Tokelau',
        'TO' => 'Tonga',
        'TT' => 'Trinidad And Tobago',
        'TN' => 'Tunisia',
        'TR' => 'Turkey',
        'TM' => 'Turkmenistan',
        'TC' => 'Turks And Caicos Islands',
        'TV' => 'Tuvalu',
        'UG' => 'Uganda',
        'UA' => 'Ukraine',
        'AE' => 'United Arab Emirates',
        'GB' => 'United Kingdom',
        'US' => 'United States',
        'UM' => 'United States Outlying Islands',
        'UY' => 'Uruguay',
        'UZ' => 'Uzbekistan',
        'VU' => 'Vanuatu',
        'VE' => 'Venezuela',
        'VN' => 'Viet Nam',
        'VG' => 'Virgin Islands, British',
        'VI' => 'Virgin Islands, U.S.',
        'WF' => 'Wallis And Futuna',
        'EH' => 'Western Sahara',
        'YE' => 'Yemen',
        'ZM' => 'Zambia',
        'ZW' => 'Zimbabwe',
    );

    $str = "<select name='country'>";
    $str.= "<option value=\"\">Select Country</option>";
    foreach($countries as $value => $name) {
        if (!empty($filterCountries)) {
            if (!in_array($value, $filterCountries)){
                continue;
            }
        }
        $str.= "<option".($va == $value?" selected=\"selected\"":"")." value=\"".$value."\">".$name."</option>";
    }
    $str.= "</select>";
    return $str;
}
?>