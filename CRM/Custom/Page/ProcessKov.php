<?php
/**
 * @copyright Copyright (C) 2013 - CiviCooP (http://www.civicoop.org)
 * @license Licensed to CiviCRM and De Goede Woning under the Academic Free License version 3.0.
 *
 * @author Erik Hommel (erik.hommel@civicoop.org)
 * @date oct 2013
 * 
 * Page to load koopovereenkomsten from csv file
 * 
 * @date 27 Jan 2014
 * @ticket BOS1401984
 */

require_once 'CRM/Utils/DgwUtils.php';
require_once 'CRM/Core/Page.php';
class CRM_Custom_Page_ProcessKov extends CRM_Core_Page {

    protected $_kovPath;
    protected $_kovSource;
    protected $_kovTable;
    protected $_kovHeader;
    protected $_kovFileName;

    function run() {
        ini_set('max_execution_time', 0);
        if (!isset($session)) {
            $session = CRM_Core_Session::singleton();
        }
        CRM_Utils_System::setTitle(ts('Laden koopovereenkomsten'));
        /*
         * check if source file exists
         */
        $this->_kovPath = CRM_Utils_DgwUtils::getDgwConfigValue('kov pad');
        $this->_kovFileName = CRM_Utils_DgwUtils::getDgwConfigValue('kov bestandsnaam');
        $this->_kovSource = $this->_kovPath.$this->_kovFileName.date("Ymd").".csv";
        if (!file_exists($this->_kovSource)) {
            $session->setStatus("Laden koopovereenkomsten is afgebroken omdat het bestand {$this->_kovSource} niet bestaat.", "Laden koopovereenkomsten mislukt", 'error');
        } else {
            /*
             * truncate load and header table and load new data
             */
            $this->_kovHeader = CRM_Utils_DgwUtils::getDgwConfigValue('kov header');
            $this->_kovTable = CRM_Utils_DgwUtils::getDgwConfigValue('kov tabel');
            $this->loadHeaderData();
            /*
             * read and process all headers
             */
            $headerDAO = CRM_Core_DAO::executeQuery("SELECT * FROM ".$this->_kovHeader." ORDER BY kov_nr");
            while ($headerDAO->fetch()) {
                if (!empty($headerDAO->kov_nr) && $headerDAO->kov_nr != 0) {
                    $this->_header_data = $headerDAO;
                    $this->processHeader($headerDAO);
                }
            }
            unset($headerDAO);
            /*
             * remove source file
             */
            //unlink($this->_kovSource);
            $this->assign('exitMsg', 'Laden koopovereenkomsten succesvol afgerond');
            $session->reset();
            $session->setStatus("Laden koopovereenkomsten is succesvol afgerond.", "Laden koopovereenkomsten afgerond", 'success');
        }
        $this->assign('returnUrl', CRM_Utils_System::url('civicrm', null, true));
        parent::run();
    }
    /*
     * function to load header data from import file
     */
    private function loadHeaderData() {
        CRM_Core_DAO::executeQuery("TRUNCATE TABLE ".$this->_kovTable);
        $sourceData = fopen($this->_kovSource, 'r');
        while ($sourceRow = fgetcsv($sourceData, 0, ";")) {
            $insImport = "INSERT INTO ".$this->_kovTable." SET ";
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
                $ovDatum = date("d-m-Y", strtotime($sourceValue));
                $insFields[] = "ov_datum = '$ovDatum'";
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
                $taxDatum = date("d-m-Y", strtotime($sourceValue));
                $insFields[] = "tax_datum = '$taxDatum'";
                unset($taxDatum);
            }
            if (isset($sourceRow[13])) {
                $sourceValue = CRM_Core_DAO::escapeString($sourceRow[13]);
                $insFields[] = "bouwkundige = '$sourceValue'";
            }
            if (isset($sourceRow[14])) {
                $sourceValue = CRM_Core_DAO::escapeString($sourceRow[14]);
                $bouwDatum = date("d-m-Y", strtotime($bouwDatum));
                $insFields[] = "bouw_datum = '$bouwDatum'";
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
        unset($sourceData);
        CRM_Core_DAO::executeQuery("TRUNCATE TABLE ".$this->_kovHeader);
        $kovHdrInsert =
"INSERT INTO ".$this->_kovHeader." (SELECT DISTINCT(kov_nr), vge_nr, corr_naam, ov_datum, vge_adres, ";
        $kovHdrInsert .=
"type, prijs, notaris, tax_waarde, taxateur, tax_datum, bouwkundige, bouw_datum, definitief FROM ".$this->_kovTable.")";
        CRM_Core_DAO::executeQuery($kovHdrInsert);
        
    }
    /*
     * function to set the type of KOV
     */
    private function setKovType($inputType) {
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
    /*
     * function to process header data and create household
     */
    private function processHeader($kovData) {
        $kov_nr = (int) $kovData->kov_nr;
        /*
         * retrieve all individuals for koopovereenkomst and place in array
         */
        $kovIndividuals = array();
        $kovIndQry = "SELECT DISTINCT(pers_nr) FROM ".$this->_kovTable." WHERE kov_nr = $kov_nr";
        $individualDAO = CRM_Core_DAO::executeQuery($kovIndQry);
        $createHouseHold = true;
        $i = 0;
        $relLabel = 'relatie koopovereenkomst';
        while ($individualDAO->fetch()) {
            $apiParams = array(
                'version'               =>  3,
                'persoonsnummer_first'  =>  $individualDAO->pers_nr
            );
            $apiIndividual = civicrm_api('DgwContact', 'Get', $apiParams);
            if (isset($apiIndividual[1]['contact_id'])) {
                $contactId = $apiIndividual[1]['contact_id'];
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
        /*
         * check if name household is the same, update if different
         */
        if (!$createHouseHold) {
            $apiParams = array(
                'version'       =>  3,
                'contact_id'    =>  $houseHoldId
            );
            $apiHouseHold = civicrm_api("Contact", "Getsingle", $apiParams);
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
            $houseHoldId = $this->createHouseHold($kovData->corr_naam, $copyContactId, $relLabel);
        }
        /*
         * update or create koopovereenkomst if there is a household
         */
        if (isset($houseHoldId) && !empty($houseHoldId)) {
            $this->processKoopovereenkomst($kovData, $houseHoldId);
            /*
             * create relationship Koopovereenkomst partner between all persons and household
             * but remove existing ones first
             */
            $koopRelLabel = CRM_Utils_DgwUtils::getDgwConfigValue('relatie koopovereenkomst');
            $apiParams = array(
                'version'   =>  3,
                'label_a_b' =>  $koopRelLabel
            );
            $apiRelType = civicrm_api('RelationshipType', 'Getsingle', $apiParams);
            if(isset( $apiRelType['id'])) {
                $relTypeId = $apiRelType['id'];
            }
            $apiParams = array(
                'version'               =>  3,
                'contact_id_b'          =>  $houseHoldId,
                'relationship_type_id'  =>  $relTypeId

            );
            $koopRelations = civicrm_api('Relationship', 'Get', $apiParams);
            if ($koopRelations['is_error'] == 0 && $koopRelations['count'] != 0) {
                foreach($koopRelations['values'] as $keyRelation => $koopRelation) {
                    if (isset($koopRelation['id'])) {
                        civicrm_api('Relationship', 'Delete', array('version'=>3, 'id'=> $koopRelation['id']));
                    }
                }
            }
            foreach ($kovIndividuals as $kovIndividual) {
                $apiParams = array(
                    'version'               =>  3,
                    'relationship_type_id'  =>  $relTypeId,
                    'contact_id_a'          =>  $kovIndividual['id'],
                    'contact_id_b'          =>  $houseHoldId
                );
                if (!empty($kovData->ov_datum)) {
                    $apiParams['start_date'] = CRM_Utils_DgwUtils::convertDMJString($kovData->ov_datum);
                } else {
                    $apiParams['start_date'] = date('Ymd');
                }
                $apiRelCreate = civicrm_api("Relationship", "Create", $apiParams);
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
    /*
     * function to create household
     */
    private function createHouseHold($houseHoldName, $contactId, $relLabel) {
        $apiParams = array(
            'version'       =>  3,
            'contact_type'  =>  'Household',
            'household_name'=>  $houseHoldName
        );
        $resultCreateHousehold = civicrm_api('Contact', 'Create', $apiParams);
        if (isset($resultCreateHousehold['id'])) {
            $houseHoldId = $resultCreateHousehold['id'];
            $groupLabel = CRM_Utils_DgwUtils::getDgwConfigValue('kov foutgroep');
            $apiParams = array(
                'version'   =>  3,
                'title'     =>  $groupLabel
            );
            $apiGroup = civicrm_api('Group', 'Getsingle', $apiParams);
            if (isset($apiGroup['id'])) {
                $groupId = $apiGroup['id'];
            }
            $apiParams = array(
                'version'       =>  3,
                'group_id'      =>  $groupId,
                'contact_id'    =>  $houseHoldId
            );
            civicrm_api('GroupContact', 'Create', $apiParams);
        }
        return($houseHoldId);
    }
    /*
     * function to create or update koopovereenkomst
     */
    private function processKoopovereenkomst($kovData, $houseHoldId) {
        $type = $this->setKovType( $kovData->type );
        $kovData->notaris = CRM_Utils_DgwUtils::upperCaseSplitTxt($kovData->notaris);
        $kovData->taxateur = CRM_Utils_DgwUtils::upperCaseSplitTxt($kovData->taxateur);
        $kovData->bouwkundige = CRM_Utils_DgwUtils::upperCaseSplitTxt($kovData->bouwkundige);
        if (trim($kovData->definitief) == "J") {
            $kovData->definitief = 1;
        } else {
            $kovData->definitief = 0;
        }

        $labelCustomTable = CRM_Utils_DgwUtils::getDgwConfigValue('tabel koopovereenkomst');
        $apiParams = array(
            'version'   =>  3,
            'title'     =>  $labelCustomTable
        );
        $apiCustomTable = civicrm_api('CustomGroup', 'Getsingle', $apiParams);
        if (isset($apiCustomTable['table_name'])) {
            $kovCustomTable = $apiCustomTable['table_name'];
        }
        if (isset($apiCustomTable['id'])) {
            $kovCustomGroupId = $apiCustomTable['id'];
        }
        /*
         * create SET part of SQL statement with all KOV fields
         */
        $kovFieldsSql = array();
        $apiParams = array();
        $apiParams['version'] = 3;
        $fldLabel = CRM_Utils_DgwUtils::getDgwConfigValue('kov nummer veld');
        $apiParams['label'] = $fldLabel;
        $apiCustomField = civicrm_api('CustomField', 'getsingle', $apiParams);
        if (isset($apiCustomField['column_name'])) {
            $kovFieldsSql[] = "{$apiCustomField['column_name']} = {$kovData->kov_nr}";
            $kovNummerFld = $apiCustomField['column_name'];
        }
        $fldLabel = CRM_Utils_DgwUtils::getDgwConfigValue('kov vge nummer veld');
        $apiParams['label'] = $fldLabel;
        $apiCustomField = civicrm_api('CustomField', 'getsingle', $apiParams);
        if (isset($apiCustomField['column_name'])) {
            $kovFieldsSql[] = "{$apiCustomField['column_name']} = {$kovData->vge_nr}";
        }
        $fldLabel = CRM_Utils_DgwUtils::getDgwConfigValue('kov vge adres veld');
        $apiParams['label'] = $fldLabel;
        $apiCustomField = civicrm_api('CustomField', 'getsingle', $apiParams);
        if (isset($apiCustomField['column_name'])) {
            $escapedString = CRM_Core_DAO::escapeString(trim($kovData->vge_adres));
            $kovFieldsSql[] = "{$apiCustomField['column_name']} = '$escapedString'";
        }
        $fldLabel = CRM_Utils_DgwUtils::getDgwConfigValue('kov overdracht veld');
        $apiParams['label'] = $fldLabel;
        $apiCustomField = civicrm_api('CustomField', 'getsingle', $apiParams);
        if (isset($apiCustomField['column_name'])) {
            if (empty($kovData->ov_datum)) {
                $sqlDatum = "";
            } else {
                $sqlDatum = CRM_Utils_DgwUtils::convertDMJString($kovData->ov_datum);
            }
            $kovFieldsSql[] = "{$apiCustomField['column_name']} = '$sqlDatum'";
        }
        $fldLabel = CRM_Utils_DgwUtils::getDgwConfigValue('kov naam veld');
        $apiParams['label'] = $fldLabel;
        $apiCustomField = civicrm_api('CustomField', 'getsingle', $apiParams);
        if (isset($apiCustomField['column_name'])) {
            $escapedString = CRM_Core_DAO::escapeString($kovData->corr_naam);
            $kovFieldsSql[] = "{$apiCustomField['column_name']} = '$escapedString'";
        }
        $fldLabel = CRM_Utils_DgwUtils::getDgwConfigValue('kov definitief veld');
        $apiParams['label'] = $fldLabel;
        $apiCustomField = civicrm_api('CustomField', 'getsingle', $apiParams);
        if (isset($apiCustomField['column_name'])) {
            $kovFieldsSql[] = "{$apiCustomField['column_name']} = '{$kovData->definitief}'";
        }
        $fldLabel = CRM_Utils_DgwUtils::getDgwConfigValue('kov type veld');
        $apiParams['label'] = $fldLabel;
        $apiCustomField = civicrm_api('CustomField', 'getsingle', $apiParams);
        if (isset($apiCustomField['column_name'])) {
            $kovFieldsSql[] = "{$apiCustomField['column_name']} = $type";
        }
        $fldLabel = CRM_Utils_DgwUtils::getDgwConfigValue('kov prijs veld');
        $apiParams['label'] = $fldLabel;
        $apiCustomField = civicrm_api('CustomField', 'getsingle', $apiParams);
        if ( isset( $apiCustomField['column_name'] ) ) {
            $kovFieldsSql[] = "{$apiCustomField['column_name']} = '{$kovData->prijs}'";
        }
        $fldLabel = CRM_Utils_DgwUtils::getDgwConfigValue('kov notaris veld');
        $apiParams['label'] = $fldLabel;
        $apiCustomField = civicrm_api('CustomField', 'getsingle', $apiParams);
        if (isset($apiCustomField['column_name'])) {
            $escapedString = CRM_Core_DAO::escapeString($kovData->notaris);
            $kovFieldsSql[] = "{$apiCustomField['column_name']} = '$escapedString'";
        }
        $fldLabel = CRM_Utils_DgwUtils::getDgwConfigValue('kov waarde veld');
        $apiParams['label'] = $fldLabel;
        $apiCustomField = civicrm_api('CustomField', 'getsingle', $apiParams);
        if (isset($apiCustomField['column_name'])) {
            $kovFieldsSql[] = "{$apiCustomField['column_name']} = '{$kovData->tax_waarde}'";
        }
        $fldLabel = CRM_Utils_DgwUtils::getDgwConfigValue('kov taxateur veld');
        $apiParams['label'] = $fldLabel;
        $apiCustomField = civicrm_api('CustomField', 'getsingle', $apiParams);
        if (isset($apiCustomField['column_name'])) {
            $escapedString = CRM_Core_DAO::escapeString($kovData->taxateur);
            $kovFieldsSql[] = "{$apiCustomField['column_name']} = '$escapedString'";
        }
        $fldLabel = CRM_Utils_DgwUtils::getDgwConfigValue('kov taxatiedatum veld');
        $apiParams['label'] = $fldLabel;
        $apiCustomField = civicrm_api('CustomField', 'getsingle', $apiParams);
        if (isset($apiCustomField['column_name'])) {
            if (empty($kovData->tax_datum)) {
                $sqlDatum = "";
            } else {
                $sqlDatum = CRM_Utils_DgwUtils::convertDMJString($kovData->tax_datum);
            }
            $kovFieldsSql[] = "{$apiCustomField['column_name']} = '$sqlDatum'";
        }
        $fldLabel = CRM_Utils_DgwUtils::getDgwConfigValue('kov bouwkundige veld');
        $apiParams['label'] = $fldLabel;
        $apiCustomField = civicrm_api('CustomField', 'getsingle', $apiParams);
        if (isset($apiCustomField['column_name'])) {
            $escapedString = CRM_Core_DAO::escapeString($kovData->bouwkundige);
            $kovFieldsSql[] = "{$apiCustomField['column_name']} = '$escapedString'";
        }
        $fldLabel = CRM_Utils_DgwUtils::getDgwConfigValue('kov bouwdatum veld');
        $apiParams['label'] = $fldLabel;
        $apiCustomField = civicrm_api('CustomField', 'getsingle', $apiParams);
        if (isset($apiCustomField['column_name'])) {
            if (empty($kovData->bouw_datum)) {
                $sqlDatum = "";
            } else {
                $sqlDatum = CRM_Utils_DgwUtils::convertDMJString($kovData->bouw_datum);
            }
            $kovFieldsSql[] = "{$apiCustomField['column_name']} = '$sqlDatum'";
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
}
