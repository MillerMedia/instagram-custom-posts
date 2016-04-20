<?php
/*
Plugin Name: Instagram Post
Description: A Instagram Post plugin to import instagram data
Author: Matt Miller
Version: 0.1
*/

if(!defined('ABSPATH')){
	exit(0);
}

//adding instagram import menu
add_action('admin_menu', 'instagram_import_plugin_setup_menu');
//adding post type for instragram
add_action('init', 'dwwp_register_post_type');

function dwwp_register_post_type(){
	add_theme_support('post-thumbnails');
    add_post_type_support( 'instagram_post', 'thumbnail' );
	set_post_thumbnail_size( 150, 150 );
 	$args = array('public' => true, 'label' => "Instagram Posts");
 	register_post_type('instagram_post', $args);	
	flush_rewrite_rules();
}
  
function instagram_import_plugin_setup_menu(){
   add_menu_page( 'Instagram Options Page', 'Inst. Options', 'manage_options', 'instagram-plugin', 'instagram_init' );
}

function instagram_init(){
//$myquery = new WP_Query("post_type=instagram_post&meta_value=123009142971078795353226844"); 	
//print_r($myquery);

	echo "<h1>Import Instagram Data</h1>";	
	echo "<form method=\"post\" action=\"admin.php?page=instagram-plugin\" >";
	echo "<label>Username</label>";
	echo "<input type=\"text\" name=\"name\" value=\"\"";
	echo "<br><br><input type=\"submit\" name=\"submit\" value=\"Submit\">";
	echo "</form>";
	
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
   		if (!empty($_POST["name"])) { 							
			get_all_posts($_POST["name"], $max_id);		
		}else{
			echo "no username entered.";
		}
	}
}

function my_attach_external_image( $url = null, $post_id = null, $post_data = array() ) {
        if ( !$url || !$post_id ) return new WP_Error('missing', "Need a valid URL and post ID...");
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        // Download file to temp location, returns full server path to temp file, ex; /home/user/public_html/mysite/wp-content/26192277_640.tmp
        $tmp = download_url( $url );
     
        // If error storing temporarily, unlink
        if ( is_wp_error( $tmp ) ) {
            @unlink($file_array['tmp_name']);   // clean up
            $file_array['tmp_name'] = '';
            return $tmp; // output wp_error
        }
     
        preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $url, $matches);    // fix file filename for query strings
        $url_filename = basename($matches[0]);                                                  // extract filename from url for title
        $url_type = wp_check_filetype($url_filename);                                           // determine file type (ext and mime/type)
     
        // assemble file data (should be built like $_FILES since wp_handle_sideload() will be using)
        $file_array['tmp_name'] = $tmp;                                                         // full server path to temp file
 
            $file_array['name'] = $url_filename;
     
        // set additional wp_posts columns
        if ( empty( $post_data['post_title'] ) ) {
            $post_data['post_title'] = basename($url_filename, "." . $url_type['ext']);         // just use the original filename (no extension)
        }
     
        // make sure gets tied to parent
        if ( empty( $post_data['post_parent'] ) ) {
            $post_data['post_parent'] = $post_id;
        }
     
        // required libraries for media_handle_sideload
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
     
        // do the validation and storage stuff
        $att_id = media_handle_sideload( $file_array, $post_id, null, $post_data );             // $post_data can override the items saved to wp_posts table, like post_mime_type, guid, post_parent, post_title, post_content, post_status
     
        // If error storing permanently, unlink
        if ( is_wp_error($att_id) ) {
            @unlink($file_array['tmp_name']);   // clean up
            return $att_id; // output wp_error
        }
     
        // set as post thumbnail if desired
            set_post_thumbnail($post_id, $att_id);
     
        return $att_id;
}

function my_attach_external_video( $url = null ,$post_id = null) {    
    $tmp = download_url( $url );
    $file_array = array(
        'name' => basename( $url ),
        'tmp_name' => $tmp
    );

    // Check for download errors
    if ( is_wp_error( $tmp ) ) {
        @unlink( $file_array[ 'tmp_name' ] );
        return $tmp;
    }

    $id = media_handle_sideload( $file_array, 0 );
    // Check for handle sideload errors.
    if ( is_wp_error( $id ) ) {
        @unlink( $file_array['tmp_name'] );
        return $id;
    }

    $attachment_url = wp_get_attachment_url( $id );
	return $attachment_url;
}

function get_all_posts($username, $max_id){
	$more_photos = TRUE;
	while($more_photos == TRUE){
		$user_instagram_data = fetchPost($username, $max_id);
		$last_id = add_posts($user_instagram_data, $user_id);
		$more = fetchPost($username, $last_id);
		$clean_last_id = str_replace("_" ,"" , $more['items'][0]['id']);

		if(instagram_id_exist($clean_last_id)){
			$more_photos = FALSE;	
			echo "Import Complete.";
		}
		
		$max_id = $last_id;
	} 
}

function add_posts($user_instagram_data, $user_id){
	
	foreach($user_instagram_data['items'] as $item){
		$id = str_replace("_" ,"" , $item['id']);
					
		if(!instagram_id_exist($id)){
			$my_post = array(
				'post_author' => $user_id,
				'post_title' => wp_strip_all_tags($item['caption']['text']),
				'post_status' => 'publish',
				'post_type' => 'instagram_post',
			);
			
			if($item['videos']){
				$getImageFile = $item['videos']['standard_resolution']['url'];
				$clean_url_for_video =  str_replace("\/" ,"/" , $getImageFile);
				$video_url = my_attach_external_video($clean_url_for_video);

				if(!is_object($video_url)){
					$my_post['post_content'] = "[video width=\"640\" height=\"640\" mp4=\"".$video_url."\"][/video]";
				} else {
					echo "Error creating video post.<br />";
				}
			}	
						
			$post_id = wp_insert_post($my_post);
			update_post_meta($post_id, "post_id", $id);

			echo "Post titled \"".$item['caption']['text']."\" created.<br />";

			// Print logs out as it runs.
			ob_flush();
			flush();
		}
						
		$getImageFile = $item['images']['standard_resolution']['url']; 			
		$wp_filetype = wp_check_filetype( $getImageFile, null );
		my_attach_external_image($getImageFile,$post_id,$my_post);
	}
	
	$instagram_id = $item['id'];
	return $instagram_id;			
}

function fetchPost($username, $max_id){
	$url = "https://www.instagram.com/".$username."/media/?max_id=" . $max_id;
	$json = file_get_contents($url);
	$array = json_decode($json, true);	
	return $array;
}

function instagram_id_exist($id){
	$args = array(
	    'meta_value' => $id,
	    'post_type' => "instagram_post",
	);

	$posts = get_posts($args);
	
	if($posts){
		return TRUE;
	}else{
		return FALSE;
	}	
}

?>