<?php

namespace Twr\ParameterBag;

/**
 * Holds a set of macros
 */

class Macros
{
    protected $macros = array();

    /**
     * Set all the macros at once
     *
     * @param array macros
     */

    public function setAll(array $macros)
    {
        $this->macros = $macros;
    }

    /**
     * Check if a macro exist
     *
     * @param string name
     *
     * @return bool
     */

    public function has($macro)
    {
        return isset($this->macros[$macro]);
    }

    /**
     * Return a macro
     *
     * @param string name
     *
     * @return array
     */

    public function get($macro)
    {
        return $this->macros[$macro];
    }
}