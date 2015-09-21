<?php

namespace DeepFreeze\IO\Stream\Exception;

use DeepFreezeSpi\IO\Stream\Exception\NotSupportedException as SpiNotSupportedException;

class NotSupportedException extends RuntimeException implements ExceptionInterface,
  SpiNotSupportedException
{

}
