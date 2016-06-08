<?php

namespace Mouf\Utils\Task;

abstract class Task
{
    /**
     * Number of tries.
     *
     * @var int
     */
    protected $nbTries = 0;

    /**
     * Prority.
     *
     * @var int
     */
    protected $priority = 1;

    /**
     * Unserialize string to Task.
     *
     * @param $string
     *
     * @return Task
     */
    public static function unserialize($string)
    {
        return unserialize($string);
    }

    /**
     * Serialize Task to string.
     *
     * @return string
     */
    public function serialize()
    {
        return serialize($this);
    }

    /**
     * Add one task try.
     */
    public function addTry()
    {
        ++$this->nbTries;
    }

    /**
     * Get task number of tries.
     *
     * @return int
     */
    public function getNumberOfTries()
    {
        return $this->nbTries;
    }

    /**
     * Get task priority.
     *
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }
}
