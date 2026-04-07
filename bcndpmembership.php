<?php

require_once 'bcndpmembership.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function bcndpmembership_civicrm_config(&$config) {
  _bcndpmembership_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function bcndpmembership_civicrm_install() {
  return _bcndpmembership_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function bcndpmembership_civicrm_enable() {
  return _bcndpmembership_civix_civicrm_enable();
}

/**
 * Gets called after a database write
 *
 * @param string $action - the operation being performed likely a create, edit, or delete
 * @param string $entity - the object type being performed on likely Individual, Contribution, or Membership
 * @param int $id - the object id on which the operation is performed on
 * @param array $object - reference to an array containing the data
 */
function bcndpmembership_civicrm_postCommit($action, $entity, $id, &$object) {
  \Drupal::logger('bcndpmembership')->notice('bcndpmembership_civicrm_post_'.$action.'_'.$entity);
  if (function_exists("bcndpmembership_civicrm_post_{$action}_{$entity}")) {
    \Drupal::logger('bcndpmembership')->notice("bcndpmembership_civicrm_post_{$action}_{$entity}(\$id, &\$object)<pre>\n\$id=$id\n\$object=" . var_export($object,1) . '</pre>');
    $tz = date_default_timezone_get();
    date_default_timezone_set('UTC');
    call_user_func("bcndpmembership_civicrm_post_{$action}_{$entity}", $id, $object);
    date_default_timezone_set($tz);
  }
}

function bcndpmembership_civicrm_pre($action, $entity, $id, &$params) {
  \Drupal::logger('bcndpmembership')->notice('bcndpmembership_civicrm_pre_'.$action.'_'.$entity);
  if (function_exists("bcndpmembership_civicrm_pre_{$action}_{$entity}")) {
    \Drupal::logger('bcndpmembership')->notice("bcndpmembership_civicrm_pre_{$action}_{$entity}(\$id, &\$object)<pre>\n\$id=$id\n\$params=" . var_export($params,1) . '</pre>');
    $tz = date_default_timezone_get();
    date_default_timezone_set('UTC');
    call_user_func("bcndpmembership_civicrm_pre_{$action}_{$entity}", $id, $params);
    date_default_timezone_set($tz);
  }
}

/**
 * This function will use cURL to retrieve the contents of a specified URL.
 */
function getUrlCurl($url) {
  $ch = curl_init();
  curl_setopt( $ch, CURLOPT_URL, $url );
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt( $ch, CURLOPT_TIMEOUT, 5);

  $content = curl_exec( $ch );
  $response = curl_getinfo( $ch );

  curl_close ( $ch );

  if ($response['http_code'] != '200')
    return -1;
  else
    return $content;
}

/**
 * This function decodes a passed in string.
 */
function cleanJson($json) {
  $json = substr($json, strpos($json,'{')+1, strlen($json));
  $json = substr($json, 0, strrpos($json,'}'));
  //$json = preg_replace('/(^|,)([\\s\\t]*)([^:]*) (([\\s\\t]*)):(([\\s\\t]*))/s', '$1"$3"$4:', trim($json));
  return json_decode('{'.$json.'}', true);
}

/**
 * This function gets the geocoding information from Google API.
 */
function getGeocode($address) {
  $config = CRM_Core_Config::singleton();
  if (!empty($config->geoAPIKey)) {
    $add = '&key=' . urlencode($config->geoAPIKey);
  }
  $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . $address . "&sensor=false" . $add;
  $json = getUrlCurl($url);
  if ($json == '-1')
    return(-1);
  $result = cleanJson($json);
  return $result;
}

/**
 * This hook is fired when a new contribution is CREATED.
 */
function bcndpmembership_civicrm_post_create_Contribution($id, $object) {

  // Contribution Type ID 501 (Nomination Fees) should not update OR create a new membership AND the Contribution Amount should be >=$1
  // KG - this only works for CASH, CHQ payment methods
  if ($object->financial_type_id != '501' && $object->total_amount >= 1 && $object->contribution_status_id == 1) {

    $contributionId = $object->id;
    $params = array(
      'id' => $contributionId,
      'version' => 3,
    );
    $result = civicrm_api('contribution', 'get', $params);
    \Drupal::logger('bcndpmembership')->notice("<pre>\n\$id=$id\n\$object=" . var_export($result,1) . '</pre>');
    if ( $result['is_error'] == 0) {
      $date = $result['values'][$contributionId]['receive_date'];
    }
    $c = strtotime(date("Y-m-d", strtotime($date)) . " +1 year");
    $newEndDate = date("Y-m-d", $c);
    \Drupal::logger('bcndpmembership')->notice('$date= ' . $date . '$newEndDate=' . $newEndDate);

    $constituencyName = bcndpmembership_civicrm_post_Address($object->contact_id);

    $contributionType = $object->financial_type_id;
    $contributionSource = $object->source;
    if (!empty($constituencyName)) { //&& $constituencyName != -1) {
      \Drupal::logger('bcndpmembership')->notice('contact_id = ' . $object->contact_id . 'constituencyName = ' . $constituencyName . 'newEndDate = ' . $newEndDate . 'contributionId = ' . $contributionId . 'date = ' . $date . 'contributionType = ' . $contributionType . 'contributionSource = ' . $contributionSource);
      bcndpmembership_retype_Membership($object->contact_id, $constituencyName, $newEndDate, $contributionId, $date, $contributionType, $contributionSource);
    }
  }
}

/**
 * This hook is fired when...
 */
function bcndpmembership_civicrm_post_edit_Contribution($id, $object) {
  \Drupal::logger('bcndpmembership')->notice("in postCommit_edit_Contribution");
  \Drupal::logger('bcndpmembership')->notice("<pre>\n\$id=$id\n\$object=" . var_export($object,1) . '</pre>');

  // Contribution Type ID 501 (Nomination Fees) should not update OR create a new membership AND the Contribution Amount should be >=$1
  // KG - this only works for CASH, CHQ payment methods
  if ($object->financial_type_id != '501' && $object->total_amount >= 1 && $object->contribution_status_id == 1) {

    $contributionId = $object->id;
    $params = array(
      'id' => $contributionId,
      'version' => 3,
    );
    $result = civicrm_api('contribution', 'get', $params);
    \Drupal::logger('bcndpmembership')->notice("<pre>\n\$id=$id\n\$object=" . var_export($result,1) . '</pre>');
    if ( $result['is_error'] == 0) {
      $date = $result['values'][$contributionId]['receive_date'];
    }
    $c = strtotime(date("Y-m-d", strtotime($date)) . " +1 year");
    $newEndDate = date("Y-m-d", $c);
    \Drupal::logger('bcndpmembership')->notice('$date= ' . $date . '$newEndDate=' . $newEndDate);

    $constituencyName = bcndpmembership_civicrm_post_Address($object->contact_id);

    $contributionType = $object->financial_type_id;
    $contributionSource = $object->source;
    if (!empty($constituencyName)) { //&& $constituencyName != -1) {
      \Drupal::logger('bcndpmembership')->notice('contact_id = ' . $object->contact_id . 'constituencyName = ' . $constituencyName . 'newEndDate = ' . $newEndDate . 'contributionId = ' . $contributionId . 'date = ' . $date . 'contributionType = ' . $contributionType . 'contributionSource = ' . $contributionSource);
      bcndpmembership_retype_Membership($object->contact_id, $constituencyName, $newEndDate, $contributionId, $date, $contributionType, $contributionSource);
    }
  }
}

/**
 * This hook is fired BEFORE a Contribution is EDITED [which is what we need when we CREATE a Contribution with Credit Card and ACH payment methods]
 */
/*function bcndpmembership_civicrm_pre_edit_Contribution($id, $params) {
  $previousContribution = $params['prevContribution'];

  if ($previousContribution->contribution_status_id && $params['contribution_status_id']) {
    if ($previousContribution->contribution_status_id != 1 && $params['contribution_status_id'] == 1) {

      // Contribution Type ID 501 (Nomination Fees) should not update OR create a new membership AND the Contribution Amount should be >=$1
      if ($previousContribution->financial_type_id != '501' && $previousContribution->total_amount >= 1) {

        // Unlike in post contribution hook - this date is already formatted for us
        $date = $previousContribution->receive_date;
        $c = strtotime(date("Y-m-d", strtotime($date)) . " +1 year");
        $newEndDate = date("Y-m-d", $c);
        \Drupal::logger('bcndpmembership')->notice('$date= ' . $date . '$newEndDate=' . $newEndDate);

        $constituencyName = bcndpmembership_civicrm_post_Address($previousContribution->contact_id);
        $contributionId = $params['id'];
        $contributionType = $previousContribution->financial_type_id;
        if (!empty($constituencyName)) {
          bcndpmembership_retype_Membership($previousContribution->contact_id, $constituencyName, $newEndDate, $contributionId, $date, $contributionType, $previousContribution->source);
        }
      }
    }

   }
}*/

/**
 * Form hook
 */
function bcndpmembership_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
$params = array(
    'title' => 'Constituency Details',
    'version' => 3,
  );

  $result = civicrm_api( 'custom_group', 'get', $params );
  if ( $result['is_error'] == 0) {
    $custom_group_id = $result['id'];
  }

  if ( $formName == 'CRM_Contact_Form_CustomData' ) {
    if ( $form->_groupID == $custom_group_id ) {

      $overrideConstituency='';$overrideConstituencyTransferName='';$overrideConstituencyTransferName_id='';
      $overrideRiding='';$overrideRidingTransferName='';$overrideRidingTransferName_id='';

      foreach ( array( 'Override Constituency', 'Constituency - Transferred To Name', 'Override Riding', 'Riding - Transferred To') as $field ) {
        $params = array(
          'custom_group_id' => $custom_group_id,
          'label' => $field,
          'version' => 3,
        );
        $result = civicrm_api('custom_field', 'get', $params);

        $custom_name = 'custom_' . $result['id'];
        if ($field == 'Override Constituency') { $overrideConstituency_custom_id = $custom_name; }
        if ($field == 'Constituency - Transferred To Name') {  $overrideConstituencyName_custom_id = $custom_name; }
        if ($field == 'Override Riding') { $overrideRiding_custom_id = $custom_name; }
        if ($field == 'Riding - Transferred To') {  $overrideRidingName_custom_id = $custom_name; }
      }

      if (preg_grep("/$overrideConstituency_custom_id/", array_keys($fields)) > 0) {
        $field_key = preg_grep("/$overrideConstituency_custom_id/", array_keys($fields));
        $overrideConstituency = $fields[$field_key[key($field_key)]];
      }

      if (preg_grep("/$overrideConstituencyName_custom_id/", array_keys($fields)) > 0) {
        $field_key = preg_grep("/$overrideConstituencyName_custom_id/", array_keys($fields));
        $overrideConstituencyTransferName = $fields[$field_key[key($field_key)]];
        $overrideConstituencyTransferName_id = $field_key[key($field_key)];
      }

      if (preg_grep("/$overrideRiding_custom_id/", array_keys($fields)) > 0) {
        $field_key = preg_grep("/$overrideRiding_custom_id/", array_keys($fields));
        $overrideRiding = $fields[$field_key[key($field_key)]];
      }

      if (preg_grep("/$overrideRidingName_custom_id/", array_keys($fields)) > 0) {
        $field_key = preg_grep("/$overrideRidingName_custom_id/", array_keys($fields));
        $overrideRidingTransferName = $fields[$field_key[key($field_key)]];
        $overrideRidingTransferName_id = $field_key[key($field_key)];
      }

      if (trim($overrideConstituencyTransferName) == true && $overrideConstituency != 1) {
        $errors[$overrideConstituencyTransferName_id] = ts( 'Constituency - Transferred To Name should not be filled out when Override Constituency is not set to Yes' );
      }
      if (trim($overrideConstituencyTransferName) == false && $overrideConstituency == '1') {
        $errors[$overrideConstituencyTransferName_id] = ts( 'Constituency - Transferred To Name should be filled out when Override Constituency is set to Yes' );
      }
      if (trim($overrideRidingTransferName) == true && $overrideRiding != 1) {
        $errors[$overrideRidingTransferName_id] = ts( 'Riding - Transferred To should not be filled out when Override Riding is not set to Yes' );
      }
      if (trim($overrideRidingTransferName) == false && $overrideRiding == '1') {
        $errors[$overrideRidingTransferName_id]  = ts( 'Riding - Transferred To should be filled out when Override Riding is set to Yes' );
      }
    }
  }
  return;
}

