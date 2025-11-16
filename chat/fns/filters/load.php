<?php

function sanitize_slug($slug, $strict = false) {
    return sanitize_username($slug, $strict = false);
}

function sanitize_filename_old($filename) {
    $filename = preg_replace('~[<>:"/\\|?*]|[\x00-\x1F]|[\x7F\xA0\xAD]|[#\[\]@!$&\'()+,;=]|[{}^\~`]~x', '-', $filename);
    $filename = ltrim($filename, '.-');
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $filename = mb_strcut(pathinfo($filename, PATHINFO_FILENAME), 0, 255 - ($ext ? strlen($ext) + 1 : 0), mb_detect_encoding($filename)) . ($ext ? '.' . $ext : '');
    return $filename;
}

function sanitize_meta_description($text) {
    $text = mb_convert_encoding($text, 'UTF-8', 'auto');
    $text = trim(preg_replace('/\s+/', ' ', $text));
    $text = strip_tags($text);
    return mb_substr($text, 0, 160, 'UTF-8');
}

function sanitize_filename($filename) {
    $filename_raw = $filename;
    $special_chars = array("?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}");
    $filename = str_replace($special_chars, '', $filename);
    $filename = preg_replace('/[\s-]+/', '-', $filename);
    $filename = trim($filename, '.-_');
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $filename = mb_strcut(pathinfo($filename, PATHINFO_FILENAME), 0, 255 - ($ext ? strlen($ext) + 1 : 0), mb_detect_encoding($filename)) . ($ext ? '.' . $ext : '');
    return $filename;
}

function sanitize_phone_number($phone_number) {
    $phone_number = preg_replace('/[^\d+]/', '', $phone_number);

    if (!preg_match('/^\+\d{10,15}$/', $phone_number)) {
        return '';
    }

    return $phone_number;
}

function phone_number_exists($phone_number) {
    $phone_number_exists = DB::connect()->count('site_users', ['phone_number' => $phone_number]);
    if ($phone_number_exists > 0) {
        return true;
    } else {
        return false;
    }
}


function slug_exists($slug) {

    $reserved_slugs = ['group', 'everyone', 'membership_packages', 'wallet'];

    if (!empty(Registry::load('config')->group_url_path) && Registry::load('config')->group_url_path !== 'group') {
        $reserved_slugs[] = Registry::load('config')->group_url_path;
    }

    if (!empty(Registry::load('config')->authentication_page_url_path) && Registry::load('config')->authentication_page_url_path !== 'entry') {
        $reserved_slugs[] = Registry::load('config')->authentication_page_url_path;
    }

    if (isset(Registry::load('settings')->disallowed_slugs) && !empty(Registry::load('settings')->disallowed_slugs)) {
        $disallowed_slugs = Registry::load('settings')->disallowed_slugs;
        foreach ($disallowed_slugs as $disallowed_slug) {
            $reserved_slugs[] = $disallowed_slug;
        }
    }

    $query = 'SELECT ';
    $query .= 'EXISTS (SELECT <user_id> FROM <site_users> WHERE <username> = :findslug) OR ';
    $query .= 'EXISTS (SELECT <page_id> FROM <custom_pages> WHERE <slug> = :findslug) OR ';
    $query .= 'EXISTS (SELECT <group_id> FROM <groups> WHERE <slug> = :findslug) AS result;';
    $slug_exists = DB::connect()->query($query, ['findslug' => $slug])->fetchAll();

    $file_exists = 'pages/'.$slug.'.php';

    if (in_array($slug, $reserved_slugs) || $slug_exists[0]['result'] || $slug_exists[0]['result'] === '1' || file_exists($file_exists) || file_exists($slug)) {
        return true;
    } else {
        return false;
    }
}

