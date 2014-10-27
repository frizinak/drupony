<?php


namespace Drupony\Tests;

use Drupony\Drupony;

class DruponyModuleTest extends \PHPUnit_Framework_TestCase {

  protected static $cacheDir;

  public static function setUpBeforeClass() {
    static::$cacheDir = DRUPONY_TEST_DIR . DIRECTORY_SEPARATOR . 'cacheDir';
    variable_set('drupony_cachedir', static::$cacheDir);
    require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'drupony.module';
  }

  public function testDruponyCacheDir() {
    foreach (drupony_get_caches() as $cache) {
      drupony_get_cache_dir($cache);
    }

    try {
      drupony_get_cache_dir('doesntexist');
    } catch (\InvalidArgumentException $e) {
      return;
    }
    $this->fail('An exception should\'ve been thrown');
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

  public function testAutoloadFinder() {
    $dir = _drupony_find_composer_autoloader_dir(
      __DIR__ . DIRECTORY_SEPARATOR . 'drupony',
      __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
    );

    $this->assertEquals(realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor'), $dir);
  }

  /**
   * @expectedException \RuntimeException
   */
  public function testAutoloadFinderFail() {
    _drupony_find_composer_autoloader_dir(__DIR__ . DIRECTORY_SEPARATOR . 'drupony', __DIR__);
  }

  public function testDruponyMainContainer() {
    $drupony = drupony_get_wrapper(TRUE);
    $this->assertFileNotExists($drupony->getCacheFilePath());
    $container = $drupony->getContainer();
    $this->assertFileExists($drupony->getCacheFilePath());
    $class = $container->getParameter('symfony.component.filesystem.class');
    $filesystem = $container->get('symfony.component.filesystem');
    $this->assertInstanceOf($class, $filesystem);
  }

}