function bcndpmembership_civicrm_postProcess( $formName, &$form ) {

  if ( $formName == 'CRM_Contact_Form_CustomData' ) {
    $params = array(
      'title' => 'Constituency Details',
      'version' => 3,
    );

    $result = civicrm_api( 'custom_group', 'get', $params );
    if ( $result['is_error'] == 0) {
      $custom_group_id = $result['id'];
    }

    if ( $form->_groupID == $custom_group_id ) {

      foreach ( array( 'Constituency - Primary', 'Override Constituency', 'Constituency - Transferred To Name') as $field ) {
        $custom_value = '';
        $params = array(
          'custom_group_id' => $custom_group_id,
          'label' => $field,
          'version' => 3,
        );
        $result = civicrm_api('custom_field', 'get', $params);

        $contact_id = $form->_entityId;
        $custom_name = 'custom_' . $result['id'];
        $custom_value = bcndpmembership_custom_value($contact_id, $custom_name);
        if ($field == 'Constituency - Primary') { $constituencyName = $custom_value; }
        if ($field == 'Override Constituency') { $overrideConstituency = $custom_value; }
        if ($field == 'Constituency - Transferred To Name') {  $overrideConstituencyName = $custom_value; }
      }

      if ($overrideConstituency != 1) {
        if (!empty($constituencyName)) {
          bcndpmembership_retype_Membership($contact_id, $constituencyName, $newEndDate="", $contributionID="", $date="", $contributionType="", $contributionSource);
        }
      }
      if ($overrideConstituency == 1) {
        if (!empty($overrideConstituencyName)) {
          bcndpmembership_retype_Membership($contact_id, $overrideConstituencyName, $newEndDate="", $contributionID="", $date="", $contributionType="", $contributionSource);
        }
      }

    }
  }

  if ( $formName == 'CRM_Contribute_Form_Contribution' ) {
    if (isset($form->_values['contribution_status_id']) && isset($form->_submitValues['contribution_status_id'])) {
    if ($form->_values['contribution_status_id'] != 1 && $form->_submitValues['contribution_status_id'] == 1) {

      // Contribution Type ID 501 (Nomination Fees) should not update OR create a new membership AND the Contribution Amount should be >=$1
      if ($form->_submitValues['financial_type_id'] != '501' && $form->_submitValues['total_amount'] >= 1) {

        // Unlike in post contribution hook - this date is already formatted for us
        $date = $form->_submitValues['receive_date'];
        $c = strtotime(date("Y-m-d", strtotime($date)) . " +1 year");
        $newEndDate = date("Y-m-d", $c);
        \Drupal::logger('bcndpmembership')->notice('$date= ' . $date . '$newEndDate=' . $newEndDate);

        $constituencyName = bcndpmembership_civicrm_post_Address($form->_contactID);
        $contributionId = $form->_id;
        $contributionType = $form->_contributionType;
        if (!empty($constituencyName)) {
          bcndpmembership_retype_Membership($form->_contactID, $constituencyName, $newEndDate, $contributionId, $date, $contributionType, $contributionSource);
        }
      }

    }
  }
  }
}

