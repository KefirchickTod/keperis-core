<?php


namespace Keperis\Eloquent\Provide\Builder;


interface BuilderInterface
{




    /**
     * Building to query
     * @return BuilderInterface
     */
    public function build();
}