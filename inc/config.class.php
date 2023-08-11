<?php

class PluginTrademarkConfig extends CommonDBTM {

   private static $_cache = null;
   private static $_i = 1;

   static function getConfig($name, $defaultValue = null) {

      if (self::$_cache === null) {
         $config = new self();
         $config->getEmpty();
         $config->fields = array_merge($config->fields, Config::getConfigurationValues('trademark'));

         self::$_cache = $config->fields;
      }

      if (isset(self::$_cache[$name]) && self::$_cache[$name] !== '') {
         return self::$_cache[$name];
      }
      return $defaultValue;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      switch (get_class($item)) {
         case 'Config':
            return [1 => t_trademark('Trademark')];
         default:
            return '';
      }
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      switch (get_class($item)) {
         case 'Config':
            $config = new self();
            $config->showFormDisplay();
            break;
      }
      return true;
   }

   protected static function checkPicture($name, $input, $old, $width = 0, $height = 0, $max_size = 500) {

      $blank = "_blank_$name";
      $new = "_new_$name";

      if (isset($input[$blank]) && $input[$blank]) {
         unset($input[$blank]);
         if (isset($old[$name]) && $old[$name]) {
            PluginTrademarkToolbox::deletePicture($old[$name]);
         }
         $input[$name] = '';
      } else if (isset($input[$new]) && !empty($input[$new])) {
         $picName = array_shift($input[$new]);
         $picPath = GLPI_TMP_DIR . '/' . $picName;
         $picResizedPath = GLPI_TMP_DIR . '/resized_' . $picName;

         if ($width || $height) {
            if (PluginTrademarkToolbox::resizePicture($picPath, $picResizedPath, $width, $height, 0, 0, 0, 0, $max_size)) {
               $picPath = $picResizedPath;
            }
         }

         if ($dest = PluginTrademarkToolbox::savePicture($picPath)) {
            $input[$name] = $dest;
         } else {
            Session::addMessageAfterRedirect(__('Unable to save picture file.'), true, ERROR);
         }

         if (isset($old['$name']) && $old['$name']) {
            PluginTrademarkToolbox::deletePicture($old['$name']);
         }
      }

      unset($input["_$name"]);
      unset($input["_prefix_$name"]);
      unset($input["_prefix_new_$name"]);
      unset($input["_tag_$name"]);
      unset($input["_tag_new_$name"]);
      unset($input["new_$name"]);
      unset($input[$blank]);
      unset($input[$new]);

      return $input;
   }

   protected static function checkCSS($name, $label, $input, $old) {
      $fullName = "{$name}_css_custom";
      $type = "{$name}_css_type";

      if (!isset($input[$type])) {
         $input[$type] = 'css';
      }

      if (isset($input[$fullName])) {
         $input[$fullName] = html_entity_decode($input[$fullName]);
         $input[$fullName] = preg_replace('/\\\\r\\\\n/', "\n", $input[$fullName]);
         $input[$fullName] = preg_replace('/\\\\n/', "\n", $input[$fullName]);

         if ($input[$type] === 'scss' && PluginTrademarkScss::hasScssSuport()) {
            try {
               PluginTrademarkScss::compileScss($input[$fullName]);
            } catch (\Throwable $th) {
               $message = sprintf(t_trademark('Unable to compile the SCSS (%1$s). Message: '), $label);
               Session::addMessageAfterRedirect($message . $th->getMessage(), true, ERROR);
            }
         }
      }

      return $input;
   }

   static function configUpdate($input) {
      $old = Config::getConfigurationValues('trademark');

      unset($input['_no_history']);

      $input = self::checkPicture('favicon_picture', $input, $old, 192, 192, 192);
      $input = self::checkPicture('login_picture', $input, $old, 145, 80, 300);
      $input = self::checkPicture('internal_picture', $input, $old, 100, 55, 300);
      $input = self::checkPicture('login_background_picture', $input, $old);

      $input = self::checkCSS('login', t_trademark('Login Page'), $input, $old);
      $input = self::checkCSS('internal', t_trademark('Internal Page'), $input, $old);

      foreach ($input as $key => $value) {
         if (str_starts_with($key, '_uploader_')) {
            unset($input[$key]);
            continue;
         }
         if ($value && strpos($key, '_blank_') === 0) {
            $name = substr($key, 7);
            $input[$name] = '';
         }
      }

      $input['timestamp'] = time();

      PluginTrademarkToolbox::setTimestamp($input['timestamp']);

      Session::addMessageAfterRedirect(__('Item successfully updated'), false, INFO);

      return $input;
   }

