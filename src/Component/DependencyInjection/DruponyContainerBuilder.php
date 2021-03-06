<?php


namespace Drupony\Component\DependencyInjection;

use Drupony\Component\DependencyInjection\ParameterBag\DrupalPersistentVariableBag;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DruponyContainerBuilder extends ContainerBuilder {

  /**
   * @var DrupalPersistentVariableBag
   */
  protected $variableBag;

  protected $druponyInitialized = FALSE;

  public function __construct(ParameterBagInterface $parameterBag = NULL) {
    parent::__construct($parameterBag);
    $this->druponyInitialize();
  }

  public function druponyInitialize() {
    if (!$this->druponyInitialized) {
      $this->variableBag = new DrupalPersistentVariableBag();
      $this->druponyInitialized = TRUE;
    }
    return $this;
  }

  /**
   * @return DrupalPersistentVariableBag
   */
  public function getVariableBag() {
    return $this->variableBag;
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
    return $this->variableBag->has($name);
  }

  /**
   * Gets a drupal variable.
   *
   * @param string $name The variable name
   *
   * @return mixed  The variable value
   */
  public function getVariable($name) {
    return $this->variableBag->get($name);
  }

  /**
   * Sets a drupal variable.
   *
   * @param string $name The variable name
   * @param mixed $name The variable value
   */
  public function setVariable($name, $value) {
    $this->variableBag->set($name, $value);
  }

  public function delVariable($name) {
    $this->variableBag->remove($name);
  }

}
