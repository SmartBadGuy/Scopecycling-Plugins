<?php
/**
 * Plugin Name: home page image
 * Plugin URI: http://oneguyandacat.com
 * Description: allows eddit homepage images
 * Version: 0
 * Author: Mathijs
 * Author URI: http://oneguyandacat.com
 * License: none
 */

/** Step 2 (from text above). */
add_action( 'admin_menu', 'homepage_pictures_plugin_menu' );

/** Step 1. */
function homepage_pictures_plugin_menu() {
	add_options_page( 'home page image', 'home page image', 'manage_options', 'oneguyandacat-homepage-image', 'oneguyandacat_scope_homepage_home' );
}
function create_database_homepage_pictures(){
   global $wpdb;

   $table_name = $wpdb->prefix . "homepage_picture";
      
   $sql = "CREATE TABLE $table_name (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order` int(2) DEFAULT '0' NOT NULL,
  title VARCHAR(255) DEFAULT '' NOT NULL,
  image VARCHAR(255) DEFAULT '' NOT NULL,
  link VARCHAR(255) DEFAULT '' NOT NULL,
  line1 VARCHAR(255) DEFAULT '' NOT NULL,
  line2 VARCHAR(255) DEFAULT '' NOT NULL,
  PRIMARY KEY `id` (`id`)
    );";
   
   $wpdb->query($sql);
}

/** Step 3. */
function oneguyandacat_scope_homepage_home() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

    try {
        
        create_database_homepage_pictures();
    } catch (Exception $e){
        echo $e->getMessage();die;
    }

    $page = isset($_GET['action']) ? $_GET['action'] : "list";
    if (isset($_GET['message'])) {
        echo '<div class="update-nag"><p>' . $_GET['message'] . '</p></div>';
    }
    echo "<h1>Home page image</h1>";

    switch($page) {
        case "edit":
            scopeHomepageShowEdit(isset($_GET['id']) ? $_GET['id'] : false);
            break;
        case "save":
            scopeHomepageSave();
            break;
        case "remove":
        case "delete":
            scopeHomepageRemove($_GET['id']);
            break;
        default:
        case "list":
            scopeHomepageShowList();
            break;
    }

    $slider_dir = '../wp-content/themes/scope/img/pictures';
    if (!is_dir($slider_dir)){
        mkdir($slider_dir);
    }
}

function scopeHomepageShowList() {
    echo '<!-- list -->';
    $items = scopeHomepageGetAll();
    echo '<!-- list got data -->';
    echo '<ul>';
    foreach($items as $item) {
        echo '<!-- item -->';
        echo '<li>';
        echo $item->title;
        echo ' <a href="?page=oneguyandacat-homepage-image&action=edit&id=' . $item->id . '">[edit]</a> ';
        echo ' <a href="?page=oneguyandacat-homepage-image&action=delete&id=' . $item->id . '" onclick="return confirm(\'Remove '.$item->title.'?\')">[delete]</a> ';
        echo '</li>';
    }
    if (count($items) == 0) {
        echo '<li>No items yet added</li>';
    }
    
    echo ' <li><a href="?page=oneguyandacat-homepage-image&action=edit">Add New</a></li>';
    
    echo '</ul>';
    echo '<!-- list end -->';
}

function scopeHomepageRemove($id) {
    global $wpdb;
    $wpdb->query("DELETE FROM ".$wpdb->prefix . "homepage_picture  where id = '". $id ."'" );
    header("location:?page=oneguyandacat-homepage-image&message=Item+removed");
}

function scopeHomepageShowEdit($id) {
    echo '<!-- edit -->';
    $item =  $id ? (array) scopeHomepageGet($id) : array();
    echo '<!-- get item -->';
    $slider_dir = '../wp-content/themes/scope/img/pictures';
echo '<!-- dir -->';
    echo '
<form enctype="multipart/form-data" action="?page=oneguyandacat-homepage-image&action=save" method="post">';
    
    echo '<input type="hidden" name="id" value="' . (isset($item["id"]) ? $item["id"] : null) . '"> <br />';
    echo 'Title<br/>';
    echo '<input type="text" name="title" value="' . (isset($item["title"]) ? $item["title"] : null) . '"> <br />';
    echo 'image Image<br/>';
    echo '<input type="file" name="image"> <br />';
    echo ''.(!empty($item["image"]) ? '<img src="'.$slider_dir.'/'.$item["image"].'" style="max-width:300px;" />' : '').' <br />';
    echo 'Image link<br/>';
    echo '<input type="text" name="link" value="' . (isset($item["link"]) ? $item["link"] : null) . '"> <br />';
    echo 'First line<br/>';
    echo '<input type="text" name="line1" value="' . (isset($item["line1"]) ? $item["line1"] : null) . '"> <br />';
	echo 'Second line<br/>';
    echo '<input type="text" name="line2" value="' . (isset($item["line2"]) ? $item["line2"] : null) . '"> <br />';
	echo '<input type="submit" name="submit" value="save" /> <br />';
echo '</form>
';
    echo '<!--  end edit -->';
}

function scopeHomepageGetAll() {
    global $wpdb;
    return $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix . "homepage_picture");
}

function scopeHomepageGet($id) {
    global $wpdb;
    $r = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix . "homepage_picture where id = '".$id."'");
    
    return $r[0];//first
    
}

function scopeHomepageSave() {
    global $wpdb;
    $slider_dir = '../wp-content/themes/scope/img/pictures';

    $item = (array) scopeHomepageGet($_POST["id"]);
    $item["id"] = isset($_POST["id"]) ? $_POST["id"] : "";
    $item["title"] = $_POST["title"];
    $item["link"] = $_POST["link"];
	$item["line1"] = $_POST["line1"];
	$item["line2"] = $_POST["line2"];
	
    $file = homepage_upload_file_scope("image");
    if ($file) {
        if (!empty($item["image"])) {
            unlink($slider_dir.$item["image"]);
        }
        $item["image"] = $file;
    }

    $wpdb->query("REPLACE INTO ".$wpdb->prefix . "homepage_picture (`id`, `title`, `image`, `link`, `line1`, `line2`) values
    ('".$item["id"]."','".$item["title"]."','".$item["image"]."','".$item["link"]."','".$item["line1"]."','".$item["line2"]."')");
        
    header("location:?page=oneguyandacat-homepage-image&action=list");
    echo '<script type="text/javascript">window.location.href="?page=oneguyandacat-homepage-image"</script>';
    die;
}

function homepage_upload_file_scope($name) {
    if(isset($_FILES[$name]) && $_FILES[$name]['name'] != '')
    {
        $slider_dir = '../wp-content/themes/scope/img/pictures';


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