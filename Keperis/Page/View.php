<?php

namespace Keperis\Page;

use Keperis\View\ValidatorInterface;

abstract class View implements ValidatorInterface
{
    static protected $types = [
        ValidatorInterface::VIEW_VALIDATOR_PHP_EXTENSION,
        ValidatorInterface::VIEW_VALIDATOR_HTML_EXTENSION,
    ];

    /**
     * @param string $file
     * @return string|null
     */
    public function validate(string $file): ?string
    {
        $file = $this->getCorrectDir($file);

        if ($file === false) {
            throw new \RuntimeException("Didnt find file  $file" . __CLASS__);
        }
        return $file;
    }


    abstract protected function getCorrectDir(string $file);
}