/**
 * This hook if fired based on whether when a custom group is accessed.
 *
 * Group 32 (Recurring) - encrypts credit card number and bank account info.
 * Group 40 (Recurring_Online_Sign_up) - encrypts credit card number and security code.
 *
 * @param string $action			the operation to be performed likely create, edit, or delete
 * @param int $groupID			the id of the custom group
 * @param int $entityID			the entity id the operation is being performed on such as a contact id
 * @param array &$params		a reference to the array containing data
 * @return none
 */
function bcndpmembership_civicrm_custom( $action, $groupID, $entityID, &$params ) {
  if ( $action != 'create' && $action != 'edit' ) {
    return;
  }

  $params = array(
    'title' => 'Constituency Details',
    'version' => 3,
  );

  $result = civicrm_api( 'custom_group', 'get', $params );
  if ( $result['is_error'] == 0) {
    $custom_group_id = $result['id'];
  }

  if ($groupID ==  $custom_group_id) {

    foreach ( array( 'Override Constituency', 'Constituency - Transferred To Name') as $field ) {
      $custom_value = '';
      $params = array(
        'custom_group_id' => $custom_group_id,
        'label' => $field,
        'version' => 3,
      );
      $result = civicrm_api('custom_field', 'get', $params);

      $contact_id = $entityID;
      $custom_name = 'custom_' . $result['id'];
      $custom_value = bcndpmembership_custom_value($contact_id, $custom_name);
      if ($field == 'Override Constituency') { $overrideConstituency = $custom_value; }
      if ($field == 'Constituency - Transferred To Name') {  $overrideConstituencyName = $custom_value; }
    }

    // compare pre and post - ONLY if $overrideConstituency has changed to == 1 is there a need to reType the Membership here

    if ($overrideConstituency == 1) {
      if (!empty($overrideConstituencyName)) {
        bcndpmembership_retype_Membership($contact_id, $overrideConstituencyName, $newEndDate="", $contributionID="", $date="", $contributionType="", $contributionSource);
      }
    }

  }

  else if ($groupID == 29) {
    // deleted custom check box for Create Membership from the Create Contribution screen. That's all handled by the post create contribution hook.
  }
}

