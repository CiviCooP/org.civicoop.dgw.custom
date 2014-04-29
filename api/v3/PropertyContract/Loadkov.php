<?php

/**
 * PropertyContract.Loadkov API
 * (redesign with BOS1402567)
 * this api loads data from csv files into tables in CiviCRM database
 * api CreateKov process that data into CiviCRM meaningful data
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 22 Apr 2014
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception when source file does not exist
 */
function civicrm_api3_property_contract_loadkov() {
  set_time_limit(0);
  /*
   * retrieve file name (incl. path) from dgw_config table
   */
  $sourceFile = CRM_Utils_DgwUtils::getDgwConfigValue("kov bestandsnaam")."kov_".date("Ymd").".csv";
  /*
   * check if source file exists
   */
  if (!file_exists($sourceFile)) {
    throw new CiviCRM_API3_Exception("Bronbestand $sourceFile niet gevonden, laden koopovereenkomsten mislukt");
  } else {
    /*
     * import data and load header
     */
    $dataLoaded = _load_source_data($sourceFile);
    if ($dataLoaded == FALSE) {
        throw new API_Exception("Kon brongegevens niet importeren, laden koopovereenkomsten mislukt");
    } else {
      $returnValues = array(
        'is_error'  =>  0,
        'message'   =>  'Koopovereenkomsten succesvol geladen in tijdelijke tabellen.'
      );
      unlink($sourceFile);
      return civicrm_api3_create_success($returnValues, array(), 'PropertyContract', 'Loadkov');
    }
  }
}
/**
 * Function to load data from import file
 * 
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 22 Apr 2014
 * @param string $sourceFile file holding data that has to be imported
 * @return boolean
 */
function _load_source_data($sourceFile) {
  $csvSeparator = _check_separator($sourceFile);
  
  $sourceData = fopen($sourceFile, 'r');
  while ($sourceRow = fgetcsv($sourceData, 0, $csvSeparator)) {
    if (!_check_empty_sourcerow($sourceRow)) {
      $insFields = _get_import_record($sourceRow);
      if (!empty($insFields)) {
        $insImport = "INSERT INTO kov_import SET ".implode(", ", $insFields);
        CRM_Core_DAO::executeQuery($insImport);
      }
    }
  }
  fclose($sourceData);
  
  $kovHdrInsert = "INSERT INTO kov_header (SELECT DISTINCT(kov_nr), vge_nr, corr_naam, 
    ov_datum, vge_adres, type, prijs, notaris, tax_waarde, taxateur, tax_datum, 
    bouwkundige, bouw_datum, definitief FROM kov_import)";
  CRM_Core_DAO::executeQuery($kovHdrInsert);
  return TRUE;
}
/**
 * Function to specifically reformat the date from the imported CSV format
 * (3-Mei-2013) to international format (3-May-2013) for the
 * months March, May and October (Maa, Mei, Okt)
 * 
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 10 Mar 2014
 * @param string $indDate
 * @return string $outDate
 * @access public
 */
function _reformat_date($inDate) {
  $outDate = $inDate;
  $months = array(
    "Maa"   => "Mar",
    "Mei"   => "May",
    "Okt"   => "Oct"
  );
  if (empty($inDate)) {
    return $outDate;
  }
  $parts = explode("-", $inDate);
  if (!isset($parts[1]) || !isset($parts[2])) {
    return $outDate;
  } else {
    $newMonth = CRM_Utils_Array::value($parts[1], $months);
    if (!empty($newMonth)) {
      $outDate = $parts[0]."-".$newMonth."-".$parts[2];
    }
  }
  return $outDate;
}
/**
 * Function to check which csv separator to use. Assumption is that
 * separator is ';', if reading first record return record with only 
 * 1 field, then ',' should be used
 * 
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 22 Apr 2014
 */
function _check_separator($sourceFile) {
  $testSeparator = fopen($sourceFile, 'r');
  /*
   * first test if semi-colon or comma separated, based on assumption that
   * it is semi-colon and it should be comma if I only get one record then
   */
  if ($testRow = fgetcsv($testSeparator, 0, ';')) {
    if (!isset($testRow[1])) {
      $csvSeparator = ",";
    } else {
      $csvSeparator = ";";
    }
  }
  fclose($testSeparator);
  return $csvSeparator;  
}
/**
 * Function to get the import record
 * 
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 24 Apr 2014
 * @param array $sourceRow
 * @return array $result
 */
function _get_import_record($sourceRow) {
  $result = array();
  if (empty($sourceRow)) {
    return $result;
  }
  foreach ($sourceRow as $sourceFieldId => $sourceValue) {
    $line = _build_import_insert($sourceFieldId, $sourceValue);
    if (!empty($line)) {
      $result[] = $line;
    }
  }
  return $result;
}
/**
 * Function to build insert line for sql based in incoming id and value
 * 
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 24 Apr 2014
 * @param int $sourceFieldId
 * @param string $sourceValue
 * @return string $result
 */
function _build_import_insert($sourceFieldId, $sourceValue) {
  $importFields = array('kov_nr', 'vge_nr', 'pers_nr', 'corr_naam', 'ov_datum', 
    'vge_adres', 'type', 'specificatie', 'prijs', 'notaris', 'tax_waarde', 'taxateur', 
    'tax_datum', 'bouwkundige', 'bouw_datum', 'definitief');
  /*
   * sourceFieldId 0, 1, 2 are integer
   */
  if ($sourceFieldId < 3) {
    if (!empty($sourceValue)) {
      $result = $importFields[$sourceFieldId].' = '.$sourceValue;
    }
    return $result;
  }
  /*
   * sourceFieldId 3, 5, 6, 8, 9, 10, 11, 13 and 15 are simple strings
   */
  $stringIds = array(3, 5, 6, 8, 9, 10, 11, 13, 15);
  if (in_array($sourceFieldId, $stringIds)) {
    $escapedValue = CRM_Core_DAO::escapeString($sourceValue);
    $result = $importFields[$sourceFieldId]." = '".$escapedValue."'";
    return $result;
  }
  /*
   * sourceFieldId 4, 12 and 14 are dates
   */
  $dateIds = array(4, 12, 14);
  if (in_array($sourceFieldId, $dateIds)) {
    $dateValue = date('d-m-Y', strtotime(_reformat_date($sourceValue)));
    if (empty($dateValue) || $dateValue == '01-01-1970') {
      $result = '';
    } else {
      $result = $importFields[$sourceFieldId]." = '".$dateValue."'";
    }
    return $result;
  }
}
function _check_empty_sourcerow($sourceRow) {
  $checkEmpty = true;
  if (!empty($sourceRow)) {
    foreach ($sourceRow as $sourceField) {
      if (!empty($sourceField)) {
        $checkEmpty = false;
      }
    }
  }
  return $checkEmpty;
}
