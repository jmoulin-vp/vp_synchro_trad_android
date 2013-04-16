<?php
/**
 * User: mfaye
 * Date: 15/04/13
 * Time: 14:57
 */

/**
 * @internal param $lastDate
 * @return string
 */
function findFileToParse()
{
  $finalCandidate = '';
  $lastDate = 0;
  $candidateFileList = glob('android_translations_*\\.csv');
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
        $lastDate = $timestamp;
        $finalCandidate = $fileName;
      }
    }
  }

  return $finalCandidate;
}

/**
 * @param $data
 * @param $languageCodePattern
 * @param $languageTranslations
 * @param $languageMap
 */
function setLanguageDefinition($data, $languageCodePattern, &$languageTranslations, &$languageMap)
{
  $isFirst = true;
  foreach ($data as $header)
  {
    if ($isFirst)
    {
      $isFirst = false;
    }
    elseif (preg_match($languageCodePattern, $header) == 1)
    {
      $languageTranslations[$header] = array();
      $languageMap[] = $header;
    }
    else
    {
      echo 'Not a valid header[' . $header . ']' . PHP_EOL;
    }
  }
}


/**
 * @param $data
 * @param $seenKeywordList
 * @param $languageMap
 * @param $invalidXmlChars
 * @param $languageTranslations
 * @return array
 */
function handleTranslation($data, $seenKeywordList, $languageMap, $invalidXmlChars, $languageTranslations, $isForFile = true)
{
  $currentKeyword = str_replace('.', '_', trim($data[0]));
  if ($currentKeyword != '' && !in_array($currentKeyword, $seenKeywordList))
  {
    $seenKeywordList[] = $currentKeyword;
    for ($i = 1; $i < count($data); $i++)
    {
      $languageMapIndex = $i - 1;
      if (isset($languageMap[$languageMapIndex]))
      {
        $translation = str_replace("\n", '\n', $data[$i]);
        $translation = trim($translation);
        $needCData = false;
        foreach ($invalidXmlChars as $char)
        {
          if (strpos($translation, $char) !== false)
          {
            $needCData = true;
            break;
          }
        }
        if ($needCData && $isForFile)
        {
          $translation = '<![CDATA[' . $translation . ']]>';
        }
        if ($translation != '')
        {
          $languageTranslations[$languageMap[$languageMapIndex]][$currentKeyword] = $translation;
        }
      }
      else
      {
        if ($data[$i] != '')
        {
          echo 'Ignoring [' . $data[$i] . '] cause it can not be associated to a language.' . PHP_EOL;
        }
      }
    }

  }
  else
  {
    if ($currentKeyword != '')
    {
      echo '[' . $currentKeyword . '] is declared more than once.' . PHP_EOL;

    }

  }

  return $languageTranslations;
}


/**
 * @param $languageTranslations
 */
function generateFiles($languageTranslations)
{
  foreach ($languageTranslations as $locale => $keyList)
  {
    $currentKeyList = $keyList;
    $currentKeyList = findPlurals($currentKeyList);

    if (count($keyList) > 0)
    {
      $file = '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL;
      $file .= '<resources>' . PHP_EOL;
      foreach ($currentKeyList as $keyName => $translation)
      {
        if (is_array($translation))
        {
          $file .= '    <plurals name="' . $keyName . '">' . PHP_EOL;
          foreach ($translation as $quantity => $translate)
          {
            $finalTranslation = str_replace("\'", "'", $translate);
            $finalTranslation = str_replace("'", "\'", $finalTranslation);
            $file .= '        <item quantity="' . $quantity . '">' . $finalTranslation . '</item>' . PHP_EOL;
          }
          $file .= '    </plurals>' . PHP_EOL;
        }else{
          $finalTranslation = str_replace("\'", "'", $translation);
          $finalTranslation = str_replace("'", "\'", $finalTranslation);
          $file .= '    <string name="' . $keyName . '">' . $finalTranslation . '</string>' . PHP_EOL;
        }

      }
      $file .= '</resources>' . PHP_EOL;
      $fileName = 'strings_' . $locale . '.xml';
      if (file_exists($fileName))
      {
        unlink($fileName);
      }
      if (file_put_contents($fileName, $file) === false)
      {
        echo 'Error when writing [' . $fileName . ']' . PHP_EOL;
        exit(1);
      }
    }
  }
}

function findPlurals($currentKeyList)
{
  ksort($currentKeyList);
  $previousKey = '';
  foreach($currentKeyList as $keyName => $translation){
    if(str_replace($previousKey, '', $keyName) == 's'){
      $singleTranslation = $currentKeyList[$previousKey];
      $currentKeyList[$previousKey] = array();
      $currentKeyList[$previousKey]['one'] = $singleTranslation;
      $currentKeyList[$previousKey]['other'] = $translation;
      unset($currentKeyList[$keyName]);
    }else{
      $previousKey = $keyName;
    }
  }

  return $currentKeyList;
}

function parseAndroidCsvTranslation($generateFile = false)
{
  $invalidXmlChars = array('&');
  $seenKeywordList = array();

  $finalCandidate = findFileToParse();
  $languageTranslations = array();
  if ($finalCandidate != '')
  {
    $mFileHandler = fopen($finalCandidate, 'r');
    if ($mFileHandler !== false)
    {
      $rowNumber = 1;
      $languageMap = array();
      $languageCodePattern = '~^[a-z]{2}$~';
      while (($data = fgetcsv($mFileHandler, 1000, ',', '"')) !== false)
      {
        if ($rowNumber == 2)
        {
          setLanguageDefinition($data, $languageCodePattern, $languageTranslations, $languageMap);
        }
        elseif ($rowNumber >= 6)
        {
          $languageTranslations = handleTranslation($data, $seenKeywordList, $languageMap, $invalidXmlChars, $languageTranslations, $generateFile);
        }
        else
        {
          echo 'Not reading line content' . PHP_EOL;
        }
        $rowNumber++;
      }
      fclose($mFileHandler);
      if($generateFile){
        generateFiles($languageTranslations);
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

  return $languageTranslations;
}