/**
 * This function will convert the abbreviated street type to it's full name.
 */
function makeFullAddress($street_address) {
  $streetShort = array("Ave", "Blvd", "Cres", "Crt", "Dr", "Pl", "Rd", "St");
  $streetLong = array("Avenue", "Boulevard", "Crescent", "Court", "Drive", "Place", "Road", "Street");
  $streetType = str_replace($streetShort, $streetLong, substr($street_address, strrpos($street_address, ' '), strlen($street_address)));
  $street_address = substr($street_address, 0, strrpos($street_address, ' ')) . $streetType;

  // if $street_address starts with # strip it - else Google will NOT return any lat/lon - and we'd default to postal code method
  if ($street_address[0] == '#') {
    $street_address = substr($street_address, 1);
  }

  return $street_address;
}

/**
 * These two hooks fire when an ADDRESS is EDITED or CREATED. This function will then retrieve constituency and riding information from the contact's address and geocoding.
 */
function bcndpmembership_civicrm_post_edit_Address($id, $object) {
  if ($object->is_primary == 1) {
    $constituencyName = bcndpmembership_civicrm_post_Address($object->contact_id);
    if (!empty($constituencyName)) { //} && $constituencyName != -1) {
      bcndpmembership_retype_Membership($object->contact_id, $constituencyName, $newEndDate="", $contributionID="", $date="", $contributionType="", $contributionSource="");
    }
  }
}

function bcndpmembership_civicrm_post_create_Address($id, $object) {
  if ($object->is_primary == 1) {
    $constituencyName = bcndpmembership_civicrm_post_Address($object->contact_id);
    if (!empty($constituencyName)) { //} && $constituencyName != -1) {
      bcndpmembership_retype_Membership($object->contact_id, $constituencyName, $newEndDate="", $contributionID="", $date="", $contributionType="", $contributionSource="");
    }
  }
}