   function getEmpty() {

      $defaultCss = 'css';

      if (PluginTrademarkScss::hasScssSuport()) {
         $defaultCss = 'scss';
      }

      $this->fields = [
         'favicon_picture' => '',
         'page_title' => '',
         'page_footer_display' => 'original',
         'page_footer_text' => '',
         'login_picture' => '',
         'login_picture_max_width' => '240px',
         'login_picture_max_height' => '130px',
         'login_css_custom' => '',
         'login_css_type' => $defaultCss,
         'login_theme' => '',
         'internal_picture' => '',
         'internal_picture_width' => '100px',
         'internal_picture_height' => '55px',
         'internal_css_custom' => '',
         'internal_css_type' => $defaultCss,
      ];
   }

   protected function buildPictureLine($name, $recommendedSize = null) {
      if (!empty($this->fields[$name])) {
         echo '<td>';
         echo Html::image(PluginTrademarkToolbox::getPictureUrl($this->fields[$name]), [
            'style' => '
               max-width: 100px;
               max-height: 100px;
               background-image: linear-gradient(45deg, #b0b0b0 25%, transparent 25%), linear-gradient(-45deg, #b0b0b0 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #b0b0b0 75%), linear-gradient(-45deg, transparent 75%, #b0b0b0 75%);
               background-size: 10px 10px;
               background-position: 0 0, 0 5px, 5px -5px, -5px 0px;',
            'class' => 'picture_square'
         ]);
         echo "&nbsp;";
         echo Html::getCheckbox([
            'title' => t_trademark('Reset'),
            'name'  => "_blank_$name"
         ]);
         echo "&nbsp;" . t_trademark('Reset');
         echo '</td>';
         echo '<td colspan="2">';
      } else {
         echo '<td colspan="3">';
      }
      Html::file([
         'name'       => "new_$name",
         'onlyimages' => true,
      ]);
      if ($recommendedSize) {
         echo '<small>';
         echo sprintf(t_trademark('Recommended size: %1$s'), $recommendedSize);
         echo '</small>';
      }
      echo '</td>';
   }

   protected function buildCssLine($name, $label) {
      $fullName = "{$name}_css_custom";
      $type = "{$name}_css_type";

      echo "<tr><th colspan='4'>" . sprintf(t_trademark('Custom CSS for %1$s'), $label) . "</th></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo t_trademark('CSS Type') . ':';
      echo "</td>";
      echo "<td colspan='3'>";

      $css_type = [
         'off' => __('Disabled'),
         'css' => 'CSS',
      ];

      if (PluginTrademarkScss::hasScssSuport()) {
         $css_type['scss'] = 'SCSS';
      }
      Dropdown::showFromArray($type, $css_type, ['value' => $this->fields[$type]]);
      echo "</td>";
      echo "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='4' style='max-width: 1000px'>";
      $rand = mt_rand();

      echo sprintf(
         '<textarea %1$s>',
         Html::parseAttributes([
            'id' => $fullName . '_' . $rand,
            'name' => $fullName,
            'class' => 'trademark-codemirror',
         ])
      );
      echo rtrim($this->fields[$fullName]);
      echo str_repeat("\n", 10);
      echo '</textarea>';

      echo Html::scriptBlock('
         jQuery(function() {
            var textarea = document.getElementById("' . $fullName . '_' . $rand . '");
            var editorCode = CodeMirror.fromTextArea(textarea, {
               mode: "text/x-scss",
               lineNumbers: true,
               viewportMargin: Infinity,
               extraKeys: {
                  "Ctrl-Space": "autocomplete"
               },
               foldGutter: true,
               gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"]
            });
            jQuery("#' . $fullName . '_' . $rand . '").data("CodeMirrorInstance", editorCode);

            // Fix bad display of gutter (see https://github.com/codemirror/CodeMirror/issues/3098 )
            setTimeout(function () {editorCode.refresh();}, ' . (500 + self::$_i++ * 100) . ');
         });
      ');
      echo "</td>";
      echo "</tr>\n";
   }

   /**
    * Print the config form for display
    *
    * @return Nothing (display)
    * */
   function showFormDisplay() {
      global $CFG_GLPI;
      if (!Config::canView()) {
         return false;
      }

      $this->getEmpty();

      $this->fields = array_merge($this->fields, Config::getConfigurationValues('trademark'));

      $canedit = Session::haveRight(Config::$rightname, UPDATE);
      if ($canedit) {
         echo "<form name='form' action=\"" . Toolbox::getItemTypeFormURL('Config') . "\" method='post'>";
      }
      echo Html::hidden('config_context', ['value' => 'trademark']);
      echo Html::hidden('config_class', ['value' => __CLASS__]);

      echo "<div class='center' id='tabsbody'>";

      $rand = mt_rand();
      echo "<style>
      .CodeMirror {
         border: 1px solid #eee;
         height: auto;
      }
      .trademark-themeselect-container .select2-selection__rendered,
      .trademark-themeselect-dropdown .select2-results__option {
         height: 80px !important;
         line-height: 80px !important;
      }
      .trademark-themeselect-container .select2-selection__rendered img,
      .trademark-themeselect-dropdown .select2-results__option img {
         width: 170px;
         height: 80px;
         object-fit:cover;
         float: right;
      }
      .trademark-themeselect-container.select2-selection--single {
         min-width: 350px;
         max-width: 350px;
         height: 80px !important;
      }
      .trademark-themeselect-container.select2-selection--single:before {
         line-height: 80px !important;
      }
      .trademark-themeselect-container .select2-selection__arrow {
         height: 79px !important;
      }
      </style>";

      echo "<div id='tabs$rand' class='horizontal'>";
      echo "<ul id='tabspanel' class='nav nav-tabs'>";
      echo "<li class='nav-item'><a href='#tab_trademark_favicon' class='nav-link active'>" . t_trademark('Favicon and Title') . "</a></li>";
      echo "<li class='nav-item'><a href='#tab_trademark_login' class='nav-link'>" . t_trademark('Login Page') . "</a></li>";
      echo "<li class='nav-item'><a href='#tab_trademark_internal' class='nav-link'>" . t_trademark('Internal Page') . "</a></li>";
      echo "</ul>";

      // General
      echo "<div id='tab_trademark_favicon' class='tab-content'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Picture') . "</td>";
      $this->buildPictureLine('favicon_picture', '192px x 192px');
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Title') . "</td>";
      echo "<td colspan='3'>";
      echo sprintf(
         '<input type="text" %1$s />',
         Html::parseAttributes([
            'name' => 'page_title',
            'value' => $this->fields['page_title'],
            'style' => 'width: 98%',
         ])
      );
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . t_trademark('Footer') . "</td>";
      echo "<td colspan='3'>";
      Dropdown::showFromArray('page_footer_display', [
         'original' => __('Original'),
         'hide' => t_trademark('Hide'),
         'custom' => t_trademark('Custom'),
      ], ['value' => $this->fields['page_footer_display']]);
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . t_trademark('Footer text') . "</td>";
      echo "<td colspan='3'>";
      Html::textarea([
         'rand' => $rand,
         'editor_id' => 'text' . $rand,
         'name' => 'page_footer_text',
         'value' => $this->fields['page_footer_text'],
         'style' => 'width: 98%',
         'rows' => 1,
         'enable_richtext' => true,
      ]);
      echo "</td>";
      echo "</tr>\n";

      echo "</table>";
      echo "</div>";

      // Login
      echo "<div id='tab_trademark_login' style='display: none;' class='tab-content'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . t_trademark('Theme') . "</td>";
      echo "<td colspan='2'>";
      echo sprintf(
         '<select %1$s>',
         Html::parseAttributes([
            'name' => 'login_theme',
         ])
      );

      echo '<option value="">' . __('Original') . '</option>';
      foreach (PluginTrademarkTheme::getLoginThemes() as $id => $theme) {
         $attrs = [
            'name' => 'login_theme',
            'value' => $id,
         ];

         if (isset($theme['login-preview']) && $theme['login-preview']) {
            $attrs['data-preview'] = $theme['login-preview'];
         }

         if ($id === $this->fields['login_theme']) {
            $attrs['selected'] = 'selected';
         }

         echo sprintf(
            '<option %1$s>%2$s</option>',
            Html::parseAttributes($attrs),
            htmlentities($theme['name'])
         );
      }
      echo '</select>';
      ?>
      <script type="text/javascript">
         function trademarkFormatThemes(theme) {
            var data = theme && theme.element && theme.element.dataset || {};
            if (!theme.id || !data.preview) {
               return jQuery('<span></span>', {
                  html: '<img src="../plugins/trademark/pics/login.preview.png"/>&nbsp;' + theme.text
               });
            }

            return jQuery('<span></span>', {
               html: '<img src="../plugins/trademark/themes/' + theme.id + '/' + data.preview + '"/>&nbsp;' + theme.text
            });
         }
         jQuery("select[name=login_theme]").select2({
            templateResult: trademarkFormatThemes,
            templateSelection: trademarkFormatThemes,
            width: '100%',
            containerCssClass: 'trademark-themeselect-container',
            dropdownCssClass: 'trademark-themeselect-dropdown',
            escapeMarkup: function(m) {
               return m;
            }
         });
      </script>
      <?php
      echo "</td>";
      echo "<td>";
      echo '<a id="trademark-preview" href="#">' . __('Preview') . '</a>';
      ?>
      <script type="text/javascript">
         jQuery('#trademark-preview').on('click', function() {
            var url = <?php echo json_encode($CFG_GLPI['root_doc'] . "/index.php") ?>;
            url += '?noAUTO=1';
            var theme = jQuery("select[name=login_theme]").val() || 'original';
            url += '&theme=' + theme;
            window.open(url, 'trademark_preview', 'titlebar=0&status=0');
         });
      </script>
      <?php
      echo "</td>";
      echo "</tr>\n";

      $themeInfo = PluginTrademarkTheme::getThemeInfo($this->fields['login_theme']);
      if ($themeInfo && isset($themeInfo['variables'])) {
         foreach ($themeInfo['variables'] as $k => $v) {
            $themeId = $themeInfo['id'];
            $fieldName = "login_theme-$themeId-$k";
            $fieldValue = $v['default'];
            if (isset($this->fields[$fieldName]) && $this->fields[$fieldName]) {
               $fieldValue = $this->fields[$fieldName];
            }
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . t_trademark($v['name']) . "</td>";
            echo "<td>";
            Html::showColorField($fieldName, [
               'value' => $fieldValue
            ]);
            echo "&nbsp;";
            echo Html::getCheckbox([
               'title' => t_trademark('Reset'),
               'name'  => "_blank_$fieldName"
            ]);
            echo "&nbsp;" . t_trademark('Reset');
            echo "</td>";
            echo "<td></td>";
            echo "<td></td>";
            echo "</tr>\n";
         }
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Picture') . "</td>";
      $this->buildPictureLine('login_picture', '145px x 80px');
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Width') . "</td>";
      echo "<td>";
      echo sprintf(
         '<input type="text" %1$s />',
         Html::parseAttributes([
            'name' => 'login_picture_max_width',
            'value' => $this->fields['login_picture_max_width'],
         ])
      );
      echo "</td>";
      echo "<td>" . __('Height')  . "</td>";
      echo "<td>";
      echo sprintf(
         '<input type="text" %1$s />',
         Html::parseAttributes([
            'name' => 'login_picture_max_height',
            'value' => $this->fields['login_picture_max_height'],
         ])
      );
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . t_trademark('Background picture') . "</td>";
      $this->buildPictureLine('login_background_picture', '1920px x 1080px');
      echo "</tr>\n";

      // Custom CSS Login
      $this->buildCssLine('login', t_trademark('Login Page'));
      echo "</table>";
      echo "</div>";

      // Internal Page
      echo "<div id='tab_trademark_internal' style='display: none;' class='tab-content'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Picture') . "</td>";
      $this->buildPictureLine('internal_picture', '100px x 55px');
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Width') . "</td>";
      echo "<td>";
      echo sprintf(
         '<input type="text" %1$s />',
         Html::parseAttributes([
            'name' => 'internal_picture_width',
            'value' => $this->fields['internal_picture_width'],
         ])
      );
      echo "</td>";
      echo "<td>" . __('Height')  . "</td>";
      echo "<td>";
      echo sprintf(
         '<input type="text" %1$s />',
         Html::parseAttributes([
            'name' => 'internal_picture_height',
            'value' => $this->fields['internal_picture_height'],
         ])
      );
      echo "</td>";
      echo "</tr>\n";

      // Custom CSS Internal
      $this->buildCssLine('internal', t_trademark('Internal Page'));
      echo "</table>";
      echo "</div>";

      echo "</div>";

      echo Html::scriptBlock(<<<JAVASCRIPT
         const tabElements = jQuery('#tabs$rand .nav-link');
         const panelElements = jQuery('#tabs$rand [id^=tab_trademark_]');
         let activeIndex = 0;

         tabElements.each(function (index, tab) {
            tab.addEventListener("click", function (event) {
               setActiveTab(index);
            });
         });

         function setActivePanel(index) {
               jQuery(panelElements[activeIndex]).hide()
               panelElements[activeIndex].tabIndex = -1;

               jQuery(panelElements[index]).show()
               panelElements[index].tabIndex = 0;
               var editor = jQuery(panelElements[index]).find('.trademark-codemirror').data('CodeMirrorInstance');
               if (editor) {
                  editor.refresh();
               }
         }

         function setActiveTab(index) {
            // Make currently active tab inactive
            jQuery(tabElements[activeIndex]).removeClass("active");
            tabElements[activeIndex].tabIndex = -1;

            // Set the new tab as active
            jQuery(tabElements[index]).addClass("active");
            tabElements[index].tabIndex = 0;

            setActivePanel(index);
            activeIndex = index;
         }
         JAVASCRIPT);

      if ($canedit) {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'>";
         echo "<td colspan='4' class='center'>";
         echo "<input type='submit' name='update' class='submit' value=\"" . _sx('button', 'Save') . "\">";
         echo "</td></tr>";
         echo "</table>";
      }

      Html::closeForm();
   }
}
