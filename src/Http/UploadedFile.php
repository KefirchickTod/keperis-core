<?php


namespace Keperis\Http;

use http\Exception\InvalidArgumentException;
use RuntimeException;
use Keperis\Interfaces\UploadedFileInterface;

/**
 * Represents Uploaded Files.
 *
 * It manages and normalizes uploaded files according to the PSR-7 standard.
 *
 * @link https://github.com/php-fig/http-message/blob/master/src/UploadedFileInterface.php
 * @link https://github.com/php-fig/http-message/blob/master/src/StreamInterface.php
 */
class UploadedFile implements UploadedFileInterface
{

    protected $name;

    protected $type;

    protected $size;

    protected $error = UPLOAD_ERR_OK; //0

    protected $stream;

    protected $moved = false;

    protected $sapi = false;

    protected $file;

    public function __construct($file, $name = null, $type = null, $size = null, $error = UPLOAD_ERR_OK, $sapi = false)
    {
        $this->file = $file;
        $this->name = strtolower($name);
        $this->type = $type;
        $this->size = $size;
        $this->error = $error;
        $this->sapi = $sapi;
    }


    public function getFile(){
        return $this->file;
    }
    /**
     * @param ServerData $data
     * @return array|static
     */
    public static function creatFromServer(ServerData $data)
    {
        if (isset($_FILES)) {
            return static::parseUploadedFiles($_FILES);
        }

        return [];
    }

    public static function parseUploadedFiles(array $uploadsFiles)
    {

        $parsed = [];
        foreach ($uploadsFiles as $field => $uploadsFile) {
            if (!isset($uploadsFile['error'])) {
                if (is_array($uploadsFile)) {
                    $parsed[$field] = self::parseUploadedFiles($uploadsFile);
                }
                continue;
            }

            $parsed[$field] = [];

            if (!is_array($uploadsFile['error'])) {
                $parsed[$field] = new static(
                    $uploadsFile['tmp_name'],
                    isset($uploadsFile['tmp_name']) ? $uploadsFile['name'] : null,
                    $uploadsFile['type'] ?? null,
                    $uploadsFile['size'] ?? null,
                    $uploadsFile['error'],
                    true
                );
            } else {
                foreach ($uploadsFile['error'] as $key => $error) {
                    $parsed[$field][] = new static(
                        $uploadsFile['tmp_name'][$key],
                        isset($uploadsFile['tmp_name']) ? $uploadsFile['name'][$key] : null,
                        $uploadsFile['type'][$key] ?? null,
                        $uploadsFile['size'][$key] ?? null,
                        $uploadsFile['error'][$key],
                        true
                    );
                }
            }
        }


        return $parsed;
    }

    /**
     * @inheritDoc
     */
    public function getStream()
    {
        if($this->moved){
            throw new \RuntimeException(sprintf('Uploaded file %1s has already been moved', $this->name));
        }
        if($this->stream === null){
            $this->stream = new Stream(fopen($this->file, 'r'));
        }
        return $this->stream;
    }

    /**
     * @inheritDoc
     */
    public function moveTo($targetPath)
    {
        if ($this->moved) {
            throw new \RuntimeException('Uploaded file already moved');
        }

        if (!is_writable(dirname($targetPath))) {
            throw new InvalidArgumentException('Upload target path is not writable');
        }
        $targetIsStream = strpos($targetPath, '://') > 0;
        if ($targetIsStream) {
            if (!copy($this->file, $targetPath)) {
                throw new RuntimeException(sprintf('Error moving uploaded file %1s to %2s', $this->name, $targetPath));
            }
            if (!unlink($this->file)) {
                throw new RuntimeException(sprintf('Error removing uploaded file %1s', $this->name));
            }
        } elseif ($this->sapi) {
            if (!is_uploaded_file($this->file)) {
                throw new RuntimeException(sprintf('%1s is not a valid uploaded file', $this->file));
            }

            if (!move_uploaded_file($this->file, $targetPath)) {
                throw new RuntimeException(sprintf('Error moving uploaded file %1s to %2s', $this->name, $targetPath));
            }
        } else {
            if (!rename($this->file, $targetPath)) {
                throw new RuntimeException(sprintf('Error moving uploaded file %1s to %2s', $this->name, $targetPath));
            }
        }

        $this->moved = true;
        return true;
    }

    public function apiLoad($file){

    }

    /**
     * @inheritDoc
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @inheritDoc
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @inheritDoc
     */
    public function getClientFilename()
    {
       return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getClientMediaType()
    {
        return $this->type;
    }
}