<?php
/**
 * @copyright Copyright (C) 2014 - CiviCooP (http://www.civicoop.org)
 * @license Licensed to CiviCRM and De Goede Woning under the Academic Free License version 3.0.
 *
 * @author Erik Hommel (erik.hommel@civicoop.org)
 * @date 27 Kan 2014
 * 
 * Page to correct koopovereenkomsten and huishoudens
 * 
 * @date 27 Jan 2014
 * @ticket BOS1401984
 */
set_time_limit(0);
require_once 'CRM/Utils/DgwUtils.php';
require_once 'CRM/Core/Page.php';

class CRM_Custom_Page_Correctkov extends CRM_Core_Page {

    function run() {
        $kov_table_title = CRM_Utils_DgwUtils::getDgwConfigValue("tabel koopovereenkomst");
        $kov_table = CRM_Utils_DgwUtils::getCustomGroupTableName($kov_table_title);
        /*
         * assume this runs after kov load so take records from kovheader
         */
        $header_dao = CRM_Core_DAO::executeQuery("SELECT kov_nr, corr_naam FROM kovhdr");
        while ($header_dao->fetch()) {
            /*
             * retrieve all households with name from header
             */
            $name = CRM_Core_DAO::escapeString($header_dao->corr_naam);
            $household_query = "SELECT id FROM civicrm_contact WHERE 
                contact_type = 'Household' AND household_name = '$name'
                AND is_deleted = 0";
            $household_dao = CRM_Core_DAO::executeQuery($household_query);
            while ($household_dao->fetch()) {
                /*
                 * check if household has a koopovereenkomst
                 */
                $kov_query = "SELECT count(*) AS count_kov FROM $kov_table WHERE entity_id = {$household_dao->id}";
                $kov_dao = CRM_Core_DAO::executeQuery($kov_query);
                if ($kov_dao->fetch()) {
                    if ($kov_dao->count_kov == 0) {
                        $upd_query = "UPDATE civicrm_contact SET is_deleted = 1 WHERE id = {$household_dao->id}";
                        CRM_Core_DAO::executeQuery($upd_query);
                    }
                }
            }
        }
        $this->assign('returnUrl', CRM_Utils_System::url('civicrm', null, true));
        $this->assign('exitMsg', "Dubbele huishoudens verwijderd");
        parent::run();
    }
}
