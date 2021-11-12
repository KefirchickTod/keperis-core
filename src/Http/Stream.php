<?php


namespace src\Http;


use Error;
use RuntimeException;
use src\Interfaces\StreamInterface;


class Stream implements StreamInterface
{

    /**
     * @var array
     */
    protected static $modes = [
        'readable' => ['r', 'r+', 'w+', 'a+', 'x+', 'c+'],
        'writable' => ['r+', 'w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+'],
    ];
    /**
     * @var resource
     */
    protected $stream;
    /**
     * @var array
     */
    private $meta;
    /**
     * @var bool|null
     */
    private $readable;
    /**
     * @var bool|null
     */
    private $writable;
    private $seekable;
    /**
     * @var int
     */
    private $size;

    /**
     * Stream constructor.
     * @param $stream resource
     */
    public function __construct($stream)
    {
        $this->attach($stream);
    }

    /**
     * @param $newStream resource
     */
    public function attach($newStream)
    {
        if (is_resource($newStream) === false) {
            throw new Error(__METHOD__ . ' argument must be a valid PHP resource');
        }

        if ($this->isAttached() === true) {
            $this->detach();
        }

        $this->stream = $newStream;
    }

    private function isAttached()
    {
        return is_resource($this->stream);
    }

    /**
     * @inheritDoc
     */
    public function detach()
    {
        $oldResource = $this->stream;
        $this->stream = null;
        $this->meta = null;
        $this->readable = null;
        $this->writable = null;
        $this->seekable = null;
        $this->size = null;

        return $oldResource;
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        if (!$this->isAttached()) {
            return '';
        }

        try {
            $this->rewind();
            return $this->getContents();
        } catch (RuntimeException $e) {
            return '';
        }
    }

    /**
     * @inheritDoc
     */
    public function rewind()
    {
        if (!$this->isSeekable() || rewind($this->stream) === false) {
            throw new RuntimeException('Could not rewind stream');
        }
    }

    /**
     * @inheritDoc
     */
    public function isSeekable()
    {
        if ($this->seekable === null) {
            $this->seekable = false;
            if ($this->isAttached()) {
                $meta = $this->getMetadata();
                $this->seekable = $meta['seekable'];
            }
        }

        return $this->seekable;
    }

    /**
     * @inheritDoc
     */
    public function getMetadata($key = null)
    {
        $meta = stream_get_meta_data($this->stream);
        return $key ? $meta[$key] : $meta;
    }

    /**
     * @inheritDoc
     */
    public function getContents()
    {
        if (!$this->isReadable() || ($contents = stream_get_contents($this->stream)) === false) {
            throw new RuntimeException('Could not get contents of stream');
        }

        return $contents;
    }

    /**
     * @inheritDoc
     */
    public function isReadable()
    {
        if (!$this->readable) {
            $this->readable = null;
            if ($this->isAttached()) {
                $meta = $this->getMetadata();
                foreach (self::$modes['readable'] as $mode) {
                    if (strpos($meta['mode'], $mode) === 0) {
                        $this->readable = true;
                        break;
                    }
                }
            }

        }
        return $this->readable;

    }

    /**
     * @inheritDoc
     */
    public function close()
    {
        return fclose($this->stream);
    }

    /**
     * @inheritDoc
     */
    public function getSize()
    {
        $size = null;
        try {
            $size = fstat($this->stream)['size'];
        } catch (RuntimeException $exception) {
            error_log($exception->getMessage());
        }
        return $size;
    }

    /**
     * @inheritDoc
     */
    public function tell()
    {
        $pointer = 0;
        try {
            $pointer = ftell($this->stream);
            if (!$pointer) {
                throw new RuntimeException("Func tell doesnt work");
            }
        } catch (RuntimeException $exception) {
            error_log($exception->getMessage());
        }
        return $pointer;
    }

    /**
     * @inheritDoc
     */
    public function eof()
    {
        return $this->isAttached() ? feof($this->stream) : true;
    }

    /**
     * @inheritDoc
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if ($this->isAttached()) {
            fseek($this->stream, $offset, $whence);
        }
    }

    /**
     * @inheritDoc
     */
    public function write($string)
    {
        if (!$this->isWritable() || ($written = fwrite($this->stream, $string)) === false) {
            throw new RuntimeException("Could not write to stream");
        }
        $this->size = null;
        return $written;

    }

    /**
     * @inheritDoc
     */
    public function isWritable()
    {
        if ($this->writable === null) {
            $this->writable = false;
            if ($this->isAttached()) {
                $meta = $this->getMetadata();
                foreach (self::$modes['writable'] as $mode) {
                    if (strpos($meta['mode'], $mode) === 0) {
                        $this->writable = true;
                        break;
                    }
                }
            }
        }

        return $this->writable;
    }

    /**
     * @inheritDoc
     */
    public function read($length)
    {
        if ($this->isReadable()) {
            return fread($this->stream, $length);
        }
        return '';
    }

}