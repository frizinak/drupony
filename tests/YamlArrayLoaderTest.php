<?php


namespace Drupony\Tests;

use Drupony\Component\DependencyInjection\Loader\YamlArrayLoader;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;

class YamlArrayLoaderTest extends \PHPUnit_Framework_TestCase {

  protected static $cacheDir;

  public static function setUpBeforeClass() {
    static::$cacheDir = DRUPONY_TEST_DIR . DIRECTORY_SEPARATOR . 'cacheDir';
  }

  protected function getLoader() {
    $container = new ContainerBuilder();
    $locator = new FileLocator(__DIR__);
    return new YamlArrayLoader($container, $locator);
  }

  public function validDataProvider() {
    $collection = array();
    $collection[][0] = array(
      'parameters' => array(
        'namespace.variable_name' => 'variable_value',
        'namespace.variable_name1' => 'variable_value1'
      ),
    );

    $collection[][0] = array(
      'services' => array(
        'namespace.service.service' => array('class' => '%namespace.variable_name%'),
        'namespace.variable_name1' => array('class' => '%namespace.variable_name%')
      ),
    );

    $collection[][0] = $collection[0][0] + $collection[1][0];

    return $collection;
  }

  public function invalidDataProvider() {
    $collection = array();
    $collection[] = array(
      'data' => array(
        'non_existent_namespace' => array(
          'namespace.variable_name' => 'variable_value',
          'namespace.variable_name1' => 'variable_value1'
        ),
      ),
      'exceptionMessage' => 'no extension',
    );

    $collection[] = array(
      'data' => array(
        'services' => array(
          'namespace.service.service' => '',
          'namespace.variable_name1' => array('class' => '%namespace.variable_name%')
        ),
      ),
      'exceptionMessage' => 'an array or a string starting',
    );

    return $collection;
  }

  public function supportedFilesProvider() {
    return array(
      array('file.module', TRUE),
      array('include.inc', TRUE),
      array('somepath/somedir/include.inc', TRUE),
      array('notsupported.txt', FALSE),
    );
  }

  /**
   * @dataProvider validDataProvider
   */
  public function testValidationOnValidData($validData) {
    $this->getLoader()->load(__FILE__, NULL, $validData);
  }

  /**
   * @dataProvider invalidDataProvider
   * @expectedException \InvalidArgumentException
   */
  public function testValidationOnInvalidData($validData, $expectedMessage) {
    try {
      $this->getLoader()->load(__FILE__, NULL, $validData);
    } catch (\InvalidArgumentException $e) {
      $this->assertNotFalse(strpos($e->getMessage(), $expectedMessage));
      throw $e;
    }
    $this->fail('An exception should\'ve been thrown');
  }

  /**
   * @dataProvider supportedFilesProvider
   */
  public function testFileSupport($file, $valid) {
    $this->assertEquals($valid, $this->getLoader()->supports($file));
  }

  public function testConfigFreshness() {
    touch(__FILE__, time() - 3600);
    $cache = new ConfigCache(static::$cacheDir . DIRECTORY_SEPARATOR . 'cache', TRUE);
    $container = new ContainerBuilder();
    $locator = new FileLocator(__DIR__);
    $loader = new YamlArrayLoader($container, $locator);

    $loader->load(__FILE__, NULL, array('parameters' => array('x' => 'y')));
    $container->compile();
    $dumper = new PhpDumper($container);
    $cache->write($dumper->dump(), $container->getResources());

    $this->assertTrue($cache->isFresh());
    touch(__FILE__, time() + 5);
    $this->assertFalse($cache->isFresh());

    $this->assertEquals($container->getParameter('x'), 'y');
  }

}
