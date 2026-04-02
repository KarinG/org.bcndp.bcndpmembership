<?php

require_once('CRM/Contribute/Form/Task.php');

/**
 * This class provides the common functionality for issuing CDN Tax Receipts for
 * one or a group of contact ids.
 */
class CRM_Bcndpmembership_Task_GeoCodeRetypeBCNDPMembership extends CRM_Contact_Form_Task {

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

   CRM_Utils_System::setTitle(ts('GeoCode and reType BCNDP Membership'));

   $buttons = array(
      array(
        'type' => 'cancel',
        'name' => ts('Back'),
      ),
      array(
        'type' => 'next',
        'name' => 'GeoCode and reType BCNDP Membership',
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
      $constituencyName = bcndpmembership_civicrm_post_Address($contactId);
      if (!empty($constituencyName)) { //&& $constituencyName != -1) {
        bcndpmembership_retype_Membership($contactId, $constituencyName, $newEndDate="", $contributionID="", $date="", $contributionType="", $contributionSource="");
      }
      $test = 1;
    }
  }

}