function bcndpmembership_civicrm_post_Address($userID) {
  $address = "";
  // pull the primary address [$address] and postal code [$postcode] info out of the database ourselves.

  $address_api = bcndpmembership_getAddress($userID);

  if (isset($address_api['street_address']) && ($address_api['street_address']) != "" && ($address_api['street_address']) != "null") {
    $street_address = makeFullAddress($address_api['street_address']);
    $address .= $street_address . " ";
  }
  if (isset($address_api['supplemental_address_1']) && ($address_api['supplemental_address_1']) != "" && ($address_api['supplemental_address_1']) != "null") {
    $address .= $address_api['supplemental_address_1'] . " ";
  }
  if (isset($address_api['city']) && ($address_api['city']) != "" && ($address_api['city']) != "null") {
    $address .= $address_api['city'] . " ";
  }
  if (isset($address_api['state_province']) && ($address_api['state_province']) != "" && ($address_api['state_province']) != "null") {
    $address .= $address_api['state_province'] . " ";
  }
  if (isset($address_api['postal_code']) && ($address_api['postal_code']) != "" && ($address_api['postal_code']) != "null") {
    $address .=  $address_api['postal_code'] . " ";
    // For opennorth.ca must be NO spaces and all UPPER CAPS
    $postcode = $address_api['postal_code'];
    $postcode = str_replace(" ", "", trim(strtoupper($postcode)));
  }

  $address = str_replace(" ", "+", trim($address));

  if ($address != "") {

   if (isset($address_api['geo_code_1']) && isset($address_api['geo_code_2']) && $address_api['geo_code_1'] != "null" && $address_api['geo_code_2'] != "null") {
        $lat = $address_api['geo_code_1'];
        $lng = $address_api['geo_code_2'];
        $result['status'] = "OK";
    }
    else {
      $result = getGeocode($address);
      if ($result['status'] == "OK") {
        $lat = $result['results'][0]['geometry']['location']['lat'];
        $lng = $result['results'][0]['geometry']['location']['lng'];
      }
    }

    $constituencyName = '';
    $constituency = '';
    $ridingName = '';
    $riding = '';

    if ($result['status'] == "OK") {

      sleep(1);
      $url = "http://represent.opennorth.ca/boundaries/?contains=" . $lat . "%2C" . $lng;
      $vote = getUrlCurl($url);

      if ($vote == '-1') {
        $constituencyName = 'Out of Service';
        $ridingName = 'Out of Service';
      }
      else {

        $data = json_decode($vote, TRUE);

        foreach ($data['objects'] as $boundary) {
          // if ($boundary['related']['boundary_set_url'] == "/boundary-sets/british-columbia-electoral-districts/") {
          if ($boundary['related']['boundary_set_url'] == "/boundary-sets/british-columbia-electoral-districts-2023-redistribution/") {
            $constituencyName = $boundary['name'];
            $constituency = $boundary['external_id'];
          }
          if ($boundary['related']['boundary_set_url'] == "/boundary-sets/federal-electoral-districts/") {
          // if ($boundary['related']['boundary_set_url'] == "/boundary-sets/federal-electoral-districts-next-election/") {
            $ridingName = $boundary['name'];
            $riding = $boundary['external_id'];
          }
        }
      }
    }
    else {
      // No lat/lng data from DataBC at the moment - but if we have a postal code use that.

      sleep(1);
      $url = "http://represent.opennorth.ca/postcodes/" . $postcode . "/";
      $vote = getUrlCurl($url);

      if ($vote == '-1') {
          $constituencyName = 'Out of Service';
          $ridingName = 'Out of Service';
      }
      else {

        $data = json_decode($vote, TRUE);

        foreach ($data['boundaries_centroid'] as $boundary) {
          // if ($boundary['related']['boundary_set_url'] == "/boundary-sets/british-columbia-electoral-districts/") {
          if ($boundary['related']['boundary_set_url'] == "/boundary-sets/british-columbia-electoral-districts-2023-redistribution/") {
            $constituencyName = $boundary['name'];
            $constituency = $boundary['external_id'];
          }
          if ($boundary['related']['boundary_set_url'] == "/boundary-sets/federal-electoral-districts/") {
          //if ($boundary['related']['boundary_set_url'] == "/boundary-sets/federal-electoral-districts-next-election/") {
            $ridingName = $boundary['name'];
            $riding = $boundary['external_id'];
          }
        }
      }
    }

    // If we have results for both - write them to the database. If we have an empty $constituencyName - Address must be out of province.
    // if (empty($constituencyName)) {
    //   $constituencyName = 'Out of Province';
    //   $ridingName = 'Out of Province';
    // }

    $params = array(
      'title' => 'Constituency Details',
      'version' => 3,
    );

    $result = civicrm_api( 'custom_group', 'get', $params );
    if ( $result['is_error'] == 0) {
      $custom_group_id = $result['id'];
    }

    foreach ( array( 'Override Constituency', 'Constituency - Transferred To Name') as $field ) {
      $custom_value = '';
      $params = array(
        'custom_group_id' => $custom_group_id,
        'label' => $field,
        'version' => 3,
      );
      $result = civicrm_api('custom_field', 'get', $params);

      $contact_id = $userID;
      $custom_name = 'custom_' . $result['id'];
      $custom_value = bcndpmembership_custom_value($contact_id, $custom_name);
      if ($field == 'Override Constituency') { $overrideConstituency = $custom_value; }
      if ($field == 'Constituency - Transferred To Name') {  $overrideConstituencyName = $custom_value; }
    }

    $uid = $userID;
    $group_name = 'Constituency Details';

    $field_name = 'Constituency - Primary';
    $value = $constituencyName;
    $result = civicrm_api( 'CustomValue', 'create', array ('version' => '3','sequential' =>'1', 'entity_id' =>$uid, "custom_" .$group_name . ":" . $field_name => $value));
    $field_name = 'Riding - Primary';
    $value = $ridingName;
    $result = civicrm_api( 'CustomValue', 'create', array ('version' => '3','sequential' =>'1', 'entity_id' =>$uid, "custom_" .$group_name . ":" . $field_name => $value));

    // KG - add Flag for massive reTyping 2024 effort
    $field_name = 'Flag';
    $value = 1;
    $result = civicrm_api( 'CustomValue', 'create', array ('version' => '3','sequential' =>'1', 'entity_id' =>$uid, "custom_" .$group_name . ":" . $field_name => $value));

    if ($overrideConstituency == 1) {
      return $overrideConstituencyName;
    }
    else {
      return $constituencyName;
    }
  }
}

/**
 * reType the Membership to a General-$constituencyName format e.g.: General-Abbotsford-Mission
 */
