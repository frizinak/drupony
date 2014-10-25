<?php


namespace Drupony\Component\DependencyInjection\Compiler;

use Drupony\Component\DependencyInjection\DruponyContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Resolves variable placeholders "&someVariable&" to container::getVariable(someVariable) calls.
 * Only those used as arguments and class properties.
 */
class ResolveVariablePlaceHolderPass implements CompilerPassInterface {

  public function process(ContainerBuilder $container) {
    if (!($container instanceof DruponyContainerBuilder)) {
      return;
    }
    /** @var $container DruponyContainerBuilder */
    $variableBag = $container->getVariableBag();
    foreach ($container->getDefinitions() as $id => $definition) {
      $definition->setArguments($variableBag->resolveValue($definition->getArguments()));
      $calls = array();
      foreach ($definition->getMethodCalls() as $name => $arguments) {
        $calls[$name] = $variableBag->resolveValue($arguments);
      }
      $definition->setMethodCalls($calls);

      $properties = array();
      foreach ($definition->getProperties() as $prop => $value) {
        $properties[$prop] = $variableBag->resolveValue($value);
      }
      $definition->setProperties($properties);
    }
  }
}
