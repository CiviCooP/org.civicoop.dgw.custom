<?php
set_time_limit(0);

/**
 * HOV.VgeAdresOld API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_h_o_v_vgeadresold($params) {
  // huishouden
  $tabel = CRM_Utils_DgwUtils::getCustomGroupTableName('Huurovereenkomst (huishouden)');
  $field_enddate = CRM_Utils_DgwUtils::getCustomFieldByName('Einddatum_HOV');
  $field_vge_id = CRM_Utils_DgwUtils::getCustomFieldByName('VGE_nummer_First');
  
  try {
    civicrm_api3_h_o_v_vgeadresold_hov($tabel, $field_enddate['column_name'], $field_vge_id['column_name']);
    
  } catch(CiviCRM_API3_Exception $e) {
    throw new API_Exception("Set household address on old failt, 
      error message: " . $e->getMessage());
    return;
  }
  
  // organisatie
  $tabel = CRM_Utils_DgwUtils::getCustomGroupTableName('Huurovereenkomst (organistatie)');
  $field_enddate = CRM_Utils_DgwUtils::getCustomFieldByName('einddatum_overeenkomst');
  $field_vge_id = CRM_Utils_DgwUtils::getCustomFieldByName('vge_nummer');
  
  try {
    civicrm_api3_h_o_v_vgeadresold_hov($tabel, $field_enddate['column_name'], $field_vge_id['column_name']);
    
  } catch(CiviCRM_API3_Exception $e) {
    throw new API_Exception("Set organization address on old failt, 
      error message: " . $e->getMessage());
    return;
  }
  
  $returnValues = array(
    'is_error'  =>  0,
    'message'   =>  'Zet oude adressen op oud succesvol uitgevoerd !'
  );
  
  return civicrm_api3_create_success($returnValues, $params, 'HOV', 'VgeAdresOld');
}

function civicrm_api3_h_o_v_vgeadresold_hov($tabel, $field_end_date, $field_vge_id){
  $sql = "SELECT entity_id, " . $field_vge_id . " AS vge_id FROM " . $tabel . " WHERE " . $field_end_date . " < '" . date('Y-m-d') . "' AND " . $field_end_date . " IS NOT NULL ";
  $dao = CRM_Core_DAO::executeQuery($sql);
  
  while ($dao->fetch()) {
    $vge = array();
    $vge = CRM_Mutatieproces_Property::getByVgeId($dao->vge_id);
    
    // get adress with entity_id
    $sql = "SELECT id, street_name, street_number, postal_code, location_type_id FROM civicrm_address WHERE location_type_id = '1' AND contact_id = '" . $dao->entity_id . "' LIMIT 1";
    $address_dao = array();
    $address_dao = CRM_Core_DAO::executeQuery($sql);
    $address_dao->fetch();
    
    // check if empty
    if(empty($vge['vge_street_name']) or empty($vge['vge_street_number']) or empty($vge['vge_postal_code'])){
      continue;
    }
    
    if(!$address_dao->N){
      continue;
    }
    
    // check if adress is the same
    if($vge['vge_street_name'] == $address_dao->street_name and $vge['vge_street_number'] == $address_dao->street_number and $vge['vge_postal_code'] == $address_dao->postal_code){
      // set location_type_id on Oud (6)
      $sql = "UPDATE civicrm_address SET location_type_id = '6' WHERE id = '" . $address_dao->id . "'";
      CRM_Core_DAO::executeQuery($sql);
    }
  }
}