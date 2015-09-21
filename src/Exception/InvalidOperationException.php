<?php

namespace DeepFreeze\IO\Stream\Exception;

use DeepFreezeSpi\IO\Stream\Exception\InvalidOperationException as SpiInvalidOperationException;

class InvalidOperationException extends RuntimeException implements ExceptionInterface,
  SpiInvalidOperationException
{

}
