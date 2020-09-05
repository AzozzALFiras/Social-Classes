<?php

class likee
{
    public $enable_proxies = false;

    function azf_Video_info($url)
    {
        $video_id = "";
        $parsed_url = parse_url($url);
        if ($parsed_url['host'] == 'l.likee.video') {
            $video_id = get_string_between(url_get_contents($url, $this->enable_proxies), '"post_id":"', '"');
        } else {
            preg_match('/(\d{3,})/', $url, $video_id);
            if (is_numeric($video_id[0] ?? "")) {
                $video_id = $video_id[0];
            }
        }
        $video_data = $this->get_video_data($video_id);
        if (!empty($video_data)) {
            $video["title"] = ($video_data["msgText"] != "") ? $video_data["msgText"] : "Likee Video " . $video_id;
            $video["source"] = "likee";
            $video["thumbnail"] = (isset($video_data["image2"]) != "") ? $video_data["image2"] : $video_data["image1"];
            $video["duration"] = format_seconds($video_data["optionData"]["dur"] / 1000);
            $video["links"][0]["url"] = $video_data["videoUrl"];
            $video["links"][0]["type"] = "mp4";
            $video["links"][0]["size"] = get_file_size($video_data["videoUrl"], $this->enable_proxies);
            $video["links"][0]["quality"] = min($video_data["videoHeight"], $video_data["videoWidth"]) . "p";
            $video["links"][0]["mute"] = "no";
            return $video;
        } else {
            return false;
        }
    }

    function get_video_data($video_id)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://likee.com/app/videoApi/getVideoInfo",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "postIds=$video_id",
            CURLOPT_HTTPHEADER => array(
                "authority: likee.com",
                "accept: application/json, text/plain, */*",
                "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.130 Safari/537.36",
                "content-type: application/x-www-form-urlencoded",
                "origin: https://likee.com",
                "sec-fetch-site: same-origin",
                "sec-fetch-mode: cors",
                "referer: https://likee.com/",
                "accept-encoding: gzip, deflate, br",
                "accept-language: en-GB,en;q=0.9,tr-TR;q=0.8,tr;q=0.7,en-US;q=0.6"
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true)["data"][0];
    }
}
