<?php

namespace DeepFreeze\IO\Stream\Exception;

use DeepFreezeSpi\IO\Stream\Exception\ObjectDisposedException as SpiObjectDisposedException;

class ObjectDisposedException extends RuntimeException implements ExceptionInterface,
  SpiObjectDisposedException
{
  private $name;


  public function __construct($name, $message = null, $code = 0, \Exception $previous = null) {
    $this->name = $name;
    $message = $message ?: sprintf('Object %s has been disposed.', $name);
    parent::__construct($message, $code, $previous);
  }


  /**
   * @return string
   */
  public function getName() {
    return $this->name;
  }
}
