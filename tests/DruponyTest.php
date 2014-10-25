<?php


namespace Drupony\Tests;

use Drupony\Drupony;

class DruponyTest extends \PHPUnit_Framework_TestCase {

  protected $cacheDir;

  public function setUp() {
    $this->cacheDir = DRUPONY_TEST_DIR . DIRECTORY_SEPARATOR . 'cacheDir';
  }

  public function testDruponyContainerParameter() {
    $drupony = new Drupony($this->cacheDir);
    $container = $drupony->getContainer();
    $this->assertTrue($container->hasParameter('test'));
    $this->assertTrue($container->hasParameter('install_profile'));

    $this->assertEquals($container->getParameter('test'), 'set');
    $this->assertEquals($container->getParameter('install_profile'), 'wicked-profile');
    $container->get('test.service');
  }

  public function testDruponyContainerService() {
    $drupony = new Drupony($this->cacheDir);
    $container = $drupony->getContainer();
    $this->assertTrue($container->has('test.service'));
    $this->assertTrue($container->has('symfony.component.filesystem'));

    $class = $container->getParameter('symfony.component.filesystem.class');
    $filesystem = $container->get('symfony.component.filesystem');

    $testService = $container->get('test.service');

    $this->assertEquals($testService->getProperty(), $container->getParameter('install_profile'));
    $this->assertInstanceOf($class, $filesystem);
  }

  public function testDruponyContainerInstanceCache() {
    $drupony = new Drupony($this->cacheDir, Drupony::CACHE_NONE, 'no-cache');
    $container = $drupony->getContainer();
    $container->someProperty = 5;

    $container = $drupony->getContainer();
    $this->assertTrue(isset($container->someProperty));
    $this->assertEquals($container->someProperty, 5);
  }

  public function testDruponyContainerDebugFileCache() {
    $now = $this->setupMTimes();

    /** -- Built container for first time. -- */
    $drupony = new Drupony($this->cacheDir, Drupony::CACHE_CHANGE, 'file-monitor-cache-test');
    $this->assertFileNotExists($drupony->getCacheFilePath());
    $drupony->getContainer();
    $this->assertFileExists($drupony->getCacheFilePath());

    /** -- Get container from cache. -- */
    touch($drupony->getCacheFilePath(), $now - 3600);
    clearstatcache();
    $this->assertEquals($now - 3600, filemtime($drupony->getCacheFilePath()));
    $drupony = new Drupony($this->cacheDir, Drupony::CACHE_CHANGE, 'file-monitor-cache-test');
    $drupony->getContainer();
    clearstatcache();
    $this->assertEquals($now - 3600, filemtime($drupony->getCacheFilePath()));

    /** -- Rebuilt container as a yaml-file has been altered. -- */
    touch(DRUPONY_TEST_PARAM_FILE, $now);
    clearstatcache();
    $this->assertEquals($now, filemtime(DRUPONY_TEST_PARAM_FILE));
    $drupony = new Drupony($this->cacheDir, Drupony::CACHE_CHANGE, 'file-monitor-cache-test');
    $drupony->getContainer();
    clearstatcache();
    $this->assertGreaterThan($now - 3600, $current = filemtime($drupony->getCacheFilePath()));

    /** -- Rebuilt container as a module-hook-file has been altered. -- */
    $now = $this->setupMTimes();
    touch($drupony->getCacheFilePath(), $now - 3600);
    touch(DRUPONY_TEST_HOOK_FILE, $now);
    clearstatcache();
    $this->assertEquals($now - 3600, filemtime($drupony->getCacheFilePath()));
    $this->assertEquals($now, filemtime(DRUPONY_TEST_HOOK_FILE));
    $drupony = new Drupony($this->cacheDir, Drupony::CACHE_CHANGE, 'file-monitor-cache-test');
    $drupony->getContainer();
    clearstatcache();
    $this->assertGreaterThan($now - 3600, filemtime($drupony->getCacheFilePath()));
  }

