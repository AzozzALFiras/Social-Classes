<?php

class imdb
{
    public $enable_proxies = false;

    function orderArray($arrayToOrder, $keys)
    {
        $ordered = array();
        foreach ($keys as $key) {
            if (isset($arrayToOrder[$key])) {
                $ordered[$key] = $arrayToOrder[$key];
            }
        }
        return $ordered;
    }

    function find_video_id($url)
    {
        preg_match('/vi\d{4,20}/', $url, $match);
        return $match[0];
    }

    function azf_Video_info($url)
    {
        $video_id = $this->find_video_id($url);
        $embed_url = "https://www.imdb.com/video/imdb/$video_id/imdb/embed";
        $embed_source = url_get_contents($embed_url, $this->enable_proxies);
        $video_data = get_string_between($embed_source, '<script class="imdb-player-data" type="text/imdb-video-player-json">', '</script>');
        $video_data = json_decode($video_data, true);
        $video["title"] = get_string_between($embed_source, '<meta property="og:title" content="', '"/>');
        $video["source"] = "imdb";
        $video["thumbnail"] = get_string_between($embed_source, '<meta property="og:image" content="', '"/>');
        if ($video["title"] != "") {
            $streams = $video_data["videoPlayerObject"]["video"]["videoInfoList"];
            $i = 0;
            foreach ($streams as $stream) {
                if ($stream["videoMimeType"] == "video/mp4") {
                    $video["links"][$i]["url"] = $stream["videoUrl"];
                    $video["links"][$i]["type"] = "mp4";
                    $video["links"][$i]["size"] = get_file_size($video["links"][$i]["url"], $this->enable_proxies);
                    $video["links"][$i]["quality"] = "hd";
                    $video["links"][$i]["mute"] = "no";
                    $i++;
                }
            }
            return $video;
        } else {
            return false;
        }
    }
}
