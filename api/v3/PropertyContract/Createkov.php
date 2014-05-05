<?php

/**
 * PropertyContract.Createkov API
 * (redesign with BOS1402567
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 24 Apr 2014
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception when required tables do not exist
 */
function civicrm_api3_property_contract_createkov() {
  set_time_limit(0);
  $countKovs = 0;
  /*
   * exception if no tables
   */
  if (!CRM_Core_DAO::checkTableExists('kov_import') || !CRM_Core_DAO::checkTableExists('kov_header')) {
    throw new Exception('Tabellen kov_header en kov_import zijn nodig, niet gevonden in database!');
  }
  /*
   * read and process all headers
   */
  $headerDAO = CRM_Core_DAO::executeQuery('SELECT * FROM kov_header ORDER BY kov_nr LIMIT 500');
  while ($headerDAO->fetch()) {
    if (!empty($headerDAO->kov_nr) && $headerDAO->kov_nr != 0) {
      _process_header($headerDAO);
      $countKovs++;
      CRM_Core_DAO::executeQuery('DELETE FROM kov_import WHERE kov_nr = '.$headerDAO->kov_nr);
      CRM_Core_DAO::executeQuery('DELETE FROM kov_header WHERE kov_nr = '.$headerDAO->kov_nr);
    }
  }
  $returnValues = array(
    'is_error'  =>  0,
    'message'   =>  $countKovs.' koopovereenkomsten succesvol geladen of bijgewerkt.'
  );
  return civicrm_api3_create_success($returnValues, array(), 'PropertyContract', 'Createkov');
}
/**
 * Function to process header data and create household
 * 
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 26 Feb 2014
 * @param object $kovData (dao)
 * 
 */
function _process_header($kovData) {
  $retrievedIndividuals = _retrieve_individuals($kovData->kov_nr);
  if ($retrievedIndividuals['create_household'] == false) {
    _update_household($retrievedIndividuals['household_id'], $kovData->corr_naam);
    $householdId = $retrievedIndividuals['household_id'];
  } else {
    $householdId = _create_household($kovData->corr_naam);
  }
  _create_kov($kovData, $householdId);
  if (isset($kovData->start_date)) {
    $startDate = $kovData->start_date;
  } else {
    $startDate = NULL;
  }
  if (isset($kovData->end_date)) {
    $endDate = $kovData->end_date;
  } else {
    $endDate = NULL;
  }
  _create_relationships($householdId, $retrievedIndividuals['individuals'], $startDate, $endDate);
  _update_contactdetails($householdId);
  return;
}
/**
 * Function to retrieve individuals for a KOV
 * 
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 24 Apr 2014
 * @param int $kovId
 * @return array $result (create_household, household_id and array individuals)
 */
function _retrieve_individuals($kovId) {
  /*
   * retrieve all individuals for koopovereenkomst and place in array
   */
  $selectInd = 'SELECT DISTINCT(pers_nr) FROM kov_import WHERE kov_nr = %1';
  $paramsInd = array(1 => array($kovId, 'Integer'));
  $daoInd = CRM_Core_DAO::executeQuery($selectInd, $paramsInd);
  $createHousehold = true;
  $householdId = null;
  $result['individuals'] = array();
  while ($daoInd->fetch()) {
    $individualId = _process_individual($daoInd, $createHousehold, $householdId);
    if (!empty($individualId)) {
      $result['individuals'][] = $individualId;
    }
  }
  $result['create_household'] = $createHousehold;
  $result['household_id'] = $householdId;
  return $result;
}
/**
 * Function to process an individual within a kov
 * @param object $daoInd
 * @param boolean $createHousehold
 * @param int $householdId
 * @return array $result
 */
