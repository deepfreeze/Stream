<?php
namespace DeepFreeze\IO\Stream;

use DeepFreezeSpi\IO\Stream\StreamInterface;

class FileStream extends StreamDecorator implements StreamInterface
{
  private $validModes = array(
    self::MODE_APPEND,
    self::MODE_CREATE,
    self::MODE_CREATE_NEW,
    self::MODE_OPEN,
    self::MODE_OPEN_OR_CREATE,
    self::MODE_TRUNCATE,
  );
  private $validAccess = array(
    self::ACCESS_READ,
    self::ACCESS_READ_WRITE,
    self::ACCESS_WRITE,
  );
  private $fopenModeMap = array(
    self::MODE_APPEND => array(
      self::ACCESS_WRITE => 'a',
      self::ACCESS_READ_WRITE => 'a+',
    ),
    self::MODE_CREATE => array(
      self::ACCESS_WRITE => 'w',
      self::ACCESS_READ_WRITE => 'w+',
    ),
    self::MODE_CREATE_NEW => array(
      self::ACCESS_WRITE => 'x',
      self::ACCESS_READ_WRITE => 'x+',
    ),
    self::MODE_OPEN => array(
      self::ACCESS_READ => 'r',
      self::ACCESS_WRITE => 'c',
      self::ACCESS_READ_WRITE => 'r+',
    ),
    self::MODE_OPEN_OR_CREATE => array(
      self::ACCESS_READ => 'r',
      self::ACCESS_WRITE => 'c',
      self::ACCESS_READ_WRITE => 'c+',
    ),
    self::MODE_TRUNCATE => array(
      self::ACCESS_WRITE => 'w',
      self::ACCESS_READ_WRITE => 'w+',
    ),
  );


  public function __construct($path, $fileMode, $fileAccess=self::MODE_OPEN) {
    $this->init($path, $fileMode, $fileAccess);
  }


  private function init(
    $path,
    $fileMode,
    $fileAccess
  ) {
    if ('' === (string)$path) {
      throw new Exception\InvalidArgumentException('path',
        $path,
        'Parameter "path" is null or empty.');
    }
    if (!in_array($fileMode, $this->validModes)) {
      throw new Exception\InvalidArgumentException('fileMode',
        $fileMode,
        'Invalid fileMode supplied.');
    }
    if (!in_array($fileAccess, $this->validAccess)) {
      throw new Exception\InvalidArgumentException('fileAccess',
        $fileAccess,
        'Invalid fileAccess supplied.');
    }

    // READ ONLY ACCESS
    if ($fileAccess === self::ACCESS_READ) {
      if (!in_array($fileMode, array(self::MODE_OPEN, self::MODE_OPEN_OR_CREATE))) {
        throw new Exception\RuntimeException('Requested file mode requires write file access.');
      }

      // OPEN_OR_CREATE has different semantics than what fopen() allows with a read-only handle
      if ($fileMode === self::MODE_OPEN_OR_CREATE) {
        if (!file_exists($path)) {
          try {
            // create the file
            fclose(WarningToException::fopen($path, 'w'));
          } catch (Exception\RuntimeException $e) {
            throw new Exception\RuntimeException(sprintf(
              'Unable to create file "%s".',
              $path
            ));
          }
        }
      }
    }

    // Check for exceptions
    switch ($fileMode) {
      case self::MODE_CREATE_NEW :
        if (file_exists($path)) {
          throw new Exception\FileExistsException(sprintf(
            'The requested file exists (%s).',
            $path
          ));
        }
        break;
      case self::MODE_OPEN :
      case self::MODE_TRUNCATE:
        if (!file_exists($path)) {
          throw new Exception\FileNotFoundException(sprintf(
            'The requested file was not found.',
            $path
          ));
        }
        break;
    }

    $fopenMode = $this->fopenModeMap[$fileMode][$fileAccess];
    $handle = WarningToException::fopen($path, $fopenMode);
    if (!$handle) {
      throw new Exception\RuntimeException(sprintf(
        'Unable to open file "%s".',
        $path
      ));
    }
    $this->stream = new NativePhpStream($handle);
  }
}
