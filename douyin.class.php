<?php

class douyin
{
    public $enable_proxies = false;

    public function azf_Video_info($url)
    {
        $web_page = url_get_contents($url, $this->enable_proxies);
        preg_match_all('/playAddr: "(.*?)"/', $web_page, $output);
        if (empty($output[1][0])) {
            return false;
        }
        $video_url = $this->get_video_url($output[1][0]);
        preg_match_all('/cover: "(.*?)"/', $web_page, $thumbnail);
        $video["title"] = get_string_between($web_page, "<title>", "</title>");
        $video["source"] = "douyin";
        $video["thumbnail"] = !empty($thumbnail[1][0]) ? $thumbnail[1][0] : "https://s16.tiktokcdn.com/musical/resource/wap/static/image/logo_144c91a.png?v=2";
        $video["links"][0]["url"] = $video_url;
        $video["links"][0]["type"] = "mp4";
        $video["links"][0]["size"] = get_file_size($video_url, $enable_proxies = false);
        $video["links"][0]["quality"] = "HD";
        $video["links"][0]["mute"] = "no";
        return $video;
    }

    function get_video_url($player_url)
    {
        $ch = curl_init($player_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, _REQUEST_USER_AGENT);
        curl_exec($ch);
        $curlInfo = curl_getinfo($ch);
        return $curlInfo["redirect_url"];
    }
}