function _process_individual($daoInd, &$createHousehold, &$householdId) {
  $contactId = _retrieve_contact($daoInd->pers_nr);
  /*
   * check if individual is hoofdhuurder anywhere
   */
  if ($createHousehold == true) {
    $checkHoofdhuurder = CRM_Utils_DgwUtils::getHuishoudens($contactId);
    if (isset($checkHoofdhuurder['count']) && $checkHoofdhuurder['count'] > 0) {
      $createHousehold = false;
      $householdId = $checkHoofdhuurder[0]['huishouden_id'];
    }
  }
  /*
   * if individual not found yet, check if koopovereenkomst partner somewhere
   */
  if ($createHousehold == true) {
    $checkHoofdhuurder = CRM_Utils_DgwUtils::getHuishoudens($result['id'], 'relatie koopovereenkomst');
    if (isset($checkHoofdhuurder['count']) && $checkHoofdhuurder['count'] > 0) {
      $createHousehold = false;
      $householdId = $checkHoofdhuurder[0]['huishouden_id'];
    }
  }
  return $contactId;
}
/**
 * Function to update household with name
 * 
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 24 Apr 2014
 * @param int $householdId
 * @param name $householdName
 */
function _update_household($householdId, $householdName) {
  if (!empty($householdId) && !empty($householdName)) {
    $householdName = CRM_Core_DAO::escapeString($householdName);
    $updateQry = "UPDATE civicrm_contact SET household_name = %1, display_name = %1,
      sort_name = %1 WHERE id = %2";
    $updateParams = array(
      1 => array($householdName, 'String'),
      2 => array($householdId, 'Integer'));
    CRM_Core_DAO::executeQuery($updateQry, $updateParams);
  }
  return;
}
/**
 * Function to retrieve contact id with persoonsnummer first
 * 
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 24 Apr 2-14
 * @param int $persoonsNummer
 * @return int $contactId
 */
function _retrieve_contact($persoonsNummer) {
  if (empty($persoonsNummer)) {
    return null;
  } else {
    $apiParams = array('version' => 3, 'persoonsnummer_first' => $persoonsNummer);
    $apiContact = civicrm_api('DgwContact', 'Get', $apiParams);
    if (isset($apiContact[1]['contact_id'])) {
      return $apiContact[1]['contact_id'];
    } else {
      return null;
    }
  }
}
/**
 * Function to create household and add to kov error group as it should not
 * be necessary to create the household
 * 
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 24 Apr 2014
 * @param string $householdName
 * @return int $householdId
 * @throws CiviCRM_API3_Exception if household create in error
 */
function _create_household($householdName) {
  if (empty($householdName)) {
    return null;
  }
  $apiParams = array('contact_type' => 'Household', 'household_name' => $householdName);
  try {
    $createdHousehold = civicrm_api3('Contact', 'Create', $apiParams);
  } catch (CiviCRM_API3_Exception $e) {
    throw new Exception('Huishouden met naam '.$householdName.' kon '
      .'niet aangemaakt worden, melding van API Contact Create : '.$e->getMessage());
  }
  $householdId = $createdHousehold['id'];
  _add_kov_fout($householdId);
  return $householdId;
}
/**
 * Function to add contact to kov error group
 * 
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 24 Apr 2014
 * @param type $householdId
 * @return void
 */
function _add_kov_fout($householdId) {
  $groupParams = array('name' => 'kov_fout', 'return' => 'id');
  try {
    $groupId = civicrm_api3('Group', 'Getvalue', $groupParams);
  } catch (CiviCRM_API3_Exception $e) {
    $groupId = 0;
  }
  if (!empty($groupId)) {
    $groupContactParams = array('group_id' => $groupId, 'contact_id' => $householdId);
    civicrm_api3('GroupContact', 'Create', $groupContactParams);
  }
  return;
}
/**
 * Functie om KOV Custom Veld Insert aan te maken
 * 
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 7 Mar 2014
 * @param string $kovCustomField
 * @return string $columnName
 * @access public
 */
function _set_kov_column_name($kovCustomField) {
  if (empty($kovCustomField)) {
    return "";
  }
  $fldLabel = CRM_Utils_DgwUtils::getDgwConfigValue($kovCustomField);
  if (empty($fldLabel)) {
    return "";
  }
  $customFieldParams = array('label' => $fldLabel, 'return' => 'column_name');
  try {
    $columnName = civicrm_api3('CustomField', 'Getvalue', $customFieldParams);
  } catch (CiviCRM_API3_Exception $e) {
    $columnName = "";
  }
  return $columnName;
}
/**
 * Functie om type koopovereenkomst te zetten
 * 
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 7 Mar 2014
 * @param string $inputType
 * @return int $outputType
 * @access public
 */
