<?php
function link_filter($data) {
    $result = true;

    $link_filter_setting = Registry::load('settings')->link_filter;
    $links = isset($data['links']) && is_array($data['links']) ? $data['links'] : [];

    if ($link_filter_setting === 'enable') {

        $url_blacklist_file = 'assets/cache/url_blacklist.cache';
        $url_blacklist = [];

        if (file_exists($url_blacklist_file)) {
            include($url_blacklist_file);
            if (!isset($url_blacklist) || !is_array($url_blacklist)) {
                $url_blacklist = [];
            }
        }

        foreach ($links as $urlToCheck) {
            $urlToCheck = trim($urlToCheck);

            if (!filter_var($urlToCheck, FILTER_VALIDATE_URL)) {
                return false; 
            }

            foreach ($url_blacklist as $blacklistedUrl) {
                $blacklistedUrl = trim($blacklistedUrl);
                
                if (strpos($blacklistedUrl, '*') !== false) {
                    $pattern = str_replace('\*', '.*', preg_quote($blacklistedUrl, '/'));
                    if (preg_match('/^' . $pattern . '$/iu', $urlToCheck)) {
                        return false;
                    }
                } else {
                    $urlHost = parse_url($urlToCheck, PHP_URL_HOST);
                    $blacklistHost = parse_url($blacklistedUrl, PHP_URL_HOST);

                    if ($urlHost && $blacklistHost) {
                        if (function_exists('idn_to_ascii')) {
                            $urlDomain = idn_to_ascii($urlHost, 0, INTL_IDNA_VARIANT_UTS46);
                            $blacklistDomain = idn_to_ascii($blacklistHost, 0, INTL_IDNA_VARIANT_UTS46);
                        } else {
                            $urlDomain = $urlHost;
                            $blacklistDomain = $blacklistHost;
                        }

                        if (strcasecmp($urlDomain, $blacklistDomain) === 0) {
                            return false;
                        }
                    } else {
                        if (strcasecmp($urlToCheck, $blacklistedUrl) === 0) {
                            return false;
                        }
                    }
                }
            }
        }
    }
    else if ($link_filter_setting === 'strict_mode') {

        $url_whitelist_file = 'assets/cache/url_whitelist.cache';
        $url_whitelist = [];

        if (file_exists($url_whitelist_file)) {
            include($url_whitelist_file);
            if (!isset($url_whitelist) || !is_array($url_whitelist)) {
                $url_whitelist = [];
            }
        }

        foreach ($links as $urlToCheck) {
            $urlToCheck = trim($urlToCheck);

            if (!preg_match('#^https?://#i', $urlToCheck)) {
                $urlToCheck = 'http://' . $urlToCheck;
            }

            if (!filter_var($urlToCheck, FILTER_VALIDATE_URL)) {
                return false;
            }

            $matched = false;

            $urlHost = parse_url($urlToCheck, PHP_URL_HOST);
            if (!$urlHost) {
                return false;
            }
            if (function_exists('idn_to_ascii')) {
                $urlDomain = idn_to_ascii($urlHost, 0, INTL_IDNA_VARIANT_UTS46);
            } else {
                $urlDomain = $urlHost;
            }

            foreach ($url_whitelist as $whitelistedUrl) {
                $whitelistedUrl = trim($whitelistedUrl);

                if (!preg_match('#^https?://#i', $whitelistedUrl)) {
                    $whitelistedUrl = 'http://' . $whitelistedUrl;
                }

                if (strpos($whitelistedUrl, '*') !== false) {
                    $whitelistHost = parse_url($whitelistedUrl, PHP_URL_HOST);
                    if (!$whitelistHost) {
                        continue;
                    }
                    if (function_exists('idn_to_ascii')) {
                        $whitelistDomain = idn_to_ascii($whitelistHost, 0, INTL_IDNA_VARIANT_UTS46);
                    } else {
                        $whitelistDomain = $whitelistHost;
                    }

                    $pattern = str_replace('\*', '.*', preg_quote($whitelistDomain, '/'));
                    if (preg_match('/^' . $pattern . '$/iu', $urlDomain)) {
                        $matched = true;
                        break;
                    }
                } else {
                    $whitelistHost = parse_url($whitelistedUrl, PHP_URL_HOST);
                    if (!$whitelistHost) {
                        continue; 
                    }
                    if (function_exists('idn_to_ascii')) {
                        $whitelistDomain = idn_to_ascii($whitelistHost, 0, INTL_IDNA_VARIANT_UTS46);
                    } else {
                        $whitelistDomain = $whitelistHost;
                    }

                    if (strcasecmp($urlDomain, $whitelistDomain) === 0) {
                        $matched = true;
                        break;
                    }
                }
            }

            if (!$matched) {
                return false;
            }
        }
    }

    return $result;
}
?>
