<?php

class flickr
{
    public $enable_proxies = false;

    function azf_Video_info($url)
    {
        $page_source = url_get_contents($url, $this->enable_proxies);
        preg_match_all('/(.*?)_(.*?)_(.*?).jpg/', $page_source, $secret_key);
        $secret_key = $secret_key[2][0];
        $site_key = get_string_between($page_source, '"site_key":"', '"');
        $media_id = get_string_between($page_source, '"photoId":"', '"');
        $api_url = "https://api.flickr.com/services/rest?photo_id=$media_id&secret=$secret_key&method=flickr.video.getStreamInfo&api_key=$site_key&format=json&nojsoncallback=1";
        $video["title"] = get_string_between($page_source, '<title>', '</title>');
        $video["source"] = "flickr";
        $video["thumbnail"] = get_string_between($page_source, '<meta property="og:image" content="', '"/>');
        if ($media_id != "" && $site_key != "" && $secret_key != "") {
            $streams = url_get_contents($api_url, $this->enable_proxies);
            $streams = json_decode($streams, true)["streams"]["stream"];
            $i = 0;
            foreach ($streams as $stream) {
                $video["links"][$i]["url"] = $stream["_content"];
                $video["links"][$i]["type"] = "mp4";
                $video["links"][$i]["quality"] = (string)$stream["type"];
                $video["links"][$i]["size"] = get_file_size($video["links"][$i]["url"], $this->enable_proxies);
                $video["links"][$i]["mute"] = "no";
                $i++;
            }
            usort($video["links"], 'sort_by_quality');
            return $video;
        } else {
            return false;
        }
    }
}
