<?php
/**
 * Plugin Name: Scope Oems
 * Plugin URI: http://oneguyandacat.com
 * Description: allows adding new reviews
 * Version: 0
 * Author: Mathijs
 * Author URI: http://oneguyandacat.com
 * License: none
 */

/** Step 2 (from text above). */
add_action( 'admin_menu', 'ogac_oem_plugin_menu' );

/** Step 1. */
function ogac_oem_plugin_menu() {
	add_options_page( 'Scope Oems', 'Scope Oems', 'manage_options', 'oneguyandacat-scope-oems', 'oneguyandacat_scope_oems_home' );
}
function create_database_ogac_oem(){
   global $wpdb;

   $table_name = $wpdb->prefix . "oem_slider";
      
   $sql = "CREATE TABLE $table_name (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order` int(2) DEFAULT '0' NOT NULL,
  title VARCHAR(255) DEFAULT '' NOT NULL,
  bike VARCHAR(255) DEFAULT '' NOT NULL,
  logo VARCHAR(255) DEFAULT '' NOT NULL,
  link VARCHAR(255) DEFAULT '' NOT NULL,
  PRIMARY KEY `id` (`id`)
    );";
   
   $wpdb->query($sql);
}

/** Step 3. */
function oneguyandacat_scope_oems_home() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

    try {
        
        create_database_ogac_oem();
    } catch (Exception $e){
        echo $e->getMessage();die;
    }

    $page = isset($_GET['action']) ? $_GET['action'] : "list";
    if (isset($_GET['message'])) {
        echo '<div class="update-nag"><p>' . $_GET['message'] . '</p></div>';
    }
    echo "<h1>OEM Merchants</h1>";

    switch($page) {
        case "edit":
            scopeOemShowEdit(isset($_GET['id']) ? $_GET['id'] : false);
            break;
        case "save":
            scopeOemSave();
            break;
        case "remove":
        case "delete":
            scopeOemRemove($_GET['id']);
            break;
        default:
        case "list":
            scopeOemShowList();
            break;
    }

    $slider_dir = '../wp-content/themes/scope/img/oem';
    if (!is_dir($slider_dir)){
        mkdir($slider_dir);
    }
}

function scopeOemShowList() {
    echo '<!-- list -->';
    $items = scopeOemGetAll();
    echo '<!-- list got data -->';
    echo '<ul>';
    foreach($items as $item) {
        echo '<!-- item -->';
        echo '<li>';
        echo $item->title;
        echo ' <a href="?page=oneguyandacat-scope-oems&action=edit&id=' . $item->id . '">[edit]</a> ';
        echo ' <a href="?page=oneguyandacat-scope-oems&action=delete&id=' . $item->id . '" onclick="return confirm(\'Remove '.$item->title.'?\')">[delete]</a> ';
        echo '</li>';
    }
    if (count($items) == 0) {
        echo '<li>No items yet added</li>';
    }
    
    echo ' <li><a href="?page=oneguyandacat-scope-oems&action=edit">Add New</a></li>';
    
    echo '</ul>';
    echo '<!-- list end -->';
}

function scopeOemRemove($id) {
    global $wpdb;
    $wpdb->query("DELETE FROM ".$wpdb->prefix . "oem_slider  where id = '". $id ."'" );
    header("location:?page=oneguyandacat-scope-oems&message=Item+removed");
}

function scopeOemShowEdit($id) {
    echo '<!-- edit -->';
    $item =  $id ? (array) scopeOemGet($id) : array();
    echo '<!-- get item -->';
    $slider_dir = '../wp-content/themes/scope/img/oem';
echo '<!-- dir -->';
    echo '
<form enctype="multipart/form-data" action="?page=oneguyandacat-scope-oems&action=save" method="post">';
    
    echo '<input type="hidden" name="id" value="' . (isset($item["id"]) ? $item["id"] : null) . '"> <br />';
    echo 'Title<br/>';
    echo '<input type="text" name="title" value="' . (isset($item["title"]) ? $item["title"] : null) . '"> <br />';
    echo 'Bike Image<br/>';
    echo '<input type="file" name="bike"> <br />';
    echo ''.(!empty($item["bike"]) ? '<img src="'. $slider_dir.'/'.$item["bike"].'" style="max-width:300px;" />' : '').' <br />';
    echo 'Logo Image<br/>';
    echo '<input type="file" name="logo"> <br />';
    echo ''.(!empty($item["logo"]) ? '<img src="'.$slider_dir.'/'.$item["logo"].'" style="max-width:300px;" />' : '').' <br />';
    echo 'Link to OEM merchant<br/>';
    echo '<input type="text" name="link" value="' . (isset($item["link"]) ? $item["link"] : null) . '"> <br />';
    echo '<input type="submit" name="submit" value="save" /> <br />';
echo '</form>
';
    echo '<!--  end edit -->';
}

