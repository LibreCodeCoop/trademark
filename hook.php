<?php

function plugin_trademark_display_login() {

   $themeInfo = null;
   if (isset($_GET['theme'])) {
      $themeInfo = PluginTrademarkTheme::getThemeInfo($_GET['theme']);
   }
   if (!$themeInfo) {
      $theme = PluginTrademarkConfig::getConfig("login_theme", '');
      $themeInfo = PluginTrademarkTheme::getThemeInfo($theme);
   }

   $loginPicture = PluginTrademarkConfig::getConfig('login_picture');

   if (!$loginPicture && $themeInfo && $themeInfo['login-background']) {
      $loginPicture = $themeInfo['login-logo'] . '&theme=' . $themeInfo['id'];
   }

   if ($loginPicture && version_compare(GLPI_VERSION, '9.5.0', '<')) {
      echo Html::css("/plugins/trademark/css/login.base.css", [
         'version' => PLUGIN_TRADEMARK_VERSION,
      ]);
   }

   $timestamp = PluginTrademarkToolbox::getTimestamp();

   $cssUrl = "/plugins/trademark/front/login.css.php?_=$timestamp";

   if (isset($_GET['theme'])) {
      $cssUrl .= "&theme=" . $_GET['theme'];
   }

   echo Html::css($cssUrl, [
      'version' => PLUGIN_TRADEMARK_VERSION,
   ]);

   ?>
   <script type="text/javascript">
      var $box = $('#firstboxlogin');
      var $wrapper = $('<div />', {
         class: 'login_wrapper'
      }).append($box.contents());
      $wrapper.prependTo($box);
      $('#display-login').appendTo($box);
   <?php

   if ($loginPicture) :
      $pictureUrl = PluginTrademarkToolbox::getPictureUrl($loginPicture);
      $css = [
         'max-width' => PluginTrademarkConfig::getConfig('login_picture_max_width', '145px'),
         'max-height' => PluginTrademarkConfig::getConfig('login_picture_max_height', '80px'),
      ];
      ?>
         var $logo_login = $('#logo_login');
         var $img = $logo_login.find('img');
         if (!$img.length) {
            $logo_login.replaceWith($('<h1 />', {
               id: 'logo_login',
               html: $('<img />', {
                  src: <?php echo json_encode($pictureUrl) ?>,
                  css: <?php echo json_encode($css) ?>
               })
            }));
            $logo_login = $('#logo_login');
         } else {
            $img.css(<?php echo json_encode($css) ?>);
            $img.attr('src', <?php echo json_encode($pictureUrl) ?>);
         }
         // $logo_login.css(<?php echo json_encode($css) ?>);
         // $logo_login.css({
         //    'background-repeat': 'no-repeat',
         //    'background-size': 'contain',
         //    'background-position': 'center',
         //    'background-image': 'url(<?php echo json_encode($pictureUrl) ?>)'
         // });
      <?php
      endif;
   $favicon = PluginTrademarkConfig::getConfig('favicon_picture');
   if ($favicon) :
      $faviconUrl = PluginTrademarkToolbox::getPictureUrl($favicon);
      ?>
         var $icon = $('link[rel*=icon]');
         $icon.attr('type', null);
         $icon.attr('href', <?php echo json_encode($faviconUrl) ?>);
      <?php
      endif;
   $pageTitle = PluginTrademarkConfig::getConfig('page_title');
   if ($pageTitle) :
      ?>
         var $title = $('title');
         var newTitle = $title.text().replace('GLPI', <?php echo json_encode($pageTitle) ?>);
         $title.text(newTitle);
      <?php
      endif;
   $footerDisplay = PluginTrademarkConfig::getConfig('page_footer_display', 'original');
   $footerText = PluginTrademarkConfig::getConfig('page_footer_text', '');
   if ($footerDisplay === 'hide') :
      ?>
         $(function() {
            $('#footer-login').hide();
         });
      <?php
      endif;
   if ($footerDisplay === 'custom') :
      $footerText = Toolbox::getHtmlToDisplay($footerText);
      ?>
         $(function() {
            $('#footer-login').html(<?php echo json_encode($footerText) ?>);
         });
      <?php
      endif;
   ?>
   </script>
   <?php
}

function plugin_trademark_install() {
   return true;
}

function plugin_trademark_uninstall() {
   return true;
}
