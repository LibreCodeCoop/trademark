<?php

define('PLUGIN_TRADEMARK_VERSION', '1.2.3');

// Minimal GLPI version, inclusive
define("PLUGIN_TRADEMARK_MIN_GLPI_VERSION", "9.3");

// Maximum GLPI version, exclusive
define("PLUGIN_TRADEMARK_MAX_GLPI_VERSION", "9.6");


$folder = basename(dirname(__FILE__));

if ($folder !== "trademark") {
   $msg = sprintf("Please, rename the plugin folder \"%s\" to \"trademark\"", $folder);
   Session::addMessageAfterRedirect($msg, true, ERROR);
}

// Init the hooks of the plugins -Needed
function plugin_init_trademark() {
   global $PLUGIN_HOOKS, $CFG_GLPI;
   $PLUGIN_HOOKS['csrf_compliant']['trademark'] = true;

   $PLUGIN_HOOKS['config_page']['trademark'] = '../../front/config.form.php?itemtype=Config&glpi_tab=PluginTrademarkConfig$1';

   $plugin = new Plugin();

   if ($plugin->isInstalled('trademark') && $plugin->isActivated('trademark')) {

      $autoload = __DIR__ . '/vendor/autoload.php';
      if (file_exists($autoload)) {
         include_once $autoload;
      };

      Plugin::registerClass('PluginTrademarkConfig', [
         'addtabon' => ['Config']
      ]);

      $PLUGIN_HOOKS['display_login']['trademark'] = "plugin_trademark_display_login";

      // Tip Trick to add version in css output
      $PLUGIN_HOOKS["add_css"]['trademark'] = new PluginTrademarkFileVersion('front/internal.css.php');
      $PLUGIN_HOOKS["add_javascript"]['trademark'] = new PluginTrademarkFileVersion('front/internal.js.php');

      $CFG_GLPI['javascript']['config']['config'][] = 'codemirror';
      $CFG_GLPI['javascript']['config']['config'][] = 'tinymce';
   }
}

// Get the name and the version of the plugin - Needed
function plugin_version_trademark() {
   return [
      'name'           => t_trademark('Trademark'),
      'version'        => PLUGIN_TRADEMARK_VERSION,
      'author'         => '<a href="https://nextflow.com.br/">Nextflow</a>, <a href="https://github.com/edgardmessias">Edgard</a>',
      'homepage'       => 'https://nextflow.com.br/plugin-glpi/trademark',
      'license'        => 'GPL v2+',
      'minGlpiVersion' => PLUGIN_TRADEMARK_MIN_GLPI_VERSION,
      'requirements'   => [
         'glpi' => [
            'min' => PLUGIN_TRADEMARK_MIN_GLPI_VERSION,
            'max' => PLUGIN_TRADEMARK_MAX_GLPI_VERSION,
         ]
      ]
   ];
}

// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_trademark_check_prerequisites() {
   if (version_compare(GLPI_VERSION, PLUGIN_TRADEMARK_MIN_GLPI_VERSION, 'lt')) {
      echo "This plugin requires GLPI >= " . PLUGIN_TRADEMARK_MIN_GLPI_VERSION;
      return false;
   } else {
      return true;
   }
}

function plugin_trademark_check_config() {
   return true;
}

function t_trademark($str) {
   return __($str, 'trademark');
}
