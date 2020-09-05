<?php

class reddit
{
    public $enable_proxies = false;

    function azf_Video_info($url)
    {
        $web_page = url_get_contents($url, $this->enable_proxies);
        $video["source"] = "reddit";
        $video["title"] = get_string_between($web_page, "<title>", "</title>");
        $video["thumbnail"] = get_string_between($web_page, '<meta property="og:image" content="', '"/>');
        $playlist_url = get_string_between($web_page, '"dashUrl":"', '"');
        if (empty($playlist_url)) {
            preg_match_all('/<a href="https:\/\/youtu.be\/(.*?)"/', $web_page, $output);
            if (empty($output[1][0])) {
                return false;
            }
            require_once __DIR__ . "/youtube.class.php";
            $download = new youtube();
            return $download->azf_Video_info("https://www.youtube.com/watch?v=" . $output[1][0]);
        }
        $xml_playlist = url_get_contents($playlist_url, $this->enable_proxies);
        preg_match_all('/<BaseURL>(.*)<\/BaseURL>/', $xml_playlist, $medias);
        $video_id = get_string_between(parse_url($playlist_url, PHP_URL_PATH), '/', '/DASHPlaylist.mpd');
        if (empty($medias[1])) {
            return false;
        }
        $medias = $medias[1];
        $video["links"] = array();
        foreach ($medias as $media) {
            $dashType = get_string_between($media, "DASH_", ".");
            $mediaUrl = "https://v.redd.it/" . $video_id . "/" . $media;
            array_push($video["links"], [
                "url" => $mediaUrl,
                "type" => $dashType == "audio" ? "m4a" : "mp4",
                "size" => get_file_size($mediaUrl, $this->enable_proxies),
                "quality" => $dashType == "audio" ? "128 kbps" : $dashType . "p",
                "mute" => true
            ]);
        }
        return $video;
    }
}
