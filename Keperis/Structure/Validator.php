<?php

namespace Keperis\Structure;

interface Validator
{

    /**
     * Validate strucutre on errors
     * @param bool $distinct
     * @return bool
     */
    public function validate(bool $distinct = false): bool;
}