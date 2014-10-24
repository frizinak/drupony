<?php


namespace Drupony\Tests;

use Drupony\Drupony;

class DruponyTest extends \PHPUnit_Framework_TestCase {

  public function testDruponyContainerParameter() {
    $dir = DRUPONY_TEST_DIR . DIRECTORY_SEPARATOR . 'cacheDir';
    $drupony = new Drupony($dir);
    $container = $drupony->getContainer();
    $this->assertTrue($container->hasParameter('test'));
    $this->assertEquals($container->getParameter('test'), 'set');
  }

  public function testDruponyContainerService() {
    $dir = DRUPONY_TEST_DIR . DIRECTORY_SEPARATOR . 'cacheDir';
    $drupony = new Drupony($dir);
    $class = $drupony->getContainer()->getParameter('symfony.component.filesystem.class');
    $filesystem = $drupony->getContainer()->get('symfony.component.filesystem');
    $this->assertInstanceOf($class, $filesystem);
  }

  public function testDruponyContainerInstanceCache() {
    $dir = DRUPONY_TEST_DIR . DIRECTORY_SEPARATOR . 'cacheDir';
    $drupony = new Drupony($dir, Drupony::CACHE_NONE, 'no-cache');
    $container = $drupony->getContainer();
    $container->someProperty = 5;

    $container = $drupony->getContainer();
    $this->assertTrue(isset($container->someProperty));
    $this->assertEquals($container->someProperty, 5);
  }

  public function testDruponyContainerDebugFileCache() {
    $dir = DRUPONY_TEST_DIR . DIRECTORY_SEPARATOR . 'cacheDir';
    $drupony = new Drupony($dir, Drupony::CACHE_CHANGE, 'file-monitor-cache-test');
    $drupony->getContainer();
    clearstatcache();
    $modified = filemtime($drupony->getCacheFilePath());
    $expectedModified = time();
    // Container should've been built
    $this->assertTrue($expectedModified + 2 > $modified && $expectedModified - 2 < $modified);

    $drupony = new Drupony($dir, Drupony::CACHE_CHANGE, 'file-monitor-cache-test');
    $drupony->getContainer();
    clearstatcache();
    // Container should be stale.
    $this->assertEquals($modified, filemtime($drupony->getCacheFilePath()));

    touch(DRUPONY_TEST_PARAM_FILE, time() - 3600);
    clearstatcache();
    $drupony = new Drupony($dir, Drupony::CACHE_CHANGE, 'file-monitor-cache-test');
    $drupony->getContainer();

    clearstatcache();
    // Container should've been rebuilt
    $this->assertGreaterThan(time() - 2, filemtime($drupony->getCacheFilePath()));
  }

  public function testDruponyContainerFileCache() {
    $dir = DRUPONY_TEST_DIR . DIRECTORY_SEPARATOR . 'cacheDir';
    $drupony = new Drupony($dir, Drupony::CACHE_FULL, 'full-cache-test');
    $drupony->getContainer();
    clearstatcache();
    $modified = filemtime($drupony->getCacheFilePath());
    $expectedModified = time();
    // Container should've been built
    $this->assertTrue($expectedModified + 2 > $modified && $expectedModified - 2 < $modified);

    $drupony = new Drupony($dir, Drupony::CACHE_FULL, 'full-cache-test');
    $drupony->getContainer();
    clearstatcache();
    // Container should be stale
    $this->assertEquals($modified, filemtime($drupony->getCacheFilePath()));

    touch(DRUPONY_TEST_PARAM_FILE, time() - 3600);
    clearstatcache();
    $modified = filemtime($drupony->getCacheFilePath());
    $drupony = new Drupony($dir, Drupony::CACHE_FULL, 'full-cache-test');
    $drupony->getContainer();
    clearstatcache();
    // Container should be stale
    $this->assertEquals($modified, filemtime($drupony->getCacheFilePath()));
  }

}
