<?php

class facebook
{
    public $enable_proxies = false;
    public $hide_dash_videos = false;
    public $url;
    public static $COOKIE_FILE = __DIR__ . "azf-facebook.txt";
    public static $USER_AGENT = _REQUEST_USER_AGENT;

    function url_get_contents($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, self::$USER_AGENT);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "authority: www.facebook.com",
            "cache-control: max-age=0",
            "upgrade-insecure-requests: 1",
            "accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
            "sec-fetch-site: none",
            "sec-fetch-mode: navigate",
            "sec-fetch-user: ?1",
            "sec-fetch-dest: document"
        ));
        if (file_exists(self::$COOKIE_FILE)) {
            curl_setopt($ch, CURLOPT_COOKIEFILE, self::$COOKIE_FILE);
            //curl_setopt($ch, CURLOPT_COOKIEJAR, self::$COOKIE_FILE);
        }
        if ($this->enable_proxies) {
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

    function get_domain($url)
    {
        $pieces = parse_url($url);
        $domain = isset($pieces['host']) ? $pieces['host'] : '';
        if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
            return $regs['domain'];
        } else {
            return false;
        }
    }

    function azf_Video_info($url)
    {
        $url = unshorten($this->remove_m($url));
        $curl_content = $this->url_get_contents($url);
        $video["title"] = $this->convert_unicode($this->get_title($curl_content));
        $video["source"] = "facebook";
        $video["thumbnail"] = $this->get_thumbnail($curl_content);
        $video["links"] = array();
        $sd_link = $this->sd_link($curl_content);
        if (!filter_var($sd_link, FILTER_VALIDATE_URL)) {
            $sd_link = get_string_between($curl_content, 'property="og:video" content="', '"');
            $sd_link = str_replace("&amp;", "&", $sd_link);
        }
        if (!empty($sd_link)) {
            array_push($video["links"], array(
                "url" => $sd_link,
                "type" => "mp4",
                "size" => get_file_size($sd_link, $this->enable_proxies),
                "quality" => "SD",
                "mute" => "no"
            ));
        }
        $hd_link = $this->hd_link($curl_content);
        if (!empty($hd_link)) {
            array_push($video["links"], array(
                "url" => $hd_link,
                "type" => "mp4",
                "size" => get_file_size($hd_link, $this->enable_proxies),
                "quality" => "HD",
                "mute" => "no"
            ));
        }
        if (!$this->hide_dash_videos) {
            preg_match_all('/"dash_manifest":"(.*)","min_quality_preference"/', $curl_content, $output);
            $formatted = $this->format_page($output[1][0] ?? "");
            preg_match_all('/FBQualityLabel="(\d{3})p"><BaseURL>(.*?)<\/BaseURL>/', $formatted, $output);
            if (!empty($output[1]) && !empty($output[2])) {
                for ($i = 0; $i < count($output[1]); $i++) {
                    $decoded_url = str_replace("&amp;", "&", $output[2][$i]);
                    $decoded_url = str_replace("\/", "/", $decoded_url);
                    array_push($video["links"], array(
                        "url" => $decoded_url,
                        "type" => "mp4",
                        "size" => get_file_size($decoded_url, $this->enable_proxies),
                        "quality" => $output[1][$i] . "p",
                        "mute" => true
                    ));
                }

            }
        }
        usort($video["links"], "sort_by_quality");
        return $video;
    }

    function change_domain($url)
    {
        $domain = $this->get_domain($url);
        $parse_url = parse_url($url);
        switch ($domain) {
            case "facebook.com":
                return "https://m.facebook.com" . $parse_url["path"] . "?" . $parse_url["query"];
                break;
            case "m.facebook.com":
                return "https://www.facebook.com" . $parse_url["path"] . "?" . $parse_url["query"];
                break;
            default:
                return "https://www.facebook.com" . $parse_url["path"] . "?" . $parse_url["query"];
                break;
        }
    }

    function clean_str($str)
    {
        return html_entity_decode(strip_tags($str), ENT_QUOTES, 'UTF-8');
    }

    function format_page($html)
    {
        $html = str_replace("\u003C\/", "</", $html);
        $html = str_replace("\u003C", "<", $html);
        $html = str_replace('\/>', '/>', $html);
        $html = str_replace('\"', '"', $html);
        return $html;
    }

    function convert_unicode($str)
    {
        $str = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
        }, $str);
        return $str;
    }

    function remove_m($url)
    {
        $url = str_replace("m.facebook.com", "www.facebook.com", $url);
        return $url;
    }

    function mobil_link($curl_content)
    {
        $regex = '@&quot;https:(.*?)&quot;,&quot;@si';
        if (preg_match_all($regex, $curl_content, $match)) {
            return $match[1][0];
        }
        return "";
    }

    function hd_link($curl_content)
    {
        $regex = '/hd_src_no_ratelimit:"([^"]+)"/';
        $playable_url = get_string_between($curl_content, '"playable_url_quality_hd":"', '"');
        if (preg_match($regex, $curl_content, $match)) {
            return $match[1];
        } else if (preg_match('/hd_src:"([^"]+)"/', $curl_content, $match)) {
            return $match[1];
        } else if (!empty($playable_url)) {
            return str_replace("\/", "/", $playable_url);
        }
        return "";
    }

    function sd_link($curl_content)
    {
        $regex = '/sd_src_no_ratelimit:"([^"]+)"/';
        $playable_url = get_string_between($curl_content, '"playable_url":"', '"');
        if (preg_match($regex, $curl_content, $match)) {
            return $match[1];
        } else {
            $mobil_link = $this->mobil_link($curl_content);
            if (!empty($mobil_link)) {
                return $mobil_link;
            } else if (!empty($playable_url)) {
                return str_replace("\/", "/", $playable_url);
            }
        }
        return "";
    }

    function get_title($curl_content)
    {
        $og_title = get_string_between($curl_content, 'property="og:title" content="', '"');
        $page_title = get_string_between($curl_content, '<title id="pageTitle">', '</title>');
        $json_title = get_string_between($curl_content, '"is_show_video":false,"name":"', '"');
        if (!empty($og_title)) {
            return $og_title;
        } else if (!empty($page_title)) {
            return $page_title;
        } else if (!empty($json_title)) {
            return $json_title;
        } else {
            return "Facebook Video";
        }
    }

    function get_thumbnail($curl_content)
    {
        $json_thumbnail = get_string_between($curl_content, '"thumbnailImage":{"uri":"', '"');
        if (preg_match('/og:image"\s*content="([^"]+)"/', $curl_content, $match)) {
            return $match[1];
        } else if (preg_match('@<meta property="twitter:image" content="(.*?)" />@si', $curl_content, $match)) {
            return $match[1];
        } else if (!empty($json_thumbnail)) {
            return str_replace("\/", "/", urldecode($json_thumbnail));
        } else {
            return "https://www.facebook.com/images/fb_icon_325x325.png";
        }
    }
}
