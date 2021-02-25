<?php
include('../../../inc/includes.php');

$path = false;
$basePath = GLPI_PLUGIN_DOC_DIR . "/trademark/";

if (isset($_GET['path'])) {
   $path = $_GET['path'];
} else {
   Html::displayErrorAndDie(__('Invalid filename'), true);
}

if (isset($_GET['theme'])) {
   $fullPath = PluginTrademarkTheme::getThemeFolder() . '/' .  $_GET['theme'] . '/' . $path;
} else {
   $fullPath = $basePath . $path;
}

if (!file_exists($fullPath)) {
   Html::displayErrorAndDie(__('File not found'), true); // Not found
}

$name = preg_replace('/[^\w\-.]+/', '', $path);

Toolbox::sendFile($fullPath, $name, null, true);
