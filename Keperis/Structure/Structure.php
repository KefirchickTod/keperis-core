<?php

namespace Keperis\Structure;

interface Structure
{

    /**
     * Set structure and valid it
     * @param array $structure
     * @param string $key
     * @return Structure
     */
    public function set(array $structure, string $key) : Structure;

    /**
     * Convert structure to query and get it
     * @param string $key
     * @return array
     */
    public function get(string $key) : array;
}