<?php

class odnoklassniki
{
    public $enable_proxies = false;

    function azf_Video_info($url)
    {
        $video_id = str_replace("video/", "", substr(parse_url($url, PHP_URL_PATH), 1));
        $data = $this->get_data($video_id);
        $data = json_decode($data, true);
        if (!empty($data)) {
            $video["source"] = "ok.ru";
            $video["title"] = $data["movie"]["title"];
            $video["thumbnail"] = $data["movie"]["poster"];
            $video['time'] = gmdate(($data["movie"]["duration"] > 3600 ? "H:i:s" : "i:s"), $data["movie"]["duration"]);
            $i = 0;
            foreach ($data["videos"] as $item) {
                $video["links"][$i]["url"] = $item["url"];
                $video["links"][$i]["type"] = "mp4";
                $video["links"][$i]["size"] = get_file_size($video["links"][$i]["url"], $this->enable_proxies);
                $video["links"][$i]["quality"] = $item["name"];
                $video["links"][$i]["mute"] = "no";
                $i++;
            }
            return $video;
        } else {
            return false;
        }
    }

    function get_data($video_id)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://ok.ru/dk?cmd=videoPlayerMetadata&mid=$video_id",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_USERAGENT => _REQUEST_USER_AGENT,
            //CURLOPT_PROXY => "localhost:59962"
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return false;
        } else {
            return $response;
        }
    }
}
