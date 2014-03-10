<?php

/**
 * PropertyContract.Load API
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 26 Feb 2014
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception when source file does not exist
 */
function civicrm_api3_property_contract_loadkov() {
    set_time_limit(0);
    $countKovs = 0;
    /*
     * retrieve file name (incl. path) from dgw_config table
     */
    $sourceFile = CRM_Utils_DgwUtils::getDgwConfigValue("kov bestandsnaam");
    $sourceFile .= date("Ymd").".csv";
    /*
     * check if source file exists
     */
    if (!file_exists($sourceFile)) {
        throw new API_Exception("Bronbestand $sourceFile niet gevonden, laden koopovereenkomsten mislukt");
    } else {
        /*
         * import data and load header
         */
        $dataLoaded = _loadHeaderData($sourceFile);
        if ($dataLoaded == FALSE) {
            throw new API_Exception("Kon brongegevens niet importeren, laden koopovereenkomsten mislukt");
        } else {
            
        }
            /*
             * read and process all headers
             */
            $headerDAO = CRM_Core_DAO::executeQuery("SELECT * FROM kovhdr ORDER BY kov_nr");
            while ($headerDAO->fetch()) {
                if (!empty($headerDAO->kov_nr) && $headerDAO->kov_nr != 0) {
                    _processHeader($headerDAO);
                    $countKovs++;
                }
            }
        unset($headerDAO);
        /*
         * remove source file
         */
        //unlink($this->_kovSource);
        CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS kovimport, kovhdr");
        $returnValues = array(
            'is_error'  =>  0,
            'message'   =>  $countKovs.' koopovereenkomsten succesvol geladen of bijgewerkt.'
        );
        return civicrm_api3_create_success($returnValues, array(), 'PropertyContract', 'Load');
    }
}
/**
 * Function to load data from import file
 * 
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 26 Feb 2014
 * @param string $sourceFile file holding data that has to be imported
 * @return boolean
 */
