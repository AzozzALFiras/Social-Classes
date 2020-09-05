<?php

class vimeo
{
    public $enable_proxies = false;

    function azf_Video_info($url)
    {
        $web_page = url_get_contents($url, $this->enable_proxies);
        if (preg_match_all('/window.vimeo.clip_page_config.player\s*=\s*({.+?})\s*;\s*\n/', $web_page, $match)) {
            $config_url = json_decode($match[1][0], true)["config_url"];
            $result = json_decode(url_get_contents($config_url, $this->enable_proxies), true);
            $video['title'] = $result["video"]["title"];
            $video["source"] = "vimeo";
            $video['duration'] = gmdate(($result["video"]["duration"] > 3600 ? "H:i:s" : "i:s"), $result["video"]["duration"]);
            $video['thumbnail'] = reset($result["video"]["thumbs"]);
            $i = 0;
            foreach ($result["request"]["files"]["progressive"] as $current) {
                $video["links"][$i]["url"] = $current["url"];
                $video["links"][$i]["type"] = "mp4";
                $video["links"][$i]["size"] = get_file_size($video["links"][$i]["url"], $this->enable_proxies);
                $video["links"][$i]["quality"] = $current["quality"];
                $video["links"][$i]["mute"] = "no";
                $i++;
            }
            usort($video["links"], "sort_by_quality");
            return $video;
        } else {
            return false;
        }
    }

    function find_video_id($url)
    {
        if (preg_match_all('/https:\/\/vimeo.com\/(channels|([^"]+))(\/staffpicks\/([^"]+)|)/', $url, $match)) {
            if (is_numeric($match[1][0])) {
                return $match[1][0];
            } else if (is_numeric($match[4][0])) {
                return $match[4][0];
            }
        }
    }

    function api_request($video_id)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://vimeo.com/$video_id?action=load_contextual_clips&page=1&stream_pos=0&offset=0",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_HTTPHEADER => array(
                "X-Requested-With: XMLHttpRequest",
                "cache-control: no-cache"
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return '';
        } else {
            return json_decode($response, true)["clips"][0];
        }
    }
}
