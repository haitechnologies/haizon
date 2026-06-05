<?php

	/*
	|--------------------------------------------------------------------------|
	|--------------------------------------------------------------------------|
	|--------------------------------------------------------------------------|
	*/


	/*
	|--------------------------------------------------------------------------
	| 	UPLOAD PHOTO ONLY
	|--------------------------------------------------------------------------
	|
	*/

	function upload_photo($photo, $renamed_photo, $photo_upload_path, $allowed_file_size){

		$photo_type 		= $_FILES["".$photo.""]["type"];
		$photo_size 		= $_FILES["".$photo.""]["size"];
		$photo_error 		= $_FILES["".$photo.""]["error"];
		$photo_tmp_name = $_FILES["".$photo.""]["tmp_name"];

		$message='';
		////////////// VALIDATION //////////////////
		if  ($photo_error > 0)												{	$message = "Error While Uploading Image."; }
		if (validatePhotoSize($photo_size, $allowed_file_size) == false) 	{ 	$message = 'Maximum Photo Size is: '.$allowed_file_size.' MB.'; }
		else if (validatePhotoType($photo_type) == false)					{	$message = 'Allowed Photo Types are webp, jpg, jpeg, gif.'; }  //Allowed Photo Types
		else if (file_exists("".$photo_upload_path."".$renamed_photo.""))	{ 	$message = $photo." Already Exists."; }
		else {

			// UPLOAD NEW PHOTO
			move_uploaded_file($photo_tmp_name, "".$photo_upload_path."".$renamed_photo."");

		}//endif

		return $message;
	}

	/*
	|--------------------------------------------------------------------------
	| 	UPLOAD PHOTO AND GENERATE THUMBNAIL
	|--------------------------------------------------------------------------
	|
	*/
	function uploadPhoto($photo, $renamed_photo, $photo_upload_path, $allowed_file_size, $old_photo='', $thumb_width = '150', $thumb_height = '100'){

		$photo_type 		= $_FILES["".$photo.""]["type"];
		$photo_size 		= $_FILES["".$photo.""]["size"];
		$photo_error 		= $_FILES["".$photo.""]["error"];
		$photo_tmp_name 	= $_FILES["".$photo.""]["tmp_name"];

		$message='';
		////////////// VALIDATION //////////////////
		if  ($photo_error > 0)												{	$message = "Error While Uploading Image."; }
		if (validatePhotoSize($photo_size, $allowed_file_size) == false) 	{ 	$message = 'Maximum Photo Size is: '.$allowed_file_size.' MB.'; }
		else if (validatePhotoType($photo_type) == false)					{	$message = 'Allowed Photo Types are webp, jpg, jpeg, gif.'; }  //Allowed Photo Types
		else if (file_exists("".$photo_upload_path."".$renamed_photo.""))	{ 	$message = $photo." Already Exists."; }
		else {

			// UPLOAD NEW PHOTO //
			if (move_uploaded_file($photo_tmp_name, "".$photo_upload_path."".$renamed_photo."")){

				// RESIZE AND CROP //
				resize_and_crop("".$photo_upload_path."".$renamed_photo."", "".$photo_upload_path."thumbs/".$renamed_photo."", $thumb_width = '150', $thumb_height = '100', $quality=100);

				if (!empty($old_photo)){
					delete_photo($old_photo, $photo_upload_path, '1'); // DELETE OLD THUMB
					delete_photo($old_photo, $photo_upload_path, '0'); // DELETE OLD PHOTO
				}
			}

		}//endif

			return $message;
	}
	
	/*
	|--------------------------------------------------------------------------
	| 	UPLOAD PHOTO AND GENERATE THUMBNAIL
	|--------------------------------------------------------------------------
	|
	*/
	function uploadNewPhoto($photo, $renamed_photo, $photo_upload_path, $allowed_file_size, $old_photo = '', $width = '500', $height = '500', $thumb_width = '150', $thumb_height = '150'){

		$photo_type 		= $_FILES["".$photo.""]["type"];
		$photo_size 		= $_FILES["".$photo.""]["size"];
		$photo_error 		= $_FILES["".$photo.""]["error"];
		$photo_tmp_name 	= $_FILES["".$photo.""]["tmp_name"];

		$message='';
		////////////// VALIDATION //////////////////
		if  	($photo_error > 0)												{	$message = "Error While Uploading Image."; }
		if 		(validatePhotoSize($photo_size, $allowed_file_size) == false) 	{ 	$message = 'Maximum Photo Size is: '.$allowed_file_size.' MB.'; }
		else if (validatePhotoType($photo_type) == false)					{	$message = 'Allowed Photo Types are webp, jpg, jpeg, gif.'; }  //Allowed Photo Types
		else if (file_exists("".$photo_upload_path."".$renamed_photo.""))	{ 	$message = $photo." Already Exists."; }
		else {

			// UPLOAD NEW PHOTO //
			if (move_uploaded_file($photo_tmp_name, "".$photo_upload_path."".$renamed_photo."")){

				// RESIZE AND CROP THUMBNAIL //
				resize_and_crop("".$photo_upload_path."".$renamed_photo."", "".$photo_upload_path."thumbs/".$renamed_photo."", $thumb_width, $thumb_height, $quality= 100);
				
				// RESIZE AND CROP PHOTO //
				resize_and_crop("".$photo_upload_path."".$renamed_photo."", "".$photo_upload_path."".$renamed_photo."", $width, $height, $quality=100);

				if (!empty($old_photo)){
					delete_photo($old_photo, $photo_upload_path, '1'); // DELETE OLD THUMB
					delete_photo($old_photo, $photo_upload_path, '0'); // DELETE OLD PHOTO
				}
			}

		}//endif

			return $message;
	}

	/*
	|--------------------------------------------------------------------------
	| 	validatePhotoType
	|--------------------------------------------------------------------------
	|
	*/
	function validatePhotoType($photo_type){

		$allowed_photo_types = array("image/webp", "image/gif", "image/jpeg", "image/jpg", "image/pjpeg", "image/x-png", "image/png");

		if ( in_array($photo_type, $allowed_photo_types) ){
			return true;
		}
		return false;
	}


	/*
	|--------------------------------------------------------------------------
	| 	validatePhotoSize
	|--------------------------------------------------------------------------
	|
	*/
	function validatePhotoSize($photo_size, $allowed_photo_size){

		$allowed_photo_size_kb 			= $allowed_photo_size*1024;
		$allowed_photo_size_bytes 	= $allowed_photo_size_kb*1024;

		if ( $photo_size < $allowed_photo_size_bytes){
			return true;
		}
		return false;
	}


	/*
	|--------------------------------------------------------------------------
	| 	renamePhoto
	|--------------------------------------------------------------------------
	|
	*/
	function renamePhoto($photo_name, $complete=0){
		$extension = explode(".", $photo_name);

		if ($complete == 1){
			// Complete Rename
			$random_number = rand(1,99999999);
			$photo_name = $random_number.'.'.$extension[1];
			return $photo_name;

		} else if ($complete == 0) {
			// Concatenate Random Digits
			$random_number = rand(1,99999);
			$photo_name = $random_number.$photo_name;
			return $photo_name;
		}
		return $photo_name; // Without Renaming
	}

	/*
	|--------------------------------------------------------------------------
	| 	renameFullPhoto
	|--------------------------------------------------------------------------
	|
	*/
	function full_rename($photo_name, $new_name){
		$extension = explode(".", $photo_name);

		// Complete Rename
		$photo_name = $new_name.'.'.$extension[1];

		return $photo_name;
	}


	/*
	|--------------------------------------------------------------------------
	| 	resize_and_crop
	|--------------------------------------------------------------------------
	|
	*/
	function resize_and_crop($original_image_url, $thumb_image_url, $thumb_w, $thumb_h, $quality=75)
		{

				$get_extension = explode('.', $original_image_url);
				$extension =  $get_extension[count($get_extension)-1];

				if ($extension=='png'){
					$original = imagecreatefrompng($original_image_url);
				
				// Add webp image support
				} else if ($extension=='webp'){
					$original = imagecreatefromwebp($original_image_url);

				} else {
					// ACQUIRE THE ORIGINAL IMAGE: http://php.net/manual/en/function.imagecreatefromjpeg.php
					$original = imagecreatefromjpeg($original_image_url);
				}
				if (!$original) return FALSE;

				// GET ORIGINAL IMAGE DIMENSIONS
				list($original_w, $original_h) = getimagesize($original_image_url);

				// RESIZE IMAGE AND PRESERVE PROPORTIONS
				$thumb_w_resize = $thumb_w;
				$thumb_h_resize = $thumb_h;
				if ($original_w > $original_h)
				{
						$thumb_h_ratio  = $thumb_h / $original_h;
						$thumb_w_resize = (int)round($original_w * $thumb_h_ratio);
				}
				else
				{
						$thumb_w_ratio  = $thumb_w / $original_w;
						$thumb_h_resize = (int)round($original_h * $thumb_w_ratio);
				}
				if ($thumb_w_resize < $thumb_w)
				{
						$thumb_h_ratio  = $thumb_w / $thumb_w_resize;
						$thumb_h_resize = (int)round($thumb_h * $thumb_h_ratio);
						$thumb_w_resize = $thumb_w;
				}

				// CREATE THE PROPORTIONAL IMAGE RESOURCE
				$thumb = imagecreatetruecolor($thumb_w_resize, $thumb_h_resize);
				if (!imagecopyresampled($thumb, $original, 0,0,0,0, $thumb_w_resize, $thumb_h_resize, $original_w, $original_h)) return FALSE;

				// ACTIVATE THIS TO STORE THE INTERMEDIATE IMAGE
				// imagejpeg($thumb, 'RAY_temp_' . $thumb_w_resize . 'x' . $thumb_h_resize . '.jpg', 100);

				// CREATE THE CENTERED CROPPED IMAGE TO THE SPECIFIED DIMENSIONS
				$final = imagecreatetruecolor($thumb_w, $thumb_h);

				$thumb_w_offset = 0;
				$thumb_h_offset = 0;
				if ($thumb_w < $thumb_w_resize)
				{
						$thumb_w_offset = (int)round(($thumb_w_resize - $thumb_w) / 2);
				}
				else
				{
						$thumb_h_offset = (int)round(($thumb_h_resize - $thumb_h) / 2);
				}

				if (!imagecopy($final, $thumb, 0,0, $thumb_w_offset, $thumb_h_offset, $thumb_w_resize, $thumb_h_resize)) return FALSE;

				// STORE THE FINAL IMAGE - WILL OVERWRITE $thumb_image_url
				if (!imagejpeg($final, $thumb_image_url, $quality)) return FALSE;
				return TRUE;
	}


	/*
	|--------------------------------------------------------------------------
	| 	display_photo
	|--------------------------------------------------------------------------
	|
	*/
	function display_photo($photo, $module, $width=200, $height=150){
			$draw_photo = '';

			// THUMB
			if ( !empty($photo) && file_exists("../uploads/".$module."/thumbs/".$photo."")){
				$draw_photo = '<img src="../uploads/'.$module.'/thumbs/'.$photo.'" width="'.$width.'" height="'.$height.'" alt="" />';

			// ACTUAL PHOTO
			} else if ( !empty($photo) && file_exists("uploads/".$module."/".$photo."")){
				$draw_photo = '<img src="../uploads/'.$module.'/'.$photo.'" width="'.$width.'" height="'.$height.'" alt="" />';

			// DEFAULT PHOTO
			} else {
				$draw_photo = '<img src="../images/no-image.png" width="100" height="100" alt="" />';
			}

		return $draw_photo;
	}


	/*
	|--------------------------------------------------------------------------
	| 	display_delete_photo
	|--------------------------------------------------------------------------
	|
	*/
	function display_delete_photo($querystring){
		return '<a href="'.$querystring.'"><img src="images/delete-icon.png" alt="" width="16" height="16" /></a>';
	}

	/*
	|--------------------------------------------------------------------------
	| 	delete_photo_thumb
	|--------------------------------------------------------------------------
	|
	*/
	function delete_photo_and_thumb($old_photo, $photo_upload_path){

		//DELETE THUMB
		if (!empty($old_photo) && file_exists("".$photo_upload_path."thumbs/".$old_photo."")){
			unlink("".$photo_upload_path."thumbs/".$old_photo."");
		}

		//DELETE PHOTO
		if (!empty($old_photo) && file_exists("".$photo_upload_path."".$old_photo."")){
			unlink("".$photo_upload_path."".$old_photo."");
		}

	}


	/*
	|--------------------------------------------------------------------------
	| 	delete_photo
	|--------------------------------------------------------------------------
	|
	*/
	function delete_photo($old_photo, $photo_upload_path, $thumb){

		//DELETE THUMB
		if ($thumb==1){
			if (!empty($old_photo) && file_exists("".$photo_upload_path."thumbs/".$old_photo."")){
				unlink("".$photo_upload_path."thumbs/".$old_photo."");
			}

		//DELETE PHOTO
		} else {
			if (!empty($old_photo) && file_exists("".$photo_upload_path."".$old_photo."")){
				unlink("".$photo_upload_path."".$old_photo."");
			}
		}
	}

	/*
	|--------------------------------------------------------------------------
	| 	display_small_photo
	|--------------------------------------------------------------------------
	|
	*/
	function display_small_photo($photo, $module, $empty=''){
			$draw_photo = '';

			// THUMB
			if ( !empty($photo) && file_exists("uploads/".$module."/thumbs/".$photo."")){
				$draw_photo = '<img src="uploads/'.$module.'/thumbs/'.$photo.'" width="75" height="50" alt="" />';

			// ACTUAL PHOTO
			} else if ( !empty($photo) && file_exists("uploads/".$module."/".$photo."")){
				$draw_photo = '<img src="uploads/'.$module.'/'.$photo.'" width="75" height="50" alt="" />';

			// DEFAULT PHOTO
			} else {
				$draw_photo = '<img src="images/noimage.png" width="75" height="50" alt="" />';
			}

		return $draw_photo;
	}




	/*
	|--------------------------------------------------------------------------
	| 	uploadPDF
	|--------------------------------------------------------------------------
	|
	*/
	function uploadPDF($pdf, $renamed_pdf, $pdf_upload_path, $allowed_file_size, $old_pdf=''){

		$pdf_type 		= $_FILES["".$pdf.""]["type"];
		$pdf_size 		= $_FILES["".$pdf.""]["size"];
		$pdf_error 		= $_FILES["".$pdf.""]["error"];
		$pdf_tmp_name = $_FILES["".$pdf.""]["tmp_name"];

		$message='';
		////////////// VALIDATION //////////////////
		if  ($pdf_error > 0)																					{	$message = "Error While Uploading Image."; }
		if (validatePDFSize($pdf_size, $allowed_file_size) == false) 	{ $message = 'Maximum PDF Size is: '.$allowed_file_size.' MB.'; }
		else if (validatePDFType($pdf_type) == false)									{	$message = 'Allowed PDF Type is pdf.'; }  //Allowed PDF Types
		else if (file_exists("".$pdf_upload_path."".$renamed_pdf.""))	{ $message = $pdf." Already Exists."; }
		else {

			// UPLOAD NEW PDF //
			if (move_uploaded_file($pdf_tmp_name, "".$pdf_upload_path."".$renamed_pdf."")){

				if (!empty($old_pdf)){
					delete_pdf($old_pdf, $pdf_upload_path, '0'); // DELETE OLD PDF
				}
			}

		}//endif

			return $message;
	}

	/*
	|--------------------------------------------------------------------------
	| 	validatePDFType
	|--------------------------------------------------------------------------
	|
	*/
	function validatePDFType($pdf_type){

		$allowed_pdf_types = array("application/pdf");

		if ( in_array($pdf_type, $allowed_pdf_types) ){
			return true;
		}
		return false;
	}


	/*
	|--------------------------------------------------------------------------
	| 	validatePDFSize
	|--------------------------------------------------------------------------
	|
	*/
	function validatePDFSize($pdf_size, $allowed_pdf_size){

		$allowed_pdf_size_kb 			= $allowed_pdf_size*1024;
		$allowed_pdf_size_bytes 	= $allowed_pdf_size_kb*1024;

		if ( $pdf_size < $allowed_pdf_size_bytes){
			return true;
		}
		return false;
	}


	/*
	|--------------------------------------------------------------------------
	| 	renamePDF
	|--------------------------------------------------------------------------
	|
	*/
	function renamePDF($pdf_name, $complete=0){
		$extension = explode(".", $pdf_name);

		if ($complete == 1){
			// Complete Rename
			$random_number = rand(1,99999999);
			$pdf_name = $random_number.'.'.$extension[1];
			return $pdf_name;

		} else if ($complete == 0) {
			// Concatenate Random Digits
			$random_number = rand(1,99999);
			$pdf_name = $random_number.$pdf_name;
			return $pdf_name;
		}
		return $pdf_name; // Without Renaming
	}

	/*
	|--------------------------------------------------------------------------
	| 	display_delete_pdf
	|--------------------------------------------------------------------------
	|
	*/
	function display_delete_pdf($querystring){
		return '<a href="'.$querystring.'"><img src="images/delete-icon.png" alt="" width="16" height="16" /></a>';
	}

	/*
	|--------------------------------------------------------------------------
	| 	delete_pdf
	|--------------------------------------------------------------------------
	|
	*/
	function delete_pdf($old_pdf, $pdf_upload_path, $thumb){

		if (!empty($old_pdf) && file_exists("".$pdf_upload_path."".$old_pdf."")){
			unlink("".$pdf_upload_path."".$old_pdf."");
		}

	}