function scopeOemGetAll() {
    global $wpdb;
    return $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix . "oem_slider");
}

function scopeOemGet($id) {
    global $wpdb;
    $r = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix . "oem_slider where id = '".$id."'");
    
    return $r[0];//first
    
}

function scopeOemSave() {
    global $wpdb;
    $slider_dir = '../wp-content/themes/scope/img/oem';

    $item = (array) scopeOemGet($_POST["id"]);
    $item["id"] = isset($_POST["id"]) ? $_POST["id"] : "";
    $item["title"] = $_POST["title"];
    $item["link"] = $_POST["link"];

    $file = oem_upload_file_scope("bike");
    if ($file) {
        if (!empty($item["bike"])) {
            unlink($slider_dir.$item["bike"]);
        }
        $item["bike"] = $file;
    }

    $file = oem_upload_file_scope("logo");
    if ($file) {
        if (!empty($item["logo"])) {
            unlink($slider_dir.$item["logo"]);
        }
        $item["logo"] = $file;
    }

    $wpdb->query("REPLACE INTO ".$wpdb->prefix . "oem_slider (`id`, `title`, `bike`, `logo`, `link`) values
    ('".$item["id"]."','".$item["title"]."','".$item["bike"]."','".$item["logo"]."','".$item["link"]."')");
        
    header("location:?page=oneguyandacat-scope-oems&action=list");
    echo '<script type="text/javascript">window.location.href="?page=oneguyandacat-scope-oems"</script>';
    die;
}

function oem_upload_file_scope($name) {
    if(isset($_FILES[$name]) && $_FILES[$name]['name'] != '')
    {
        $slider_dir = '../wp-content/themes/scope/img/oem';


        $mode = '0666';
        $userfile_name = $_FILES[$name]['name'];
        $userfile_tmp = $_FILES[$name]['tmp_name'];
        $userfile_size = $_FILES[$name]['size'];
        $userfile_type = $_FILES[$name]['type'];
        $prod_img = $slider_dir.'/'.$_GET['upload'].'/'.$userfile_name;
        $size = 1600;

        $prod_img_thumb = $prod_img;
        move_uploaded_file($userfile_tmp, $prod_img);
        chmod ($prod_img, octdec($mode));

        $sizes = getimagesize($prod_img);

        $aspect_ratio = $sizes[1]/$sizes[0];

        if ($sizes[0] <= $size)
        {
            $new_width = $sizes[0];
            $new_height = $sizes[1];
        }else{
            $new_width = $size;
            $new_height = abs($new_width*$aspect_ratio);
        }

        $destimg=ImageCreateTrueColor($new_width,$new_height)
        or die('Problem In Creating image');
        if ($userfile_type != "image/png"){
            $srcimg=ImageCreateFromJPEG($prod_img)
            or die('Problem In opening Source Image');
        } else {
            $srcimg=ImageCreateFrompng($prod_img)
            or die('Problem In opening Source Image');
        }
        imagealphablending( $destimg, false );
        imagesavealpha( $destimg, true );
        if(function_exists('imagecopyresampled'))
        {
            imagecopyresampled($destimg,$srcimg,0,0,0,0,$new_width,$new_height,ImageSX($srcimg),ImageSY($srcimg))
            or die('Problem In resizing');
        }else{
            Imagecopyresized($destimg,$srcimg,0,0,0,0,$new_width,$new_height,ImageSX($srcimg),ImageSY($srcimg))
            or die('Problem In resizing');
        }
        unlink($prod_img);
        if ($userfile_type != "image/png"){
            ImageJPEG($destimg,$prod_img_thumb,90)
            or die('Problem In saving');
        } else {
            imagepng($destimg, $prod_img_thumb, 9);
        }
        imagedestroy($destimg);

        return $userfile_name;
    }
    return false;
}