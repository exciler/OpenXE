<?php

function xentral_autoloader($class) {
  $classes = array(
    'PDF'=>__DIR__.'/www/lib/pdf/fpdf.php',
  );
  if(isset($classes[$class]) && is_file($classes[$class]))
  {
    include_once $classes[$class];
    return;
  }
  if($class === 'FPDFWAWISION')
  {
    if(is_file(__DIR__.'/conf/user_defined.php'))
    {
      include_once __DIR__.'/conf/user_defined.php';
    }
    if(!defined('USEFPDF3')){
      define('USEFPDF3', true);
    }
    if(defined('USEFPDF3') && USEFPDF3)
    {
      if(is_file(__DIR__ .'/www/lib/pdf/fpdf_3.php'))
      {
        require_once __DIR__ .'/www/lib/pdf/fpdf_3.php';
      }else {
        require_once __DIR__ .'/www/lib/pdf/fpdf.php';
      }
    }
    else if(defined('USEFPDF2') && USEFPDF2)
    {
      if(is_file(__DIR__ .'/www/lib/pdf/fpdf_2.php'))
      {
        require_once __DIR__ .'/www/lib/pdf/fpdf_2.php';
      }else {
        require_once __DIR__ .'/www/lib/pdf/fpdf.php';
      }
    } else {
      require_once __DIR__ .'/www/lib/pdf/fpdf.php';
    }
    return;
  }
}

spl_autoload_register('xentral_autoloader');
