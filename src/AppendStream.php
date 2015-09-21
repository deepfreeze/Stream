<?php
namespace DeepFreeze\IO\Stream;


use DeepFreeze\IO\Stream\Exception\RuntimeException;
use DeepFreezeSpi\IO\Stream\StreamInterface;

class AppendStream extends StreamDecorator
{
  /**
   * @var int
   */
  private $currentStreamIndex = 0;
  /**
   * @var bool
   */
  private $canSeek = true;
  /**
   * @var bool
   */
  private $canRead = true;
  /**
   * @var StreamInterface[]
   */
  private $streams = array();


  public function __construct(array $streams) {
    $this->setStreams($streams);
  }


  private function setStreams(array $streams) {
    $this->streams = array();
    foreach ($streams as $stream) {
      $this->appendStream($stream);
    }
  }


  public function appendStream(StreamInterface $stream) {
    if (!$stream->canRead()) {
      throw new Exception\NotSupportedException('Stream does not support read.');
    }
    if (!$stream->canSeek()) {
      $this->canSeek = false;
    }
    $this->streams[] = $stream;
  }


  /**
   * @return bool
   */
  public function canRead() {
    return $this->canRead;
  }


  /**
   * @return bool
   */
  public function canSeek() {
    return true;
  }


  /**
   * @return bool
   */
  public function canWrite() {
    return false;
  }


  /**
   * @return int|null
   */
  public function getLength() {
    $length = 0;
    foreach ($this->streams as $stream) {
      $streamLength = $stream->getLength();
      if (null === $streamLength) {
        return null;
      }
      $length += $streamLength;
    }
    return $length;
  }


  /**
   * Get Current Pointer Position
   *
   * As we cannot guarantee any of the underlying streams did not have write operations or
   * truncations applied to them, we cannot cache this position.
   */
  public function getPosition() {
    if (empty($this->streams)) {
      return 0;
    }
    $position = 0;

    foreach ($this->streams as $index => $stream) {
      if ($index == $this->currentStreamIndex) {
        break;
      }
      $position += $stream->getLength();
    }
    $position += $this->streams[$this->currentStreamIndex]->getPosition();
    return $position;
  }


  /**
   *
   */
  public function dispose() {
    foreach ($this->streams as $stream) {
      $stream->dispose();
    }
    $this->canRead = false;
    $this->canSeek = false;
  }


  /**
   *
   * @param int $count
   * @return null|string
   */
  public function read($count = 0) {
    $count = (int)$count;
    if ($count <= 0 ) {
      throw new Exception\InvalidArgumentException('Read length must be greater than "0".');
    }
    if (empty($this->streams)) {
      return null;
    }
    $buffer = '';
    $bytesRemaining = $count;
    $currentStreamIndex = $this->currentStreamIndex;
    $streamCount = count($this->streams);
    do {
      $read = $this->streams[$currentStreamIndex]->read($bytesRemaining);
      if (empty($read)) {
        ++$currentStreamIndex;
        $this->currentStreamIndex = $currentStreamIndex;
        // Out of files to read
        if ($currentStreamIndex >= $streamCount) {
          return $buffer;
        }
      }
      $buffer .= $read;
      $bytesRemaining = $count - strlen($buffer);
    } while ($bytesRemaining > 0);
    return $buffer;
  }


  /**
   * @param int $position
   * @param string $whence
   * @return int
   */
  public function seek($position, $whence = null) {
    if (!$this->canSeek()) {
      throw new Exception\InvalidOperationException('This stream does not support seek.');
    }
    switch ($whence) {
      case static::SEEK_CURRENT:
        return $this->seekFromCurrent($position);

      case static::SEEK_END:
        return $this->seekFromEnd($position);

      case static::SEEK_ORIGIN:
        return $this->seekFromOrigin($position);

      default:
        throw new Exception\InvalidArgumentException('whence');
    }
  }


  /**
   * Seek from the origin of the stream
   * @param int $position
   * @return int
   */
  private function seekFromOrigin($position) {
    // Short Circuit no streams to process.
    if ($position < 0) {
      throw new Exception\InvalidArgumentException('Cannot seek before beginning of the file.');
    }
    if (empty($this->streams)) {
      return 0;
    }

    $currentPosition = 0;
    $fileStart = $fileEnd = 0;
    foreach ($this->streams as $index => $stream) {
      $fileStart = $currentPosition;
      $fileEnd = $currentPosition + $stream->getLength();
      // Position is beyond the end of this file.
      if ($position > $fileEnd) {
        $currentPosition = $fileEnd;
        continue;
      }

      // Position is within this file.
      $seekOffset = $position - $fileStart;
      $stream->seek($seekOffset, static::SEEK_ORIGIN);
      $this->currentStreamIndex = $index;
      return $position;
    }

    // Position is beyond the last file:
    $seekOffset = $position - $fileStart;
    $streamCount = count($this->streams);
    $this->currentStreamIndex = $streamCount - 1;
    $this->streams[$this->currentStreamIndex]->seek($seekOffset, SEEK_SET);
    return $position;
  }


