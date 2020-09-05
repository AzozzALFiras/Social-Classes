<?php

class linkedin
{
    public $enable_proxies = false;

    public function azf_Video_info($url)
    {
        $web_page = url_get_contents($url, $this->enable_proxies);
        $data_sources = get_string_between($web_page, 'data-sources="', '"');
        if (empty($data_sources)) {
            return false;
        }
        $video["title"] = get_string_between($web_page, '<title>', '</title>');
        $video["source"] = "linkedin";
        $video["thumbnail"] = html_entity_decode(get_string_between($web_page, 'data-poster-url="', '"'));
        $video_url = json_decode(html_entity_decode($data_sources), true)[0]["src"];
        $video["links"][0]["url"] = $video_url;
        $video["links"][0]["type"] = "mp4";
        $video["links"][0]["size"] = get_file_size($video_url, $this->enable_proxies);
        $video["links"][0]["quality"] = "HD";
        $video["links"][0]["mute"] = "no";
        return $video;
    }
}
