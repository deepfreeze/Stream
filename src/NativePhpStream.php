<?php
namespace DeepFreeze\IO\Stream;

use DeepFreeze\IO\Stream\Exception\NotSupportedException;
use DeepFreezeSpi\IO\Stream\StreamInterface;

class NativePhpStream implements StreamInterface
{
  private $defaultCopyBufferSize = 65536;
  private $readableModes = array(
    'r',
    'rb',
    'rt',
    'rw',
    'r+',
    'r+b',
    'r+t',
    'w+',
    'w+b',
    'w+t',
    'a+',
    'a+b',
    'a+t',
    'c+',
    'c+b',
    'c+t',
    'x+',
    'x+b',
    'x+t',
  );
  private $writeableModes = array(
    'rw',
    'r+',
    'r+b',
    'r+t',
    'w',
    'wb',
    'wt',
    'w+',
    'w+b',
    'w+t',
    'a',
    'ab',
    'at',
    'a+',
    'a+b',
    'a+t',
    'c',
    'cb',
    'ct',
    'c+',
    'c+b',
    'c+t',
    'x',
    'xb',
    'xt',
    'x+',
    'x+b',
    'x+t',
  );

  /**
   * File Handle
   * @var resource
   */
  private $handle;

  /**
   * @var int
   */
  private $fileSize;

  /**
   * @var bool
   */
  private $canRead;

  /**
   * @var bool
   */
  private $canWrite;

  /**
   * @var bool
   */
  private $canTimeout;

  /**
   * @var bool
   */
  private $canSeek;


  /**
   * NativePhpStream constructor.
   * @param resource $handle Stream Resource Handle
   */
  public function __construct($handle = null) {
    $this->setHandle($handle);
  }


  /**
   * Set the native PHP handle.
   * @param $handle
   */
  private function setHandle($handle) {
    if (!is_resource($handle)) {
      throw new Exception\InvalidArgumentException(
        'handle',
        $handle,
        sprintf('The parameter "handle" must be a stream resource.')
      );
    }
    $this->handle = $handle;
    $this->initHandleState();
  }


  /**
   * Initialize the stream properties
   * Set the stream capabilities.
   *
   */
  private function initHandleState() {
    $handle = $this->handle;
    $metaData = stream_get_meta_data($handle);
    $canSeek = $metaData['seekable'];
    $fileMode = $metaData['mode'];
    $this->canRead = in_array($fileMode, $this->readableModes);
    $this->canWrite = in_array($fileMode, $this->writeableModes);
    $this->canTimeout = null;
    $this->canSeek = $canSeek;
  }


  /**
   * @inheritdoc
   * @return bool
   */
  public function canRead() {
    return $this->canRead;
  }


  /**
   * @inheritdoc
   * @return bool
   */
  public function canSeek() {
    return $this->canSeek;
  }


  /**
   * @inheritdoc
   * @return bool
   */
  public function canTimeout() {
    return $this->canTimeout;
  }


  /**
   * @inheritdoc
   * @return bool
   */
  public function canWrite() {
    return $this->canWrite;
  }


  /**
   * @inheritdoc
   * @return int|null
   */
  public function getLength() {
    $this->requireOpenFileHandle();
    $this->requireSeekableHandle();

    if (null !== $this->fileSize) {
      return $this->fileSize;
    }

    $stats = fstat($this->handle);
    if (isset($stats['size'])) {
      $this->fileSize = $stats['size'];
      return $this->fileSize;
    }

    return null;
  }


  /**
   * @inheritdoc
   * @return int
   */
  public function getPosition() {
    $this->requireOpenFileHandle();
    $this->requireSeekableHandle();
    return ftell($this->handle);
  }


  /**
   * @inheritdoc
   * @param int $position
   */
  public function setPosition($position) {
    if ($position < 0) {
      throw new Exception\InvalidArgumentException('position',
        $position,
        'Position must be greater than 0.');
    }
    $this->seek($position, self::SEEK_ORIGIN);
  }


  /**
   * @inheritdoc
   * @param StreamInterface $destination
   * @param int|null $bufferSize
   */
  public function copyTo(StreamInterface $destination, $bufferSize = null) {
    if (null === $destination) {
      throw new Exception\InvalidArgumentException('destination');
    }
    if (!$this->canRead() && !$this->canWrite()) {
      throw new Exception\ObjectDisposedException('this');
    }
    if (!$destination->canRead() && !$destination->canWrite()) {
      throw new Exception\ObjectDisposedException('destination');
    }
    if (!$this->canRead()) {
      throw new Exception\NotSupportedException();
    }
    if (!$destination->canWrite()) {
      throw new Exception\NotSupportedException();
    }

    $this->internalCopyTo($destination, $bufferSize);
  }


