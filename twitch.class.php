<?php

class twitch
{
    public $enable_proxies = false;

    function azf_Video_info($url)
    {
        $clip_name = $this->get_clip_name($url);
        if ($clip_name === false) {
            return false;
        } else {
            $video["title"] = "Twitch Video Clip from " . $this->get_poster_name($url);
            $video["source"] = "twitch";
            $video["thumbnail"] = "https://blog.twitch.tv/assets/uploads/generic-email-header-1.jpg";
            $api_response = $this->api_request($clip_name);
            $video["links"] = array();
            foreach ($api_response["data"]["clip"]["videoQualities"] as $videoQuality) {
                array_push($video["links"], array(
                    "url" => $videoQuality["sourceURL"],
                    "type" => "mp4",
                    "size" => get_file_size($videoQuality["sourceURL"]),
                    "quality" => $videoQuality["quality"] . "p",
                    "mute" => false
                ));
            }
            usort($video["links"], 'sort_by_quality');
            return $video;
        }
    }

    function get_clip_name($url)
    {
        $parsed_url = parse_url($url);
        $path = explode("/", $parsed_url["path"]);
        if (count($path) == 2) {
            return $path[1];
        } else if ($path[2] == "clip" && isset($path[3]) != "") {
            return $path[3];
        } else {
            return false;
        }
    }

    function get_poster_name($url)
    {
        $parsed_url = parse_url($url);
        $path = explode("/", $parsed_url["path"]);
        if (count($path) == 2) {
            return $path[1];
        } else if ($path[2] == "clip" && isset($path[3]) != "") {
            return $path[1];
        } else {
            return false;
        }
    }

    function generate_operation($clip_name)
    {
        $operation = array(
            0 =>
                array(
                    'operationName' => 'VideoAccessToken_Clip',
                    'variables' =>
                        array(
                            'slug' => $clip_name,
                        ),
                    'extensions' =>
                        array(
                            'persistedQuery' =>
                                array(
                                    'version' => 1,
                                    'sha256Hash' => '9bfcc0177bffc730bd5a5a89005869d2773480cf1738c592143b5173634b7d15',
                                ),
                        ),
                ),
        );
        return json_encode($operation);
    }

    function api_request($clip_name)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://gql.twitch.tv/gql",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $this->generate_operation($clip_name),
            CURLOPT_HTTPHEADER => array(
                "Client-Id: kimne78kx3ncx6brgo4mv6wki5h1ko",
                "Content-Type: application/json"
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true)[0];
    }
}
