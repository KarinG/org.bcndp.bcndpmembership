<?php

/**
 * Job.Retypeconstituencymembership API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_job_Retypeconstituencymembership_spec(&$params) {
  $params['start'] = array(
    'title' => 'Starting Contact ID',
    'type' => CRM_Utils_Type::T_INT,
  );
  $params['end'] = array(
    'title' => 'Ending Contact ID',
    'type' => CRM_Utils_Type::T_INT,
  );
}

/**
 * Job.Retypeconstituencymembership API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_job_Retypeconstituencymembership($params) {
  $test = 1;
  $start_cid = $params['start'];
  $end_cid = $params['end'];

  for ($contactId = $start_cid; $contactId <= $end_cid; $contactId++) {
    $constituencyName = bcndpmembership_civicrm_post_Address($contactId);
    if (!empty($constituencyName)) { //&& $constituencyName != -1) {
      bcndpmembership_retype_Membership($contactId, $constituencyName, $newEndDate="", $contributionID="", $date="", $contributionType="", $contributionSource="");
    }
    $test = 1;
  }

  // if we get here - report success:
  $result['messages'] = 'Job Executed - please verify Results';
  return civicrm_api3_create_success($result['messages']);

}

