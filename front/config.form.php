<?php

include ("../../../inc/includes.php");

// No autoload when plugin is not activated
require_once('../inc/config.class.php');

$config = new PluginTrademarkConfig();
if (isset($_POST["update"])) {
   $config->configUpdate($_POST);

   Html::back();
}

Html::redirect($CFG_GLPI["root_doc"]."/front/config.form.php?forcetab=".
               urlencode('PluginTrademarkConfig$1'));