function sanitize_array($array) {
    function filter(&$value) {
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    array_walk_recursive($array, "filter");
    return $array;
}

function username_exists($username) {
    return slug_exists($username);
}

function getSvgDimensions($filePath) {
    $content = file_get_contents($filePath);
    if ($content === false) return false;

    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($content);
    if ($xml === false || $xml->getName() !== 'svg') return false;

    $attributes = $xml->attributes();

    $width = 0;
    $height = 0;

    // Try width & height attributes first
    if (isset($attributes['width'])) {
        $width = (int) filter_var($attributes['width'], FILTER_SANITIZE_NUMBER_INT);
    }
    if (isset($attributes['height'])) {
        $height = (int) filter_var($attributes['height'], FILTER_SANITIZE_NUMBER_INT);
    }

    // If missing, try viewBox (min-x, min-y, width, height)
    if (($width === 0 || $height === 0) && isset($attributes['viewBox'])) {
        $viewBox = preg_split('/\s+/', trim($attributes['viewBox']));
        if (count($viewBox) === 4) {
            $width = (int) $viewBox[2];
            $height = (int) $viewBox[3];
        }
    }

    return [$width, $height];
}


function isJson($string) {
    try {
        json_decode($string);
    }catch(TypeError $e) {
        return false;
    }
    return (json_last_error() == JSON_ERROR_NONE);
}

function isImage($img) {

    $mime = mime_content_type($img);

    if ($mime === 'image/svg+xml') {
        $content = file_get_contents($img);
        return strpos($content, '<svg') !== false;
    } else {
        return (bool)getimagesize($img);
    }

    return false;
}

function sanitize_username($username, $strict = false) {
    $username = strip_all_tags($username);
    $username = remove_accents($username);
    $username = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '', $username);
    $username = preg_replace('/&.+?;/', '', $username);

    if ($strict) {
        $username = preg_replace('|[^a-z0-9 _.\-]|i', '', $username);
    }
    $remove_slug_chars = array('\'', '"', ',', '/', '\\', '@', '$', ';', '#', '~', '(', ')', ':', '[', ']', '<', '>', '{', '}', '?', '&', '!');
    $username = str_replace($remove_slug_chars, '', $username);
    $username = trim($username);
    $username = preg_replace('|\s+|', ' ', $username);
    $username = preg_replace('/\s+/', '-', $username);
    $username = preg_replace('/[\x{2028}\x{205F}\x{3000}\x{0020}\x{00A0}\x{2000}-\x{200A}]/u', '', $username);
    return $username;
}

function sanitize_nickname($nickname) {
    $nickname = strip_all_tags($nickname);
    $nickname = remove_accents($nickname);
    $nickname = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '', $nickname);
    $nickname = preg_replace('/&.+?;/', '', $nickname);

    $remove_slug_chars = array('\'', '"', ',', '/', '\\', '@', '$', ';', '#', '~', '(', ')', ':', '[', ']', '<', '>', '{', '}', '?', '&', '!');
    $nickname = str_replace($remove_slug_chars, '', $nickname);
    $nickname = trim($nickname);
    $nickname = preg_replace('|\s+|', ' ', $nickname);
    return $nickname;
}

function validate_date($date, $format = 'Y-m-d H:i:s') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}

function isGeoValid($type, $value) {
    $pattern = ($type == 'latitude')
    ? '/^(\+|-)?(?:90(?:(?:\.0{1,8})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,8})?))$/'
    : '/^(\+|-)?(?:180(?:(?:\.0{1,8})?)|(?:[0-9]|[1-9][0-9]|1[0-7][0-9])(?:(?:\.[0-9]{1,8})?))$/';

    if (preg_match($pattern, $value)) {
        return true;
    } else {
        return false;
    }
}

function strip_all_tags($string, $remove_breaks = false) {
    $string = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $string);
    $string = strip_tags($string);

    if ($remove_breaks) {
        $string = preg_replace('/[\r\n\t ]+/', ' ', $string);
    }

    return trim($string);
}

function reset_mbstring_encoding() {
    mbstring_binary_safe_encoding(true);
}

function email_validator($email_address) {
    include('fns/filters/email_validator.php');
    return $result;
}

function mbstring_binary_safe_encoding($reset = false) {
    static $encodings = array();
    static $overloaded = null;

    if (is_null($overloaded)) {
        if (function_exists('mb_internal_encoding') && ((int) ini_get('mbstring.func_overload') & 2)) {
            $overloaded = true;
        } else {
            $overloaded = false;
        }
    }

    if (false === $overloaded) {
        return;
    }

    if (! $reset) {
        $encoding = mb_internal_encoding();
        array_push($encodings, $encoding);
        mb_internal_encoding('ISO-8859-1');
    }

    if ($reset && $encodings) {
        $encoding = array_pop($encodings);
        mb_internal_encoding($encoding);
    }
}

