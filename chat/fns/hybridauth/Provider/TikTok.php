<?php

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Data;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\User;

class TikTok extends OAuth2
{
    public $scope = 'user.info.basic';
    protected $apiBaseUrl = 'https://open.tiktokapis.com/v2/';
    protected $authorizeUrl = 'https://www.tiktok.com/v2/auth/authorize/';
    protected $accessTokenUrl = 'https://open.tiktokapis.com/v2/oauth/token/';
    protected $refreshTokenUrl = 'https://open.tiktokapis.com/v2/oauth/token/';
    protected $apiDocumentation = 'https://developers.tiktok.com/doc/overview/';

    public function initialize()
    {
        parent::initialize();

        $tiktok_code_verifier = random_string(['length' => 6]);

        $this->AuthorizeUrlParameters = array(
            'response_type' => 'code',
            'client_key' => $this->clientId,
            'redirect_uri' => $this->callback,
            'scope' => $this->scope,
		 'code_challenge' => hash('sha256', $tiktok_code_verifier),
		 'code_challenge_method'=>'S256',
        );

        $this->tokenExchangeParameters = array(
            'client_key' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->callback,
        );

        $this->tokenRefreshParameters = array(
            'client_key' => $this->clientId,
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->getStoredData('refresh_token'),
        );
    }

    public function getUserProfile()
    {
        $response = $this->apiRequest('user/info/?fields=open_id,union_id,avatar_url,display_name');
        if (!property_exists($response, 'data') || !property_exists($response->data, 'user') || !property_exists($response->data->user, 'union_id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }
        $data = new Data\Collection($response->data->user);

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->get('union_id');
        $userProfile->displayName = $data->get('display_name');
        $userProfile->description = $data->get('bio_description');
        $userProfile->profileURL = $data->get('profile_deep_link');
        $userProfile->photoURL = $data->get('avatar_url');

        return $userProfile;
    }
}