<?php

class instagram
{
    public $enable_proxies = false;
    public $url;
    public static $COOKIE_FILE = __DIR__ . "azf-instagram.txt";
    public static $USER_AGENT = _REQUEST_USER_AGENT;
    private $post_page;

    function azf_Video_info($url)
    {
        $this->post_page = $this->url_get_contents($url, $this->enable_proxies);
        $azf_Video_info = $this->media_data($this->post_page);
        $video["title"] = $this->get_title($this->post_page);
        $video["source"] = "instagram";
        $video["thumbnail"] = $this->get_thumbnail($this->post_page);
        $i = 0;
        foreach ($azf_Video_info["links"] as $link) {
            switch ($link["type"]) {
                case "video":
                    $video["links"][$i]["url"] = $link["url"];
                    $video["links"][$i]["type"] = "mp4";
                    $video["links"][$i]["size"] = get_file_size($video["links"]["0"]["url"], $enable_proxies = false);
                    $video["links"][$i]["quality"] = "HD";
                    $video["links"][$i]["mute"] = "no";
                    $i++;
                    break;
                case "image":
                    $video["links"][$i]["url"] = $link["url"];
                    $video["links"][$i]["type"] = "jpg";
                    $video["links"][$i]["size"] = get_file_size($video["links"]["0"]["url"], $enable_proxies = false);
                    $video["links"][$i]["quality"] = "HD";
                    $video["links"][$i]["mute"] = "yes";
                    $i++;
                    break;
                default:
                    break;
            }
        }
        return $video;
    }

    function azf_Video_info_beta($url)
    {
        $this->post_page = $this->url_get_contents($url, $this->enable_proxies);
        $video["title"] = $this->get_title($this->post_page);
        $video["source"] = "instagram";
        //$video["thumbnail"] = $this->get_thumbnail($this->post_page);
        $video["thumbnail"] = get_string_between($this->post_page, '"display_url":"', '"');
        $video["thumbnail"] = str_replace("\u0026", "&", $video["thumbnail"]);
        $video["links"][0]["url"] = $this->getVideoUrl();
        $video["links"][0]["type"] = "mp4";
        $video["links"][0]["size"] = get_file_size($video["links"]["0"]["url"], $enable_proxies = false);
        $video["links"][0]["quality"] = "HD";
        $video["links"][0]["mute"] = "no";
        return $video;
    }

    function getPostShortcode($url)
    {
        if (substr($url, -1) != '/') {
            $url .= '/';
        }
        preg_match('/\/(p|tv)\/(.*?)\//', $url, $output);
        return ($output['2'] ?? '');
    }

    function getVideoUrl($postShortcode = "")
    {
        //$pageContent = $this->url_get_contents('https://www.instagram.com/p/' . $postShortcode);
        preg_match_all('/"video_url":"(.*?)",/', $this->post_page, $out);
        if (!empty($out[1][0])) {
            return str_replace('\u0026', '&', $out[1][0]);
        } else {
            return null;
        }
    }

    function url_get_contents($url, $enable_proxies = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, self::$USER_AGENT);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        if (file_exists(self::$COOKIE_FILE)) {
            curl_setopt($ch, CURLOPT_COOKIEFILE, self::$COOKIE_FILE);
            //curl_setopt($ch, CURLOPT_COOKIEJAR, self::$COOKIE_FILE);
        }
        if ($enable_proxies) {
            if (!empty($_SESSION["proxy"] ?? null)) {
                $proxy = $_SESSION["proxy"];
            } else {
                $proxy = get_proxy();
                $_SESSION["proxy"] = $proxy;
            }
            curl_setopt($ch, CURLOPT_PROXY, $proxy['ip'] . ":" . $proxy['port']);
            if (!empty($proxy['username']) && !empty($proxy['password'])) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['username'] . ":" . $proxy['password']);
            }
            $chunkSize = 1000000;
            curl_setopt($ch, CURLOPT_TIMEOUT, (int)ceil(3 * (round($chunkSize / 1048576, 2) / (1 / 8))));
        }
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    function media_data($post_page)
    {
        preg_match_all("/window.__additionalDataLoaded.'.{5,}',(.*).;/", $post_page, $matches);
        if (!$matches) {
            return false;
        } else {
            $json = $matches[1][0];
            $data = json_decode($json, true);
            if ($data['graphql']['shortcode_media']['__typename'] == "GraphImage") {
                $imagesdata = $data['graphql']['shortcode_media']['display_resources'];
                $length = count($imagesdata);
                $azf_Video_info['links'][0]['type'] = 'image';
                $azf_Video_info['links'][0]['url'] = $imagesdata[$length - 1]['src'];
                $azf_Video_info['links'][0]['status'] = 'success';
            } else {
                if ($data['graphql']['shortcode_media']['__typename'] == "GraphSidecar") {
                    $counter = 0;
                    $multipledata = $data['graphql']['shortcode_media']['edge_sidecar_to_children']['edges'];
                    foreach ($multipledata as &$media) {
                        if ($media['node']['is_video'] == "true") {
                            $azf_Video_info['links'][$counter]["url"] = $media['node']['video_url'];
                            $azf_Video_info['links'][$counter]["type"] = 'video';
                        } else {
                            $length = count($media['node']['display_resources']);
                            $azf_Video_info['links'][$counter]["url"] = $media['node']['display_resources'][$length - 1]['src'];
                            $azf_Video_info['links'][$counter]["type"] = 'image';
                        }
                        $counter++;
                        $azf_Video_info['type'] = 'media';
                    }
                    $azf_Video_info['status'] = 'success';
                } else {
                    if ($data['graphql']['shortcode_media']['__typename'] == "GraphVideo") {
                        $videolink = $data['graphql']['shortcode_media']['video_url'];
                        $azf_Video_info['links'][0]['type'] = 'video';
                        $azf_Video_info['links'][0]['url'] = $videolink;
                        $azf_Video_info['links'][0]['status'] = 'success';
                    } else {
                        $azf_Video_info['links']['status'] = 'fail';
                    }
                }
            }
            $owner = $data['graphql']['shortcode_media']['owner'];
            $azf_Video_info['username'] = $owner['username'];
            $azf_Video_info['full_name'] = $owner['full_name'];
            $azf_Video_info['profile_pic_url'] = $owner['profile_pic_url'];
            return $azf_Video_info;
        }
    }

    function get_type($curl_content)
    {
        if (preg_match_all('@<meta property="og:type" content="(.*?)" />@si', $curl_content, $match)) {
            return $match[1][0];
        }
    }

    function get_image($curl_content)
    {
        if (preg_match_all('@<meta property="og:image" content="(.*?)" />@si', $curl_content, $match)) {
            return $match[1][0];
        }

    }

    function get_video($curl_content)
    {

        if (preg_match_all('@<meta property="og:video" content="(.*?)" />@si', $curl_content, $match)) {
            return $match[1][0];
        }

    }

    function get_thumbnail($post_page)
    {
        preg_match_all("/window.__additionalDataLoaded.'.{5,}',(.*).;/", $post_page, $matches);
        if (!$matches) {
            return false;
        }
        $json = $matches[1][0];
        $data = json_decode($json, true)["graphql"];
        return $data["shortcode_media"]['display_resources'][0]["src"];
    }

    function get_title($curl_content)
    {
        if (preg_match_all('@<title>(.*?)</title>@si', $curl_content, $match)) {
            return $match[1][0];
        }
    }
}
