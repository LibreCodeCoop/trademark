<?php

class PluginTrademarkFileVersion {

   private $_file = "";
   private $_initialized = false;

   public function __construct($file = "") {
      $this->_file = $file;
   }

   public function __toString() {
      // First call is inside of "file_exists", so it is necessary to return a valid file
      if (!$this->_initialized) {
         $this->_initialized = true;
         return $this->_file;
      }

      // Second call is inside concatenation

      $timestamp = PluginTrademarkToolbox::getTimestamp();

      return $this->_file . "?_=" . $timestamp;
   }
}
