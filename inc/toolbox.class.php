<?php

class PluginTrademarkToolbox {

   static public function getTimestamp() {
      global $GLPI_CACHE;

      if (!$GLPI_CACHE) {
         return PluginTrademarkConfig::getConfig('timestamp', time());
      }

      $timestamp = $GLPI_CACHE->get('trademark_timestamp');
      if (!$timestamp) {
         $timestamp = time();
         $GLPI_CACHE->set('trademark_timestamp', $timestamp);
      }
      return $timestamp;
   }

   static public function setTimestamp($timestamp = null) {
      global $GLPI_CACHE;

      if (!$timestamp) {
         $timestamp = time();
      }

      if (!$GLPI_CACHE) {
         Config::setConfigurationValues('trademark', [
            'timestamp' => $timestamp,
         ]);
         return;
      }

      $GLPI_CACHE->set('trademark_timestamp', $timestamp);
   }

   static public function startsWith($haystack, $needle) {
      $length = strlen($needle);
      return (substr($haystack, 0, $length) === $needle);
   }

   static function getPictureUrl($path) {
      global $CFG_GLPI;

      $path = Html::cleanInputText($path); // prevent xss

      if (empty($path)) {
         return null;
      }

      return Html::getPrefixedUrl('/plugins/trademark/front/picture.send.php?path=' . $path);
   }

   static public function savePicture($src, $uniq_prefix = null) {
      $basePath = GLPI_PLUGIN_DOC_DIR . "/trademark";

      if (function_exists('Document::isPicture') && !Document::isPicture($src)) {
         return false;
      }

      $filename     = uniqid($uniq_prefix);
      $ext          = pathinfo($src, PATHINFO_EXTENSION);
      $subdirectory = substr($filename, -2); // subdirectory based on last 2 hex digit

      $i = 0;
      do {
         // Iterate on possible suffix while dest exists.
         // This case will almost never exists as dest is based on an unique id.
         $dest = $basePath
         . '/' . $subdirectory
         . '/' . $filename . ($i > 0 ? '_' . $i : '') . '.' . $ext;
         $i++;
      } while (file_exists($dest));

      if (!is_dir($basePath . '/' . $subdirectory) && !mkdir($basePath . '/' . $subdirectory, 0777, true)) {
         return false;
      }

      if (!rename($src, $dest)) {
         return false;
      }

      return substr($dest, strlen($basePath . '/')); // Return dest relative to GLPI_PICTURE_DIR
   }

   public static function deletePicture($path) {
      $basePath = GLPI_PLUGIN_DOC_DIR . "/trademark";
      $fullpath = $basePath . '/' . $path;

      if (!file_exists($fullpath)) {
         return false;
      }

      $fullpath = realpath($fullpath);
      if (!static::startsWith($fullpath, realpath($basePath))) {
         return false;
      }

      return @unlink($fullpath);
   }

   /**
    * Resize a picture to the new size
    * Always produce a JPG file!
    *
    * @since 0.85
    *
    * @param string  $source_path   path of the picture to be resized
    * @param string  $dest_path     path of the new resized picture
    * @param integer $new_width     new width after resized (default 71)
    * @param integer $new_height    new height after resized (default 71)
    * @param integer $img_y         y axis of picture (default 0)
    * @param integer $img_x         x axis of picture (default 0)
    * @param integer $img_width     width of picture (default 0)
    * @param integer $img_height    height of picture (default 0)
    * @param integer $max_size      max size of the picture (default 500, is set to 0 no resize)
    *
    * @return boolean
    **/
   static function resizePicture(
      $source_path,
      $dest_path,
      $new_width = 71,
      $new_height = 71,
      $img_y = 0,
      $img_x = 0,
      $img_width = 0,
      $img_height = 0,
      $max_size = 500
   ) {

      //get img informations (dimensions and extension)
      $img_infos  = getimagesize($source_path);
      if (empty($img_width)) {
         $img_width  = $img_infos[0];
      }
      if (empty($img_height)) {
         $img_height = $img_infos[1];
      }
      if (empty($new_width)) {
         $new_width  = $img_infos[0];
      }
      if (empty($new_height)) {
         $new_height = $img_infos[1];
      }

      // Image max size is 500 pixels : is set to 0 no resize
      if ($max_size > 0) {
         if (($img_width > $max_size)
            || ($img_height > $max_size)
         ) {
            $source_aspect_ratio = $img_width / $img_height;
            if ($source_aspect_ratio < 1) {
               $new_width  = $max_size * $source_aspect_ratio;
               $new_height = $max_size;
            } else {
               $new_width  = $max_size;
               $new_height = $max_size / $source_aspect_ratio;
            }
         }
      }

      $img_type = $img_infos[2];

      switch ($img_type) {
         case IMAGETYPE_BMP:
            $source_res = imagecreatefromwbmp($source_path);
            break;

         case IMAGETYPE_GIF:
            $source_res = imagecreatefromgif($source_path);
            break;

         case IMAGETYPE_JPEG:
            $source_res = imagecreatefromjpeg($source_path);
            break;

         case IMAGETYPE_PNG:
            $source_res = imagecreatefrompng($source_path);
            break;

         default:
            return false;
      }

      //create new img resource for store thumbnail
      $source_dest = imagecreatetruecolor($new_width, $new_height);

      // set transparent background for PNG/GIF
      if ($img_type === IMAGETYPE_GIF || $img_type === IMAGETYPE_PNG) {
         imagecolortransparent($source_dest, imagecolorallocatealpha($source_dest, 0, 0, 0, 127));
         imagealphablending($source_dest, false);
         imagesavealpha($source_dest, true);
      }

      //resize image
      imagecopyresampled(
         $source_dest,
         $source_res,
         0,
         0,
         $img_x,
         $img_y,
         $new_width,
         $new_height,
         $img_width,
         $img_height
      );

      //output img
      $result = null;
      switch ($img_type) {
         case IMAGETYPE_GIF:
         case IMAGETYPE_PNG:
            $result = imagepng($source_dest, $dest_path);
            break;

         case IMAGETYPE_JPEG:
         default:
            $result = imagejpeg($source_dest, $dest_path, 90);
            break;
      }
      return $result;
   }
}
