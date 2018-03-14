<?php
/**
 * Plugin Name: Slider Upload
 * Plugin URI: http://oneguyandacat.com
 * Description: allows uploading of slider images
 * Version: 0
 * Author: Name Of The Plugin Author
 * Author URI: http://oneguyandacat.com
 * License: none, it's free
 */

/** Step 2 (from text above). */
add_action( 'admin_menu', 'my_plugin_menu' );

/** Step 1. */
function my_plugin_menu() {
	add_options_page( 'Slider images', 'Slider Images', 'manage_options', 'oneguyandacat-sliding-image', 'oneguyandacat_sliding_home' );
}
function create_database(){
   global $wpdb;

   $table_name = $wpdb->prefix . "slider";
      
   $sql = "CREATE TABLE $table (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order` int(2) DEFAULT '0' NOT NULL,
  title VARCHAR(255) DEFAULT '' NOT NULL,
  img VARCHAR(255) DEFAULT '' NOT NULL,
  slider_name VARCHAR(255) DEFAULT '' NOT NULL,  
  PRIMARY KEY `id` (`id`)
    );";
   
   $wpdb->query($sql);
}

/** Step 3. */
function oneguyandacat_sliding_home() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
        try {
            create_database();
        } catch (Exception $e){
            
        }
                global $wpdb;

        $slider_dir = '../wp-content/themes/scope/img/slider';
        if(isset($_GET['remove'])){
            if (file_exists($_GET['url']))
            unlink($_GET['url']);
            $wpdb->query("DELETE FROM ".$wpdb->prefix . "slider WHERE id = ".$_GET['remove']);
        }
        if (isset($_GET['update_db'])){
            if (isset($_POST['youtube'])) {
                preg_match("#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+(?=\?)|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#", $_POST['youtube'], $matches);
                if (!empty($matches))
                    $_POST['youtube'] = $matches[0];

            }
            $qeeee = "UPDATE
            ".$wpdb->prefix . "slider set
            `order` = '".$_POST['order']."',
            `content` = '".$_POST['content']."',
            `content_bold` = '".$_POST['content_bold']."',
            `youtube` = '".$_POST['youtube']."',
            `title` = '".$_POST['title']."'
            WHERE id = '".$_GET['update_db']."'";
//            echo $qeeee;die;
            $wpdb->query($qeeee);
        }
        if(isset($_FILES['image']) && $_FILES['image']['name'] != '')
        {


                $mode = '0666';
                $userfile_name = $_FILES['image']['name'];
                $userfile_tmp = $_FILES['image']['tmp_name'];
                $userfile_size = $_FILES['image']['size'];
                $userfile_type = $_FILES['image']['type'];

                if (isset($_FILES['image']['name'])) 
                {
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
                }

        }
        $slider = array('home','products','r3c','r4c','r5c','O2','scope');
        
        if (is_dir("../wp-content/themes/scope/")){
            echo '<div class="wrap">';
            
            if (!is_dir($slider_dir)){
                mkdir($slider_dir);
            }
            foreach($slider as $slide){
                echo '<h3>Slider: '.$slide.'</h3>';
                echo '<form name="upload_'.$slide.'" method="post" action="?page=oneguyandacat-sliding-image&upload='.$slide.'" enctype="multipart/form-data">';
                
                echo 'Afbeelding: <input name="image" type="file" /><input type="submit" value="upload" name="submit">';
                echo '</form>';
                if (!is_dir($slider_dir."/".$slide)){
                    mkdir($slider_dir."/".$slide);
                }
                if ($handle = opendir($slider_dir."/".$slide)) {
                    
                    while (false !== ($file = readdir($handle))) {
                        if ('.' === $file) continue;
                        if ('..' === $file) continue;
                        
                        $rows = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix . "slider WHERE img = '".$file."' AND `name` = '".$slide."'");
                        if (empty($rows[0])){
                            $wpdb->query("INSERT INTO ".$wpdb->prefix . "slider (`id`,`order`,`title`,`img`,`name`) values ('','0','new','$file','$slide')");                          
                        }
                    }
                    closedir($handle);
                }
                
                $rows = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix . "slider WHERE `name` = '".$slide."' ORDER BY `order`");
                echo '<div style="display:table">';
                foreach ($rows as $row){
                echo '<div style="float:left;" style="margin-right:10px;"><img style="max-width:150px;" src="'.$slider_dir.'/'.$slide.'/'.$row->img.'" /><br/><span>
                            <form name="" action="?page=oneguyandacat-sliding-image&update_db='.$row->id.'" method="post">
                            <span>Title (image alt tag):</span><br />
                            <input style="max-width:100%" type="text" name="title" placeholder="title" value="'.$row->title.'" />
                            <br/>
                            <span>Text first line (non bold):</span><br />
                            <input style="max-width:100%" type="text" name="content" placeholder="Text line" value="'.$row->content.'" /><br />
                            <span>Text second line (bold):</span><br />
                            <input style="max-width:100%" type="text" name="content_bold" placeholder="Text line, bold" value="'.$row->content_bold.'" /><br />
                            <span>Youtube video (unique key, url will be parsed) :</span><br />
                            <input style="max-width:100%" type="text" name="youtube" placeholder="Youtube url" value="'.$row->youtube.'" /><br />

                            <span>Order of item, lower is earlier</span><br />
                            <input style="max-width:100%" type="text" name="order" placeholder="order" value="'.$row->order.'" />
                            <?php

                            ?>
                            <br/><input style="max-width:100%" type="submit" name="submit" value="save title & order" /></form>
<a href="?page=oneguyandacat-sliding-image&remove='.$row->id."&url=".  urlencode($slider_dir.'/'.$slide.'/'.$row->img).'">remove</a></span></div>';
                }
                                    echo '</div>';

            }
            echo '</div>';
        } else {
            wp_die("You need the scope theme to use this plugin");
        }
}

?>