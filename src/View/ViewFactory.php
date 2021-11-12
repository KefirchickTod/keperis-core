<?php


namespace Keperis\View;

use Keperis\View\ValidatorInterface;
use Keperis\View\Renderer\Render;
use Keperis\View\Validators\Validator;

/**
 * Class ViewFactory
 * @package src\View
 * @author Zahar Pylypchuck
 */
class ViewFactory
{

    public static function makeWithOwnValidator(ValidatorInterface $validator, string $file, $data = [])
    {
        return (new View(new Render($validator), $file, $data));
    }

    public static function makeWithoutDir(string $file, $data = [])
    {
        return (new View(new Render(new Validator()), $file, $data));
    }

    public static function make(string $file, $data = [])
    {
        return (new View(new Render(new Validator()), $file, $data))->withDir('layots');
    }
}
