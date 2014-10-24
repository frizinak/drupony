<?php


namespace Drupony;

use Drupony\Component\DependencyInjection\DruponyContainerBuilder;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
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
      return $this->container->druponyInitialize();
    }

    if (!$this->cacheMethod) {
      $this->container = $this->createContainer();
      return $this->container->druponyInitialize();
    }

    $fileSystem = new Filesystem();
    $fileSystem->exists($this->cacheDir) || $fileSystem->mkdir($this->cacheDir);
    $cacheFilepath = $this->getCacheFilePath();
    $cache = new ConfigCache($cacheFilepath, !($this->cacheMethod & self::CACHE_FULL));
    if (!$cache->isFresh()) {
      $this->container = $this->createContainer();
      $this->cacheContainer($this->container, $cache, $this->containerClass);
      return $this->container;
    };

    require_once $cache;
    $this->container = new $this->containerClass();
    return $this->container->druponyInitialize();
  }

  public function getCacheFilePath() {
    return $this->cacheDir . DIRECTORY_SEPARATOR . $this->containerClass . '.php';
  }

  /**
   * Creates the container from services.yml and parameters.yml inside enabled module dirs.
   *
   * @return DruponyContainerBuilder
   */
  protected function createContainer() {
    $container = new DruponyContainerBuilder();
    $yamlLoader = new YamlFileLoader($container, new FileLocator(DRUPAL_ROOT));
    foreach (module_list() as $module) {
      if (!($path = drupal_get_path('module', $module))) continue;
      foreach (array('services.yml', 'parameters.yml') as $filename) {
        try {
          $yamlLoader->load($path . DIRECTORY_SEPARATOR . $filename);
        } catch (\InvalidArgumentException $e) {
        }
      }
    }

    $container->compile();
    return $container;
  }

  protected function cacheContainer(ContainerBuilder $container, ConfigCache $cache, $class) {
    $dumper = new PhpDumper($container);
    $content = $dumper->dump(array('class' => $class, 'base_class' => 'Drupony\\Component\\DependencyInjection\\DruponyContainerBuilder'));
    $cache->write($content, $container->getResources());
  }
}
