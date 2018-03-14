<?php
/**
 * Plugin Name: Scope Reviews
 * Plugin URI: http://oneguyandacat.com
 * Description: allows adding new reviews
 * Version: 0
 * Author: Mathijs
 * Author URI: http://oneguyandacat.com
 * License: none
 */

/** Step 2 (from text above). */
add_action( 'admin_menu', 'ogac_review_plugin_menu' );

/** Step 1. */
function ogac_review_plugin_menu() {
	add_options_page( 'Scope Reviews', 'Scope Reviews', 'manage_options', 'oneguyandacat-scope-Reviews', 'oneguyandacat_scope_Reviews_home' );
}
function create_database_ogac_review(){
   global $wpdb;

   $table_name = $wpdb->prefix . "review_slider";
      
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
function oneguyandacat_scope_Reviews_home() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

    try {
        
        create_database_ogac_review();
    } catch (Exception $e){
        echo $e->getMessage();die;
    }

    $page = isset($_GET['action']) ? $_GET['action'] : "list";
    if (isset($_GET['message'])) {
        echo '<div class="update-nag"><p>' . $_GET['message'] . '</p></div>';
    }
    echo "<h1>Reviews</h1>";
    switch($page) {
        case "edit":
            scopeReviewshowEdit(isset($_GET['id']) ? $_GET['id'] : false);
            break;
        case "save":
            scopeReviewsave();
            break;
        case "remove":
        case "delete":
            scopeReviewRemove($_GET['id']);
            break;
        default:
        case "list":
            scopeReviewshowList();
            break;
    }

    $slider_dir = '../wp-content/themes/scope/img/review';
    if (!is_dir($slider_dir)){
        mkdir($slider_dir);
    }
}

function scopeReviewshowList() {
    echo '<!-- list -->';
    $items = scopeReviewGetAll();
    echo '<!-- list got data -->';
    echo '<ul>';
    foreach($items as $item) {
        echo '<!-- item -->';
        echo '<li>';
        echo $item->title;
        echo ' <a href="?page=oneguyandacat-scope-Reviews&action=edit&id=' . $item->id . '">[edit]</a> ';
        echo ' <a href="?page=oneguyandacat-scope-Reviews&action=delete&id=' . $item->id . '" onclick="return confirm(\'Remove '.$item->title.'?\')">[delete]</a> ';
        echo '</li>';
    }
    if (count($items) == 0) {
        echo '<li>No items yet added</li>';
    }
    
    echo ' <li><a href="?page=oneguyandacat-scope-Reviews&action=edit">Add New</a></li>';
    
    echo '</ul>';
    echo '<!-- list end -->';
}

function scopeReviewRemove($id) {
    global $wpdb;
    $wpdb->query("DELETE FROM ".$wpdb->prefix . "review_slider  where id = '". $id ."'" );
    header("location:?page=oneguyandacat-scope-Reviews&message=Item+removed");
        echo '<script type="text/javascript">window.location.href="?page=oneguyandacat-scope-Reviews&message=Item+removed"</script>';

    die;
}

function scopeReviewshowEdit($id) {
    echo '<!-- edit -->';
    $item =  $id ? (array) scopeReviewGet($id) : array();
    echo '<!-- get item -->';
    $slider_dir = '../wp-content/themes/scope/img/review';
echo '<!-- dir -->';
    echo '
<form enctype="multipart/form-data" action="?page=oneguyandacat-scope-Reviews&action=save" method="post">';
    
    echo '<input type="hidden" name="id" value="' . (isset($item["id"]) ? $item["id"] : null) . '"> <br />';
    echo 'Title<br/>';
    echo '<input type="text" name="title" value="' . (isset($item["title"]) ? $item["title"] : null) . '"> <br />';
    echo 'Thumb Image<br/>';
    echo '<input type="file" name="bike"> <br />';
    echo ''.(!empty($item["bike"]) ? '<img src="'. $slider_dir.'/'.$item["bike"].'" style="max-width:300px;" />' : '').' <br />';
    echo 'PDF File<br/>';
    echo '<input type="file" name="logo"> <br />';
    echo ''.(!empty($item["logo"]) ? '<a href="'.$slider_dir.'/'.$item["logo"].'" >'.$item["logo"].'</a>' : '').' <br />';
    //echo 'Link to Review merchant<br/>';
    echo '<input type="hidden" name="link" value="' . (isset($item["link"]) ? $item["link"] : null) . '"> <br />';
    echo '<input type="submit" name="submit" value="save" /> <br />';
echo '</form>
';
    echo '<!--  end edit -->';
}

function scopeReviewGetAll() {
    global $wpdb;
    return $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix . "review_slider");
}

function scopeReviewGet($id) {
    global $wpdb;
    $r = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix . "review_slider where id = '".$id."'");
    
    return $r[0];//first
    
}

function scopeReviewsave() {
    global $wpdb;
    $slider_dir = '../wp-content/themes/scope/img/review';

    $item = (array) scopeReviewGet($_POST["id"]);
    $item["id"] = isset($_POST["id"]) ? $_POST["id"] : "";
    $item["title"] = $_POST["title"];
    $item["link"] = $_POST["link"];

    $file = Review_upload_file_scope("bike");
    if ($file) {
        if (!empty($item["bike"])) {
            unlink($slider_dir.$item["bike"]);
        }
        $item["bike"] = $file;
    }

    $file = Review_upload_file_scope("logo");
    if ($file) {
        if (!empty($item["logo"])) {
            unlink($slider_dir.$item["logo"]);
        }
        $item["logo"] = $file;
    }

    $wpdb->query("REPLACE INTO ".$wpdb->prefix . "review_slider (`id`, `title`, `bike`, `logo`, `link`) values "
        ."('".$item["id"]."','".$item["title"]."','".$item["bike"]."','".$item["logo"]."','".$item["link"]."')");
        
    header("location:?page=oneguyandacat-scope-Reviews&action=list");
    echo '<script type="text/javascript">window.location.href="?page=oneguyandacat-scope-Reviews"</script>';
    die;
}

function Review_upload_file_scope($name) {
    if(isset($_FILES[$name]) && $_FILES[$name]['name'] != '')
    {
    $slider_dir = '../wp-content/themes/scope/img/review';


        $mode = '0666';
        $userfile_name = $_FILES[$name]['name'];
        $userfile_tmp = $_FILES[$name]['tmp_name'];
        $userfile_size = $_FILES[$name]['size'];
        $userfile_type = $_FILES[$name]['type'];
        $prod_img = $slider_dir.'/'.$_GET['upload'].'/'.$userfile_name;
        $size = 1600;

        $prod_img_thumb = $prod_img;
        move_uploaded_file($userfile_tmp, $prod_img);
        if (in_array($userfile_type, array('image/png','image/jpg'))){
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
        }
        return $userfile_name;
    }
    return false;
}