  /**
   * Seek from the current position.
   * @param $targetPosition
   * @return int
   */
  private function seekFromCurrent($targetPosition) {
    if (empty($this->streams)) {
      if ($targetPosition < 0) {
        throw new Exception\InvalidArgumentException('Cannot seek before beginning of stream.');
      }
      return $targetPosition;
    }

    if ($targetPosition === 0) {
      return $this->getPosition();
    }

    if ($targetPosition > 0) {
      $index = $this->currentStreamIndex;
      $maxIndex = count($this->streams) - 1;
      $currentStreamPosition = $this->streams[$index]->getPosition();
      $currentPosition = -$currentStreamPosition;
      $fileStart = $currentPosition;
      for ($index = $this->currentStreamIndex; $index <= $maxIndex; $index++) {
        $fileStart = $currentPosition;
        $fileEnd = $currentPosition + $this->streams[$index]->getLength();
        // Position is beyond the end of this file.
        if ($targetPosition > $fileEnd) {
          $currentPosition = $fileEnd;
          continue;
        }

        // Position is within this file.
        $seekOffset = $targetPosition - $fileStart;
        $this->streams[$index]->seek($seekOffset, static::SEEK_ORIGIN);
        $this->currentStreamIndex = $index;
        return $this->getPosition();
      }

      // Position is beyond the last file:
      $seekOffset = $targetPosition - $fileStart;
      $streamCount = count($this->streams);
      $this->currentStreamIndex = $streamCount - 1;
      $this->streams[$this->currentStreamIndex]->seek($seekOffset, SEEK_SET);
      return $this->getPosition();
    }

    if ($targetPosition < 0) {
      $fileStart = -$this->streams[$this->currentStreamIndex]->getPosition();
      $fileEnd = $fileStart + $this->streams[$this->currentStreamIndex]->getLength();
      $currentPosition = $fileEnd;
      /**
       * @var int $index
       * @var StreamInterface $stream
       */
      for ($index = $this->currentStreamIndex; $index >= 0; --$index) {
        $stream = $this->streams[$index];
        $fileEnd = $currentPosition;
        $fileStart = $currentPosition - $stream->getLength();
        // Position is beyond the end of this file.
        if ($targetPosition < $fileStart) {
          $currentPosition = $fileStart;
          continue;
        }

        // Position is within this file.
        $seekOffset = $targetPosition - $fileEnd;
        $stream->seek($seekOffset, static::SEEK_END);
        $this->currentStreamIndex = $index;
        return $this->getPosition();
      }

      // Position is before the first file:
      throw new Exception\RuntimeException('Unable to seek before start position.');
    }

    // Unreachable code, supposedly.
    throw new RuntimeException('Error??');
  }


  /**
   * Seek relative from the End of the Stream
   * @param int $position
   * @return int
   */
  private function seekFromEnd($position) {
    if (empty($this->streams)) {
      if ($position < 0) {
        return 0;
      }
    }
    // If it's at or beyond the end of the last file:
    if ($position >= 0) {
      $this->currentStreamIndex = count($this->streams) - 1;
      $this->streams[$this->currentStreamIndex]->seek($position, SEEK_END);
      return $this->getPosition();
    }

    // Otherwise, complicated!
    $currentPosition = 0;
    /**
     * @var int $index
     * @var StreamInterface $stream
     */
    foreach (array_reverse($this->streams, true) as $index => $stream) {
      $fileEnd = $currentPosition;
      $fileStart = $currentPosition - $stream->getLength();
      // Position is beyond the end of this file.
      if ($position < $fileStart) {
        $currentPosition = $fileStart;
        continue;
      }

      // Position is within this file.
      $seekOffset = $position - $fileEnd;
      $stream->seek($seekOffset, static::SEEK_END);
      $this->currentStreamIndex = $index;
      return $this->getPosition();
    }

    // Position is before the first file:
    throw new Exception\RuntimeException('Unable to seek before start position.');
  }
}
