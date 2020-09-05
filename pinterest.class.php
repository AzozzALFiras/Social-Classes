<?php

class pinterest
{
    public $enable_proxies = false;

    function azf_Video_info($url)
    {
        $parsed_url = parse_url($url);
        if ($parsed_url['host'] == 'pin.it') {
            $original_url = unshorten($url, $this->enable_proxies);
            if (isset($original_url) != "") {
                $url = strtok($original_url, '?');
            }
        }
        $page_source = url_get_contents($url, $this->enable_proxies);
        $video["title"] = get_string_between($page_source, "<title>", "</title>");
        $video["source"] = "pinterest";
        $video["thumbnail"] = get_string_between($page_source, '"image_cover_url":"', '"');
        $video_data = get_string_between($page_source, '<script id="initial-state" type="application/json">', '</script>');
        $streams = json_decode($video_data, true)["resourceResponses"][0]["response"]["data"]["videos"]["video_list"];
        if ($streams != "") {
            $i = 0;
            foreach ($streams as $stream) {
                $ext = pathinfo(parse_url($stream["url"])["path"], PATHINFO_EXTENSION);
                if ($ext != "m3u8") {
                    $video["links"][$i]["url"] = $stream["url"];
                    $video["links"][$i]["type"] = $ext;
                    $video["links"][$i]["size"] = get_file_size($video["links"][$i]["url"], $this->enable_proxies);
                    $video["links"][$i]["quality"] = min($stream["height"], $stream["width"]) . "p";
                    $video["links"][$i]["mute"] = "no";
                    $i++;
                }
            }
            return $video;
        } else {
            return false;
        }
    }
}
