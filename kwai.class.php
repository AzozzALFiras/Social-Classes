<?php

class kwai
{
    public $enable_proxies = false;

    public function azf_Video_info($url)
    {
        $web_page = url_get_contents($url, $this->enable_proxies);
        $video_url = get_string_between($web_page, '<video src="', '"');
        $video["title"] = get_string_between($web_page, '"userName":"', '","headUrl"');
        $video["source"] = "kwai";
        $video["thumbnail"] = get_string_between($web_page, 'poster="', '"');
        $video["links"][0]["url"] = $video_url;
        $video["links"][0]["type"] = "mp4";
        $video["links"][0]["size"] = get_file_size($video_url, $this->enable_proxies);
        $video["links"][0]["quality"] = "HD";
        $video["links"][0]["mute"] = "no";
        return $video;
    }
}
