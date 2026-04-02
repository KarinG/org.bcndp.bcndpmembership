<?php

require_once('CRM/Contribute/Form/Task.php');

/**
 * This class provides the common functionality for issuing CDN Tax Receipts for
 * one or a group of contact ids.
 */
class CRM_Bcndpmembership_Task_RetypeBCNDPMembership extends CRM_Contact_Form_Task {

  const MAX_COUNT = 10;

  private $_receipts;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {

    parent::preProcess();

  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {

   CRM_Utils_System::setTitle(ts('reType BCNDP Membership'));

   $buttons = array(
      array(
        'type' => 'cancel',
        'name' => ts('Back'),
      ),
      array(
        'type' => 'next',
        'name' => 'reType BCNDP Membership',
        'isDefault' => TRUE,
        'js' => array('onclick' => "return submitOnce(this,'{$this->_name}','" . ts('Processing') . "');"),
      ),
    );
    $this->addButtons($buttons);

  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */

  function postProcess() {

    $params = $this->controller->exportValues($this->_name);

    foreach ($this->_contactIds as $item => $contactId) {
      // KG
      // This would also GeoCode:
      // $constituencyName = bcndpmembership_civicrm_post_Address($contactId);
      // Instead: grab constituencyName from database

      $constituencyName = '';
      $params = array(
        'title' => 'Constituency Details',
        'version' => 3,
      );

      require_once 'api/api.php';
      $result = civicrm_api( 'custom_group', 'get', $params );
      $test = 1;
      if ( $result['is_error'] == 0) {
        $custom_group_id = $result['id'];
      }

      foreach ( array( 'Constituency - Primary', 'Override Constituency', 'Constituency - Transferred To Name') as $field ) {
        $custom_value = '';
        $params = array(
          'custom_group_id' => $custom_group_id,
          'label' => $field,
          'version' => 3,
        );
        $result = civicrm_api('custom_field', 'get', $params);

        $custom_name = 'custom_' . $result['id'];
        $custom_value = bcndpmembership_custom_value($contactId, $custom_name);
        if ($field == 'Constituency - Primary') { $constituencyName = $custom_value; }
        if ($field == 'Override Constituency') { $overrideConstituency = $custom_value; }
        if ($field == 'Constituency - Transferred To Name') {  $overrideConstituencyName = $custom_value; }
        }

        if ($overrideConstituency == 1) {
          if (!empty($overrideConstituencyName)) {
            bcndpmembership_retype_Membership($contactId, $overrideConstituencyName, $newEndDate="", $contributionID="", $date="", $contributionType="", $contributionSource="");
          }
        }
        else {// if (!empty($constituencyName)) { //&& $constituencyName != -1) {
          bcndpmembership_retype_Membership($contactId, $constituencyName, $newEndDate="", $contributionID="", $date="", $contributionType="", $contributionSource="");
        }
        $test = 1;
      }
  }

}