function bcndpmembership_retype_Membership($userID, $constituencyName, $newEndDate, $contributionId, $date, $contributionType, $contributionSource) {

  $createNewMembership = FALSE;
  if (strpos($contributionSource, 'CreateNewMembership') !== false) {
    $createNewMembership = TRUE;
  }

  $params = array(
    'contact_id' => $userID,
    'version' => 3,
  );

  $result = civicrm_api( 'membership','get',$params);
  $memStatus = "";
  if ( $result['is_error'] == 0) {
    if ($result['count'] == 1) {
      $membershipId = $result['id'];
      $memStatus = $result['values'][$membershipId]['status_id'];
      $furthest_endDate = $result['values'][$membershipId]['end_date'];
    }
    else {
      $furthest_endDate = "";
      foreach ($result['values'] as $k => $single_membership) {
        if (isset($single_membership['end_date'])) {
          if (($furthest_endDate == "") || $single_membership['end_date'] > $furthest_endDate) {
            $furthest_endDate = $single_membership['end_date'];
            $membershipId = $single_membership['id'];
            $memStatus = $single_membership['status_id'];
          }
        }
      }
    }
  }

  \Drupal::logger('bcndpmembership')->notice('membershipId = ' . $membershipId . 'memStatus = ' . $memStatus . 'furthest_endDate = ' . $furthest_endDate);

  $new_id = $current_id = $grace_id = $expired_id = $contributionTypeForMembership_id = '';
  $params = array('name' => 'New', 'version' => 3);
  $result_status = civicrm_api( 'membership_status','get',$params );
  if ( $result_status['is_error'] == 0) { $new_id = $result_status['id']; }
  $params = array('name' => 'Current', 'version' => 3);
  $result_status = civicrm_api( 'membership_status','get',$params );
  if ( $result_status['is_error'] == 0) { $current_id = $result_status['id']; }
  $params = array('name' => 'Grace', 'version' => 3);
  $result_status = civicrm_api( 'membership_status','get',$params );
  if ( $result_status['is_error'] == 0) { $grace_id = $result_status['id']; }
  $params = array('name' => 'Expired', 'version' => 3);
  $result_status = civicrm_api( 'membership_status','get',$params );
  if ( $result_status['is_error'] == 0) { $expired_id = $result_status['id']; }
  $params = array('name' => 'Lifetime', 'version' => 3);
  $result_status = civicrm_api( 'membership_status','get',$params );
  if ( $result_status['is_error'] == 0) { $lifetime_id = $result_status['id']; }
  $params = array('name' => 'Cancelled', 'version' => 3);
  $result_status = civicrm_api( 'membership_status','get',$params );
  if ( $result_status['is_error'] == 0) { $cancelled_id = $result_status['id']; }

  // KG July 16
  // Pull up someone's FED MEMBERSHIP - if any
  $params = array(
    'title' => 'Federal Membership',
    'version' => 3,
  );

  $result_Fed = civicrm_api( 'custom_group', 'get', $params );
  if ( $result_Fed['is_error'] == 0) {
    $custom_group_id = $result_Fed['id'];
  }

  $CRMID = $ExpirationDate = $CurrentJoinDate = '';
  foreach ( array( 'CRMID', 'ExpirationDate', 'CurrentJoinDate') as $field ) {
    $custom_value = '';
    $params = array(
      'custom_group_id' => $custom_group_id,
      'label' => $field,
      'version' => 3,
    );
    $result_field = civicrm_api('custom_field', 'get', $params);

    $contact_id = $userID;
    $custom_name = 'custom_' . $result_field['id'];
    $custom_value = bcndpmembership_custom_value($contact_id, $custom_name);
    if ($field == 'CRMID') { $CRMID = $custom_value; }
    if ($field == 'ExpirationDate') { $ExpirationDate = $custom_value; }
    if ($field == 'CurrentJoinDate') { $CurrentJoinDate = $custom_value; }
    }

    // KG Aug 19 - if Federal Membership has expired for more than 90 days it should have no impact
    if ($CRMID) {
    $contributionDate = $date;
    if (!empty($contributionId)) {
       $maxGraceDate = date("Y-m-d", strtotime($ExpirationDate . "+90 days" ));
       if ($contributionDate > $maxGraceDate) {
         $CRMID = $ExpirationDate = $CurrentJoinDate = '';
       }
    }
    }
  // KG July 16
  // 1003 on BCNDP; 8 on drupal7.local [there was no API for this in 4.2 - but there is one in 4.4 - use it.
  $params = array(
    'name' => 'General-',
  );
  $result_financialTypeID = civicrm_api3('membership_type', 'get', $params);
  if ( $result_financialTypeID['is_error'] == 0) {
    $result_GeneralMembershipID = $result_financialTypeID['id'];
    $contributionTypeForMembership_id = $result_financialTypeID['values'][$result_GeneralMembershipID]['financial_type_id'];
  }

  $MembershipConstituencyType_id = "";
  if (!empty($constituencyName)) {
    $name = 'General-' . $constituencyName;
    $params = array(
      'name' => $name,
      'version' => 3,
    );
    $result = civicrm_api('membership_type','get', $params);
    if ($result['is_error'] == 0 && isset($result['id'])) {
      $MembershipConstituencyType_id = $result['id'];
    }
  }

  if (empty($MembershipConstituencyType_id)) {
    $name = 'General-';
    $params = array(
      'name' => $name,
      'version' => 3,
    );
    $result = civicrm_api('membership_type','get', $params);
    if ($result['is_error'] == 0) {
      $MembershipConstituencyType_id = $result['id'];
    }
  }

  \Drupal::logger('bcndpmembership')->notice('MembershipConstituencyType_id = ' . $MembershipConstituencyType_id . 'userID = ' . $userID . 'source = ' . $source);

  if (!empty($contributionId)) {
    if ($memStatus == $new_id || $memStatus == $current_id || $memStatus == $grace_id) {
      // KG July 16
      if (!empty($CRMID)) {$newEndDate = max($ExpirationDate, $newEndDate);}
      $source = 'Status was: ' . $memStatus . '; Membership updated via API: post create contribution ID: ' . $contributionId;
      if (!empty($CRMID)) {$source = $source . '; CRMID: ' . $CRMID;}
      $params = array(
        'contact_id' => $userID,
        'id' =>  $membershipId,
        'membership_type_id' => $MembershipConstituencyType_id,
        'end_date' => $newEndDate,
        'source' => $source,
        'version' => 3,
      );
      $result = civicrm_api('membership','update', $params);
      require_once 'api/v3/MembershipStatus.php';
      $newStatus = civicrm_api3_membership_status_calc(array('membership_id' => $membershipId));
      $params = array(
        'contact_id' => $userID,
        'id' =>  $membershipId,
        'membership_type_id' => $MembershipConstituencyType_id,
        'end_date' => $newEndDate,
        'source' => $source,
        'status_id' => $newStatus['id'],
        'version' => 3,
      );
      $result = civicrm_api('membership','update', $params);
    }
    else if ($memStatus == $expired_id || ($contributionType == $contributionTypeForMembership_id && $memStatus != $lifetime_id) || ($createNewMembership && $memStatus != $lifetime_id)) {
      if (!empty($CRMID)) {$newEndDate = max($ExpirationDate, $newEndDate); $date = $CurrentJoinDate;}
      $source = 'Status was: ' . $memStatus . 'ContributionType was: ' .$contributionType . '; Membership created via API: post create contribution ID: ' . $contributionId;
      if (!empty($CRMID)) {$source = $source . '; CRMID: ' . $CRMID;}
      $params = array(
        'contact_id' => $userID,
        'membership_type_id' => $MembershipConstituencyType_id,
        'join_date' => $date,
        'start_date' => $date,
        'end_date' => $newEndDate,
        'source' => $source,
        'status_id' => 1,
        'version' => 3,
      );
      $result = civicrm_api('membership','create', $params);
      // what IF contributionID AND of specific Membership fees Contribution Type.
    }
    else if ($memStatus == $lifetime_id) {
      $source = 'Status was: ' . $memStatus . '; Membership updated via API: retype Membership';
      $params = array(
        'contact_id' => $userID,
        'id' =>  $membershipId,
        'membership_type_id' => $MembershipConstituencyType_id,
        'source' => $source,
        'version' => 3,
      );
      $result = civicrm_api('membership','update', $params);
    }
    else if ($memStatus == $cancelled_id) {
      if (!empty($CRMID)) {
      if ($CurrentJoinDate > $furthest_endDate) {
        $newEndDate = max($ExpirationDate, $newEndDate); $date = $CurrentJoinDate;
        $source = 'Status was: ' . $memStatus . 'ContributionType was: ' .$contributionType . '; Membership created via API: post create contribution ID: ' . $contributionId;
        if (!empty($CRMID)) {$source = $source . '; CRMID: ' . $CRMID;}
        $params = array(
          'contact_id' => $userID,
          'membership_type_id' => $MembershipConstituencyType_id,
          'join_date' => $date,
          'start_date' => $date,
          'end_date' => $newEndDate,
          'source' => $source,
          'status_id' => 1,
          'version' => 3,
        );
        $result = civicrm_api('membership','create', $params);
        // what IF contributionID AND of specific Membership fees Contribution Type.
      }
      }
    }
    elseif ((empty($memStatus)) && !empty($CRMID)) {
      // create a new Membership
      $newEndDate = $ExpirationDate; $date = $CurrentJoinDate;
      $source = 'Status was: ' . $memStatus . '; Membership created via API: retype Membership';
      if (!empty($CRMID)) {$source = $source . '; CRMID: ' . $CRMID;}
      $params = array(
        'contact_id' => $userID,
        'membership_type_id' => $MembershipConstituencyType_id,
        'join_date' => $date,
        'start_date' => $date,
        'end_date' => $newEndDate,
        'source' => $source,
        'status_id' => 1,
        'version' => 3,
      );
      $result = civicrm_api('membership','create', $params);
    }
    }
  else {
    // we came via post_Address or post_create_Membership hook
    if (($memStatus == $new_id || ($memStatus == $current_id && empty($CRMID)) || ($memStatus == $grace_id && empty($CRMID)) || ($memStatus == $expired_id && empty($CRMID)) || $memStatus == $lifetime_id)) {
      $source = 'Status was: ' . $memStatus . '; Membership updated via API: retype Membership';
      $params = array(
        'contact_id' => $userID,
        'id' =>  $membershipId,
        'membership_type_id' => $MembershipConstituencyType_id,
        'source' => $source,
        'version' => 3,
      );
      $result = civicrm_api('membership','update', $params);
    }
    elseif ((empty($memStatus)) && !empty($CRMID)) {
      // create a new Membership
      $newEndDate = $ExpirationDate; $date = $CurrentJoinDate;
      $source = 'Status was: ' . $memStatus . '; Membership created via API: retype Membership';
      if (!empty($CRMID)) {$source = $source . '; CRMID: ' . $CRMID;}
      $params = array(
        'contact_id' => $userID,
        'membership_type_id' => $MembershipConstituencyType_id,
        'join_date' => $date,
        'start_date' => $date,
        'end_date' => $newEndDate,
        'source' => $source,
        'status_id' => 1,
        'version' => 3,
      );
      $result = civicrm_api('membership','create', $params);
    }
    elseif (($memStatus == $expired_id) && !empty($CRMID)) {
      // create a new Membership
      $newEndDate = $ExpirationDate; $date = $CurrentJoinDate;
      $source = 'Status was: ' . $memStatus . '; Membership created via API: retype Membership';
      if (!empty($CRMID)) {$source = $source . '; CRMID: ' . $CRMID;}
      $params = array(
        'contact_id' => $userID,
        'membership_type_id' => $MembershipConstituencyType_id,
        'join_date' => $date,
        'start_date' => $date,
        'end_date' => $newEndDate,
        'source' => $source,
        'status_id' => 1,
        'version' => 3,
      );
      $result = civicrm_api('membership','create', $params);
    }
    elseif (($memStatus == $grace_id) && !empty($CRMID) || ($memStatus == $current_id) && !empty($CRMID)) {
      // updating existing Membership
      $newEndDate = max($ExpirationDate, $newEndDate, $furthest_endDate);
      $source = 'Status was: ' . $memStatus . '; KG Membership updated via API: retype Membership';
      if (!empty($CRMID)) {$source = $source . '; CRMID: ' . $CRMID;}
      $params = array(
        'contact_id' => $userID,
        'membership_type_id' => $MembershipConstituencyType_id,
        'end_date' => $newEndDate,
        'source' => $source,
        'version' => 3,
      );
      $result = civicrm_api('membership','update', $params);
      require_once 'api/v3/MembershipStatus.php';
      $newStatus = civicrm_api3_membership_status_calc(array('membership_id' => $membershipId));
      $params = array(
        'contact_id' => $userID,
        'id' =>  $membershipId,
        'membership_type_id' => $MembershipConstituencyType_id,
        'end_date' => $newEndDate,
        'source' => $source,
        'status_id' => $newStatus['id'],
        'version' => 3,
      );
      $result = civicrm_api('membership','update', $params);
    }
  }
}

