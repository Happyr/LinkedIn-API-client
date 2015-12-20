<?php

namespace Happyr\LinkedIn\Exception;

/**
 * Class LoginError.
 *
 * @author Tobias Nyholm
 */
class LoginError
{
    /**
     * @var string name
     */
    protected $name;

    /**
     * @var string description
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('Name: %s, Description: %s', $this->getName(), $this->getDescription());
    }
}
