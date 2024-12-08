<?php
// #ddev-generated: Automatically generated Drupal settings file.
if (file_exists($app_root . '/' . $site_path . '/settings.ddev.php') &&
getenv('IS_DDEV_PROJECT') == 'true') {
include $app_root . '/' . $site_path . '/settings.ddev.php';
include $app_root . '/' . $site_path . '/settings.local.php';
}
else if (getenv('STAGING_SERVER') == 'true') {
include $app_root . '/' . $site_path . '/settings.staging.php';
}
else {
include $app_root . '/' . $site_path . '/settings.production.php';
}
$databases['default']['default'] = array (
  'database' => 'db',
  'username' => 'db',
  'password' => 'db',
  'prefix' => '',
  'host' => 'db',
  'port' => 3306,
  'isolation_level' => 'READ COMMITTED',
  'driver' => 'mysql',
  'namespace' => 'Drupal\\mysql\\Driver\\Database\\mysql',
  'autoload' => 'core/modules/mysql/src/Driver/Database/mysql/',
);
$settings['hash_salt'] = 'bm8dia27vphiuPn562lfEFp__tddL_buKfl9DTWpMrN2XZ4zn-VbsyXWIYVtVrS4id37vsmcbw';
