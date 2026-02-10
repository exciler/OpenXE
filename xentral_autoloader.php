<?php

function xentral_autoloader($class) {
  $classes = array(
    'erpooSystem'=>__DIR__.'/www/eproosystem.php',
    'erpAPICustom'=>__DIR__.'/www/lib/class.erpapi_custom.php',
    'RemoteCustom'=>__DIR__.'/www/lib/class.remote_custom.php',
    'HttpClient'=>__DIR__.'/www/lib/class.httpclient.php',
    'DatabaseUpgrade'=>__DIR__.'/phpwf/plugins/class.databaseupgrade.php',
    'PDF_EPS'=>__DIR__.'/www/lib/pdf/fpdf_final.php',
    'SuperFPDF'=>__DIR__.'/www/lib/dokumente/class.superfpdf.php',
    'Briefpapier'=>__DIR__.'/www/lib/dokumente/class.briefpapier.php',
    'PDF'=>__DIR__.'/www/lib/pdf/fpdf.php',
    'SpeditionPDF'=>__DIR__.'/www/lib/dokumente/class.spedition.php',
    'EtikettenPDF'=>__DIR__.'/www/lib/dokumente/class.etiketten.php',
    'Dokumentenvorlage'=>__DIR__.'/www/lib/dokumente/class.dokumentenvorlage.php',
    'SepaMandat'=>__DIR__.'/www/lib/dokumente/class.sepamandat.php',
    'WikiParser'=>__DIR__.'/www/plugins/class.wikiparser.php',
    'IndexPoint'=>__DIR__.'/www/plugins/class.wikiparser.php',
    'phpprint'=>__DIR__.'/www/plugins/php-print.php',
    'LiveimportBase'=>__DIR__.'/www/plugins/liveimport/LiveimportBase.php',
  );
  if(isset($classes[$class]) && is_file($classes[$class]))
  {
    include_once $classes[$class];
    return;
  }
  if($class === 'AES')
  {
    if(version_compare(phpversion(),'7.1', '>=') && is_file(__DIR__.'/www/lib/class.aes2.php')){
        include __DIR__.'/www/lib/class.aes2.php';
    } else{
      include __DIR__ . '/www/lib/class.aes.php';
    }
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
  if($class === 'BriefpapierCustom')
  {
    if(is_file(__DIR__.'/www/lib/dokumente/class.briefpapier_custom.php'))
    {
      include __DIR__.'/www/lib/dokumente/class.briefpapier_custom.php';
    }else{
      class BriefpapierCustom extends Briefpapier
      {

      }
    }
  }

  if(substr($class, -3) === 'PDF') {
    $file = __DIR__.'/www/lib/dokumente/class.'.strtolower(substr($class,0,-3)).'.php';
    if(file_exists($file)) {
      include $file;
    }
    elseif(file_exists(__DIR__.'/www/lib/dokumente/class.'.strtolower($class).'.php')) {
      include __DIR__.'/www/lib/dokumente/class.'.strtolower($class).'.php';
    }
  }
  elseif(substr($class, -9) === 'PDFCustom') {
    $file = __DIR__.'/www/lib/dokumente/class.'.strtolower(substr($class,0,-9)).'_custom.php';
    if(file_exists($file)) {
      include $file;
    }
  }
}

spl_autoload_register('xentral_autoloader');