function _loadHeaderData($sourceFile) {
    /* 
     * create temporary files for import and header
     */
    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS kovimport, kovhdr");
    $createImportFile = "CREATE TEMPORARY TABLE kovimport (
  kov_nr int(11) DEFAULT NULL,
  vge_nr int(11) DEFAULT NULL,
  pers_nr int(11) DEFAULT NULL,
  corr_naam varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  ov_datum varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  vge_adres varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  type varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  prijs varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  notaris varchar(75) COLLATE utf8_unicode_ci DEFAULT NULL,
  tax_waarde varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  taxateur varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  tax_datum varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  bouwkundige varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  bouw_datum varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  definitief varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY(kov_nr, pers_nr)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
    CRM_Core_DAO::executeQuery($createImportFile);
    
    $createHeaderFile = "CREATE TEMPORARY TABLE kovhdr (
  kov_nr int(11) NOT NULL DEFAULT '0',
  vge_nr int(11) DEFAULT NULL,
  corr_naam varchar(128) DEFAULT NULL,
  ov_datum varchar(45) DEFAULT NULL,
  vge_adres varchar(128) DEFAULT NULL,
  type varchar(45) DEFAULT NULL,
  prijs varchar(45) DEFAULT NULL,
  notaris varchar(75) DEFAULT NULL,
  tax_waarde varchar(45) DEFAULT NULL,
  taxateur varchar(45) DEFAULT NULL,
  tax_datum varchar(45) DEFAULT NULL,
  bouwkundige varchar(45) DEFAULT NULL,
  bouw_datum varchar(45) DEFAULT NULL,
  definitief varchar(45) DEFAULT NULL,
  PRIMARY KEY (kov_nr)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
    CRM_Core_DAO::executeQuery($createHeaderFile);
    
    $sourceData = fopen($sourceFile, 'r');
    while ($sourceRow = fgetcsv($sourceData, 0, ";")) {
        $insImport = "INSERT INTO kovimport SET ";
        $insFields = array();
        if (isset($sourceRow[0]) && !empty($sourceRow[0])) {
            $insFields[] = "kov_nr = {$sourceRow[0]}";
            $importKov = true;
        } else {
            $importKov = false;
        }
        if (isset($sourceRow[1])) {
            $insFields[] = "vge_nr = {$sourceRow[1]}";
        }
        if (isset($sourceRow[2])) {
            $insFields[] = "pers_nr = {$sourceRow[2]}";
        }
        if (isset($sourceRow[3])) {
            $sourceValue = CRM_Core_DAO::escapeString($sourceRow[3]);
            $insFields[] = "corr_naam = '$sourceValue'";
        }
        if (isset($sourceRow[4])) {
            $sourceValue = CRM_Core_DAO::escapeString($sourceRow[4]);
            $ovDatum = date("d-m-Y", strtotime(_reformatDate($sourceValue)));
            if (!empty($ovDatum) && $ovDatum != "01-01-1970") {
                $insFields[] = "ov_datum = '$ovDatum'";
            }
            unset($ovDatum);
        }
        if (isset($sourceRow[5])) {
            $sourceValue = CRM_Core_DAO::escapeString($sourceRow[5]);
            $insFields[] = "vge_adres = '$sourceValue'";
        }
        if (isset($sourceRow[6])) {
            $sourceValue = CRM_Core_DAO::escapeString($sourceRow[6]);
            $insFields[] = "type = '$sourceValue'";
        }
        if (isset($sourceRow[8])) {
            $sourceValue = CRM_Core_DAO::escapeString($sourceRow[8]);
            $insFields[] = "prijs = '$sourceValue'";
        }
        if (isset($sourceRow[9])) {
            $sourceValue = CRM_Core_DAO::escapeString($sourceRow[9]);
            $insFields[] = "notaris = '$sourceValue'";
        }
        if (isset($sourceRow[10])) {
            $sourceValue = CRM_Core_DAO::escapeString($sourceRow[10]);
            $insFields[] = "tax_waarde = '$sourceValue'";
        }
        if (isset($sourceRow[11])) {
            $sourceValue = CRM_Core_DAO::escapeString($sourceRow[11]);
            $insFields[] = "taxateur = '$sourceValue'";
        }
        if (isset($sourceRow[12])) {
            $sourceValue = CRM_Core_DAO::escapeString($sourceRow[12]);
            $taxDatum = date("d-m-Y", strtotime(_reformatDate($sourceValue)));
            if (!empty($taxDatum) && $taxDatum != "01-01-1970") {
                $insFields[] = "tax_datum = '$taxDatum'";
            }
            unset($taxDatum);
        }
        if (isset($sourceRow[13])) {
            $sourceValue = CRM_Core_DAO::escapeString($sourceRow[13]);
            $insFields[] = "bouwkundige = '$sourceValue'";
        }
        if (isset($sourceRow[14])) {
            $sourceValue = CRM_Core_DAO::escapeString($sourceRow[14]);
            $bouwDatum = date("d-m-Y", strtotime(_reformatDate($sourceValue)));
            if ($sourceRow[0] == "216") {
            }
            if (!empty($bouwDatum) && $bouwDatum != "01-01-1970") {
                $insFields[] = "bouw_datum = '$bouwDatum'";
            }
            unset($bouwDatum);
        }
        if (isset($sourceRow[15])) {
            $sourceValue = CRM_Core_DAO::escapeString($sourceRow[15]);
            $insFields[] = "definitief = '$sourceValue'";
        }
        if ($importKov) {
            $insImport .= implode(", ", $insFields);
            CRM_Core_DAO::executeQuery($insImport);
        }
    }
    fclose($sourceData);
    unset($sourceData, $sourceRow);
    $kovHdrInsert =
"INSERT INTO kovhdr (SELECT DISTINCT(kov_nr), vge_nr, corr_naam, ov_datum, vge_adres, 
    type, prijs, notaris, tax_waarde, taxateur, tax_datum, bouwkundige, bouw_datum, 
    definitief FROM kovimport)";
    CRM_Core_DAO::executeQuery($kovHdrInsert);
    return TRUE;
}
/**
 * Function to process header data and create household
 * 
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 26 Feb 2014
 * @param object $kovData (dao)
 * 
 */
