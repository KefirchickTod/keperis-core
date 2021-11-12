<?php

namespace Keperis\Page\Components\Table\View;

use Keperis\View\ValidatorInterface;
use Keperis\View\Validators\Validator;

class TableViewValidator implements ValidatorInterface
{

    static private $types = [
        Validator::VIEW_VALIDATOR_PHP_EXTENSION,
        Validator::VIEW_VALIDATOR_HTML_EXTENSION,
    ];

    /**
     * @param string $file
     * @return string|null
     */
    public function validate(string $file)
    {
        $file = $this->getCorrectDir($file);

        if ($file === false) {
            throw new \RuntimeException("Didnt find file  $file" . __CLASS__);
        }
        return $file;
    }

    private function getCorrectDir(string $file)
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
