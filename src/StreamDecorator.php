<?php
namespace DeepFreeze\IO\Stream;

use DeepFreezeSpi\IO\Stream\StreamInterface;

class StreamDecorator implements StreamInterface
{
  /**
   * @var StreamInterface
   */
  protected $stream;


  /**
   * @return bool
   */
  public function canRead() {
    return $this->stream->canRead();
  }


  /**
   * @return bool
   */
  public function canSeek() {
    return $this->stream->canSeek();
  }


  /**
   * @return bool
   */
  public function canTimeout() {
    return $this->stream->canTimeout();
  }


  /**
   * @return bool
   */
  public function canWrite() {
    return $this->stream->canTimeout();
  }


  /**
   * @return int
   */
  public function getLength() {
    return $this->stream->getLength();
  }


  /**
   * @return int
   */
  public function getPosition() {
    return $this->stream->getPosition();
  }


  /**
   * @param int $position
   */
  public function setPosition($position) {
    $this->stream->setPosition($position);
  }


  /**
   * @param int $ms
   */
  public function setReadTimeout($ms) {
    $this->stream->setReadTimeout($ms);
  }


  /**
   * @return int
   */
  public function getReadTimeout() {
    return $this->stream->getReadTimeout();
  }


  /**
   * @param int $ms
   */
  public function setWriteTimeout($ms) {
    $this->stream->setWriteTimeout($ms);
  }


  /**
   * @return int
   */
  public function getWriteTimeout() {
    return $this->stream->getWriteTimeout();
  }


  public function copyTo(StreamInterface $destination, $bufferSize = null) {
    $this->stream->copyTo($destination, $bufferSize);
  }


  public function dispose() {
    $this->stream->dispose();
  }


  public function flush() {
    $this->stream->flush();
  }


  /**
   * @param int $length
   * @return string
   */
  public function read($length = 0) {
    return $this->stream->read($length);
  }


  public function seek($position, $whence = null) {
    return $this->stream->seek($position, $whence);
  }


  public function setLength($length) {
    $this->stream->setLength($length);
  }


  public function write($data, $length = null) {
    $this->stream->write($data, $length);
  }

}
