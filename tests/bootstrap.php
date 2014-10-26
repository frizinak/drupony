<?php
clearstatcache();
define('DRUPAL_ROOT', __DIR__);
define('DRUPONY_TEST_DIR', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'drupony_tests');

// Spoof some drupal methods.
function module_list() {
  return array('drupony');
}

function drupal_get_path($type, $module) {
  return $module;
}

function variable_get($name, $default = NULL) {
  global $conf;
  if (isset($conf[$name])) {
    return $conf[$name];
  }
  return $default;
}

function variable_set($name, $value) {
  $GLOBALS['conf'][$name] = $value;
}

function variable_del($name) {
  unset($GLOBALS['conf'][$name]);
}

function module_hook($module, $hook) {
  $function = $module . '_' . $hook;
  if (function_exists($function)) {
    return TRUE;
  }
  else {
    require_once __DIR__ . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . $module . '.module';
  }

  return function_exists($function);
}

function module_invoke($module, $hook) {
  $args = func_get_args();
  unset($args[0], $args[1]);
  if (module_hook($module, $hook)) {
    return call_user_func_array($module . '_' . $hook, $args);
  }
}

function conf_path(){
  return 'sites/default';
}

$loader = require __DIR__ . '/../vendor/autoload.php';
register_shutdown_function(function () {
  $fs = new \Symfony\Component\Filesystem\Filesystem();
  $fs->remove(DRUPONY_TEST_DIR);
});