function _set_kov_type($inputType) {
  $outputType = "fout";
  if (!empty($inputType)) {
    $inputType = strtolower($inputType);
    switch($inputType) {
      case "koopgarant extern":
        $outputType = 1;
        break;
      case "koopgarant zittende huurders":
        $outputType = 2;
        break;
      case "koopplus extern":
        $outputType = 3;
        break;
      case "koopplus zittende huurders":
        $outputType = 4;
        break;
      case "reguliere verkoop":
        $outputType = 5;
        break;
    }
  }
  return $outputType;
}
/**
 * Functie om koopovereenkomst te verwerken
 * 
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 7 Mar 2014
 * @param object $kovData dao with header data
 * @param int $householdId
 * @return void
 */
function _create_kov($kovData, $householdId) {
  _correct_kov_data($kovData);
  $kovCustomTable = _get_kov_table_name();
  /*
   * create SET part of SQL statement with all KOV fields
   */
  $kovData->type = _set_kov_type($kovData->type);
  $kovCustomFields = _get_kov_custom_fields();
  $kovFieldsSql = _set_insert_fields($kovData, $kovCustomFields);
  if (!empty($kovFieldsSql)) {
    $kovExists = CRM_Utils_DgwUtils::checkKovExists($kovData->kov_nr);
      if ($kovExists) {
        $kovNummerFld = _set_kov_column_name($kovCustomFields['kov_nr']);
        $kovSql = "UPDATE $kovCustomTable SET ".implode(", ", $kovFieldsSql)." WHERE $kovNummerFld = {$kovData->kov_nr}";
    } else {
        $kovFieldsSql[] = "entity_id = $householdId";
        $kovSql = "INSERT INTO $kovCustomTable SET ".implode(", ", $kovFieldsSql);
    }
    $result = CRM_Core_DAO::executeQuery($kovSql);
  }
}
/**
 * Function to correct the KOV data (upper/lowercase for names and definitief
 * 
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 24 Apr 2014
 * @param type $kovData
 */
function _correct_kov_data(&$kovData) {
  $kovData->notaris = CRM_Utils_DgwUtils::upperCaseSplitTxt($kovData->notaris);
  $kovData->taxateur = CRM_Utils_DgwUtils::upperCaseSplitTxt($kovData->taxateur);
  $kovData->bouwkundige = CRM_Utils_DgwUtils::upperCaseSplitTxt($kovData->bouwkundige);
  if (trim($kovData->definitief) == "J") {
    $kovData->definitief = 1;
  } else {
    $kovData->definitief = 0;
  }
}
/**
 * Function to get the custom table name of the kov custom table
 * 
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 24 Apr 2014
 * @return string $apiCustomTable['table_name']
 */
function _get_kov_table_name() {  
  $labelCustomTable = CRM_Utils_DgwUtils::getDgwConfigValue('tabel koopovereenkomst');
  $customTableParams = array('version'   =>  3, 'title'     =>  $labelCustomTable);
  $apiCustomTable = civicrm_api('CustomGroup', 'Getsingle', $customTableParams);
  if (isset($apiCustomTable['table_name'])) {
      return $apiCustomTable['table_name'];
  } else {
    return "";
  }
}
/**
 * Function to get the custom fields for kov with the field names in the input file
 * 
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 24 Apr 2014
 * @return array $customFieldNames
 */
function _get_kov_custom_fields() {
  $kovCustomFields = array(
    "kov_nr"        => "kov nummer veld", 
    "vge_nr"        => "kov vge nummer veld", 
    "vge_adres"     => "kov vge adres veld",
    "ov_datum"      => "kov overdracht veld", 
    "corr_naam"     => "kov naam veld", 
    "definitief"    => "kov definitief veld", 
    "type"          => "kov type veld",
    "prijs"         => "kov prijs veld", 
    "notaris"       => "kov notaris veld", 
    "tax_waarde"    => "kov waarde veld", 
    "taxateur"      => "kov taxateur veld",
    "tax_datum"     => "kov taxatiedatum veld", 
    "bouwkundige"   => "kov bouwkundige veld", 
    "bouw_datum"    => "kov bouwdatum veld"
  );
  return $kovCustomFields;
}
/**
 * Function to set array with all insert fields for kov
 * 
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 24 Apr 2014
 * @param object $kovData
 * @param array $customFields
 * @return array $kovSqlFields
 */
