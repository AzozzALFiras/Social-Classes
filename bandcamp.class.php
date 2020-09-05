<?php

class bandcamp
{
    public $enable_proxies = false;

    function azf_Video_info($url)
    {
        $web_page = url_get_contents($url, $this->enable_proxies);
        if (preg_match_all('@content="https://bandcamp.com/EmbeddedPlayer/(.*?)">@si', $web_page, $match)) {
            $embed_url = "https://bandcamp.com/EmbeddedPlayer/" . $match[1][0];
            $embed_page = url_get_contents($embed_url, $this->enable_proxies);
            if (preg_match_all('@var playerdata = (.*?)"};@si', $embed_page, $match)) {
                $player_json = $match[1][0] . '"}';
                $player_data = json_decode($player_json, true);
                $i = 0;
                $data["thumbnail"] = $player_data["album_art"];
                if (!empty($player_data["tracks"])) {
                    foreach ($player_data["tracks"] as $key => $p_data) {
                        if (!empty($p_data["file"]["mp3-128"])) {
                            $data["title"] = $p_data["title"];
                            $data["duration"] = gmdate(($p_data["duration"] > 3600 ? "H:i:s" : "i:s"), $p_data["duration"]);
                            $data["links"][$i]["url"] = $p_data["file"]["mp3-128"];
                            $data["links"][$i]["type"] = "mp3";
                            $data["links"][$i]["quality"] = "128 kbps";
                            $data["links"][$i]["size"] = get_file_size($data["links"][$i]["url"], $this->enable_proxies);
                            $i++;
                        }
                    }
                }
                $data["source"] = "bandcamp";
                return $data;
            }
        }
    }
}
