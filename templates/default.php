<?php
/**
 * Template with images in background
 */

$block .= '<div class="instagram_line">';
$i = 0;
if(isset($result->data) and !empty($result->data)) {
	foreach ($result->data as $p) {
	    $url_img = $p->images->standard_resolution->url;
	    $url_post = $p->link;
	    $block .= '<a href="'.$url_post.'" target="_blank" class="instagram_pic_bkg pic_'.$i.'" style="background-image:url('.$url_img.')"></a>';
	    $i ++;
	}
} elseif(isset($result->meta->error_message) and !empty($result->meta->error_message)) {
	echo $result->meta->error_message;
}
$block .= '</div>';