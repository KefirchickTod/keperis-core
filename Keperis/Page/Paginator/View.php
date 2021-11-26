<?php

namespace Keperis\Page\Paginator;

use Keperis\View\ValidatorInterface;
use \Keperis\Page\View as BaseComponentView;

class View extends BaseComponentView
{


    protected function getCorrectDir(string $file)
    {
        $fileDir = explode('.', $file);
        if (count($fileDir) > 1) {
            $file = join('/', $fileDir);
        }
        foreach (self::$types as $type) {
            if (file_exists("{$file}.{$type}")) {
                return "{$file}.{$type}";
            }
        }

        return false;
    }

}
