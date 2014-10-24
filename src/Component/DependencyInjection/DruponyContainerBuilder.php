<?php


namespace Drupony\Component\DependencyInjection;

use Drupony\Component\DependencyInjection\ParameterBag\DrupalPersistentVariableBag;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DruponyContainerBuilder extends ContainerBuilder {

  /**
   * @var DrupalPersistentVariableBag
   */
  protected $variablesBag;

  protected $druponyInitialized = FALSE;

  public function __construct(ParameterBagInterface $parameterBag = NULL) {
    parent::__construct($parameterBag);
    $this->druponyInitialize();
  }

  public function druponyInitialize() {
    if (!$this->druponyInitialized) {
      $this->variablesBag = new DrupalPersistentVariableBag();
      $this->druponyInitialized = TRUE;
    }
    return $this;
  }

  /**
   * @return DrupalPersistentVariableBag
   */
  public function getVariablesBag() {
    return $this->variablesBag;
  }

  /**
   * Checks if a drupal variable exists.
   * note: Unlike symfony's default parameterBag,
   *       DrupalVariableBag also returns false if the key exists but the value is_null.
   *
   * @param string $name The variable name
   * @return bool
   */
  public function hasVariable($name) {
    return $this->variablesBag->has($name);
  }

  /**
   * Gets a drupal variable.
   *
   * @param string $name The variable name
   *
   * @return mixed  The variable value
   */
  public function getVariable($name) {
    return $this->variablesBag->get($name);
  }

  /**
   * Sets a drupal variable.
   *
   * @param string $name The variable name
   * @param mixed  $name The variable value
   */
  public function setVariable($name, $value) {
    $this->variablesBag->set($name, $value);
  }

}
