<?php


namespace Drupony\Tests;

use Drupony\Drupony;

class DruponyTest extends \PHPUnit_Framework_TestCase {

  protected $cacheDir;
  protected $sourceFiles = array();

  public function setUp() {
    $this->cacheDir = DRUPONY_TEST_DIR . DIRECTORY_SEPARATOR . 'cacheDir';
    $candidates = array('params' => 'parameters.yml', 'services' => 'services.yml', 'hook' => 'drupony.module');
    foreach ($candidates as $k => $fn) {
      $path = DRUPAL_ROOT . DIRECTORY_SEPARATOR . drupal_get_path('module', 'drupony') . DIRECTORY_SEPARATOR . $fn;
      file_exists($path) && $this->sourceFiles[$k] = $path;
    }

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
    $this->setMTime($drupony->getCacheFilePath(), $now - 3600);
    $this->assertEquals($now - 3600, filemtime($drupony->getCacheFilePath()));
    $drupony = new Drupony($this->cacheDir, Drupony::CACHE_CHANGE, 'file-monitor-cache-test');
    $GLOBALS['wop'] = 1;
    $drupony->getContainer();
    unset($GLOBALS['wop']);
    $this->assertEquals($now - 3600, filemtime($drupony->getCacheFilePath()));

    /** -- Rebuilt container as a yaml-file has been altered. -- */
    $this->setMTime($this->sourceFiles['params'], $now);
    $this->assertGreaterThan(filemtime($drupony->getCacheFilePath()), filemtime($this->sourceFiles['params']));
    $this->assertEquals($now, filemtime($this->sourceFiles['params']));
    $drupony = new Drupony($this->cacheDir, Drupony::CACHE_CHANGE, 'file-monitor-cache-test');
    $drupony->getContainer();
    $this->assertGreaterThan($now - 3600, filemtime($drupony->getCacheFilePath()));

    /** -- Rebuilt container as a module-hook-file has been altered. -- */
    $now = $this->setupMTimes();
    $this->setMTime($drupony->getCacheFilePath(), $now - 3600);
    $this->setMTime($this->sourceFiles['hook'], $now);
    $this->assertEquals($now - 3600, filemtime($drupony->getCacheFilePath()));
    $this->assertEquals($now, filemtime($this->sourceFiles['hook']));
    $drupony = new Drupony($this->cacheDir, Drupony::CACHE_CHANGE, 'file-monitor-cache-test');
    $drupony->getContainer();
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
    $this->setMTime($drupony->getCacheFilePath(), $mtime);
    $this->assertEquals($mtime, filemtime($drupony->getCacheFilePath()));
    $drupony = new Drupony($this->cacheDir, Drupony::CACHE_FULL, 'full-cache-test');
    $drupony->getContainer();
    $this->assertEquals($mtime, filemtime($drupony->getCacheFilePath()));

    /** -- Get container from cache even though a yaml-file has been altered. -- */
    $this->setMTime($this->sourceFiles['params'], $now);
    $this->assertEquals($now, filemtime($this->sourceFiles['params']));
    $drupony = new Drupony($this->cacheDir, Drupony::CACHE_FULL, 'full-cache-test');
    $drupony->getContainer();
    $this->assertEquals($mtime, filemtime($drupony->getCacheFilePath()));

    /** -- Get container from cache even though a module-hook-file has been altered. -- */
    $this->setupMTimes();
    $this->setMTime($drupony->getCacheFilePath(), $mtime);
    $this->setMTime($this->sourceFiles['hook'], $now);
    $this->assertEquals($now - 3600, filemtime($drupony->getCacheFilePath()));
    $this->assertEquals($now, filemtime($this->sourceFiles['hook']));
    $drupony = new Drupony($this->cacheDir, Drupony::CACHE_FULL, 'full-cache-test');
    $drupony->getContainer();
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

  protected function setMTime($filePath, $mtime) {
    if (!touch($filePath, $mtime)) {
      throw new \RuntimeException(sprintf('File %s\'s filemtime could not be set', $filePath));
    }
    if (($real = filemtime($filePath)) !== $mtime) {
      throw new \RuntimeException(
        sprintf('File %s\'s filemtime (%s) should\'ve been set but doesn\'t match requested mtime (%s)',
                $filePath,
                $real,
                $mtime)
      );
    }
  }

  protected function setupMTimes() {
    $now = time();
    foreach ($this->sourceFiles as $path) {
      $this->setMTime($path, $now - 7200);
    }
    return $now;
  }
}
