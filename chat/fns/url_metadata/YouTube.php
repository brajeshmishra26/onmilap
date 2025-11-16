<?php

$result['mime_type'] = 'video/youtube';

if (isset($link_meta_data['title'])) {
    $result['title'] = $link_meta_data['title'];
    $result['image'] = $link_meta_data['thumbnail_url'];

    if (isset($link_meta_data['description'])) {
        $result['description'] = $link_meta_data['description'];
    } else if (isset($link_meta_data['author_name'])) {
        $result['description'] = $link_meta_data['author_name'];
    }

    $yt_pattern = '/<iframe[^>]+src="([^"]+)"/i';
    $yt_embed_code = $link_meta_data['html'];

    if (preg_match($yt_pattern, $yt_embed_code, $yt_matches)) {
        if (isset($yt_matches[1])) {
            $yt_matches = $yt_matches[1];

            if (filter_var($yt_matches, FILTER_VALIDATE_URL) !== false) {
                $result['url'] = $yt_matches;
            }
        }
    }

}