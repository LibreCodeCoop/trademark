<?php

if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
}

$_GET["donotcheckversion"]   = true;
$dont_check_maintenance_mode = true;

// Redirect if is a not cached URL
if (!isset($_GET['_'])) {
   //std cache, with DB connection
   include_once GLPI_ROOT . "/inc/db.function.php";
   include_once GLPI_ROOT . '/inc/config.php';

   $timestamp = PluginTrademarkToolbox::getTimestamp();

   // Disable cache and redirect to cached URL
   header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
   header("Cache-Control: post-check=0, pre-check=0", false);
   header("Pragma: no-cache");

   $file = basename(__FILE__);
   $url = "$file?_=$timestamp";
   if (isset($_GET['v'])) {
      $url .= '&v=' . $_GET['v'];
   }
   Html::redirect($url, 302);
   die;
}

include('../../../inc/includes.php');

$name = 'internal';
$css = "";

$picture = PluginTrademarkConfig::getConfig("{$name}_picture", '');
if ($picture) {
   $css .= "#header #c_logo {";
   $css .= " width: " . PluginTrademarkConfig::getConfig("{$name}_picture_width", '100px') . ";";
   $css .= " height: " . PluginTrademarkConfig::getConfig("{$name}_picture_height", '55px') . ";";
   $css .= " background-size: contain;";
   $css .= " background-repeat: no-repeat;";
   $css .= " background-position: center;";
   $css .= " background-image: url(\"" . PluginTrademarkToolbox::getPictureUrl($picture) . "\");";
   $css .= "}";
}

$css_type = PluginTrademarkConfig::getConfig("{$name}_css_type", 'scss');
$css_custom = PluginTrademarkConfig::getConfig("{$name}_css_custom", '');

$css_custom = html_entity_decode($css_custom);

if ($css_type === 'scss' && $css_custom && PluginTrademarkScss::hasScssSuport()) {
   try {
      $css .= PluginTrademarkScss::compileScss($css_custom);
   } catch (\Throwable $th) {
      Toolbox::logWarning($th->getMessage());
   }
} else if ($css_type === 'css') {
   $css .= $css_custom;
}

header('Content-Type: text/css');

$is_cacheable = !isset($_GET['debug']) && !isset($_GET['nocache']);
if ($is_cacheable) {
   // Makes CSS cacheable by browsers and proxies
   $max_age = WEEK_TIMESTAMP;
   header_remove('Pragma');
   header('Cache-Control: public');
   header('Cache-Control: max-age=' . $max_age);
   header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + $max_age));
}

echo $css;
