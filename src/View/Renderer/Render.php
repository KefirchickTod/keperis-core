<?php


namespace src\View\Renderer;

/**
 * Class Render
 * @package Src\src\View\Renderer
 * @author Zahar Pylypchuck
 * @version 0.1
 */

use src\View\RenderInterface;
use src\View\ValidatorInterface;


class Render implements RenderInterface
{


    private $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @inheritDoc
     */
    public function render(string $file, array $data = []): string
    {
        $file = $this->validator->validate($file);
        ob_start();
        extract($data, EXTR_SKIP);

        try {
             include_once $file;
        } catch (\Exception $exception) {
            ob_end_clean();
        }

        return trim(ob_get_clean());
    }

}