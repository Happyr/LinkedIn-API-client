<?php

namespace HappyR\LinkedIn\Exceptions;

/**
 * Class LoginError
 *
 * @author Tobias Nyholm
 *
 */
class LoginError
{
    /**
     * @var string name
     *
     */
    protected $name;

    /**
     * @var string description
     *
     */
    protected $description;

    /**
     * @param string $name
     * @param string $description
     */
    public function __construct($name, $description)
    {
        $this->name = $name;
        $this->description = $description;
    }

    /**
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}