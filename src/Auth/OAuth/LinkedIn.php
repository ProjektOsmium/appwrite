<?php

namespace Auth\OAuth;

use Auth\OAuth;

class LinkedIn extends OAuth
{
    /**
     * @var array
     */
    protected $user = [];

    /**
     * @var array
     */
    protected $scope = [
        'r_basicprofile',
        'r_emailaddress',
        //'r_fullprofile', //TODO request permission from linkedIn for this scope
    ];

    /**
     * Documentation
     *
     * OAuth:
     * https://developer.linkedin.com/docs/oauth2
     *
     * API/V2:
     * https://developer.linkedin.com/docs/guide/v2
     *
     * Basic Profile Fields:
     * https://developer.linkedin.com/docs/fields/basic-profile
     *
     */

    /**
     * @return string
     */
    public function getName():string
    {
        return 'linkedin';
    }

    /**
     * @return string
     */
    public function getLoginURL():string
    {
        return 'https://www.linkedin.com/oauth/v2/authorization?' . http_build_query([
                'response_type' => 'code',
                'client_id' => $this->appID,
                'redirect_uri' => $this->callback,
                'scope' => implode(' ', $this->scope),
                'state' => json_encode($this->state),
            ]);
    }

    /**
     * @param string $code
     * @return string
     */
    public function getAccessToken(string $code):string
    {
        $accessToken = $this->request('POST', 'https://www.linkedin.com/oauth/v2/accessToken', ['Content-Type: application/x-www-form-urlencoded'],
            http_build_query([
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->callback,
                'client_id' => $this->appID,
                'client_secret' => $this->appSecret,
            ])
        );

        $accessToken = json_decode($accessToken, true);

        if(isset($accessToken['access_token'])) {
            return $accessToken['access_token'];
        }

        return '';
    }

    /**
     * @param $accessToken
     * @return string
     */
    public function getUserID(string $accessToken):string
    {
        $user = $this->getUser($accessToken);

        if(isset($user['id'])) {
            return $user['id'];
        }

        return '';
    }

    /**
     * @param $accessToken
     * @return string
     */
    public function getUserEmail(string $accessToken):string
    {
        $user = $this->getUser($accessToken);

        if(isset($user['emailAddress'])) {
            return $user['emailAddress'];
        }

        return '';
    }

    /**
     * @param $accessToken
     * @return string
     */
    public function getUserName(string $accessToken):string
    {
        $user = $this->getUser($accessToken);
        $name = '';

        if(isset($user['firstName'])) {
            $name = $user['firstName'];
        }

        if(isset($user['lastName'])) {
            $name = (empty($name)) ? $user['lastName'] : $name . ' ' . $user['lastName'];
        }

        return $name;
    }

    /**
     * @param string $accessToken
     * @return array
     */
    protected function getUser(string $accessToken)
    {
        if(empty($this->user)) {
            $this->user = json_decode($this->request('GET', 'https://api.linkedin.com/v1/people/~:(id,first-name,last-name,location,headline,industry,num-connections,picture-url,positions,summary,specialties,email-address)?format=json', ['Authorization: Bearer ' . urlencode($accessToken)]), true);
        }

        return $this->user;
    }
}