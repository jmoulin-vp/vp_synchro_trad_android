<?php
/**
 * User: mfaye
 * Date: 09/04/13
 * Time: 10:32
 */


require_once('include/function.php');

$resDir = '/home/FRANCE-VPG/user/www/dev_vp_android/res/';
$defaultBu = 'fr';

parseAndroidCsvTranslation(true);

$xmlToDeport = glob('strings_*.xml');
$pattern = '~strings_([a-z]{2})\\.xml~';
$buToCulture = array();
$buToCulture['fr'] = 'fr';
$buToCulture['br'] = 'pt-rBR';
$buToCulture['pl'] = 'pl';
$buToCulture['it'] = 'it';
$buToCulture['es'] = 'es';
$buToCulture['uk'] = 'en';

foreach($xmlToDeport as $fileName){
  $matchList = array();
  if(preg_match($pattern, $fileName, $matchList) == 1){
    $currentBu = $matchList[1];
    $destinationFile = $resDir . 'values-' . $buToCulture[$currentBu] . '/strings.xml';
    if(file_exists($destinationFile)){
      $saveFileName = './save_' . $currentBu . '_' . date('Y-m-d_h-i-s');
      if(rename($destinationFile, $saveFileName) ===  false){
        echo 'Unable to move [' . $destinationFile . '] to [' . $saveFileName . ']' . PHP_EOL;
        exit(1);
      }
    }
    if(copy($fileName, $destinationFile) ===  false){
      echo 'Could not copy [' . $fileName . '] to [' . $destinationFile . ']' . PHP_EOL;
      exit(1);
    }
    if($currentBu == $defaultBu){
      $destinationFile = $resDir . 'values/strings.xml';
      if(file_exists($destinationFile)){
        $saveFileName = './save_' . date('Y-m-d_h-i-s');
        if(rename($destinationFile, $saveFileName) ===  false){
          echo 'Unable to move [' . $destinationFile . '] to [' . $saveFileName . ']' . PHP_EOL;
          exit(1);
        }
      }
      if(copy($fileName, $destinationFile) ===  false){
        echo 'Could not copy [' . $fileName . '] to [' . $destinationFile . ']' . PHP_EOL;
        exit(1);
      }
    }
  }else{
    echo 'huh ?! [' . $fileName . ']' . PHP_EOL;
  }
}
