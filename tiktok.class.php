<?php

class tiktok
{
    public $enable_proxies = false;
    private $tries = 0;
    private $maxTries = 10;

    private function get_redirect_url($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, _REQUEST_USER_AGENT);
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlInfo = curl_getinfo($ch);
        while ($this->tries++ < $this->maxTries && !filter_var($curlInfo["redirect_url"], FILTER_VALIDATE_URL)) {
            $curlInfo["redirect_url"] = $this->get_redirect_url($url);
        }
        return $curlInfo["redirect_url"];
    }

    function azf_Video_info($url)
    {
        $this->tries++;
        $url = unshorten($url, $this->enable_proxies);
        $web_page = url_get_contents($url, $this->enable_proxies);
        $data = json_decode(get_string_between($web_page, '<script id="__NEXT_DATA__" type="application/json" crossorigin="anonymous">', '</script>'), true);
        if (empty($data["props"]["pageProps"] ?? null)) {
            return false;
        }
        $video["source"] = "tiktok";
        $video["title"] = $data["props"]["pageProps"]["shareMeta"]["title"];
        $thumbnail = get_string_between($web_page, 'property="og:image" content="', '"');
        if (!empty($data["props"]["pageProps"]["videoData"]["itemInfos"]["coversOrigin"] ?? "")) {
            $video["thumbnail"] = $data["props"]["pageProps"]["videoData"]["itemInfos"]["coversOrigin"];
        } else if (!empty($thumbnail)) {
            $video["thumbnail"] = $thumbnail;
        } else {
            $video["thumbnail"] = "https://s16.tiktokcdn.com/musical/resource/wap/static/image/logo_144c91a.png?v=2";
        }
        $video["links"] = array();
        $original_video = $data["props"]["pageProps"]["videoData"]["itemInfos"]["video"]["urls"][0];
        array_push($video["links"], array(
            "url" => $original_video,
            "type" => "mp4",
            "quality" => "watermarked",
            "size" => get_file_size($original_video, $this->enable_proxies),
            "mute" => false
        ));
        $video_data = url_get_contents($original_video, $this->enable_proxies);
        preg_match("/vid:([a-zA-Z0-9]+)/", $video_data, $matches);
        if (count($matches) > 1) {
            $clean_video = $this->get_redirect_url("https://api.tiktokv.com/aweme/v1/playwm/?video_id=" . $matches[1]);
            if (filter_var($clean_video, FILTER_VALIDATE_URL)) {
                array_push($video["links"], array(
                    "url" => $clean_video,
                    "type" => "mp4",
                    "quality" => "hd",
                    "size" => get_file_size($clean_video, $this->enable_proxies),
                    "mute" => false
                ));
            }
        }
        $audio_url = $data['props']['pageProps']['videoObjectPageProps']['videoProps']['audio']['mainEntityOfPage']['@id'] ?? null;
        if (!empty($audio_url)) {
            $audio_page = url_get_contents($audio_url, $this->enable_proxies);
            $audio_data = get_string_between($audio_page, '<script id="__NEXT_DATA__" type="application/json" crossorigin="anonymous">', '</script>');
            $audio_data = json_decode($audio_data, true);
            if (!empty($audio_data)) {
                array_push($video["links"], array(
                    "url" => $audio_data['props']['pageProps']['musicData']['playUrl']['UrlList'][0],
                    "type" => "mp3",
                    "quality" => "128 kbps",
                    "size" => get_file_size($audio_data['props']['pageProps']['musicData']['playUrl']['UrlList'][0], $this->enable_proxies),
                    "mute" => false
                ));
            }
        }
        if (!filter_var($video["links"][0]["url"], FILTER_VALIDATE_URL)) {
            while ($this->tries++ < $this->maxTries) {
                $this->azf_Video_info($url);
            }
        }
        return $video;
    }
}
