<?php


namespace Drupony\Component\DependencyInjection\ParameterBag;

use Drupony\Component\DependencyInjection\Exception\VariableTruncateException;

class DrupalPersistentVariableBag extends DrupalVariableBag {

  /**
   * @inheritdoc
   */
  public function set($name, $value) {
    variable_set($name, $value);
  }

  /**
   * @inheritdoc
   */
  public function remove($name) {
    variable_del($name);
  }

  public function clear() {
    throw new VariableTruncateException();
  }

}
