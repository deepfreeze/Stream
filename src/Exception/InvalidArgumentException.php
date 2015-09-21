<?php

namespace DeepFreeze\IO\Stream\Exception;

use DeepFreezeSpi\IO\Stream\Exception\InvalidArgumentException as SpiInvalidArgumentException;

class InvalidArgumentException extends RuntimeException implements ExceptionInterface,
  SpiInvalidArgumentException
{
  private $argumentName;
  private $argumentValue;

  public function __construct($name, $value=null, $message=null, $code=0, \Exception $prevException=null) {
    $this->argumentName = $name;
    $this->argumentValue = $value;
    $message = $message ?: sprintf('The argument for "%s" is invalid.', $name);
    parent::__construct($message, $code, $prevException);
  }


  /**
   * @return string
   */
  public function getArgumentName() {
    return $this->argumentName;
  }


  /**
   * @return mixed
   */
  public function getArgumentValue() {
    return $this->argumentValue;
  }
}
