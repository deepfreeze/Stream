<?php
namespace DeepFreeze\IO\Stream;

use DeepFreezeSpi\IO\Stream\StreamInterface;

/**
 * Class LazyLoadStream
 * This class overrides every function of StreamDecorator, so it doesn't inherit.
 * @package DeepFreeze\IO\Stream
 */
class LazyLoadStream implements StreamInterface
{
  /**
   * @var StreamInterface
   */
  protected $stream;


  /**
   * @var callable
   */
  private $callback;

  /**
   * @var array
   */
  private $callbackParams = array();
  public function __construct($callback, $callbackParams = array()) {
    $this->setCallback($callback, $callbackParams);
  }


  /**
   * Return the created stream.
   *
   * As this is a pure decorator class, exposing the stream does not impart any risk of
   * incoherent state.
   *
   * @return StreamInterface
   */
  public function getStream() {
    $this->initializeStream();
    return $this->stream;
  }


  /**
   * @return callable
   */
  public function getCallback() {
    return $this->callback;
  }



  /**
   * @param callable $callback
   */
  public function setCallback($callback, $callbackParams = null) {
    // Validate the callback on use.  The callback may not be valid at that time.
    $this->callback = $callback;
    if (null === $callbackParams) {
      // Don't modify
      return;
    }
    if (!is_array($callbackParams)) {
      // Assume One Parameter callback.
      $callbackParams = array($callbackParams);
    }
    $this->callbackParams = $callbackParams;
  }


  private function initializeStream() {
    // If stream is already initialized
    if (null !== $this->stream) {
      return;
    }
    if (!is_callable($this->callback)) {
      throw new Exception\RuntimeException('The provided callback is not valid.');
    }
    $stream = call_user_func_array($this->callback, $this->callbackParams);
    if (!$stream instanceof StreamInterface) {
      throw new Exception\RuntimeException('The callback did not return a valid StreamInterface instance.');
    }
    $this->stream = $stream;
  }

  /**
   * @return bool
   */
  public function canRead() {
    $this->initializeStream();
    return $this->stream->canRead();
  }


  /**
   * @return bool
   */
  public function canSeek() {
    $this->initializeStream();
    return $this->stream->canSeek();
  }


  /**
   * @return bool
   */
  public function canTimeout() {
    $this->initializeStream();
    return $this->stream->canTimeout();
  }


  /**
   * @return bool
   */
  public function canWrite() {
    $this->initializeStream();
    return $this->stream->canTimeout();
  }


  /**
   * @return int
   */
  public function getLength() {
    $this->initializeStream();
    return $this->stream->getLength();
  }


  /**
   * @return int
   */
  public function getPosition() {
    $this->initializeStream();
    return $this->stream->getPosition();
  }


  /**
   * @param int $position
   */
  public function setPosition($position) {
    $this->initializeStream();
    $this->stream->setPosition($position);
  }


  /**
   * @param int $ms
   */
  public function setReadTimeout($ms) {
    $this->initializeStream();
    $this->stream->setReadTimeout($ms);
  }


  /**
   * @return int
   */
  public function getReadTimeout() {
    $this->initializeStream();
    return $this->stream->getReadTimeout();
  }


  /**
   * @param int $ms
   */
  public function setWriteTimeout($ms) {
    $this->initializeStream();
    $this->stream->setWriteTimeout($ms);
  }


  /**
   * @return int
   */
  public function getWriteTimeout() {
    $this->initializeStream();
    return $this->stream->getWriteTimeout();
  }


  public function copyTo(StreamInterface $destination, $bufferSize = null) {
    $this->initializeStream();
    $this->stream->copyTo($destination, $bufferSize);
  }


  public function dispose() {
    $this->initializeStream();
    $this->stream->dispose();
  }


  public function flush() {
    $this->initializeStream();
    $this->stream->flush();
  }


  /**
   * @param int $length
   * @return string
   */
  public function read($length = 0) {
    $this->initializeStream();
    return $this->stream->read($length);
  }


  public function seek($position, $whence = null) {
    $this->initializeStream();
    return $this->stream->seek($position, $whence);
  }


  public function setLength($length) {
    $this->initializeStream();
    $this->stream->setLength($length);
  }


  public function write($data, $length = null) {
    $this->initializeStream();
    $this->stream->write($data, $length);
  }
}
