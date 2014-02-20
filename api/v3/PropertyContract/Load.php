<?php

/**
 * PropertyContract.Load API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_property_contract_load_spec(&$spec) {
  $spec['filepath']['api.required'] = 1;
}

/**
 * PropertyContract.Load API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_property_contract_load($params) {
    ini_set('max_execution_time', 0);
    if (!isset($params['filepath']) || empty($params['filepath'])) {
        throw new API_Exception("Geen map opgegeven waarin het bestand koopovereenkomsten staat");
        return;
    }
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
        $isError = true;
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
}

