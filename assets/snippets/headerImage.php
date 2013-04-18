<?php
$path = 'assets/img/slider_pics/';

if ($handle = opendir($modx->config['base_path'] . $path)) {
  $images = array();

  /* This is the correct way to loop over the directory. */
  while (false !== ($entry = readdir($handle))) {
    if ($entry !== '.' && $entry !== '..') {
      $images[] = $entry;
    }
  }

  closedir($handle);

  $idx = rand(0, count($images)-1);
  return $path . $images[$idx];
}