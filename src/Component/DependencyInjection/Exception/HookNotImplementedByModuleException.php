<?php


namespace Drupony\Component\DependencyInjection\Exception;

class HookNotImplementedByModuleException extends \Exception {

  public function __construct($module, $hook) {
    parent::__construct(sprintf('Module %s does not implement hook_%s', $module, $hook));
  }
}