function convertMarkdownToHTML($text) {

    for ($i = 6; $i >= 1; $i--) {
        $pattern = '/^' . str_repeat('#', $i) . '\s*(.*?)\s*$/m';
        $replacement = '<h' . $i . '>$1</h' . $i . '>';
        $text = preg_replace($pattern, $replacement, $text);
    }

    $text = preg_replace('/`(.*?)`/', '<code>$1</code>', $text);

    $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);

    $text = preg_replace('/(?<!\*)\*(?!\*)(.*?)\*(?!\*)/', '<em>$1</em>', $text);

    $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2">$1</a>', $text);

    if (preg_match_all('/^(\d+\..*?)(\r?\n|$)/m', $text, $matches)) {
        $text = preg_replace_callback('/(^|\n)((\d+\..*?)(\r?\n|$))+/m', function ($match) {
            $items = preg_split('/\r?\n/', trim($match[0]));
            $list = "<ol>\n";
            foreach ($items as $item) {
                $list .= '<li>' . preg_replace('/^\d+\.\s*/', '', $item) . "</li>\n";
            }
            $list .= "</ol>";
            return $list;
        }, $text);
    }

    if (preg_match_all('/^([\-\*]\s.*?)(\r?\n|$)/m', $text, $matches)) {
        $text = preg_replace_callback('/(^|\n)(([\-\*]\s.*?)(\r?\n|$))+/m', function ($match) {
            $items = preg_split('/\r?\n/', trim($match[0]));
            $list = "<ul>\n";
            foreach ($items as $item) {
                $list .= '<li>' . preg_replace('/^[\-\*]\s*/', '', $item) . "</li>\n";
            }
            $list .= "</ul>";
            return $list;
        }, $text);
    }

    $text = preg_replace('/^(\s*)([-*_]){3,}\s*$/m', '<hr>', $text);
    $text = nl2br($text);

    return $text;
}



function seems_utf8($str) {
    mbstring_binary_safe_encoding();
    $length = strlen($str);
    reset_mbstring_encoding();
    for ($i = 0; $i < $length; $i++) {
        $c = ord($str[$i]);
        if ($c < 0x80) {
            $n = 0;
        } elseif (($c & 0xE0) == 0xC0) {
            $n = 1;
        } elseif (($c & 0xF0) == 0xE0) {
            $n = 2;
        } elseif (($c & 0xF8) == 0xF0) {
            $n = 3;
        } elseif (($c & 0xFC) == 0xF8) {
            $n = 4;
        } elseif (($c & 0xFE) == 0xFC) {
            $n = 5;
        } else {
            return false;
        }
        for ($j = 0; $j < $n; $j++) {
            // n bytes matching 10bbbbbb follow ?
            if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80)) {
                return false;
            }
        }
    }
    return true;
}


