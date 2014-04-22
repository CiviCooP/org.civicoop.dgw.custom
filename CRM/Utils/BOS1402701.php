<?php

/******
 * FIX voor BOS1402701
 * 
 * vanwege de aanpassing in CRM_Core_BAO_CustomValueTable::getEntityValues
 * kloppen de 'ids' die in de key van array staan niet meer. 
 * De IDs zijn niet meer de 'ids' van het veld maar fieldid_id bijv 23_657800 (hier moeten we alleen de nummer 23 hebben)
 * 
 * 
 * Deze klasse wordt gebruikt in de volgende bestanden
 * - CRM/Activity/BAO/Activity.php (Regel 2635)
 */
class CRM_Utils_BOS1402701 {
  
  public static function fix($array) {
    $return = array();
    foreach($array as $value) {
      $keys = explode("_", $value);
      if (isset($keys[0])) {
        $return[] = $keys[0];
      }
    }
    return $keys;
  }
  
}