<?php
if ( !class_exists( 'pws_wpcsv_engine' ) ) {
	class pws_wpcsv_engine {

		var $post_fields = array( );

		function __construct( $settings ) { // Constructor
			$this->post_fields = array( 'ID', 'post_date', 'post_status', 'post_title', 'post_content', 'post_excerpt', 'post_parent', 'post_name', 'post_type', 'ping_status', 'comment_status', 'menu_order', 'post_author' );
			$this->settings = $settings;
		}

		function export( $post_type = NULL ) {
			global $wpdb;

			$posts_table = $wpdb->prefix . 'posts';
			$postmeta_table = $wpdb->prefix . 'postmeta';
			$post_fields = implode( ", ", $this->post_fields );
			$sql = "SELECT DISTINCT $post_fields FROM $posts_table WHERE post_status in ('publish','future', 'private') AND post_type NOT IN ( 'nav_menu_item' ) ORDER BY post_modified DESC";

			if ( $post_type ) {
				$filter = "post_type = '".$post_type."'";
			} else {
				$filter = "post_type NOT IN ( 'nav_menu_item' )";
			}
			$sql = "SELECT DISTINCT {$post_fields} FROM {$posts_table} WHERE post_status in ('publish','future','private','draft') AND {$filter} ORDER BY post_modified DESC";

			$posts = $wpdb->get_results( $sql );

			if ( isset( $posts[0] ) ) {
      	$sql = "SELECT DISTINCT meta_key FROM $postmeta_table WHERE meta_key NOT LIKE '\_%'";
				$custom_fields = $wpdb->get_col( $sql );
				
				$post1 = get_object_vars($posts[0]);

		  		$post_array[] = array_merge( array_keys( $post1 ), array( 'post_tags', 'post_categories' ), Array( 'thumbnail' ), $custom_fields );

				$meta_array = array( ); 
				foreach ( $custom_fields as $cf ) {
					$cf = mysql_real_escape_string( $cf );
					$sql = "SELECT post_id, meta_value FROM $postmeta_table WHERE meta_key = '$cf'";
					$results = $wpdb->get_results( $sql, OBJECT_K );  
					$meta_array[$cf] = $results;
				}

				foreach ( $posts as $p ) { 
					$p = get_object_vars( $p );
					$id = $p['ID'];

					// Process thumb separately
					$thumb_id = get_post_thumbnail_id( $id );
					$thumb_src = wp_get_attachment_image_src( $thumb_id, 'full' );
					$thumb_url = $thumb_src[0];
					$upload_dir = wp_upload_dir();
					$thumb_file = preg_replace( '|' . WP_CONTENT_URL . '/' . basename( $upload_dir['baseurl'] ) . '/|', '', $thumb_url );

					$cfs = array( );
					foreach ( $custom_fields as $cf ) {
						$val = $meta_array[$cf][$id]->meta_value;
						$cfs[] = $val;
					}

					# Convert User id to username
					if ( !empty( $p['post_author'] ) ) {
						$user = get_user_by( 'id', $p['post_author'] );
						$p['post_author'] = $user->get( 'user_login' );
					}

					// Get post tags for each post
					$post_tags = wp_get_post_tags( $id );
					$tags = array();

					foreach($post_tags as $t) {
						$tag = get_category( $t );
						$tags[] = trim( $tag->slug );
					}

					// Get post categories for each post
					$post_categories = wp_get_post_categories( $id );
					$cats = array();
	
					foreach($post_categories as $c){
						$cat = get_category( $c );
						$cats[] = trim( $cat->name ) . ':' . trim( $cat->slug );
					}

					$taxonomy_fields = array( implode( ",", $tags ), implode(",", $cats ) );

					$post_array[] = array_merge( array_values( $p ), array_values( $taxonomy_fields ), Array( $thumb_file ), $cfs );
				}
			}

      return $post_array;
		}

	
		function import( $posts ) {
			$stats = array( 'Insert' => array( ), 'Update' => array( ), 'Delete' => array( ), 'Error' => array( ) );

			$this->row_index = 0;
			foreach( $posts as $post ) {
				$result = $this->import_post( $post, false );
				foreach( $result as $k => $v ) {
					$stats[$k][] = $v;
				}
			}
			return $stats;
		}

		function import_post( $post, $perm_delete ) { 

			$cf = array( );
			$this->row_index++;

			foreach( $post as $key => $val ) {
				$attach_id = NULL;
				if ( ( in_array( $key, $this->post_fields ) ) || ( in_array( $key, array( 'post_categories', 'post_tags') ) ) ) {
					$p[$key] = $val;
				} elseif ( $key != 'thumbnail' ) {
					$cf[$key] = $val;
				} elseif ( $key == 'thumbnail' ) {
					$thumb_file = $val;
				} // End if
			} // End foreach
			global $wpdb;
			$posts_table = $wpdb->prefix . 'posts';

			// Pre-import data sanitization

			if ( preg_match( '/\//', $p['post_date'] ) ) { # If it has slashes then determine US/English format
				if ( $this->settings['date_format'] == 'US' ) {
					list( $mm, $dd, $the_rest ) = explode( '/', $p['post_date'] );
				} else {
					list( $dd, $mm, $the_rest ) = explode( '/', $p['post_date'] );
				}
				list( $yyyy, $time ) = explode( ' ', $the_rest );
				$p['post_date'] = "{$yyyy}-{$mm}-{$dd} $time";
			}

			$p['post_date'] = date( 'Y-m-d H:i:s', strtotime( $p['post_date'] ) );
			$p['post_date_gmt'] = get_gmt_from_date( $p['post_date'] ); 
			if ( $p['post_parent'] > 0 ) {
				$post_parent = get_post( $p['post_parent'], ARRAY_A );
				if ( !isset( $post_parent ) || $post_parent['post_type'] != 'page' ) {
					$id = ( empty( $p['ID'] ) ) ? "[row {$this->row_index}]" : $p['ID'];
					$action['Error'] = Array( 'id' => $id, 'error_id' => ERROR_MISSING_POST_PARENT );
				}
			}

			# Convert User id to username
			if ( !empty( $p['post_author'] ) ) {
				$user = get_user_by( 'login', $p['post_author'] );
				
				if ( $user ) {
					$p['post_author'] = $user->get( 'ID' );
				} else {
					$id = ( empty( $p['ID'] ) ) ? "[row {$this->row_index}]" : $p['ID'];
					$action['Error'] = Array( 'id' => $id, 'error_id' => ERROR_INVALID_AUTHOR );
				}
			}

			// CREATE
			if ( $p['ID'] == "" ) { 

				$id = wp_insert_post( $p );

				// update tags
				$tags = explode( ",", $p['post_tags'] );
				array_walk( $tags, create_function( '&$v, $k', '$v = trim($v);' ) );

				foreach( $tags as $tag ) {
					$tarr = get_term_by( 'slug', $tag, 'post_tag' );
					$tag_ids[] = $tar['term_id'];
				}

				$tag_ids = implode( ",", $tags );
				wp_set_post_tags( $id, $tag_ids, FALSE );

				// update categories
				if ( !empty( $p['post_categories'] ) ) {
					$cat_ids = $this->get_cat_ids( $p['post_categories'] );
					wp_set_object_terms( $id, $cat_ids, 'category', FALSE );
				} else { // Remove all categories - probably does nothing on creates
					wp_set_object_terms( $id, array( ), 'category', FALSE );
				}

				// wp_insert_post and wp_publish_post don't appear to support publishing to the future, so hack required:
				if ( strtotime( $p['post_date'] ) > time() ) {
					$wpdb->update( $posts_table, array( 'post_status' => 'future' ), array( 'ID' => $id ) );
				}

				# Custom fields	
				foreach( $cf as $key => $val ) {
					if ( !empty( $val ) ) { 
						add_post_meta( $id, $key, $val, true );
					}
				}


				// Add thumbnail if one can be found
				if ( !empty( $thumb_file ) ) { // Ignore blank thumb_file fields

					// Check media library for image
					$sql = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value = '$thumb_file'";
					$attach_id = $wpdb->get_var( $sql );

					if ( empty( $attach_id ) ) { // Not found in media library, check folder
						$imagefile = WP_CONTENT_DIR . '/uploads/' . $thumb_file;
						$imageurl = WP_CONTENT_URL . '/uploads/' . $thumb_file;
						$imported = false;
						if ( is_file( $imagefile ) ) {
							$attach_id = $this->import_image( $imagefile, $imageurl ); // Import image, maybe refactor to use WP media_handle_upload function
							$imported = true;
						}
					}

					if ( isset( $attach_id ) && !empty( $attach_id ) ) { // If image found in media library or folder, add meta data and link to post.
						// Get path to image
						$image_record = get_post( $attach_id, 'ARRAY_A' );
						$guid = $image_record['guid'];
						$filepath = WP_CONTENT_DIR . preg_replace( '/' . addcslashes( WP_CONTENT_URL, '/' ) . '/', '', $guid );
						
						if ( $imported ) {
							// Get meta data
							$image_meta = $this->get_image_metadata( $filepath );

							// Attach meta data
							$this->add_post_image_meta( $attach_id, $id, $filepath, $image_meta );
						}

						// Attach image to post
						update_post_meta( $id, '_thumbnail_id', $attach_id );
					} else { // No image found but thumb specified
						// Error message
					}
				} else { // If the field is empty, then any thumb should be detached from the post
					delete_post_meta( $id, '_thumbnail_id' );					
				}

				wp_publish_post( $id );
				$action['Insert'] = $id;
			} else {
				$pid = ( $p['ID'] < 0 ) ? $p['ID']*-1 : $p['ID'];
				$post_val = get_post($pid);
				$post_exists = ( !empty( $post_val ) ) ? TRUE : FALSE;

				// MODIFY
				if ( $post_exists ) {
					if ( is_numeric( $p['ID'] ) && $p['ID'] >  0 ) {

						wp_update_post( $p );
	
						// wp_update_post and wp_publish_post don't appear to support publishing to the future, so hack required:
						if ( strtotime( $p['post_date'] ) > time() ) {
							$wpdb->update( $posts_table, array( 'post_status' => 'future' ), array( 'ID' => $p['ID'] ) );
						}

						// update tags
						$tags = explode( ",", $p['post_tags'] );
						array_walk( $tags, create_function( '&$v, $k', '$v = trim($v);' ) );

						foreach( $tags as $tag ) {
							$tarr = get_term_by( 'slug', $tag, 'post_tag' );
							$tag_ids[] = $tar['term_id'];
						}

						$tag_ids = implode( ",", $tags );
						wp_set_post_tags( $p['ID'], $tag_ids, FALSE );

						// update categories
						if ( !empty( $p['post_categories'] ) ) {
							$cat_ids = $this->get_cat_ids( $p['post_categories'] );
							wp_set_object_terms( $p['ID'], $cat_ids, 'category', FALSE );
						} else { // Remove all categories
							wp_set_object_terms( $p['ID'], array( ), 'category', FALSE );
						}

						foreach( $cf as $key => $val ) {
							if ( !empty( $val ) ) {
								update_post_meta( $p['ID'], $key, $val );
							} else {
								delete_post_meta( $p['ID'], $key );
							}
						}

						// Add thumbnail if one can be found
						if ( !empty( $thumb_file ) ) { // Ignore blank thumb_file fields

							// Check media library for image
							$sql = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value = '$thumb_file'";
							$attach_id = $wpdb->get_var( $sql );

							if ( empty( $attach_id ) ) { // Not found in media library, check folder
								$imagefile = WP_CONTENT_DIR . '/uploads/' . $thumb_file;
								$imageurl = WP_CONTENT_URL . '/uploads/' . $thumb_file;
								$imported = false;
								if ( is_file( $imagefile ) ) {
									$attach_id = $this->import_image( $imagefile, $imageurl ); // Import image, maybe refactor to use WP media_handle_upload function
									$imported = true;
								}
							}

							if ( isset( $attach_id ) && !empty( $attach_id ) ) { // If image found in media library or folder, add meta data and link to post.
								// Get path to image
								$image_record = get_post( $attach_id, 'ARRAY_A' );
								$guid = $image_record['guid'];
								$filepath = WP_CONTENT_DIR . preg_replace( '/' . addcslashes( WP_CONTENT_URL, '/' ) . '/', '', $guid );

								if ( $imported ) {
									// Get meta data
									$image_meta = $this->get_image_metadata( $filepath );

									// Attach meta data
									$this->add_post_image_meta( $attach_id, $id, $filepath, $image_meta );
								}

								// Attach image to post
								update_post_meta( $p['ID'], '_thumbnail_id', $attach_id );
							} else { // No image found but thumb specified
								// Error message
							}
						} else { // If the field is empty, then any thumb should be detached from the post
							delete_post_meta( $p['ID'], '_thumbnail_id' );					
						}

						$action['Update'] = $pid;
					}
	
					if ( $p['ID'] <  0 ) { // Delete
						$id = $p['ID']*-1; // Unsign integer
					
						wp_delete_post( $id, $perm_delete ); // Move to trash or delete permanently
						$action['Delete'] = $pid;
					}
				} else { // Post ID doesn't exist
					$action['Error'] = Array( 'id' => $pid, 'error_id' => ERROR_MISSING_POST_ID );
				}
			}
			return $action;
		}

		/*
		
		Function: get_cat_ids
		 
		Description: A comma separated string specifying each category to be created and/or associated with the post. ie
		
		1. 'one, two, three' will create categories of the same names and same slugs
		2. 'one:slug1, two:slug2, three:slug3' will create categories with names one, two, three and slugs slug1, slug2, slug3
		3. 'parent:parentslug, parentslug~child:childslug' will first create the parent category, and then attach a child category to it
						
		@param $csv_cats

		*/

		function get_cat_ids( $csv_cats ) {
		
			$this->pwsd = FALSE;
			
			$this->elog( $csv_cats );
			
			$cats = explode( ",", trim( $csv_cats, ',' ) );
			
			array_walk( $cats, create_function( '&$v, $k', '$v = trim($v);' ) );
			foreach ( $cats as $c ) {
	
				if ( empty( $c ) ) continue;
				$this->elog( 'start loop' );
	
				$psplit = explode( '~', $c );
	
				$this->elog( $c, 'c' );
	
				$this->elog( $psplit, 'psplit' );

				if ( count( $psplit ) == 2 ) {
					$cat_parent = get_term_by( 'slug', $psplit[0], 'category', ARRAY_A );
					$this->elog( $cat_parent, 'cat parent term' );
					$cat_parent = $cat_parent['term_id']; // Needs to be the id for use in wp_insert_term below
					$c = $psplit[1];
				} else {
					$cat_parent = 0; // No parent
					$this->elog( 'No', 'Parent' );
				}
				$csplit = explode( ':', $c );
				
				$this->elog( $csplit, 'csplit' );			
				
				if ( count( $csplit ) == 2 ) {
					$cat_name = $csplit[0];
					$cat_slug = $csplit[1];
				} else {
					$cat_slug = $csplit[0];
					$cat_name = $csplit[0]; // Name and slug should be the same if slug isn't differentiated
				}

				$this->elog( $cat_name, 'cat name' );
				
				$this->elog( $cat_slug, 'cat slug' );

				$this->elog( $cat_parent, 'cat parent' );

				$term = get_term_by( 'slug', $cat_slug, 'category', ARRAY_A );

				$this->elog( $term, 'get term by' );

				if ( $term ) {
					$cat_ids[] = (int)$term['term_id'];
				} else { // new category
					$new_term = wp_insert_term( $cat_name, 'category', array( 'slug' => $cat_slug, 'parent' => $cat_parent ) );
					$this->elog( $new_term, 'new term' );
					$cat_ids[] = (int)$new_term['term_id'];
				}
			}
			
			$this->pwsd = FALSE;
			
			return $cat_ids;
		}


		function elog( $msg, $tag = 'DEBUG') {
			if ( $this->pwsd ) {
				if ( is_array( $msg ) || is_object( $msg ) ) {
					$error_msg = $tag . ": " . print_r( $msg, TRUE ) . "\r";
				} else {
					$error_msg = $tag . ": " . $msg . "\r";
				}
				error_log( $error_msg, 3, dirname( __FILE__ ) . '/imp.log' );
			}
		}

		function add_post_image_meta( $image_id, $post_id, $file, $meta ) {

			// Let WP run inbuilt functions
			if ( !is_wp_error($image_id) ) {
				wp_update_attachment_metadata( $image_id, wp_generate_attachment_metadata( $image_id, $file ) );
			}

			if ( !isset( $meta['caption'] ) ) $meta['caption'] = '';

			// Manually update the image title, content, etc
			$image_data = array(
				'ID' => $image_id,
				'post_title' => $meta['title'],
				'post_content' => $meta['content'],
				'post_excerpt' => $meta['caption'],
				'post_name' => $meta['title']	
			);
			wp_update_post( $image_data );

			
			return; //Disable image meta_data

			foreach( $meta as $key => $val ) {
				if ( substr( $key, 0, 4) == 'iptc' ) {
					update_post_meta( $post_id, $key, $val );
				} elseif ( substr( $key, 0, 4) == 'exif' ) {
					update_post_meta( $post_id, $key, $val );
				}
			}
		}
 
		function import_image( $file, $url ) {

			// Get mime type - necessary here or wp_get_attachment_meta fails later
			$mimetype = wp_check_filetype($file, null );

			// Construct the attachment array
			$attachment = array(
				'post_mime_type' => $mimetype['type'],
				'guid' => $url,
				'post_parent' => 0,
				'post_title' => 'temp_title',
				'post_content' => 'temp_content'
			);

			// Save the data
			$image_id = wp_insert_attachment($attachment, $file);

			return $image_id;
		}

		function get_image_metadata( $file ) {

			$temp = wp_check_filetype($file, null );
			$meta['mimetype'] = $temp['type'];
			$meta['title'] = explode( '.', basename( $file ) );
			$meta['title'] = $meta['title'][0];
			$meta['content'] = '';

			require_once(ABSPATH . "wp-admin" . '/includes/image.php');
			// use image exif/iptc data for title and caption defaults if possible
			if ( $image_meta = @wp_read_image_metadata($file) ) {
				if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) )
					$meta['title'] = $image_meta['title'];
				if ( trim( $image_meta['caption'] ) )
					$meta['content'] = $image_meta['caption'];
			}

			// EXIF
			if ( in_array( $meta['mimetype'], Array( 'image/jpg', 'image/jpeg', 'image/tiff' ) ) ) {
				$exif = exif_read_data( $file );
			}
			// IPTC
			$size = getimagesize( $file, $info );
			if ( isset( $info['APP13'] ) ) {
				$iptc = iptcparse( $info['APP13'] );
			}

			if ( isset( $exif['ExifImageWidth'] ) ) {
				$meta['exif_width'] = $exif['ExifImageWidth'];
			} elseif ( isset( $exif['COMPUTED']['Width'] ) ) {
				$meta['exif_width'] = $exif['COMPUTED']['Width'];
			}

			if ( isset( $exif['ExifImageLength'] ) ) {
				$meta['exif_height'] = $exif['ExifImageLength'];
			} elseif ( isset( $exif['COMPUTED']['Height'] ) ) {
				$meta['exif_height'] = $exif['COMPUTED']['Height'];
			}

			
			if ( ( $meta['exif_width'] * 0.9) <= $meta['exif_height'] && ( $meta['exif_width'] * 1.1 ) >= $meta['exif_height'] ) {
				$meta['exif_orientation'] = 'square';
			} elseif ( ( $meta['exif_height'] * 1.9) <= $meta['exif_width'] ) {
				$meta['exif_orientation'] = 'panorama';
			} elseif ( isset( $exif['Orientation'] ) && in_array( $exif['Orientation'], array( 1, 2, 3, 4 ) ) ) {
				$meta['exif_orientation'] = 'landscape';
			} elseif ( isset( $exif['Orientation'] ) &&  in_array( $exif['Orientation'], array( 5, 6, 7, 8 ) ) ) {
				$meta['exif_orientation'] = 'portrait';
			} else {
				$meta['exif_orientation'] = 'landscape';
			}

			if ( isset( $exif['DateTime'] ) ) {
				$meta['exif_created'] = $exif['DateTime'];
			} elseif ( isset( $exif['FileDateTime'] ) ) {
				$meta['exif_created'] = date( 'Y:m:d H:i:s', $exif['FileDateTime'] );
			}

			$meta['iptc_author'] = ( isset( $iptc['2#080'] ) ) ? implode( ',', $iptc['2#080'] ) : '';

			return $meta;
		}

	}
}
?>