function _processHeader($kovData) {
    $kov_nr = (int) $kovData->kov_nr;
    /*
     * retrieve all individuals for koopovereenkomst and place in array
     */
    $kovIndividuals = array();
    $kovIndQry = "SELECT DISTINCT(pers_nr) FROM kovimport WHERE kov_nr = $kov_nr";
    $individualDAO = CRM_Core_DAO::executeQuery($kovIndQry);
    $createHouseHold = true;
    $i = 0;
    $relLabel = 'relatie koopovereenkomst';
    while ($individualDAO->fetch()) {
        $apiContactGetParams = array(
            'version'               =>  3,
            'persoonsnummer_first'  =>  $individualDAO->pers_nr
        );
        $apiIndividual = civicrm_api('DgwContact', 'Get', $apiContactGetParams);
        if (isset($apiIndividual[1]['contact_id'])) {
            $contactId = $apiIndividual[1]['contact_id'];
            unset($apiIndividual, $apiContactGetParams);
            $kovIndividuals[$i]['id'] = $contactId;
            /*
             * if we have no household yet, check if contact is
             * hoofdhuurder or koopovereenkomst partner somewhere and
             * use that household
             */
            if ($createHouseHold) {
                $checkHoofdHuurder = CRM_Utils_DgwUtils::getHuishoudens($contactId);
                if (isset($checkHoofdHuurder['count'])) {
                    if ($checkHoofdHuurder['count'] > 0) {
                        $createHouseHold = false;
                        $houseHoldId = $checkHoofdHuurder[0]['huishouden_id'];
                        $copyContactId = $contactId;
                        $kovIndividuals[$i]['rel'] = "hoofdhuurder";
                    }
                }
                if ($createHouseHold) {
                    $checkKoopPartner = CRM_Utils_DgwUtils::getHuishoudens($contactId, $relLabel);
                    if (isset($checkKoopPartner['count'])) {
                        if ($checkKoopPartner['count'] > 0) {
                            $createHouseHold = false;
                            $copyContactId = $contactId;
                            $houseHoldId = $checkKoopPartner[0]['huishouden_id'];
                            $kovIndividuals[$i]['rel'] = "koopovereenkomst";
                        }
                    }
                }
            }
        }
        $i++;
    }
    unset($individualDAO);
    /*
     * check if name household is the same, update if different
     */
    if (!$createHouseHold) {
        $apiContactGetParams = array(
            'version'       =>  3,
            'contact_id'    =>  $houseHoldId
        );
        $apiHouseHold = civicrm_api("Contact", "Getsingle", $apiContactGetParams);
        if (!isset($apiHouseHold['is_error']) || $apiHouseHold['is_error'] == 0) {
            if (isset($apiHouseHold['household_name'])) {
                if ($apiHouseHold['household_name'] != $kovData->corr_naam) {
                    /*
                     * BOS1401984 (remove create new household and update instead
                     */
                    $household_name = CRM_Core_DAO::escapeString($kovData->corr_naam);
                    $update_query = "UPDATE civicrm_contact SET household_name = 
                        '$household_name', display_name = '$household_name', 
                        sort_name = '$household_name' WHERE id = $houseHoldId";
                    CRM_Core_DAO::executeQuery($update_query);
                }
            }
        }
        unset($apiHouseHold, $apiContactGetParams);
    }
    /*
     * create household if required and put in correction group
     */
    if ($createHouseHold) {
        /*
         * determine who's addresses/emails/phones have to be copied into household
         */
        $copyContactId = $kovIndividuals[0]['id'];
        foreach ($kovIndividuals as $kovIndividual) {
            if (isset($kovIndividual['rel']) && $kovIndividual['rel'] == "hoofdhuurder") {
                $copyContactId = $kovIndividual['id'];
            }
        }
        $houseHoldId = _createHouseHold($kovData->corr_naam);
    }
    /*
     * update or create koopovereenkomst if there is a household
     */
    if (isset($houseHoldId) && !empty($houseHoldId)) {
        _processKoopovereenkomst($kovData, $houseHoldId);
        /*
         * create relationship Koopovereenkomst partner between all persons and household
         * but remove existing ones first
         */
        $koopRelLabel = CRM_Utils_DgwUtils::getDgwConfigValue('relatie koopovereenkomst');
        $apiRelTypeGetParams = array('label_a_b' =>  $koopRelLabel);
        $apiRelType = civicrm_api3('RelationshipType', 'Getsingle', $apiRelTypeGetParams);
        if(isset( $apiRelType['id'])) {
            $relTypeId = $apiRelType['id'];
        }
        $apiRelGetParams = array(
            'contact_id_b'          =>  $houseHoldId,
            'relationship_type_id'  =>  $relTypeId

        );
        $koopRelations = civicrm_api3('Relationship', 'Get', $apiRelGetParams);
        if ($koopRelations['is_error'] == 0 && $koopRelations['count'] != 0) {
            foreach($koopRelations['values'] as $keyRelation => $koopRelation) {
                if (isset($koopRelation['id'])) {
                    civicrm_api3('Relationship', 'Delete', array('id'=> $koopRelation['id']));
                }
            }
        }
        foreach ($kovIndividuals as $kovIndividual) {
            $apirelCreateParams = array(
                'relationship_type_id'  =>  $relTypeId,
                'contact_id_a'          =>  $kovIndividual['id'],
                'contact_id_b'          =>  $houseHoldId
            );
            if (!empty($kovData->ov_datum)) {
                $apiParams['start_date'] = CRM_Utils_DgwUtils::convertDMJString($kovData->ov_datum);
            } else {
                $apiParams['start_date'] = date('Ymd');
            }
            civicrm_api3("Relationship", "Create", $apirelCreateParams);
        }
    }
    /*
     * update contact details (address, phone, email) for huishouden
     */
    if (isset($copyContactId)) {
        CRM_Utils_DgwUtils::processAddressesHoofdHuurder($copyContactId, $relLabel);
        CRM_Utils_DgwUtils::processEmailsHoofdHuurder($copyContactId, $relLabel);
        CRM_Utils_DgwUtils::processPhonesHoofdHuurder($copyContactId, $relLabel);
    }
}
/**
 * Functie om huishouden aan te maken indien nodig
 * 
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 26 Feb 2014
 * @param string $houseHoldName
 * @param int $contactId
 * @param string $relLabel
 * @return int $houseHoldId
 */
