<?php

class PluginTrademarkScss {

   static function getNamespace() {
      $namespace = 'ScssPhp\ScssPhp';
      $compiler = "$namespace\Compiler";

      if (!class_exists($compiler)) {
         $namespace = 'Leafo\ScssPhp';
         $compiler = "$namespace\Compiler";
      }
      if (!class_exists($compiler)) {
         return false;
      }

      return $namespace;
   }

   static function hasScssSuport() {
      $namespace = static::getNamespace();
      return !empty($namespace);
   }

   static function compileScss($content, $variables = []) {
      global $GLPI_CACHE;

      $namespace = static::getNamespace();

      if (!$namespace) {
         return '';
      }

      $compiler = "$namespace\Compiler";

      /** @var ScssPhp\ScssPhp\Compiler */
      $scss = new $compiler();
      if (method_exists($scss, 'setOutputStyle')) {
         $scss->setOutputStyle("compressed");
      } else {
         $scss->setFormatter("$namespace\Formatter\Crunched");
      }
      $scss->addImportPath(GLPI_ROOT);

      $scss->setVariables($variables);

      $ckey = md5($content . json_encode($variables));

      if ($GLPI_CACHE->has($ckey) && !isset($_GET['reload']) && !isset($_GET['nocache'])) {
         $css = $GLPI_CACHE->get($ckey);
      } else {
         $css = $scss->compile($content);
         if (!isset($_GET['nocache'])) {
            $GLPI_CACHE->set($ckey, $css);
         }
      }

      return $css;
   }

   static function getLoginCSS($theme = null, $variables = []) {
      if (!$theme) {
         $theme = PluginTrademarkConfig::getConfig("login_theme", '');
      }
      $themeInfo = null;
      if ($theme) {
         $themeInfo = PluginTrademarkTheme::getThemeInfo($theme);
      }

      $picture = PluginTrademarkConfig::getConfig("login_background_picture", '');

      if (!$picture && $themeInfo && $themeInfo['login-background']) {
         $picture = $themeInfo['login-background'] . '&theme=' . $themeInfo['id'];
      }

      $css = '';
      if ($picture) {
         $css .= "#firstboxlogin, #text-login, #logo_login {";
         $css .= " background-color: transparent;";
         $css .= "}";
         $css .= "html {";
         $css .= " height: 100%;";
         $css .= "}";
         $css .= "body {";
         $css .= " background-size: cover;";
         $css .= " background-repeat: no-repeat;";
         $css .= " background-position: center;";
         $css .= " background-image: url(\"" . PluginTrademarkToolbox::getPictureUrl($picture) . "\");";
         $css .= "}";
      }

      $css_type = PluginTrademarkConfig::getConfig("login_css_type", 'scss');
      $css_custom = PluginTrademarkConfig::getConfig("login_css_custom", '');

      $css_custom = html_entity_decode($css_custom);

      if ($css_type === 'scss' && PluginTrademarkScss::hasScssSuport()) {
         $variables = [];
         if ($themeInfo && isset($themeInfo['variables'])) {
            foreach ($themeInfo['variables'] as $k => $v) {
               $themeId = $themeInfo['id'];
               $fieldName = "login_theme-$themeId-$k";
               $fieldValue = PluginTrademarkConfig::getConfig($fieldName, $v['default']);
               $variables[$k] = $fieldValue;
            }
         }

         if ($themeInfo && $themeInfo['login-scss']) {
            $scssPath = str_replace('\\', '/', $themeInfo['path'] . '/' . $themeInfo['login-scss']);
            $css_custom = "@import '" . $scssPath . "';\n" . $css_custom;
         }

         try {
            $css .= PluginTrademarkScss::compileScss($css_custom, $variables);
         } catch (\Throwable $th) {
            Toolbox::logWarning($th->getMessage());
         }
      } else {
         if ($themeInfo && $themeInfo['login-css']) {
            $css .= file_get_contents($themeInfo['path'] . '/' . $themeInfo['login-css']) . "\n";
         }

         if ($css_type === 'css') {
            $css .= $css_custom;
         }
      }

      return $css;
   }
}
