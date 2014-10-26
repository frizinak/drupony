<?php


namespace Drupony\Component\DependencyInjection\Loader;

use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class YamlArrayLoader extends YamlFileLoader {

  protected $yamlArray;

  public function load($file, $type = NULL, array $yamlArray = array()) {
    $this->yamlArray = $yamlArray;
    parent::load($file, $type);
  }

  protected function loadFile($file) {
    return $this->validateYamlArray($this->yamlArray);
  }

  protected function validateYamlArray($yamlArray) {
    foreach (array_keys($yamlArray) as $namespace) {
      if (in_array($namespace, array('parameters', 'services'))) {
        continue;
      }

      if (!$this->container->hasExtension($namespace)) {
        throw new \InvalidArgumentException(
          sprintf('There is no extension able to load the configuration for "%s"', $namespace)
        );
      }
    }
    return $yamlArray;
  }

  public function supports($resource, $type = NULL) {
    $drupalHookFiles = array('inc' => TRUE, 'module' => TRUE);
    return is_string($resource) && isset($drupalHookFiles[pathinfo($resource, PATHINFO_EXTENSION)]);
  }
}
