<?php
/**
 * Template with Bootstrap Framework
 */

$block .= '<div class="row">';
$i = 0;
if(isset($result->data) and !empty($result->data)) {
    foreach ($result->data as $p) {
        if($i%4 == 0 and $i != 0) {
            $block .= '</div>';
            $block .= '<div class="row">';
        }
        $url_img = $p->images->standard_resolution->url;
        $url_post = $p->link;
        $block .= '<div class="col-sm-3 col-xs-6">';
        $block .= '<a href="'.$url_post.'" target="_blank" class="pic_'.$i.'"><img src="'.$url_img.'" class="img-responsive"></a>';
        $block .= '</div>';
        $i ++;
    }
} elseif(isset($result->meta->error_message) and !empty($result->meta->error_message)) {
    echo $result->meta->error_message;
}
$block .= '</div>';