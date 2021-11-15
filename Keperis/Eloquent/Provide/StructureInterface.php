<?php

namespace Keperis\Eloquent\Provide;

interface StructureInterface
{

    /**
     * @param array $structure
     * @param string $key
     * @return static
     */
    public function set(array $structure, string $key);

    /**
     * @param string|null $key
     * @return mixed
     */
    public function get(?string $key = null);

}