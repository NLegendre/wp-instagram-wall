<?php
/**
 * Default template
 */

$block .= '<div class="instagram_line">';
$i = 0;
if(isset($result->data) and !empty($result->data)) {
	foreach ($result->data as $p) {
	    if($i%4 == 0 and $i != 0) {
	        $block .= '</div>';
	        $block .= '<div class="instagram_line">';
	    }
	    $url_img = $p->images->standard_resolution->url;
	    $url_post = $p->link;
	    $block .= '<a href="'.$url_post.'" target="_blank" class="instagram_pic pic_'.$i.'"><img src="'.$url_img.'"></a>';
	    $i ++;
	}
} elseif(isset($result->meta->error_message) and !empty($result->meta->error_message)) {
    echo $result->meta->error_message;
}
$block .= '</div>';