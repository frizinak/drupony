<?php

define('DRUPONY_TEST_DIR', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'drupony_tests');
define('DRUPONY_TEST_PARAM_FILE', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'parameters.yml');

define('DRUPAL_ROOT', dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..');
function module_list() {
  return array('drupony');
}

function drupal_get_path($type, $module) {
  return $module . DIRECTORY_SEPARATOR . 'tests';
}

$loader = require __DIR__ . '/../vendor/autoload.php';
register_shutdown_function(function () {
  $fs = new \Symfony\Component\Filesystem\Filesystem();
  $fs->remove(DRUPONY_TEST_DIR);
});

