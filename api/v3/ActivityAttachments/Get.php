<?php
/**
 * ActivityAttachments.Get API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_activity_attachments_get_spec(&$spec) {
  $spec['activity_id']['api.required'] = 1;
}

/**
 * ActivityAttachments.Get API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_activity_attachments_get($params) {
  if (array_key_exists('activity_id', $params) and !empty($params['activity_id'])) {
    $returnValues = array();
    $returnValues = CRM_Core_BAO_File::getEntityFile('civicrm_activity', $params['activity_id'], TRUE);
    
    return civicrm_api3_create_success($returnValues, $params, 'ActivityAttachments', 'Get');
  } else {
    throw new API_Exception(/*errorMessage*/ 'No activity_id ', /*errorCode*/ 1234);
  }
}