function _createHouseHold($houseHoldName) {
    $apiParams = array(
        'contact_type'  =>  'Household',
        'household_name'=>  $houseHoldName 
    );
    $resultCreateHousehold = civicrm_api3('Contact', 'Create', $apiParams);
    if (isset($resultCreateHousehold['id'])) {
        $houseHoldId = $resultCreateHousehold['id'];
        $groupLabel = CRM_Utils_DgwUtils::getDgwConfigValue('kov foutgroep');
        $apiGroup = civicrm_api3('Group', 'Getsingle', array('title' => $groupLabel));
        if (isset($apiGroup['id'])) {
            $groupId = $apiGroup['id'];
        }
        $apiParams = array(
            'group_id'      =>  $groupId,
            'contact_id'    =>  $houseHoldId
        );
        civicrm_api3('GroupContact', 'Create', $apiParams);
    }
    return($houseHoldId);
}
/**
 * Functie om koopovereenkomst te verwerken
 * 
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 7 Mar 2014
 * @param object $kovData, dao with header data
 * @param int $houseHoldId
 * @return void
 * @access public
 */
function _processKoopovereenkomst($kovData, $houseHoldId) {
    $kovData->notaris = CRM_Utils_DgwUtils::upperCaseSplitTxt($kovData->notaris);
    $kovData->taxateur = CRM_Utils_DgwUtils::upperCaseSplitTxt($kovData->taxateur);
    $kovData->bouwkundige = CRM_Utils_DgwUtils::upperCaseSplitTxt($kovData->bouwkundige);
    if (trim($kovData->definitief) == "J") {
        $kovData->definitief = 1;
    } else {
        $kovData->definitief = 0;
    }

    $labelCustomTable = CRM_Utils_DgwUtils::getDgwConfigValue('tabel koopovereenkomst');
    $customTableParams = array(
        'version'   =>  3,
        'title'     =>  $labelCustomTable
    );
    $apiCustomTable = civicrm_api('CustomGroup', 'Getsingle', $customTableParams);
    if (isset($apiCustomTable['table_name'])) {
        $kovCustomTable = $apiCustomTable['table_name'];
    }
    /*
     * create SET part of SQL statement with all KOV fields
     */
    $kovFieldsSql = array();
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
    foreach ($kovCustomFields as $daoField => $kovCustomField) {
        $columnName = _setKovColumnName($kovCustomField);
        if (!empty($columnName)) {
            switch ($daoField) {
                case "kov_nr":
                    $kovNummerFld = $columnName;
                    $insertLine = "$columnName = '{$kovData->kov_nr}'"; 
                    break;
                case "vge_nr":
                    $insertLine = "$columnName = '{$kovData->vge_nr}'";
                    break;
                case "vge_adres":
                    $vgeAdres = CRM_Core_DAO::escapeString($kovData->vge_adres);
                    $insertLine = "$columnName = '$vgeAdres'";
                    break;
                case "ov_datum":
                    if (empty($kovData->ov_datum)) {
                        $sqlOvDatum = NULL;
                    } else {
                        $sqlOvDatum = CRM_Utils_DgwUtils::convertDMJString($kovData->ov_datum);
                    }
                    $insertLine = "$columnName = '$sqlOvDatum'";
                    break;
                case "corr_naam":
                    $corrNaam = CRM_Core_DAO::escapeString($kovData->corr_naam);
                    $insertLine = "$columnName = '$corrNaam'";
                    break;
                case "definitief":
                    $insertLine = "$columnName = '{$kovData->definitief}'";
                    break;
                case "type":
                    $type = _setKovType($kovData->type);
                    $insertLine = "$columnName = '$type'";
                    break;
                case "prijs":
                    $insertLine = "$columnName = '{$kovData->prijs}'";
                    break;
                case "notaris":
                    $notaris = CRM_Core_DAO::escapeString($kovData->notaris);
                    $insertLine = "$columnName = '$notaris'";
                    break;
                case "tax_waarde":
                    $insertLine = "$columnName = '{$kovData->tax_waarde}'";
                    break;
                case "taxateur":
                    $taxateur = CRM_Core_DAO::escapeString($kovData->taxateur);
                    $insertLine = "$columnName = '$taxateur'";
                    break;
                case "tax_datum":
                    if (empty($kovData->tax_datum)) {
                        $sqlTaxDatum = "";
                    } else {
                        $sqlTaxDatum = CRM_Utils_DgwUtils::convertDMJString($kovData->tax_datum);
                    }
                    $insertLine = "$columnName = '$sqlTaxDatum'";
                    break;
                case "bouwkundige":
                    $bouwkundige = CRM_Core_DAO::escapeString($kovData->bouwkundige);
                    $insertLine = "$columnName = '$bouwkundige'";
                    break;
                case "bouw_datum":
                    if (empty($kovData->bouw_datum)) {
                        $sqlBouwDatum = "";
                    } else {
                        $sqlBouwDatum = CRM_Utils_DgwUtils::convertDMJString($kovData->bouw_datum);
                    }
                    $insertLine = "$columnName = '$sqlBouwDatum'";
                    break;
            }
            $kovFieldsSql[] = $insertLine;
        }
    }
    $kovExists = CRM_Utils_DgwUtils::checkKovExists($kovData->kov_nr);
    if ($kovExists) {
        $kovSql = "UPDATE $kovCustomTable SET ".implode(", ", $kovFieldsSql)." WHERE $kovNummerFld = {$kovData->kov_nr}";
    } else {
        $kovFieldsSql[] = "entity_id = $houseHoldId";
        $kovSql = "INSERT INTO $kovCustomTable SET ".implode(", ", $kovFieldsSql);
    }
    CRM_Core_DAO::executeQuery($kovSql);
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
function _setKovColumnName($kovCustomField) {
    if (empty($kovCustomField)) {
        return "";
    }
    $fldLabel = CRM_Utils_DgwUtils::getDgwConfigValue($kovCustomField);
    if (empty($fldLabel)) {
        return "";
    }
    $customFieldParams = array(
        'label'     =>  $fldLabel,
        'return'    =>  'column_name'
    );
    try {
        $columnName = civicrm_api3('customField', 'Getvalue', $customFieldParams);
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
function _setKovType($inputType) {
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
function _reformatDate($inDate) {
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
        If (!empty($newMonth)) {
            $outDate = $parts[0]."-".$newMonth."-".$parts[2];
        }
    }
    return $outDate;
}