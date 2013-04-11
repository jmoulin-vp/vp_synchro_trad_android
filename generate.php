<?php
/**
 * User: mfaye
 * Date: 09/04/13
 * Time: 10:32
 */

$candidateFileList = glob('android_translations_*\\.csv');
$lastDate = '';
$finalCandidate = '';
foreach ($candidateFileList as $fileName)
{
  $matchList = array();
  if (preg_match('~android_translations_([0-9]{4}-[0-9]{2}-[0-9]{2})_([0-9]{2}-[0-9]{2})\\.csv~', $fileName, $matchList) == 1)
  {
    list($year, $month, $day) = explode('-', $matchList[1]);
    list($hour, $minute) = explode('-', $matchList[2]);
    $timestamp = mktime($hour, $minute, 0, $month, $day, $year);
    if ($timestamp > $lastDate)
    {
      $finalCandidate = $fileName;
    }
  }
}
if ($finalCandidate != '')
{
  $mFileHandler = fopen($finalCandidate, 'r');
  if ($mFileHandler !== false)
  {
    $rowNumber = 1;
    $languageTranslations = array();
    $languageMap = array();
    while (($data = fgetcsv($mFileHandler, 1000, ',', '"')) !== false)
    {
      if ($rowNumber == 1)
      {
        $isFirst = true;
        foreach ($data as $header)
        {
          if ($isFirst)
          {
            $isFirst = false;
          }
          else
          {
            $languageTranslations[$header] = array();
            $languageMap[] = $header;
          }
        }
      }
      else
      {
        $currentKeyWord = trim($data[0]);
        for ($i = 1; $i < count($data); $i++)
        {
          $languageMapIndex = $i - 1;
          $translation = str_replace("\n", '\n', $data[$i]);
          $translation = trim($translation);
          if ($translation != '')
          {
            $languageTranslations[$languageMap[$languageMapIndex]][$currentKeyWord] = $translation;
          }
        }
      }
      $rowNumber++;
    }
    fclose($mFileHandler);
    foreach ($languageTranslations as $locale => $keyList)
    {
      if (count($keyList) > 0)
      {
        $file = '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL;
        $file .= '<resources>' . PHP_EOL;
        foreach ($keyList as $keyName => $translation)
        {
          $file .= '    <string name="' . str_replace('.', '_', $keyName) . '">' . $translation . '</string>' . PHP_EOL;

        }
        $file .= '</resources>' . PHP_EOL;
        $fileName = 'strings_' . $locale . '.xml';
        if (file_put_contents($fileName, $file) === false)
        {
          echo 'Error when writing [' . $fileName . ']' . PHP_EOL;
          exit(1);
        }
      }
    }
  }
  else
  {
    echo 'Could not open [' . $finalCandidate . ']' . PHP_EOL;
    exit(1);
  }
}
else
{
  echo 'There is no file to transform' . PHP_EOL;
  exit(1);
}