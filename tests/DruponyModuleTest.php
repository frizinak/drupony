<?php


namespace Drupony\Tests;

use Drupony\Drupony;

class DruponyModuleTest extends \PHPUnit_Framework_TestCase {

  protected static $cacheDir;
  protected static $setup = false;

  public static function setUpBeforeClass() {
    if (self::$setup) return;
    self::$setup = true;
    static::$cacheDir = DRUPONY_TEST_DIR . DIRECTORY_SEPARATOR . 'cacheDir';
    variable_set('drupony_cachedir', static::$cacheDir);
    require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'drupony.module';
  }

  public function cacheDirProvider() {
    self::setUpBeforeClass();
    return array(
      array(static::$cacheDir, ''),
      array(static::$cacheDir . DIRECTORY_SEPARATOR . 'container', 'container'),
      array(static::$cacheDir . DIRECTORY_SEPARATOR . 'container' . DIRECTORY_SEPARATOR . 'deeper', 'container////deeper'),
    );
  }

  /**
   * @dataProvider cacheDirProvider
   */
  public function testDruponyCacheDir($expected, $arg) {
    $this->assertEquals($expected, drupony_get_cache_dir($arg));
  }

  public function testDruponyCacheClear() {
    $drupony = new Drupony(drupony_get_cache_dir('container'), Drupony::CACHE_FULL, __FUNCTION__);
    $this->assertFileNotExists($drupony->getCacheFilePath());

    $drupony->getContainer();
    $this->assertFileExists($drupony->getCacheFilePath());
    drupony_clear_cache('container');
    $this->assertFileNotExists($drupony->getCacheFilePath());
    $this->assertFileExists(static::$cacheDir);

    $drupony = new Drupony(drupony_get_cache_dir('container'), Drupony::CACHE_FULL, __FUNCTION__);

    $drupony->getContainer();
    $this->assertFileExists($drupony->getCacheFilePath());
    drupony_flush_caches();
    $this->assertFileNotExists($drupony->getCacheFilePath());
    $this->assertFileExists(static::$cacheDir);
  }

  public function testDruponyMainContainer() {
    $drupony = drupony_get_wrapper(true);
    $this->assertFileNotExists($drupony->getCacheFilePath());
    $container = $drupony->getContainer();
    $this->assertFileExists($drupony->getCacheFilePath());
    $class = $container->getParameter('symfony.component.filesystem.class');
    $filesystem = $container->get('symfony.component.filesystem');
    $this->assertInstanceOf($class, $filesystem);
  }

}
