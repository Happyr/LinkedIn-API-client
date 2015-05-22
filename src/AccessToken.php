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
     * @param string        $token
     * @param \DateTime|int $expiresIn
     */
    public function __construct($token = null, $expiresIn = null)
    {
        $this->token = $token;

        if ($expiresIn !== null) {
            if ($expiresIn instanceof \DateTime) {
                $this->expiresAt = $expiresIn;
            } else {
                $this->expiresAt = new \DateTime(sprintf('+%dseconds', $expiresIn));
            }
        }
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
     * @param \DateTime $expiresAt
     *
     * @return $this
     */
    public function setExpiresAt(\DateTime $expiresAt = null)
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
