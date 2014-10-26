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

class DrupalModuleHookLoader extends FileLoader {

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

  /**
   * Parses definitions
   *
   * @param array $content
   * @param string $file
   */
  private function parseDefinitions($content, $file) {
    if (!isset($content['services'])) {
      return;
    }

    if (!is_array($content['services'])) {
      throw new InvalidArgumentException(sprintf('The "services" key should contain an array in %s.', $file));
    }

    foreach ($content['services'] as $id => $service) {
      $this->parseDefinition($id, $service, $file);
    }
  }

  /**
   * Parses a definition.
   *
   * @param string $id
   * @param array $service
   * @param string $file
   *
   * @throws InvalidArgumentException When tags are invalid
   */
  private function parseDefinition($id, $service, $file) {
    if (is_string($service) && 0 === strpos($service, '@')) {
      $this->container->setAlias($id, substr($service, 1));

      return;
    }

    if (!is_array($service)) {
      throw new InvalidArgumentException(sprintf('A service definition must be an array or a string starting with "@" but %s found for service "%s" in %s.', gettype($service), $id, $file));
    }

    if (isset($service['alias'])) {
      $public = !array_key_exists('public', $service) || (bool) $service['public'];
      $this->container->setAlias($id, new Alias($service['alias'], $public));

      return;
    }

    if (isset($service['parent'])) {
      $definition = new DefinitionDecorator($service['parent']);
    }
    else {
      $definition = new Definition();
    }

    if (isset($service['class'])) {
      $definition->setClass($service['class']);
    }

    if (isset($service['scope'])) {
      $definition->setScope($service['scope']);
    }

    if (isset($service['synthetic'])) {
      $definition->setSynthetic($service['synthetic']);
    }

    if (isset($service['synchronized'])) {
      $definition->setSynchronized($service['synchronized']);
    }

    if (isset($service['lazy'])) {
      $definition->setLazy($service['lazy']);
    }

    if (isset($service['public'])) {
      $definition->setPublic($service['public']);
    }

    if (isset($service['abstract'])) {
      $definition->setAbstract($service['abstract']);
    }

    if (isset($service['factory_class'])) {
      $definition->setFactoryClass($service['factory_class']);
    }

    if (isset($service['factory_method'])) {
      $definition->setFactoryMethod($service['factory_method']);
    }

    if (isset($service['factory_service'])) {
      $definition->setFactoryService($service['factory_service']);
    }

    if (isset($service['file'])) {
      $definition->setFile($service['file']);
    }

    if (isset($service['arguments'])) {
      $definition->setArguments($this->resolveServices($service['arguments']));
    }

    if (isset($service['properties'])) {
      $definition->setProperties($this->resolveServices($service['properties']));
    }

    if (isset($service['configurator'])) {
      if (is_string($service['configurator'])) {
        $definition->setConfigurator($service['configurator']);
      }
      else {
        $definition->setConfigurator(array($this->resolveServices($service['configurator'][0]), $service['configurator'][1]));
      }
    }

    if (isset($service['calls'])) {
      if (!is_array($service['calls'])) {
        throw new InvalidArgumentException(sprintf('Parameter "calls" must be an array for service "%s" in %s. Check your YAML syntax.', $id, $file));
      }

      foreach ($service['calls'] as $call) {
        $args = isset($call[1]) ? $this->resolveServices($call[1]) : array();
        $definition->addMethodCall($call[0], $args);
      }
    }

    if (isset($service['tags'])) {
      if (!is_array($service['tags'])) {
        throw new InvalidArgumentException(sprintf('Parameter "tags" must be an array for service "%s" in %s. Check your YAML syntax.', $id, $file));
      }

      foreach ($service['tags'] as $tag) {
        if (!is_array($tag)) {
          throw new InvalidArgumentException(sprintf('A "tags" entry must be an array for service "%s" in %s. Check your YAML syntax.', $id, $file));
        }

        if (!isset($tag['name'])) {
          throw new InvalidArgumentException(sprintf('A "tags" entry is missing a "name" key for service "%s" in %s.', $id, $file));
        }

        $name = $tag['name'];
        unset($tag['name']);

        foreach ($tag as $attribute => $value) {
          if (!is_scalar($value) && NULL !== $value) {
            throw new InvalidArgumentException(sprintf('A "tags" attribute must be of a scalar-type for service "%s", tag "%s", attribute "%s" in %s. Check your YAML syntax.', $id, $name, $attribute, $file));
          }
        }

        $definition->addTag($name, $tag);
      }
    }

    if (isset($service['decorates'])) {
      $renameId = isset($service['decoration_inner_name']) ? $service['decoration_inner_name'] : NULL;
      $definition->setDecoratedService($service['decorates'], $renameId);
    }

    $this->container->setDefinition($id, $definition);
  }

  /**
   * Validates the array contents.
   *
   * @param mixed $content
   * @param string $file
   *
   * @return array
   *
   * @throws InvalidArgumentException When service file is not valid
   */
  private function validate($content, $file) {
    if (NULL === $content) {
      return $content;
    }

    if (!is_array($content)) {
      throw new InvalidArgumentException(sprintf('The service file "%s" is not valid. It should contain an array.', $file));
    }

    foreach (array_keys($content) as $namespace) {
      if (in_array($namespace, array('imports', 'parameters', 'services'))) {
        continue;
      }

      if (!$this->container->hasExtension($namespace)) {
        $extensionNamespaces = array_filter(array_map(function ($ext) {
          return $ext->getAlias();
        }, $this->container->getExtensions()));
        throw new InvalidArgumentException(sprintf(
                                             'There is no extension able to load the configuration for "%s" (in %s). Looked for namespace "%s", found %s',
                                             $namespace,
                                             $file,
                                             $namespace,
                                             $extensionNamespaces ? sprintf('"%s"', implode('", "', $extensionNamespaces)) : 'none'
                                           ));
      }
    }

    return $content;
  }

  /**
   * Resolves services.
   *
   * @param string|array $value
   *
   * @return array|string|Reference
   */
  private function resolveServices($value) {
    if (is_array($value)) {
      $value = array_map(array($this, 'resolveServices'), $value);
    }
    elseif (is_string($value) && 0 === strpos($value, '@=')) {
      return new Expression(substr($value, 2));
    }
    elseif (is_string($value) && 0 === strpos($value, '@')) {
      if (0 === strpos($value, '@@')) {
        $value = substr($value, 1);
        $invalidBehavior = NULL;
      }
      elseif (0 === strpos($value, '@?')) {
        $value = substr($value, 2);
        $invalidBehavior = ContainerInterface::IGNORE_ON_INVALID_REFERENCE;
      }
      else {
        $value = substr($value, 1);
        $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE;
      }

      if ('=' === substr($value, -1)) {
        $value = substr($value, 0, -1);
        $strict = FALSE;
      }
      else {
        $strict = TRUE;
      }

      if (NULL !== $invalidBehavior) {
        $value = new Reference($value, $invalidBehavior, $strict);
      }
    }

    return $value;
  }

  /**
   * Loads from Extensions
   *
   * @param array $content
   */
  private function loadFromExtensions($content) {
    foreach ($content as $namespace => $values) {
      if (in_array($namespace, array('imports', 'parameters', 'services'))) {
        continue;
      }

      if (!is_array($values)) {
        $values = array();
      }

      $this->container->loadFromExtension($namespace, $values);
    }
  }
}
