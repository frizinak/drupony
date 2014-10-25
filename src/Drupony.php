<?php


namespace Drupony;

use Drupony\Component\DependencyInjection\Compiler\ResolveVariablePlaceHolderPass;
use Drupony\Component\DependencyInjection\DruponyContainerBuilder;
use Drupony\Component\DependencyInjection\Loader\DrupalModuleHookLoader;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Filesystem\Filesystem;

class Drupony {

  const CACHE_NONE = 0;
  const CACHE_CHANGE = 1;
  const CACHE_FULL = 2;

  /**
   * @var DruponyContainerBuilder
   */
  protected $container;
  protected $cacheDir;
  protected $cacheMethod;

  protected $containerClass = 'DruponyContainer';

  public function __construct($cacheDir, $cache = self::CACHE_FULL, $name = 'default') {
    $this->cacheMethod = (int) $cache;
    $this->cacheDir = $cacheDir;
    $this->containerClass .= ucfirst(preg_replace('/[^a-z0-9_]/i', '_', $name));
  }

  /**
   * @return DruponyContainerBuilder
   */
  public function getContainer() {
    if (isset($this->container)) {
      return $this->container;
    }

    if (!$this->cacheMethod) {
      $this->container = $this->createContainer();
      return $this->container;
    }

    $fileSystem = new Filesystem();
    $fileSystem->exists($this->cacheDir) || $fileSystem->mkdir($this->cacheDir);
    $cacheFilepath = $this->getCacheFilePath();
    $cache = new ConfigCache($cacheFilepath, !($this->cacheMethod & self::CACHE_FULL));
    if (!$cache->isFresh()) {
      $this->cacheContainer($this->createContainer(), $cache, $this->containerClass);
    };

    require_once $cache;
    $this->container = $this->prepareContainer(new $this->containerClass());
    return $this->container;
  }

  public function getCacheFilePath() {
    return $this->cacheDir . DIRECTORY_SEPARATOR . $this->containerClass . '.php';
  }

  protected function prepareContainer(DruponyContainerBuilder $container) {
    $container->druponyInitialize();
    $container->set('drupony', $this);
    return $container;
  }

  /**
   * Creates the container from services.yml, parameters.yml (inside enabled module dirs),
   * hook_drupony_parameters and hook_drupony_services.
   *
   * @return DruponyContainerBuilder
   */
  protected function createContainer() {
    $container = new DruponyContainerBuilder();
    $locator = new FileLocator(DRUPAL_ROOT);
    $yamlLoader = new YamlFileLoader($container, $locator);
    $moduleLoader = new DrupalModuleHookLoader($container, $locator);

    foreach (module_list() as $module) {
      if (!($path = drupal_get_path('module', $module))) continue;

      foreach (array('parameters', 'services') as $type) {
        $yml = $path . DIRECTORY_SEPARATOR . $type . '.yml';
        $hook = 'drupony_' . $type;

        if (file_exists(DRUPAL_ROOT . DIRECTORY_SEPARATOR . $yml)) {
          $yamlLoader->load($yml);
        }

        if (module_hook($module, 'drupony_' . $type)) {
          // File should now be included.
          // Drupal hook implementation will probably never change in 7
          // but still worth noting that this is dirty.
          $functionReflector = new \ReflectionFunction($module . '_' . $hook);
          $definition = module_invoke($module, $hook);
          $moduleLoader->load($functionReflector->getFileName(), NULL, array($type => $definition));
        }
      }
    }

    $container->has('drupony') || $this->prepareContainer($container);
    $container->addCompilerPass(new ResolveVariablePlaceHolderPass(), PassConfig::TYPE_OPTIMIZE);
    $container->compile();
    return $container;
  }

  protected function cacheContainer(ContainerBuilder $container, ConfigCache $cache, $class) {
    $dumper = new PhpDumper($container);
    $content = $dumper->dump(array('class' => $class, 'base_class' => 'Drupony\\Component\\DependencyInjection\\DruponyContainerBuilder'));
    $cache->write($content, $container->getResources());
  }
}