/**
 * Get the PRIMARY contact address
 */
function bcndpmembership_getAddress($contact_id) {
  $address = NULL;
  $params = array(
    'version' => 3,
    'contact_id' => $contact_id,
    'is_primary' => 1,
  );

  $results = civicrm_api('address', 'get', $params);

  if ($results['is_error'] == 0) {
    $address = array_shift($results['values']);
  }

  $address = isset($address) ? $address : array();
  // add actual names for province and country instead of just having ids
  require_once 'CRM/Core/BAO/Address.php';
  CRM_Core_BAO_Address::fixAddress($address);

  return $address;
}

function bcndpmembership_custom_value($contact_id, $custom_name) {
  // contact_id an integer like 102
  // $custom_name in format like  "custom_22"
  $params = array(
    'return' => $custom_name,
    'version' => 3,
    'id' => $contact_id,
  );

  $result = civicrm_api( 'contact','get',$params );

  if (isset($result['values'][$result['id']][$custom_name]) ) {
    //This is the value of the custom field
    $custom_value = $result['values'][$result['id']][$custom_name];
    $custom_id = substr($custom_name[0], 7 );

    //This gets information about the custom field from the CustomField entity
    $results2=civicrm_api("CustomField","get", array ('version' => '3','sequential' =>'1', 'id' =>$custom_id));
    $custom_html_type = array();
    if (isset($results2['values'][0]['html_type'])) {
      $custom_html_type = $results2['values'][0]['html_type'];
    }
    if (in_array($custom_html_type, array( "Select", "Radio", "Multi-Select", "Advanced Multi-Select", "Autocomplete Select" )) ) {
      $custom_option_group_id = $results2['values'][0]['option_group_id'];
      //if it is an option group then we get the option values using the OptionValue entity:
      $results3=civicrm_api("OptionValue","get", array ('version' => '3','sequential' =>'1', 'option_group_id' =>$custom_option_group_id, 'value' => $custom_value));

      $custom_label=$results3['values'][0]['label'];
      //if it is a multi-value type custom field we return the label of the OptionValue as the 'user friendly' value of the field
      return $custom_label;

    } else { // not 'select' etc
      // if it is an ordinary custom field we return the value:
      return $custom_value;
    }
  }
}

