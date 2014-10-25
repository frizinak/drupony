<?php

define('DRUPONY_TEST_DIR', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'drupony_tests');
define('DRUPONY_TEST_PARAM_FILE', __DIR__ . DIRECTORY_SEPARATOR . 'parameters.yml');
define('DRUPONY_TEST_HOOK_FILE', __DIR__ . DIRECTORY_SEPARATOR . 'drupony.module');

define('DRUPONY_TEST_PARAM_FILE_MTIME', filemtime(DRUPONY_TEST_PARAM_FILE));
define('DRUPONY_TEST_HOOK_FILE_MTIME', filemtime(DRUPONY_TEST_HOOK_FILE));

define('DRUPAL_ROOT', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..');

// Spoof some drupal methods.
function module_list() {
  return array('drupony');
}

function drupal_get_path($type, $module) {
  return $module . DIRECTORY_SEPARATOR . 'tests';
}

$conf = array();
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
    require_once __DIR__ . DIRECTORY_SEPARATOR . $module . '.module';
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

$loader = require __DIR__ . '/../vendor/autoload.php';
register_shutdown_function(function () {
  $fs = new \Symfony\Component\Filesystem\Filesystem();
  $fs->remove(DRUPONY_TEST_DIR);
  touch(DRUPONY_TEST_HOOK_FILE, DRUPONY_TEST_HOOK_FILE_MTIME);
  touch(DRUPONY_TEST_PARAM_FILE, DRUPONY_TEST_PARAM_FILE_MTIME);
});

