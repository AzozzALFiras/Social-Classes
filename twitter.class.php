<?php

class twitter
{
    public $enable_proxies = false;

    function get_url()
    {
        return json_decode(database::find("SELECT * FROM options WHERE option_name='general_settings' LIMIT 1")[0]["option_value"], true)["url"];
    }

    function find_id($url)
    {
        $domain = str_ireplace("www.", "", parse_url($url, PHP_URL_HOST));
        switch ($domain) {
            case "twitter.com":
                $arr = explode("/", $url);
                return end($arr);
                break;
            case "mobile.twitter.com":
                $arr = explode("/", $url);
                return end($arr);
                break;
            default:
                $arr = explode("/", $url);
                return end($arr);
                break;
        }
    }

    function get_token()
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->get_url() . "/assets/js/codebird-cors-proxy/oauth2/token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "grant_type=client_credentials",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/x-www-form-urlencoded",
                "x-authorization: Basic SzB3OHJsRENCNnpCQjczOVRHdDFCTFkybjozZGs5b3FjN0NRb0k5MGZDeWs5SmNaRXZTODhidmtQMVlIeEkzeWx5b3JsMWNOYUQ1SA=="
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return false;
        } else {
            return json_decode($response, true);
        }
    }

    function tweet_data($tweet_id)
    {
        $curl = curl_init();
        //$token_data = $this->get_token();
        $token_data["access_token"] = "AAAAAAAAAAAAAAAAAAAAANRILgAAAAAAnNwIzUejRCOuH5E6I8xnZz4puTs%3D1Zv7ttfk8LF81IUq16cHjhLTvJu4FA33AGWWjCpTnA";
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->get_url() . "/assets/js/codebird-cors-proxy/1.1/statuses/show/$tweet_id.json?tweet_mode=extended&include_entities=true",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "x-authorization: Bearer " . $token_data["access_token"]
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return false;
        } else {
            return json_decode($response, true);
        }
    }

    function azf_Video_info($url)
    {
        //$url = str_replace("mobile.twitter.com", "twitter.com", $url);
        $url = preg_replace('/\?.*/', '', $url);
        $tweet_data = $this->tweet_data($this->find_id($url));
        $data["title"] = $this->clean_title($tweet_data["full_text"]);
        $data["thumbnail"] = $tweet_data["entities"]["media"][0]["media_url_https"];
        $i = 0;
        if (isset($tweet_data["extended_entities"]["media"][0]) != "") {
            foreach ($tweet_data["extended_entities"]["media"][0]["video_info"]["variants"] as $video) {
                if ($video["content_type"] == "video/mp4") {
                    $data["links"][$i]["url"] = $video["url"];
                    $data["links"][$i]["type"] = "mp4";
                    $data["links"][$i]["size"] = get_file_size($data["links"][$i]["url"]);
                    $data["links"][$i]["quality"] = $this->get_quality($data["links"][$i]["url"]);
                    $data["links"][$i]["mute"] = "no";
                    $i++;
                }
            }
            $data["source"] = "twitter";
            usort($data["links"], "sort_by_quality");
            return $data;
        } else {
            return false;
        }
    }

    function clean_title($string)
    {
        preg_match_all('/(.*?)https:\/\/t.co\//', $string, $output);
        if (!empty($output[1][0])) {
            return $output[1][0];
        } else {
            return "Undefined";
        }
    }

    function get_quality($url)
    {
        preg_match_all('/vid\/(.*?)x(.*?)\//', $url, $output);
        if (!empty($output[2][0])) {
            return $output[2][0] . "p";
        } else {
            return "gif";
        }
    }
}
