<?php
include 'fns/captcha/load.php';

if (Registry::load('settings')->captcha === 'google_recaptcha_v2') {
    if (!isset($data['g-recaptcha-response']) || empty(trim($data['g-recaptcha-response']))) {
        $result['error_message'] = Registry::load('strings')->invalid_captcha;
        $result['error_variables'][] = 'captcha';
        $noerror = false;
    } else if (!validate_captcha('google_recaptcha_v2', $data['g-recaptcha-response'])) {
        $result['error_message'] = Registry::load('strings')->invalid_captcha;
        $result['error_variables'][] = 'captcha';
        $noerror = false;
    }
} else if (Registry::load('settings')->captcha === 'hcaptcha') {
    if (!isset($data['h-captcha-response']) || empty(trim($data['h-captcha-response']))) {
        $result['error_message'] = Registry::load('strings')->invalid_captcha;
        $result['error_variables'][] = 'captcha';
        $noerror = false;
    } else if (!validate_captcha('hcaptcha', $data['h-captcha-response'])) {
        $result['error_message'] = Registry::load('strings')->invalid_captcha;
        $result['error_variables'][] = 'captcha';
        $noerror = false;
    }
} elseif (Registry::load('settings')->captcha === 'cloudflare_turnstile') {
    if (!isset($data['cf-turnstile-response']) || empty(trim($data['cf-turnstile-response']))) {
        $result['error_message'] = Registry::load('strings')->invalid_captcha;
        $result['error_variables'][] = 'captcha';
        $noerror = false;
    } elseif (!validate_captcha('cloudflare_turnstile', $data['cf-turnstile-response'])) {
        $result['error_message'] = Registry::load('strings')->invalid_captcha;
        $result['error_variables'][] = 'captcha';
        $noerror = false;
    }
} elseif (Registry::load('settings')->captcha === 'friendly_captcha') {
    if (!isset($data['frc-captcha-response']) || empty(trim($data['frc-captcha-response']))) {
        $result['error_message'] = Registry::load('strings')->invalid_captcha;
        $result['error_variables'][] = 'captcha';
        $noerror = false;
    } elseif (!validate_captcha('friendly_captcha', $data['frc-captcha-response'])) {
        $result['error_message'] = Registry::load('strings')->invalid_captcha;
        $result['error_variables'][] = 'captcha';
        $noerror = false;
    }
}
?>