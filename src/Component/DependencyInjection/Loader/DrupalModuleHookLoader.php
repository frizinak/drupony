<?php

namespace Drupony\Component\DependencyInjection\Loader;

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Loader\FileLoader;

use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\ExpressionLanguage\Expression;

class DrupalModuleHookLoader extends YamlFileLoader {

  protected $yamlError = '';

  public function load($file, $type = NULL, array $content = array()) {
    $path = $this->locator->locate($file);
    $this->container->addResource(new FileResource($path));
    $content = $this->validate($content, $file);
    if (empty($content)) {
      return;
    }

    if (isset($content['parameters'])) {
      if (!is_array($content['parameters'])) {
        throw new InvalidArgumentException(sprintf('The "parameters" key should contain an array in %s.', $file));
      }
      foreach ($content['parameters'] as $key => $value) {
        $this->container->setParameter($key, $this->resolveServices($value));
      }
    }

    $this->loadFromExtensions($content);
    $this->parseDefinitions($content, $file);
  }

  /**
   * Returns true if this class supports the given resource.
   *
   * @param mixed $resource A resource
   * @param string $type The resource type
   *
   * @return bool    true if this class supports the given resource, false otherwise
   */
  public function supports($resource, $type = NULL) {
    $drupalHookFiles = array('inc' => TRUE, 'module' => TRUE);
    return is_string($resource) && isset($drupalHookFiles[pathinfo($resource, PATHINFO_EXTENSION)]);
  }

}
