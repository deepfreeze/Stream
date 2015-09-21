<?php
namespace DeepFreeze\IO\Stream;


class TemporaryStream extends StreamDecorator
{
  private $validModes = array(
    self::MODE_OPEN,
    self::MODE_APPEND,
  );

  public function __construct($fileMode=self::MODE_OPEN, $memoryLimit=null) {
    $this->init($fileMode, $memoryLimit);
  }

  private function init($fileMode, $memoryLimit) {
    if (!in_array($fileMode, $this->validModes)) {
      throw new Exception\InvalidArgumentException('fileMode',
        $fileMode,
        'Invalid fileMode supplied.');
    }
    $memoryLimit = ($memoryLimit !== null) ? (int)$memoryLimit : null;
    if ($memoryLimit <= 0) {
      throw new Exception\InvalidArgumentException('memoryLimit', $memoryLimit, sprintf(
        'The parameter "%s" must be an integer greater than zero.', $memoryLimit
      ));
    }


    $fopenMode = ($fileMode === self::MODE_APPEND) ? 'a+' : 'r+';
    $handle = WarningToException::fopen('php://temp', $fopenMode);
    if (!$handle) {
      throw new Exception\RuntimeException('Unable to create memory instance.');
    }
    $this->stream = new NativePhpStream($handle);
  }
}