function _set_insert_fields($kovData, $customFields) {
  $kovSqlFields = array();
  foreach ($customFields as $daoField => $customField) {
    $columnName = _set_kov_column_name($customField);
    if (!empty($columnName)) {
      $insertLine = _set_insert_line($columnName, $daoField, $kovData->$daoField);
      $kovSqlFields[] = $insertLine;
    }
  }
  return $kovSqlFields;
}
function _set_insert_line($columnName, $daoField, $daoValue) {
  $dateFields = array('ov_datum', 'tax_datum', 'bouw_datum');
  if (in_array($daoField, $dateFields)) {
    if (empty($daoValue)) {
      $insertLine = $columnName." = NULL";
    } else {
      $insertLine = $columnName." = '".CRM_Utils_DgwUtils::convertDMJString($daoValue)."'";
    }
  } else {
    $insertLine = $columnName." = '".CRM_Core_DAO::escapeString($daoValue)."'";
  }
  return $insertLine;
}
/**
 * Function to add relationship 'koopovereenkomst partner' for all individuals
 * 
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 24 Apr 2014
 * @param int $householdId
 * @param array $individuals
 * @param string $startDate
 */
function _create_relationships($householdId, $individuals, $startDate, $endDate) {
  if (!empty($householdId) && !empty($individuals)) {
    $relTypeParams = array('name_a_b' => 'Koopovereenkomst partner', 'return' => 'id');
    try {
      $relTypeId = civicrm_api3('RelationshipType', 'Getvalue', $relTypeParams);
    } catch (CiviCRM_API3_Exception $e) {
      throw new CiviCRM_API3_Exception('Kan geen relatie vinden voor koopovereenkomst '
        .'partner, melding van API RelationshipType Getvalue ; '.$e->getMessage());
    }
    foreach ($individuals as $contactId) {
      $createRelParams = array('relationship_type_id' => $relTypeId, 'contact_id_a' => $contactId,
        'contact_id_b' => $householdId);
      if (!empty($startDate)) {
        $createRelParams['start_date'] = $startDate;
      }
      if (!empty($endDate)) {
        $createRelParams['end_date'] = $endDate;
      }
      try {
        civicrm_api3('Relationship', 'Create', $createRelParams);
      } catch (CiviCRM_API3_Exception $ex) {
        $message = $ex->getMessage();
        if ($message != 'Relationship already exists') {
          throw new Exception('Fout bij aanmaken relatie id '.$relTypeId.
            ', melding van API Relationship Create : '.$message);
        }
      }
    }
  }
}
/**
 * Function to update household email, address and phone with data from hoofdhuurder
 * or koopovereenkomst partner if there is no active hoofdhuurder
 * 
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 25 Apr 2014
 * @param type $huishoudenId
 */
function _update_contactdetails($huishoudenId) {
  if (!empty($huishoudenId)) {
    /*
     * get active Hoofdhuurder
     */
    $activeHoofdHuurders = CRM_Utils_DgwUtils::getHoofdhuurders($huishoudenId, true);
    if (!empty($activeHoofdHuurders)) {
      CRM_Utils_DgwUtils::processAddressesHoofdHuurder($activeHoofdHuurders[0]['contact_id'], 'relatie hoofdhuurder', false);
      CRM_Utils_DgwUtils::processEmailsHoofdHuurder($activeHoofdHuurders[0]['contact_id'], 'relatie hoofdhuurder', false);
      CRM_Utils_DgwUtils::processPhonesHoofdHuurder($activeHoofdHuurders[0]['contact_id'], 'relatie hoofdhuurder', false);
    } else {
      /*
       * get active koopovereenkomst partner
       */
      $activeKoopPartners = CRM_Utils_DgwUtils::getKooppartners($huishoudenId, true);
      if (!empty($activeKoopPartners)) {
      CRM_Utils_DgwUtils::processAddressesHoofdHuurder($activeKoopPartners[0]['contact_id'], 'relatie koopovereenkomst', false);
      CRM_Utils_DgwUtils::processEmailsHoofdHuurder($activeKoopPartners[0]['contact_id'], 'relatie koopovereenkomst', false);
      CRM_Utils_DgwUtils::processPhonesHoofdHuurder($activeKoopPartners[0]['contact_id'], 'relatie koopovereenkomst', false);
      }
    }
  }
  return;
}