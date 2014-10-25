<?php


namespace Drupony\Component\DependencyInjection\ParameterBag;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class DrupalVariableBag extends ParameterBag {

  /**
   * @inheritdoc
   */
  public function __construct(array $variables = array()) {
    $this->parameters =& $GLOBALS['conf'];
    $this->add($variables);
  }

  /**
   * @inheritdoc
   */
  public function add(array $variables) {
    foreach ($variables as $key => $value) {
      $this->set($key, $value);
    }
  }

  /**
   * @inheritdoc
   */
  public function get($name, $default = NULL) {
    return variable_get($name, $default);
  }

  /**
   * @inheritdoc
   */
  public function set($name, $value) {
    $this->parameters[$name] = $value;
  }

  /**
   * @inheritdoc
   */
  public function has($name) {
    return isset($this->parameters[$name]);
  }

  /**
   * @inheritdoc
   */
  public function remove($name) {
    unset($this->parameters[$name]);
  }

  /**
   * no-op
   */
  public function resolve() {
  }

  public function isResolved() {
    return TRUE;
  }

  public function resolveString($value, array $resolved = array()) {
    if (preg_match('/\&([^\&\s]+)\&$/', $value, $match)) {
      $definition = new Definition();
      $definition->setFactoryService('service_container');
      $definition->setFactoryMethod('getVariable');
      $definition->setArguments(array($match[1]));
      return $definition;
    }
    return $value;
  }
}
