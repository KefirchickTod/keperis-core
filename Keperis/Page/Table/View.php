<?php

namespace Keperis\Page\Table;

use Keperis\View\ValidatorInterface;

class View  implements ValidatorInterface
{
    static private $types = [
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