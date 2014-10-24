<?php


namespace Drupony\Component\DependencyInjection\Exception;

class VariableTruncateException extends \RuntimeException {

  public function __construct() {
    parent::__construct('This action would logically lead to truncation of the drupal variables table.');
  }
}