  /**
   * Perform the copy between this stream and the destination.
   * @param StreamInterface $destination
   * @param int|null $bufferSize
   */
  protected function internalCopyTo(StreamInterface $destination, $bufferSize = null) {
    if (null !== $bufferSize && $bufferSize <= 0) {
      throw new Exception\InvalidArgumentException('bufferSize',
        $bufferSize,
        'Parameter "bufferSize" must be greater than 0.');
    }

    // Use native implementation if possible.
    if ($destination instanceof self) {
      // Ensure pending data is dealt with, as we are bypassing write()
      $destination->flush();
      stream_copy_to_stream($this->handle, $destination->handle);
      return;
    }

    // Copy in chunks
    $bufferSize = $bufferSize ?: $this->defaultCopyBufferSize;
    while (($data = $this->read($bufferSize)) !== null) {
      $destination->write($data);
    }
  }


  /**
   * @inheritdoc
   */
  public function flush() {
    if (!$this->handle) {
      return;
    }
    fflush($this->handle);
  }


  /**
   * @inheritdoc
   * @param int $length
   * @return string
   */
  public function read($length) {
    $length = (int)$length;
    if ($length < 0) {
      throw new Exception\InvalidArgumentException('Parameter "count" must be greater than 0.');
    }
    $this->requireOpenFileHandle();
    $this->requireReadableHandle();

    $buffer = fread($this->handle, $length);
    return $buffer;
  }


  /**
   * @inheritdoc
   * @param int $position
   * @param null $whence
   * @return int
   */
  public function seek($position, $whence = null) {
    $this->requireOpenFileHandle();
    $this->requireSeekableHandle();
    switch ($whence) {
      case self::SEEK_ORIGIN :
        $result = fseek($this->handle, $position, SEEK_SET);
        break;
      case self::SEEK_CURRENT :
        $result = fseek($this->handle, $position, SEEK_CUR);
        break;
      case self::SEEK_END :
        $result = fseek($this->handle, $position, SEEK_END);
        break;
      default:
        throw new Exception\InvalidArgumentException('whence',
          $whence, 'Invalid value provided to whence.');
    }
    if ($result === -1) {
      throw new Exception\RuntimeException('Unable to seek file.');
    }
    return $result;
  }


  /**
   * @inheritdoc
   * @param int $length
   */
  public function setLength($length) {
    $length = (int)$length;
    if ($length < 0) {
      throw new Exception\InvalidArgumentException('length',
        $length,
        'Parameter Length must be greater than 0.');
    }
    $this->requireOpenFileHandle();
    $this->requireSeekableHandle();
    $this->requireWritableHandle();
    $this->flush();
    $success = ftruncate($this->handle, $length);
    if (!$success) {
      throw new Exception\RuntimeException('Unable to set length.');
    }
  }


  /**
   * @inheritdoc
   * @param string $data
   * @param null $length
   * @return int
   */
  public function write($data, $length = null) {
    $length = (int)$length;
    if ($length < 0) {
      throw new Exception\InvalidArgumentException('length',
        $length,
        'Parameter "length" must be greater than or equal to 0.');
    }
    $this->requireOpenFileHandle();
    $this->requireWritableHandle();

    // The filesize cache is now invalidated.
    $this->fileSize = null;

    if ($length === 0) {
      $length = null;
    }
    if ($length === null) {
      $result = fwrite($this->handle, $data);
    } else {
      $result = fwrite($this->handle, $data, $length);
    }
    if ($result === false) {
      throw new Exception\RuntimeException('Error writing data.');
    }
    return $result;

  }


  /**
   * @inheritdoc
   * @param int $ms
   */
  public function setReadTimeout($ms) {
    throw new NotSupportedException("Timeouts are not yet implemented.");
  }


  /**
   * @inheritdoc
   */
  public function getReadTimeout() {
    throw new NotSupportedException("Timeouts are not yet implemented.");
  }


  /**
   * @inheritdoc
   * @param int $ms
   */
  public function setWriteTimeout($ms) {
    throw new NotSupportedException("Timeouts are not yet implemented.");
  }


  /**
   * @inheritdoc
   */
  public function getWriteTimeout() {
    throw new NotSupportedException("Timeouts are not yet implemented.");
  }


  /**
   * @inheritdoc
   */
  public function dispose() {
    if ($this->handle) {
      fclose($this->handle);
    }
    $this->canRead = false;
    $this->canWrite = false;
    $this->canSeek = false;
  }


  private function requireOpenFileHandle() {
    if (null == $this->handle) {
      throw new Exception\ObjectDisposedException('File is closed.');
    }
  }


  private function requireSeekableHandle() {
    if (!$this->canSeek()) {
      throw new Exception\NotSupportedException('Stream does not support seeking.');
    }
  }


  private function requireWritableHandle() {
    if (!$this->canWrite()) {
      throw new Exception\NotSupportedException('Stream does not support writing.');
    }
  }


  private function requireReadableHandle() {
    if (!$this->canRead()) {
      throw new Exception\NotSupportedException('Stream does not support reading.');
    }
  }
}
