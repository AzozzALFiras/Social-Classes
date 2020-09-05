<?php

class imgur
{
    public $enable_proxies = false;
    public $url;

    function azf_Video_info($url)
    {
        $curl_content = url_get_contents($url, $this->enable_proxies);
        $video["title"] = $this->get_title($curl_content);
        $video["source"] = "imgur";
        $video["thumbnail"] = $this->get_thumbnail($curl_content);
        $video["links"]["0"]["url"] = $this->get_media($curl_content);
        $video["links"]["0"]["type"] = "mp4";
        $video["links"]["0"]["size"] = get_file_size($video["links"]["0"]["url"], $this->enable_proxies);
        $video["links"]["0"]["quality"] = "HD";
        $video["links"]["0"]["mute"] = "no";
        return $video;
    }

    function get_title($curl_content)
    {
        if (preg_match_all('/og:title"\s*content="([^"]+)"/', $curl_content, $match)) {
            return $match[1][0];
        } else if (preg_match_all('/property="og:title"\s*content="([^"]+)"/', $curl_content, $match)) {
            return $match[1][0];
        }
    }

    function get_thumbnail($curl_content)
    {
        if (preg_match_all('/meta itemprop="thumbnailUrl"\s*content="([^"]+)"/', $curl_content, $match)) {
            return $match[1][0];
        } else if (preg_match_all('/property="og:image"\s*content="([^"]+)"/', $curl_content, $match)) {
            return $match[1][0];
        }
    }

    function get_media($curl_content)
    {
        if (preg_match_all('/meta itemprop="contentURL"\s*content="([^"]+)"/', $curl_content, $match)) {
            return $match[1][0];
        } else if (preg_match_all('/property="og:video"\s*content="([^"]+)"/', $curl_content, $match)) {
            return $match[1][0];
        }
    }
}
