<?php


namespace Keperis\View;

/**
 * Interface ValidatorInterface
 * @package src\Interfaces\View
 */
interface ValidatorInterface
{

    /**
     * Extension for files
     */
    const VIEW_VALIDATOR_PHP_EXTENSION = 'php';
    const VIEW_VALIDATOR_HTML_EXTENSION = 'html';
    /**
     * @param string $file
     * @return string|null
     */
    public function validate(string $file);
}