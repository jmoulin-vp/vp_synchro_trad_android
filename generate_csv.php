<?php
/**
 * User: mfaye
 * Date: 15/04/13
 * Time: 10:57
 */

require_once('include/function.php');

$filename = 'strings';

$document = simplexml_load_file($filename . '.xml');

$stringsXmlTranslations = array();
echo 'Parsing strings.xml';
/**
 * @var $child SimpleXMLElement
 */
foreach($document as $child){
  if($child->getName() == 'string'){
    $keyName = $child->attributes()->{"name"}->__toString();
    $stringsXmlTranslations[$keyName] = $child->__toString();
  }elseif($child->getName() == 'plurals'){
    $singleName = $child->attributes()->{"name"};
    foreach($child->item as $item){
      if($item->attributes()->{"quantity"} == 'one'){
        $stringsXmlTranslations[$singleName->__toString()] = $item->__toString();
      }else{
        $stringsXmlTranslations[$singleName.'s'] = $item->__toString();
      }
    }
  }else{
    echo 'Unknown node type : ' . $child->getName() . PHP_EOL;
  }
}
$cultureList = array('br','it','uk','es','pl');
ksort($stringsXmlTranslations);
echo 'Done strings.xml' . PHP_EOL;
echo 'Parsing CSV file from google Doc' . PHP_EOL;
$googleDocsCsv = parseAndroidCsvTranslation();
echo 'Done parsing CSV file from google Doc' . PHP_EOL;
echo 'Comparing file from application and from google doc' . PHP_EOL;
$finalCsv = array();
$finalCsvHeader = array('keyword', 'fr', 'br', 'it', 'uk', 'es', 'pl');
$finalCsv[] = $finalCsvHeader;


foreach($stringsXmlTranslations as $keyName => $frenchTranslation){
  if(!array_key_exists($keyName, $googleDocsCsv['fr'])){
    //La clé n'est pas dans le fichier Google Doc
    //Je rajoute la clé dans le google doc avec la trad FR et une trad vide pour les autres pays
    $googleDocsCsv['fr'][$keyName] = $frenchTranslation;
    foreach($cultureList as $culture){
      $googleDocsCsv[$culture][$keyName] = '';
    }
  }else{
    foreach($cultureList as $culture){
      if(!array_key_exists($keyName, $googleDocsCsv[$culture])){
        $googleDocsCsv[$culture][$keyName] = '';
      }
    }
  }
  $finalCsvRow = array();
  $finalCsvRow[] = $keyName;
  for($i = 1; $i < count($finalCsvHeader); $i++){
    $finalCsvRow[] = $googleDocsCsv[$finalCsvHeader[$i]][$keyName];
  }
  $finalCsv[] = $finalCsvRow;
}
echo 'Generating synced CSV file' . PHP_EOL;
$csvFilename = 'android_synchro_' . date('Y-m-d_H-i-s') . '.csv';
$file = fopen($csvFilename, 'w+');
if($file !== false){
  foreach($finalCsv as $csvRow){
    if(fputcsv($file, $csvRow) === false){
//      echo 'Could not write [' . print_r($csvRow, true) . ']' . PHP_EOL;
    }
  }
  fclose($file);
}
echo 'Done generating synced CSV file' . PHP_EOL;