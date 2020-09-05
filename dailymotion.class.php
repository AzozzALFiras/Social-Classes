<?php

class dailymotion
{
    public $enable_proxies = false;
    public $url;

    function find_video_id($url)
    {
        $domain = str_ireplace("www.", "", parse_url($url, PHP_URL_HOST));
        switch (true) {
            case($domain == "dai.ly"):
                $video_id = str_replace('https://dai.ly/', "", $url);
                $video_id = str_replace('/', "", $video_id);
                return $video_id;
                break;
            case($domain == "dailymotion.com"):
                $url_parts = parse_url($url);
                $path_arr = explode("/", rtrim($url_parts['path'], "/"));
                $video_id = $path_arr[2];
                return $video_id;
                break;
        }
    }

    function azf_Video_info($url)
    {
        $video_id = $this->find_video_id($url);
        $web_page = url_get_contents("https://www.dailymotion.com/embed/video/" . $video_id, $this->enable_proxies);
        preg_match_all('/var config =(.*);/', $web_page, $match);
        if (isset($match[1][0]) != "") {
            $data = json_decode($match[1][0], true);
            $video["title"] = $data["metadata"]["title"];
            $video["source"] = "dailymotion";
            $video["thumbnail"] = $data["metadata"]["posters"][max(array_keys($data["metadata"]["posters"]))];
            $video["duration"] = format_seconds($data["metadata"]["duration"]);
            $streams_m3u8 = url_get_contents($data["metadata"]["qualities"]["auto"][0]["url"], $this->enable_proxies);
            preg_match_all('/#EXT-X-STREAM-INF:(.*)/', $streams_m3u8, $streams_raw);
            $streams_raw = $streams_raw[1];
            $streams = array();
            foreach ($streams_raw as $stream) {
                $quality = get_string_between($stream, 'NAME="', '"');
                if (!isset($streams[$quality])) {
                    $streams[$quality]["quality"] = $quality;
                    $streams[$quality]["url"] = get_string_between($stream, 'PROGRESSIVE-URI="', '"');
                }
            }
            $i = 0;
            foreach ($streams as $stream){
                $video["links"][$i]["url"] = $stream["url"];
                $video["links"][$i]["type"] = "mp4";
                $video["links"][$i]["size"] = get_file_size($video["links"][$i]["url"], $this->enable_proxies);
                $video["links"][$i]["quality"] = $stream["quality"] . "p";
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
