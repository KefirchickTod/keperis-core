<?php


namespace Keperis\View\Validators;


use Keperis\View\ValidatorInterface;

/**
 * Class Validator
 * @package src\View\Validator
 * @version 0.1
 * @author Zahar Py;ypchuck
 */
class Validator implements ValidatorInterface
{
    const VIEW_VALIDATOR_PHP_EXTENSION = 'php';
    const VIEW_VALIDATOR_HTML_EXTENSION = 'html';
    const VIEW_VALIDATOR_FILE_DIR = ROOT_PATH . '/resource/views/';

    /**
     * @inheritDoc
     */
    public function validate(string $file)
    {
        $file = $this->getCorrectDir($file);

        $file = $this->validType($file, [self::VIEW_VALIDATOR_PHP_EXTENSION, self::VIEW_VALIDATOR_HTML_EXTENSION]);


        if ($file === false) {
            throw new \RuntimeException("Didnt find file  $file" . __CLASS__);
        }
        return $file;
    }

    private function validType(string $file, array $types){

        foreach ($types as $type){
            $file = "$file.$type";

            if(file_exists($file)){
                return $file;
            }
        }

        return false;
    }

    /**
     * @param string $file
     * @return string
     * Delete dot in file nama and get correct file dir;
     */
    private function getCorrectDir(string $file)
    {
        $fileDir = explode('.', $file);
        if (count($fileDir) > 1) {
            $file = join('/', $fileDir);
        }
        return self::VIEW_VALIDATOR_FILE_DIR .$file;
    }
}