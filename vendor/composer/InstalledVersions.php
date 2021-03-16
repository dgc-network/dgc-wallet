<?php











namespace Composer;

use Composer\Autoload\ClassLoader;
use Composer\Semver\VersionParser;






class InstalledVersions
{
private static $installed = array (
  'root' => 
  array (
    'pretty_version' => 'dev-master',
    'version' => 'dev-master',
    'aliases' => 
    array (
    ),
    'reference' => 'bef2e13959a1cef4a5dc10edb277857680d4ec7d',
    'name' => 'dgc-network/dgc-payment',
  ),
  'versions' => 
  array (
    'composer/installers' => 
    array (
      'pretty_version' => 'v1.10.0',
      'version' => '1.10.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '1a0357fccad9d1cc1ea0c9a05b8847fbccccb78d',
    ),
    'dgc-network/dgc-payment' => 
    array (
      'pretty_version' => 'dev-master',
      'version' => 'dev-master',
      'aliases' => 
      array (
      ),
      'reference' => 'bef2e13959a1cef4a5dc10edb277857680d4ec7d',
    ),
    'jalle19/simple-json-rpc-client' => 
    array (
      'pretty_version' => '1.0.9',
      'version' => '1.0.9.0',
      'aliases' => 
      array (
      ),
      'reference' => 'eecfbfd02168c761d1221d9857f8dcc09db345b1',
    ),
    'roundcube/plugin-installer' => 
    array (
      'replaced' => 
      array (
        0 => '*',
      ),
    ),
    'shama/baton' => 
    array (
      'replaced' => 
      array (
        0 => '*',
      ),
    ),
    'zendframework/zend-escaper' => 
    array (
      'pretty_version' => '2.2.10',
      'version' => '2.2.10.0',
      'aliases' => 
      array (
      ),
      'reference' => '232249785a19b982d86e502d161c90ce13202eed',
    ),
    'zendframework/zend-http' => 
    array (
      'pretty_version' => '2.2.10',
      'version' => '2.2.10.0',
      'aliases' => 
      array (
      ),
      'reference' => 'feb1c409f856ace2c0326fa86ac98136859b7e93',
    ),
    'zendframework/zend-loader' => 
    array (
      'pretty_version' => '2.2.10',
      'version' => '2.2.10.0',
      'aliases' => 
      array (
      ),
      'reference' => '8d7131b6c063251f99772eab2eba44573497dc25',
    ),
    'zendframework/zend-stdlib' => 
    array (
      'pretty_version' => '2.2.10',
      'version' => '2.2.10.0',
      'aliases' => 
      array (
      ),
      'reference' => 'b42d784bd142e916058a9d68eeafcc10ff63c12e',
    ),
    'zendframework/zend-uri' => 
    array (
      'pretty_version' => '2.2.10',
      'version' => '2.2.10.0',
      'aliases' => 
      array (
      ),
      'reference' => 'ee7412a16ddd59d3f0935dfb6f09a889b7d1b561',
    ),
    'zendframework/zend-validator' => 
    array (
      'pretty_version' => '2.2.10',
      'version' => '2.2.10.0',
      'aliases' => 
      array (
      ),
      'reference' => '6c5637d535bc2cdaa5c85467490bfb17744c13e6',
    ),
  ),
);
private static $canGetVendors;
private static $installedByVendor = array();







public static function getInstalledPackages()
{
$packages = array();
foreach (self::getInstalled() as $installed) {
$packages[] = array_keys($installed['versions']);
}


if (1 === \count($packages)) {
return $packages[0];
}

return array_keys(array_flip(\call_user_func_array('array_merge', $packages)));
}









public static function isInstalled($packageName)
{
foreach (self::getInstalled() as $installed) {
if (isset($installed['versions'][$packageName])) {
return true;
}
}

return false;
}














public static function satisfies(VersionParser $parser, $packageName, $constraint)
{
$constraint = $parser->parseConstraints($constraint);
$provided = $parser->parseConstraints(self::getVersionRanges($packageName));

return $provided->matches($constraint);
}










public static function getVersionRanges($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

$ranges = array();
if (isset($installed['versions'][$packageName]['pretty_version'])) {
$ranges[] = $installed['versions'][$packageName]['pretty_version'];
}
if (array_key_exists('aliases', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['aliases']);
}
if (array_key_exists('replaced', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['replaced']);
}
if (array_key_exists('provided', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['provided']);
}

return implode(' || ', $ranges);
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getVersion($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['version'])) {
return null;
}

return $installed['versions'][$packageName]['version'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getPrettyVersion($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['pretty_version'])) {
return null;
}

return $installed['versions'][$packageName]['pretty_version'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getReference($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['reference'])) {
return null;
}

return $installed['versions'][$packageName]['reference'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getRootPackage()
{
$installed = self::getInstalled();

return $installed[0]['root'];
}







public static function getRawData()
{
return self::$installed;
}



















public static function reload($data)
{
self::$installed = $data;
self::$installedByVendor = array();
}




private static function getInstalled()
{
if (null === self::$canGetVendors) {
self::$canGetVendors = method_exists('Composer\Autoload\ClassLoader', 'getRegisteredLoaders');
}

$installed = array();

if (self::$canGetVendors) {
foreach (ClassLoader::getRegisteredLoaders() as $vendorDir => $loader) {
if (isset(self::$installedByVendor[$vendorDir])) {
$installed[] = self::$installedByVendor[$vendorDir];
} elseif (is_file($vendorDir.'/composer/installed.php')) {
$installed[] = self::$installedByVendor[$vendorDir] = require $vendorDir.'/composer/installed.php';
}
}
}

$installed[] = self::$installed;

return $installed;
}
}
