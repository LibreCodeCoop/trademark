<?php

use Symfony\Component\Finder\Finder;

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks {

   protected $name = "trademark";
   protected $issues = "https://github.com/edgardmessias/glpi-trademark/issues";
   protected $locale_keywords = "t_trademark";

   protected function getLocaleFiles() {
      $finder = new Finder();
      $finder
         ->files()
         ->name('*.po')
         ->in('locales');

      $files = [];
      foreach ($finder as $file) {
         $files[] = str_replace('\\', '/', $file->getRelativePathname());
      }

      return $files;
   }

   public function compile_locales() {
      $files = $this->getLocaleFiles();

      foreach ($files as $file) {
         $lang = basename($file, ".po");

         $this->taskExec('msgfmt')->args([
            "locales/$lang.po",
            "-o",
            "locales/$lang.mo",
         ])->run();
      }
   }

   public function update_locales() {
      $finder = new Finder();
      $finder
         ->files()
         ->name('*.php')
         ->in(__DIR__)
         ->exclude([
            'vendor'
         ])
         ->sortByName();

      if (!$finder->hasResults()) {
         return false;
      }

      $args = [];

      foreach ($finder as $file) {
         $args[] = str_replace('\\', '/', $file->getRelativePathname());
      }

      $args[] = '-D';
      $args[] = '.';
      $args[] = '-o';
      $args[] = "locales/{$this->name}.pot";
      $args[] = '-L';
      $args[] = 'PHP';
      $args[] = '--add-comments=TRANS';
      $args[] = '--from-code=UTF-8';
      $args[] = '--force-po';
      $args[] = "--keyword={$this->locale_keywords}";
      $args[] = "--package-name={$this->name}";

      if ($this->issues) {
         $args[] = "--msgid-bugs-address={$this->issues}";
      }

      try {
         $content = file_get_contents('setup.php');
         $name = 'PLUGIN_' . strtoupper($this->name) . '_VERSION';
         preg_match("/'$name',\s*'([\w\.]+)'/", $content, $matches);
         $args[] = '--package-version=' . $matches[1];
      } catch (\Exception $ex) {
         echo $ex->getMessage();
      }

      putenv("LANG=C");

      $this->taskExec('xgettext')->args($args)->run();

      $this->taskReplaceInFile("locales/{$this->name}.pot")
         ->from('CHARSET')
         ->to('UTF-8')
         ->run();

      $this->taskExec('msginit')->args([
         '--no-translator',
         '-i',
         "locales/{$this->name}.pot",
         '-l',
         'en_GB.UTF8',
         '-o',
         'locales/en_GB.po',
      ])->run();

      $files = $this->getLocaleFiles();

      foreach ($files as $file) {
         $lang = basename($file, ".po");

         if ($lang === "en_GB") {
            continue;
         }

         $this->taskExec('msgmerge')->args([
            "--update",
            "locales/$lang.po",
            "locales/{$this->name}.pot",
            "--lang=$lang",
            "--backup=off",
         ])->run();
      }

      $this->compile_locales();
   }

   public function build() {
      $this->_remove(["$this->name.zip", "$this->name.tgz"]);

      $this->compile_locales();
      $this->compile_themes();

      $tmpPath = $this->_tmpDir();

      $exclude = glob(__DIR__ . '/.*');
      $exclude[] = 'plugin.xml';
      $exclude[] = 'screenshots';
      $exclude[] = 'tools';
      $exclude[] = 'vendor';
      $exclude[] = "$this->name.zip";
      $exclude[] = "$this->name.tgz";

      $this->taskCopyDir([__DIR__ => $tmpPath])
         ->exclude($exclude)
         ->run();

      $composer_file = "$tmpPath/composer.json";
      if (file_exists($composer_file)) {
         $hasDep = false;
         try {
            $data = json_decode(file_get_contents($composer_file), true);
            $hasDep = isset($data['require']) && count($data['require']) > 0;
         } catch (\Exception $ex) {
            $hasDep = true;
         }

         if ($hasDep) {
            $this->taskComposerInstall()
               ->workingDir($tmpPath)
               ->noDev()
               ->run();
         }
      }

      $this->_remove("$tmpPath/composer.lock");

      // Pack
      $this->taskPack("$this->name.zip")
         ->addDir($this->name, $tmpPath)
         ->run();

      $this->taskPack("$this->name.tgz")
         ->addDir($this->name, $tmpPath)
         ->run();
   }

   public function compile_themes() {
      $finder = new Finder();
      $finder
         ->files()
         ->name('*.scss')
         ->in('themes');

      $files = [];
      foreach ($finder as $file) {
         $fullPath = $file->getPath();
         // var_dump($fullPath);die;
         $fullPath .= '/' . $file->getFilenameWithoutExtension();
         $fullPath .= '.css';
         $files[$file->getRealPath()] = $fullPath;
      }

      $this->taskScss($files)->run();
   }
}
