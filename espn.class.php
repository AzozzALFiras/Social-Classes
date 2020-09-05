<?php

class espn
{
    public $enable_proxies = false;

    function find_media_id($url)
    {
        if (preg_match("/(id=\d{5,20}|id\/\d{5,20}|video\/\d{5,20})/", $url, $match)) {
            $video_id = (int)filter_var($match[0], FILTER_SANITIZE_NUMBER_INT);
            return $video_id;
        }
    }

    function azf_Video_info($url)
    {
        $media_id = $this->find_media_id($url);
        $api_url = "http://cdn.espn.com/core/video/clip/_/id/" . $media_id . "?xhr=1&device=desktop&country=us&lang=en&region=us&site=espn&edition-host=espn.com&one-site=true&site-type=full";
        $rest_api = url_get_contents($api_url, $this->enable_proxies);
        $rest_api = json_decode($rest_api, true);
        $data["title"] = $rest_api["meta"]["title"];
        $data["source"] = "espn";
        $data["thumbnail"] = $rest_api["meta"]["image"];
        $data["duration"] = gmdate(($rest_api["content"]["duration"] > 3600 ? "H:i:s" : "i:s"), $rest_api["content"]["duration"]);
        if (!empty($rest_api["content"]["links"]["source"])) {
            $i = 0;
            foreach ($rest_api["content"]["links"]["source"] as $key => $link) {
                switch ($key) {
                    case "href":
                        $data["links"][$i]["url"] = $link;
                        $data["links"][$i]["type"] = "mp4";
                        $data["links"][$i]["size"] = get_file_size($data["links"][$i]["url"], $this->enable_proxies);
                        $data["links"][$i]["quality"] = "360p";
                        $data["links"][$i]["mute"] = "no";
                        $i++;
                        break;
                    case "HD":
                        $data["links"][$i]["url"] = $link["href"];
                        $data["links"][$i]["type"] = "mp4";
                        $data["links"][$i]["size"] = get_file_size($data["links"][$i]["url"], $this->enable_proxies);
                        $data["links"][$i]["quality"] = "720p";
                        $data["links"][$i]["mute"] = "no";
                        $i++;
                        break;
                }
            }
            return $data;
        }
    }
}
