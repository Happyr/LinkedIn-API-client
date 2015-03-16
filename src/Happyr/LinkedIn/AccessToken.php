<?php

namespace Happyr\LinkedIn;

/**
 * @author Tobias Nyholm
 */
class AccessToken
{
    /**
     * @var null|string token
     */
    private $token;

    /**
     * @var \DateTime expiresAt
     */
    private $expiresAt;

    /**
     * @param string    $token
     * @param \DateTime $expiresIn
     */
    public function __construct($token = null, \DateTime $expiresIn = null)
    {
        $this->token = $token;
        $this->expiresAt = $expiresIn;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->token ?: '';
    }

    /**
     * Does a token string exist?
     *
     * @return bool
     */
    public function hasToken()
    {
        return !empty($this->token);
    }

    /**
     * @param string $json
     */
    public function constructFromJson($json)
    {
        $data = json_decode($json, true);

        if (isset($data['access_token'])) {
            $this->token = $data['access_token'];
        }

        if (isset($data['expires_in'])) {
            $this->expiresAt = new \DateTime(sprintf('+%dseconds', $data['expires_in']));
        }
    }

    /**
     * @param \DateTime $expiresAt
     *
     * @return $this
     */
    public function setExpiresAt(\DateTime $expiresAt=null)
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * @param null|string $token
     *
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getToken()
    {
        return $this->token;
    }
}