  public function testDruponyContainerFileCache() {
    $now = $this->setupMTimes();

    /** -- Built container for first time. -- */
    $drupony = new Drupony($this->cacheDir, Drupony::CACHE_FULL, 'full-cache-test');
    $this->assertFileNotExists($drupony->getCacheFilePath());
    $drupony->getContainer();
    $this->assertFileExists($drupony->getCacheFilePath());

    /** -- Get container from cache. -- */
    $mtime = $now - 3600;
    touch($drupony->getCacheFilePath(), $mtime);
    clearstatcache();
    $this->assertEquals($mtime, filemtime($drupony->getCacheFilePath()));
    $drupony = new Drupony($this->cacheDir, Drupony::CACHE_FULL, 'full-cache-test');
    $drupony->getContainer();
    clearstatcache();
    $this->assertEquals($mtime, filemtime($drupony->getCacheFilePath()));

    /** -- Get container from cache even though a yaml-file has been altered. -- */
    touch(DRUPONY_TEST_PARAM_FILE, $now);
    clearstatcache();
    $this->assertEquals($now, filemtime(DRUPONY_TEST_PARAM_FILE));
    $drupony = new Drupony($this->cacheDir, Drupony::CACHE_FULL, 'full-cache-test');
    $drupony->getContainer();
    clearstatcache();
    $this->assertEquals($mtime, filemtime($drupony->getCacheFilePath()));

    /** -- Get container from cache even though a module-hook-file has been altered. -- */
    $this->setupMTimes();
    touch($drupony->getCacheFilePath(), $mtime);
    touch(DRUPONY_TEST_HOOK_FILE, $now);
    clearstatcache();
    $this->assertEquals($now - 3600, filemtime($drupony->getCacheFilePath()));
    $this->assertEquals($now, filemtime(DRUPONY_TEST_HOOK_FILE));
    $drupony = new Drupony($this->cacheDir, Drupony::CACHE_FULL, 'full-cache-test');
    $drupony->getContainer();
    clearstatcache();
    $this->assertEquals($mtime, filemtime($drupony->getCacheFilePath()));
  }

  public function testDruponyVariableBag() {
    $drupony = new Drupony($this->cacheDir, Drupony::CACHE_FULL, 'variables');
    $drupony2 = new Drupony($this->cacheDir, Drupony::CACHE_FULL, 'variables');
    $container = $drupony->getContainer();
    $container2 = $drupony2->getContainer();

    $container->setVariable('site_name', 'drupony.org');
    $this->assertTrue($container2->hasVariable('site_name'));
    $this->assertEquals($container2->getVariable('site_name'), 'drupony.org');
    $this->assertArrayHasKey('site_name', $GLOBALS['conf']);
    $this->assertFalse($container2->hasVariable('never_set'));
    $container2->delVariable('site_name');

    $this->assertFalse($container->hasVariable('site_name'));
    $this->assertArrayNotHasKey('site_name', $GLOBALS['conf']);

    $container->getVariableBag()->add(array('site_name' => 'drupony.org', 'admin_theme' => 'seven'));
    $this->assertTrue($container2->hasVariable('site_name'));
    $this->assertEquals($container2->getVariable('site_name'), 'drupony.org');
    $this->assertTrue($container2->hasVariable('admin_theme'));
    $this->assertEquals($container2->getVariable('admin_theme'), 'seven');
  }

  /**
   * @expectedException Drupony\Component\DependencyInjection\Exception\VariableTruncateException
   */
  public function testDruponyClearVariableBag() {
    $drupony = new Drupony($this->cacheDir, Drupony::CACHE_FULL, 'variables');
    $drupony->getContainer()->getVariableBag()->clear();
  }

  protected function setupMTimes() {
    $now = time();
    touch(DRUPONY_TEST_PARAM_FILE, $now - 7200);
    touch(DRUPONY_TEST_HOOK_FILE, $now - 7200);
    return $now;
  }
}
