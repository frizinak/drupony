<?php


/**
 * Implements hook_flush_caches().
 */
function drupony_flush_caches() {
  drupony_clear_cache('container');
}

/**
 * Implements hook_admin_menu_cache_info
 */
function drupony_admin_menu_cache_info() {
  $caches = array();
  $caches['drupony'] = array(
    'title' => t('Drupony'),
    'callback' => 'drupony_flush_caches',
  );
  return $caches;
}
