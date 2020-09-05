<?php

class izlesene
{
    public $enable_proxies = false;

    function azf_Video_info($url)
    {
        $web_page = url_get_contents($url, $this->enable_proxies);
        if (preg_match_all('/videoObj\s*=\s*({.+?})\s*;\s*\n/', $web_page, $match)) {
            $player_json = $match[1][0];
            $player_data = json_decode($player_json, true);
            $data["title"] = $player_data["videoTitle"];
            $data["source"] = "izlesene";
            $data["thumbnail"] = $player_data["posterURL"];
            $data["duration"] = gmdate(($player_data["duration"] / 1000 > 3600 ? "H:i:s" : "i:s"), $player_data["duration"] / 1000);
            if (!empty($player_data["media"]["level"])) {
                $i = 0;
                foreach ($player_data["media"]["level"] as $video) {
                    $data["links"][$i]["url"] = $video["source"];
                    $data["links"][$i]["type"] = "mp4";
                    $data["links"][$i]["size"] = get_file_size($video["source"], $this->enable_proxies);
                    $data["links"][$i]["quality"] = $video["value"] . "p";
                    $data["links"][$i]["mute"] = "no";
                    $i++;
                }
                return $data;
            }
        }
    }
}
