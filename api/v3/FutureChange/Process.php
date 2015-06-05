<?php
/**
 * FutureChange.Process API
 *
 * @author Erik Hommel (erik.hommel@civicoop.org)
 * @date 3 Feb 2014
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception when no location type with name Oud found with API LocationType Getvalue
 * @throws API_Exception when problem with API Address Get for location_type_id
 */
function civicrm_api3_future_change_process($params) {
    ini_set('max_execution_time', 0);
    /*
     * retrieve location_type_id for Oud, Toekomst and Thuis
     */
    $location_params_oud = array(
        'name'      =>  "Oud",
        'return'    =>  'id'
    );
    try {
        $location_type_id_oud = civicrm_api3('LocationType', 'Getvalue', $location_params_oud);
    } catch(CiviCRM_API3_Exception $e) {
        throw new API_Exception("Location type Oud not found in CiviCRM installation, 
            error from API LocationType Getvalue: ".$e->getMessage());
        return;
    }
    $location_params_thuis = array(
        'name'      =>  "Contactadres",
        'return'    =>  'id'
    );
    try {
        $location_type_id_thuis = civicrm_api3('LocationType', 'Getvalue', $location_params_thuis);
    } catch(CiviCRM_API3_Exception $e) {
        throw new API_Exception("Location type Contactadres not found in CiviCRM installation,
            error from API LocationType Getvalue: ".$e->getMessage());
        return;
    }
    $location_params_toekomst = array(
        'name'      =>  "Toekomst",
        'return'    =>  'id'
    );
    try {
        $location_type_id_toekomst = civicrm_api3('LocationType', 'Getvalue', $location_params_toekomst);
    } catch(CiviCRM_API3_Exception $e) {
        throw new API_Exception("Location type Toekomst not found in CiviCRM installation, 
            error from API LocationType Getvalue: ".$e->getMessage());
        return;
    }
    unset($location_params_oud, $location_params_thuis, $location_params_toekomst);
    /*
     * retrieve all addresses with location_type Oud
     */
    $address_params = array(
        'location_type_id'  =>  $location_type_id_toekomst,
        'options'           =>  array('limit' => 9999)
    );
    try {
        $addresses = civicrm_api3('Address', 'Get', $address_params);
    } catch(CiviCRM_API3_Exception $e) {
        throw new API_Exception("Problem retrieving addresses with location type Oud, 
            error from API Address Get :".$e->getMessage());
    }
    $count_addresses = 0;
    /*
     * process all found addresses
     */
    foreach($addresses['values'] as $address) {
        /*
         * retrieve date from supplementel_address_1
         */
        if (isset($address['supplemental_address_1'])) {
            $date_change = _retrieveDateChange($address['supplemental_address_1']);
            if ($date_change <= date('Ymd')) {
                /*
                 * remove existing address with location_type Oud
                 */
                $query_delete = "DELETE FROM civicrm_address WHERE 
                    location_type_id = $location_type_id_oud AND contact_id = {$address['contact_id']}";
                CRM_Core_DAO::executeQuery($query_delete);
                /*
                 * update current Thuis address to Oud
                 */
                $query_update = "UPDATE civicrm_address SET location_type_id = 
                    $location_type_id_oud WHERE location_type_id = $location_type_id_thuis 
                    AND contact_id = {$address['contact_id']}";
                CRM_Core_DAO::executeQuery($query_update);
                /*
                 * to be sure: set all addresses for contact to is_primary = 0
                 */
                $query_primary = "UPDATE civicrm_address SET is_primary = 0 
                    WHERE contact_id = {$address['contact_id']}";
                CRM_Core_DAO::executeQuery($query_primary);
                /*
                 * update current Toekomst naar Thuis with is_primary=1 and empty supplemental_address_1
                 */
                $query_toekomst = "UPDATE civicrm_address SET location_type_id = $location_type_id_thuis, 
                    is_primary = 1, supplemental_address_1 = '' WHERE contact_id = {$address['contact_id']} 
                    AND location_type_id = $location_type_id_toekomst";
                CRM_Core_DAO::executeQuery($query_toekomst);
                $count_addresses++;
            }
        }
    }
    $returnValues = array(
        'is_error'  =>  0,
        'message'   =>  $count_addresses.' toekomstmutaties addressen succesvol verwerkt'
    );
    return civicrm_api3_create_success($returnValues, array(), 'FutureChange', 'Process');
}
/**
 * Function to pluck date address to be changed from input string
 * 
 * @author Erik Hommel (erik.hommel@civicoop.org)
 * @date 3 Feb 2014
 * @param string $date_address
 * @return string $date_change
 */
function _retrieveDateChange($date_address) {
    $date_change = "";
    if (empty($date_address)) {
        return $date_change;
    }
    $spaties = explode((" "), $date_address);
    if (!isset($spaties[1])) {
        return $date_change;
    } else {
        $brackets = explode(")", $spaties[1]);
        
        if (!isset($brackets[0])) {
            return $date_change;
        } else {
            $date_change = date('Ymd', strtotime($brackets[0]));
        }
    }
    return $date_change;
}

