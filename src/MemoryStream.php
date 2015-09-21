<?php
namespace DeepFreeze\IO\Stream;


class MemoryStream extends StreamDecorator
{
  private $validModes = array(
    self::MODE_OPEN,
    self::MODE_APPEND,
  );

  public function __construct($fileMode=self::MODE_OPEN) {
    $this->init($fileMode);
  }

  private function init($fileMode) {
    if (!in_array($fileMode, $this->validModes)) {
      throw new Exception\InvalidArgumentException('fileMode',
        $fileMode,
        'Invalid fileMode supplied.');
    }

    $fopenMode = ($fileMode === self::MODE_APPEND) ? 'a+' : 'r+';
    $handle = WarningToException::fopen('php://memory', $fopenMode);
    if (!$handle) {
      throw new Exception\RuntimeException('Unable to create memory instance.');
    }
    $this->stream = new NativePhpStream($handle);
  }
}
