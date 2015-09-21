<?php
namespace DeepFreeze\IO\Stream;


class WarningToException {
  public static function fopen($path, $mode) {
    $ex = null;
    set_error_handler(function () use (&$ex, $path, $mode) {
      $ex = new Exception\RuntimeException(sprintf(
        'Error opening file "%s" with filemode "%s".',
        $path,
        $mode
      ));
    });
    $handle = fopen($path, $mode);
    restore_error_handler();
    if ($ex instanceof \Exception) {
      throw $ex;
    }
    return $handle;
  }
}