/**
 * Implementation of hook_civicrm_searchTasks().
 */
function bcndpmembership_civicrm_searchTasks($objectType, &$tasks ) {
  if ( $objectType == 'contact' ) {
    $alreadyinlist1 = FALSE;
    $alreadyinlist2 = FALSE;
    $alreadyinlist3 = FALSE;
    foreach ($tasks as $key => $task) {
      if($task['class'] == 'CRM_Bcndpmembership_Task_RetypeBCNDPMembership') {
        $alreadyinlist1 = TRUE;
      }
      if($task['class'] == 'CRM_Bcndpmembership_Task_GeoCodeRetypeBCNDPMembership') {
        $alreadyinlist2 = TRUE;
      }
     if($task['class'] == 'CRM_Bcndpmembership_Task_CalcRetypeBCNDPMembership') {
        $alreadyinlist3 = TRUE;
      }
    }
    if (!$alreadyinlist1) {
      $tasks[] = array (
        'title' => ts('BCNDP Membership - reType'),
        'class' => 'CRM_Bcndpmembership_Task_RetypeBCNDPMembership',
        'result' => TRUE);
    }
    if (!$alreadyinlist2) {
      $tasks[] = array (
        'title' => ts('BCNDP Membership - GeoCode and reType'),
        'class' => 'CRM_Bcndpmembership_Task_GeoCodeRetypeBCNDPMembership',
        'result' => TRUE);
    }
    if (!$alreadyinlist3) {
      $tasks[] = array (
        'title' => ts('BCNDP Membership - Calculate and reType'),
        'class' => 'CRM_Bcndpmembership_Task_CalcRetypeBCNDPMembership',
        'result' => TRUE);
    }
  }
}

function _bcndpmembership_civicrm_domain_info($key) {
  static $domain;
    if (empty($domain)) {
      $domain = civicrm_api('Domain', 'getsingle', array('version' => 3, 'current_domain' => TRUE));
    }
    switch($key) {
      case 'version':
        return explode('.',$domain['version']);
      default:
        if (!empty($domain[$key])) {
          return $domain[$key];
        }
        $config_backend = unserialize($domain['config_backend']);
        return $config_backend[$key];
    }
}

function _bcndpmembership_civicrm_nscd_fid() {
  $version = _bcndpmembership_civicrm_domain_info('version');
  return (($version[0] <= 4) && ($version[1] <= 3)) ? 'next_sched_contribution' : 'next_sched_contribution_date';
}