function remove_accents($string) {
    if (! preg_match('/[\x80-\xff]/', $string)) {
        return $string;
    }

    if (seems_utf8($string)) {
        $chars = array(
            'ª' => 'a',
            'º' => 'o',
            'À' => 'A',
            'Á' => 'A',
            'Â' => 'A',
            'Ã' => 'A',
            'Ä' => 'A',
            'Å' => 'A',
            'Æ' => 'AE',
            'Ç' => 'C',
            'È' => 'E',
            'É' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'Ì' => 'I',
            'Í' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'Ð' => 'D',
            'Ñ' => 'N',
            'Ò' => 'O',
            'Ó' => 'O',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ö' => 'O',
            'Ù' => 'U',
            'Ú' => 'U',
            'Û' => 'U',
            'Ü' => 'U',
            'Ý' => 'Y',
            'Þ' => 'TH',
            'ß' => 's',
            'à' => 'a',
            'á' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'ä' => 'a',
            'å' => 'a',
            'æ' => 'ae',
            'ç' => 'c',
            'è' => 'e',
            'é' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'ì' => 'i',
            'í' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ð' => 'd',
            'ñ' => 'n',
            'ò' => 'o',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ö' => 'o',
            'ø' => 'o',
            'ù' => 'u',
            'ú' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ý' => 'y',
            'þ' => 'th',
            'ÿ' => 'y',
            'Ø' => 'O',
            'Ā' => 'A',
            'ā' => 'a',
            'Ă' => 'A',
            'ă' => 'a',
            'Ą' => 'A',
            'ą' => 'a',
            'Ć' => 'C',
            'ć' => 'c',
            'Ĉ' => 'C',
            'ĉ' => 'c',
            'Ċ' => 'C',
            'ċ' => 'c',
            'Č' => 'C',
            'č' => 'c',
            'Ď' => 'D',
            'ď' => 'd',
            'Đ' => 'D',
            'đ' => 'd',
            'Ē' => 'E',
            'ē' => 'e',
            'Ĕ' => 'E',
            'ĕ' => 'e',
            'Ė' => 'E',
            'ė' => 'e',
            'Ę' => 'E',
            'ę' => 'e',
            'Ě' => 'E',
            'ě' => 'e',
            'Ĝ' => 'G',
            'ĝ' => 'g',
            'Ğ' => 'G',
            'ğ' => 'g',
            'Ġ' => 'G',
            'ġ' => 'g',
            'Ģ' => 'G',
            'ģ' => 'g',
            'Ĥ' => 'H',
            'ĥ' => 'h',
            'Ħ' => 'H',
            'ħ' => 'h',
            'Ĩ' => 'I',
            'ĩ' => 'i',
            'Ī' => 'I',
            'ī' => 'i',
            'Ĭ' => 'I',
            'ĭ' => 'i',
            'Į' => 'I',
            'į' => 'i',
            'İ' => 'I',
            'ı' => 'i',
            'Ĳ' => 'IJ',
            'ĳ' => 'ij',
            'Ĵ' => 'J',
            'ĵ' => 'j',
            'Ķ' => 'K',
            'ķ' => 'k',
            'ĸ' => 'k',
            'Ĺ' => 'L',
            'ĺ' => 'l',
            'Ļ' => 'L',
            'ļ' => 'l',
            'Ľ' => 'L',
            'ľ' => 'l',
            'Ŀ' => 'L',
            'ŀ' => 'l',
            'Ł' => 'L',
            'ł' => 'l',
            'Ń' => 'N',
            'ń' => 'n',
            'Ņ' => 'N',
            'ņ' => 'n',
            'Ň' => 'N',
            'ň' => 'n',
            'ŉ' => 'n',
            'Ŋ' => 'N',
            'ŋ' => 'n',
            'Ō' => 'O',
            'ō' => 'o',
            'Ŏ' => 'O',
            'ŏ' => 'o',
            'Ő' => 'O',
            'ő' => 'o',
            'Œ' => 'OE',
            'œ' => 'oe',
            'Ŕ' => 'R',
            'ŕ' => 'r',
            'Ŗ' => 'R',
            'ŗ' => 'r',
            'Ř' => 'R',
            'ř' => 'r',
            'Ś' => 'S',
            'ś' => 's',
            'Ŝ' => 'S',
            'ŝ' => 's',
            'Ş' => 'S',
            'ş' => 's',
            'Š' => 'S',
            'š' => 's',
            'Ţ' => 'T',
            'ţ' => 't',
            'Ť' => 'T',
            'ť' => 't',
            'Ŧ' => 'T',
            'ŧ' => 't',
            'Ũ' => 'U',
            'ũ' => 'u',
            'Ū' => 'U',
            'ū' => 'u',
            'Ŭ' => 'U',
            'ŭ' => 'u',
            'Ů' => 'U',
            'ů' => 'u',
            'Ű' => 'U',
            'ű' => 'u',
            'Ų' => 'U',
            'ų' => 'u',
            'Ŵ' => 'W',
            'ŵ' => 'w',
            'Ŷ' => 'Y',
            'ŷ' => 'y',
            'Ÿ' => 'Y',
            'Ź' => 'Z',
            'ź' => 'z',
            'Ż' => 'Z',
            'ż' => 'z',
            'Ž' => 'Z',
            'ž' => 'z',
            'ſ' => 's',
            'Ș' => 'S',
            'ș' => 's',
            'Ț' => 'T',
            'ț' => 't',
            '€' => 'E',
            '£' => '',
            'Ơ' => 'O',
            'ơ' => 'o',
            'Ư' => 'U',
            'ư' => 'u',
            'Ầ' => 'A',
            'ầ' => 'a',
            'Ằ' => 'A',
            'ằ' => 'a',
            'Ề' => 'E',
            'ề' => 'e',
            'Ồ' => 'O',
            'ồ' => 'o',
            'Ờ' => 'O',
            'ờ' => 'o',
            'Ừ' => 'U',
            'ừ' => 'u',
            'Ỳ' => 'Y',
            'ỳ' => 'y',
            'Ả' => 'A',
            'ả' => 'a',
            'Ẩ' => 'A',
            'ẩ' => 'a',
            'Ẳ' => 'A',
            'ẳ' => 'a',
            'Ẻ' => 'E',
            'ẻ' => 'e',
            'Ể' => 'E',
            'ể' => 'e',
            'Ỉ' => 'I',
            'ỉ' => 'i',
            'Ỏ' => 'O',
            'ỏ' => 'o',
            'Ổ' => 'O',
            'ổ' => 'o',
            'Ở' => 'O',
            'ở' => 'o',
            'Ủ' => 'U',
            'ủ' => 'u',
            'Ử' => 'U',
            'ử' => 'u',
            'Ỷ' => 'Y',
            'ỷ' => 'y',
            'Ẫ' => 'A',
            'ẫ' => 'a',
            'Ẵ' => 'A',
            'ẵ' => 'a',
            'Ẽ' => 'E',
            'ẽ' => 'e',
            'Ễ' => 'E',
            'ễ' => 'e',
            'Ỗ' => 'O',
            'ỗ' => 'o',
            'Ỡ' => 'O',
            'ỡ' => 'o',
            'Ữ' => 'U',
            'ữ' => 'u',
            'Ỹ' => 'Y',
            'ỹ' => 'y',
            'Ấ' => 'A',
            'ấ' => 'a',
            'Ắ' => 'A',
            'ắ' => 'a',
            'Ế' => 'E',
            'ế' => 'e',
            'Ố' => 'O',
            'ố' => 'o',
            'Ớ' => 'O',
            'ớ' => 'o',
            'Ứ' => 'U',
            'ứ' => 'u',
            'Ạ' => 'A',
            'ạ' => 'a',
            'Ậ' => 'A',
            'ậ' => 'a',
            'Ặ' => 'A',
            'ặ' => 'a',
            'Ẹ' => 'E',
            'ẹ' => 'e',
            'Ệ' => 'E',
            'ệ' => 'e',
            'Ị' => 'I',
            'ị' => 'i',
            'Ọ' => 'O',
            'ọ' => 'o',
            'Ộ' => 'O',
            'ộ' => 'o',
            'Ợ' => 'O',
            'ợ' => 'o',
            'Ụ' => 'U',
            'ụ' => 'u',
            'Ự' => 'U',
            'ự' => 'u',
            'Ỵ' => 'Y',
            'ỵ' => 'y',
            'ɑ' => 'a',
            'Ǖ' => 'U',
            'ǖ' => 'u',
            'Ǘ' => 'U',
            'ǘ' => 'u',
            'Ǎ' => 'A',
            'ǎ' => 'a',
            'Ǐ' => 'I',
            'ǐ' => 'i',
            'Ǒ' => 'O',
            'ǒ' => 'o',
            'Ǔ' => 'U',
            'ǔ' => 'u',
            'Ǚ' => 'U',
            'ǚ' => 'u',
            'Ǜ' => 'U',
            'ǜ' => 'u',
        );

        $locale = 'en_US';

        if (in_array($locale, array('de_DE', 'de_DE_formal', 'de_CH', 'de_CH_informal', 'de_AT'), true)) {
            $chars['Ä'] = 'Ae';
            $chars['ä'] = 'ae';
            $chars['Ö'] = 'Oe';
            $chars['ö'] = 'oe';
            $chars['Ü'] = 'Ue';
            $chars['ü'] = 'ue';
            $chars['ß'] = 'ss';
        } elseif ('da_DK' === $locale) {
            $chars['Æ'] = 'Ae';
            $chars['æ'] = 'ae';
            $chars['Ø'] = 'Oe';
            $chars['ø'] = 'oe';
            $chars['Å'] = 'Aa';
            $chars['å'] = 'aa';
        } elseif ('ca' === $locale) {
            $chars['l·l'] = 'll';
        } elseif ('sr_RS' === $locale || 'bs_BA' === $locale) {
            $chars['Đ'] = 'DJ';
            $chars['đ'] = 'dj';
        }

        $string = strtr($string, $chars);
    } else {
        $chars = array();
        $chars['in'] = "\x80\x83\x8a\x8e\x9a\x9e"
        . "\x9f\xa2\xa5\xb5\xc0\xc1\xc2"
        . "\xc3\xc4\xc5\xc7\xc8\xc9\xca"
        . "\xcb\xcc\xcd\xce\xcf\xd1\xd2"
        . "\xd3\xd4\xd5\xd6\xd8\xd9\xda"
        . "\xdb\xdc\xdd\xe0\xe1\xe2\xe3"
        . "\xe4\xe5\xe7\xe8\xe9\xea\xeb"
        . "\xec\xed\xee\xef\xf1\xf2\xf3"
        . "\xf4\xf5\xf6\xf8\xf9\xfa\xfb"
        . "\xfc\xfd\xff";

        $chars['out'] = 'EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy';

        $string = strtr($string, $chars['in'], $chars['out']);
        $double_chars = array();
        $double_chars['in'] = array("\x8c", "\x9c", "\xc6", "\xd0", "\xde", "\xdf", "\xe6", "\xf0", "\xfe");
        $double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
        $string = str_replace($double_chars['in'], $double_chars['out'], $string);
    }

    return $string;
}

?>