/*
	|--------------------------------------------------------------------------
	| 	UPLOAD PHOTO AND GENERATE THUMBNAIL
	|--------------------------------------------------------------------------
	|
	*/
function uploadPhotoArray($photo, $index, $renamed_photo, $photo_upload_path, $allowed_file_size, $old_photo = '', $width = '500', $height = '500', $thumb_width = '150', $thumb_height = '150')
{

	$photo_type 		= $_FILES["" . $photo . ""]["type"][$index];
	$photo_size 		= $_FILES["" . $photo . ""]["size"][$index];
	$photo_error 		= $_FILES["" . $photo . ""]["error"][$index];
	$photo_tmp_name 	= $_FILES["" . $photo . ""]["tmp_name"][$index];

	$message = '';
	////////////// VALIDATION //////////////////
	if ($photo_error > 0) {
		$message = "Error While Uploading Image.";
	}
	if (validatePhotoSize($photo_size, $allowed_file_size) == false) {
		$message = 'Maximum Photo Size is: ' . $allowed_file_size . ' MB.';
	} else if (validatePhotoType($photo_type) == false) {
		$message = 'Allowed Photo Types are webp, jpg, jpeg, gif.';
	}  //Allowed Photo Types
	else if (file_exists("" . $photo_upload_path . "" . $renamed_photo . "")) {
		$message = $photo . " Already Exists.";
	} else {

		// UPLOAD NEW PHOTO //
		if (move_uploaded_file($photo_tmp_name, "" . $photo_upload_path . "" . $renamed_photo . "")) {

			// RESIZE AND CROP THUMBNAIL //
			resize_and_crop("" . $photo_upload_path . "" . $renamed_photo . "", "" . $photo_upload_path . "thumbs/" . $renamed_photo . "", $thumb_width, $thumb_height, $quality = 100);

			// RESIZE AND CROP PHOTO //
			resize_and_crop("" . $photo_upload_path . "" . $renamed_photo . "", "" . $photo_upload_path . "" . $renamed_photo . "", $width, $height, $quality = 100);

			if (!empty($old_photo)) {
				delete_photo($old_photo, $photo_upload_path, '1'); // DELETE OLD THUMB
				delete_photo($old_photo, $photo_upload_path, '1'); // DELETE OLD PHOTO
			}
		}
	} //endif

	return $message;
}


?>
