<?php

namespace Inviqa;

use Composer\DependencyResolver\Problem;
use Composer\DependencyResolver\Pool;

/**
 * Represents a dependency problem in the configuration.
 */
class ConfigurationProblem extends Problem
{
    private $message;

    public function __construct(Pool $pool, $message)
    {
        parent::__construct($pool);
        $this->message = $message;
    }

    public function getPrettyString(array $installedMap = array())
    {
        return "\n    - $this->message";
    }
}
