<?php
// $Id$

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2012
 * $Id$
 *
 */
class CRM_Bcndpmembership_Form_Report_bcndpMembershipContribution extends CRM_Report_Form {

  protected $_addressField = FALSE;

  protected $_emailField = FALSE;

  protected $_phoneField = FALSE;

  protected $_summary = NULL;

  protected $_customGroupExtends = array('Membership','Individual','Contact','Contribution');
  protected $_customGroupGroupBy = FALSE;

  static private $nscd_fid = '';
  static private $processors = array();
  static private $version = array();
  static private $financial_types = array();
  static private $prefixes = array();
  static private $contributionStatus = array();

  function __construct() {
    self::$nscd_fid = _bcndpmembership_civicrm_nscd_fid();
    self::$version = _bcndpmembership_civicrm_domain_info('version');
    self::$financial_types = (self::$version[0] <= 4 && self::$version[1] <= 2) ? array() : CRM_Contribute_PseudoConstant::financialType();
    if (self::$version[0] <= 4 && self::$version[1] < 4) {
      self::$prefixes = CRM_Core_PseudoConstant::individualPrefix();
      self::$contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus();
    }
    else {
      self::$prefixes =  CRM_Contact_BAO_Contact::buildOptions('individual_prefix_id');
      self::$contributionStatus = CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id');
    }

    $params = array('version' => 3, 'sequential' => 1, 'is_test' => 0, 'return.name' => 1);
    $result = civicrm_api('PaymentProcessor', 'get', $params);
    foreach($result['values'] as $pp) {
      self::$processors[$pp['id']] = $pp['name'];
    }

    $this->_columns = array(
      'bcndp_revenuesharing_recurring_series' =>
      array(
        'dao' => 'CRM_Contribute_DAO_ContributionRecur',
        'fields' =>
        array(
          'base_allocated_to' =>
          array(
            'title' => "Share base amount with",
            'default' => TRUE,
          ),
          'increase_allocated_to' =>
          array(
            'title' => "Share increase bonus with",
            'default' => TRUE,
          ),
        ),
        'filters' =>
        array(
          'base_allocated_to' =>
          array(
            'title' => "Share base amount with",
            'operator' => 'like',
          ),
          'increase_allocated_to' =>
          array(
            'title' => "Share increase bonus with",
            'operator' => 'like',
          ),
        ),
      ),
      'civicrm_contact' =>
      array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        array(
          'sort_name' =>
          array('title' => ts('Contact Name'),
            'required' => TRUE,
            'default' => TRUE,
            'no_repeat' => TRUE,
          ),
          'id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'first_name' =>
          array('title' => ts('First Name'),
            'no_repeat' => TRUE,
	    'default' => TRUE,
          ),
          'last_name' =>
          array('title' => ts('Last Name'),
            'no_repeat' => TRUE,
	    'default' => TRUE,
          ),
          'do_not_email' =>
          array('title' => ts('Do Not Email'),
	              'default' => TRUE,
          ),
          'do_not_phone' =>
          array('title' => ts('Do Not Phone'),
	                        'default' => TRUE,
          ),
          'do_not_mail' =>
          array('title' => ts('Do Not Mail'),
	                        'default' => TRUE,
          ),
          'do_not_sms' =>
          array('title' => ts('Do Not SMS'),
          ),
          'do_not_trade' =>
          array('title' => ts('Do Not Trade'),
          ),
        ),
        'filters' =>
        array(
          'sort_name' =>
          array('title' => ts('Contact Name'),
            'operator' => 'like',
          ),
          'id' =>
          array('no_display' => TRUE),
          'do_not_email' =>
          array('title' => ts('Do Not Email'),
          ),
          'do_not_phone' =>
          array('title' => ts('Do Not Phone'),
          ),
          'do_not_mail' =>
          array('title' => ts('Do Not Mail'),
          ),
          'do_not_sms' =>
          array('title' => ts('Do Not SMS'),
          ),
          'do_not_trade' =>
          array('title' => ts('Do Not Trade'),
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_membership' =>
        array(
          'dao' => 'CRM_Member_DAO_Membership',
          'fields' =>
            array(
              'membership_type_id' => array(
                'title' => 'Membership Type',
                'required' => TRUE,
                'no_repeat' => TRUE,
              ),
              'membership_start_date' => array('title' => ts('Start Date'),
                'default' => TRUE,
              ),
              'membership_end_date' => array('title' => ts('End Date'),
                'default' => TRUE,
              ),
              'join_date' => array('title' => ts('Join Date'),
                'default' => TRUE,
              ),
            ),
          'filters' => array(
            'join_date' =>
              array('operatorType' => CRM_Report_Form::OP_DATE),
            'membership_start_date' =>
              array('operatorType' => CRM_Report_Form::OP_DATE),
            'membership_end_date' =>
              array('operatorType' => CRM_Report_Form::OP_DATE),
            'owner_membership_id' =>
              array('title' => ts('Membership Owner ID'),
                'operatorType' => CRM_Report_Form::OP_INT,
              ),
            'tid' =>
              array(
                'name' => 'membership_type_id',
                'title' => ts('Membership Types'),
                'type' => CRM_Utils_Type::T_INT,
                'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                'options' => CRM_Member_PseudoConstant::membershipType(),
              ),
          ),
          'grouping' => 'member-fields',
        ),
      'civicrm_membership_status' =>
        array(
          'dao' => 'CRM_Member_DAO_MembershipStatus',
          'alias' => 'mem_status',
          'fields' =>
            array('name' => array('title' => ts('Status'),
              'default' => TRUE,
            ),
            ),
          'filters' => array(
            'sid' =>
              array(
                'name' => 'id',
                'title' => ts('Status'),
                'type' => CRM_Utils_Type::T_INT,
                'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                'options' => CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'label'),
              ),
          ),
          'grouping' => 'member-fields',
        ),
      'civicrm_contribution' => array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => array(
          'id' => array(
            'no_display' => TRUE,
            'title' => ts('Contribution ID(s)'),
            'required' => TRUE,
            //'dbAlias' => "GROUP_CONCAT(contribution_civireport.id SEPARATOR ', ')",
          ),
          //'total_amount' => array(
          //  'title' => ts('Amount Contributed to date'),
          //  'required' => TRUE,
          //  'statistics' => array(
          //    'sum' => ts("Total Amount contributed")
          //  ),
          //),
        ),
        'filters' => array(
          'total_amount' => array(
            'title' => ts('Total Amount'),
            'operatorType' => CRM_Report_Form::OP_FLOAT,
            'type' => CRM_Utils_Type::T_FLOAT,
          ),
        ),
      ),
      'civicrm_iats_customer_codes' =>
        array(
          'dao' => 'CRM_Contribute_DAO_Contribution',
          'order_bys' => array(
            'expiry' => array(
              'title' => ts("Expiry Date"),
            ),
          ),
          'fields' =>
            array(
              'customer_code' => array('title' => 'customer code', 'default' => TRUE),
              'expiry' => array('title' => 'Expiry Date', 'default' => TRUE),
            ),
        ),
      'civicrm_contribution_recur' => array(
        'dao' => 'CRM_Contribute_DAO_ContributionRecur',
        'order_bys' => array(
          'id' => array(
            'title' => ts("Series ID"),
          ),
          //'amount' => array(
          //  'title' => ts("Current Amount"),
          //),
          'start_date' => array(
            'title' => ts('Start Date'),
          ),
          'modified_date' => array(
            'title' => ts('Modified Date'),
          ),
          self::$nscd_fid  => array(
            'title' => ts('Next Scheduled Contribution Date'),
          ),
          'cycle_day'  => array(
            'title' => ts('Cycle Day'),
          ),
          'payment_processor_id' => array(
            'title' => ts('Payment Processor'),
          ),
        ),
        'fields' => array(
          'id' => array(
            //'no_display' => TRUE,
            'required' => TRUE,
            'title' => ts('Series ID'),
          ),
       //   'recur_id' => array(
       //     'name' => 'id',
       //     'title' => ts('Series ID'),
       //   ),
        //  'invoice_id' => array(
        //    'title' => ts('Invoice ID'),
        //    'default' => FALSE,
        //  ),
          'currency' => array(
            'title' => ts("Currency")
          ),
          'amount' => array(
            'title' => ts('Series Amount'),
            'default' => TRUE,
          ),
          'contribution_status_id' => array(
            'title' => ts('Recurring Series Status'),
          ),
          'frequency_interval' => array(
            'title' => ts('Frequency interval'),
            'default' => TRUE,
          ),
          'frequency_unit' => array(
            'title' => ts('Frequency unit'),
            'default' => TRUE,
          ),
          'installments' => array(
            'title' => ts('Installments'),
            'default' => TRUE,
          ),
          'start_date' => array(
            'title' => ts('Start Date'),
            'default' => TRUE,
          ),
          'create_date' => array(
            'title' => ts('Create Date'),
          ),
          'modified_date' => array(
            'title' => ts('Modified Date'),
          ),
          'cancel_date' => array(
            'title' => ts('Cancel Date'),
          ),
          self::$nscd_fid => array(
            'title' => ts('Next Scheduled Contribution Date'),
            'default' => TRUE,
          ),
          'next_scheduled_day'  => array(
            'name' => self::$nscd_fid,
            'dbAlias' => 'DAYOFMONTH(contribution_recur_civireport.next_sched_contribution)',
            'title' => ts('Next Scheduled Day of the Month'),
          ),
          'cycle_day'  => array(
            'title' => ts('Cycle Day'),
          ),
          'failure_count' => array(
            'title' => ts('Failure Count'),
          ),
          'failure_retry_date' => array(
            'title' => ts('Failure Retry Date'),
          ),
          'payment_processor_id' => array(
            'title' => ts('Payment Processor'),
            'default' => TRUE,
          ),
        ),
        'filters' => array(
          'contribution_status_id' => array(
            'title' => ts('Recurring Series Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => self::$contributionStatus,
            //'default' => array(5),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_INT,
          ),
          'payment_processor_id' => array(
            'title' => ts('Payment Processor'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => self::$processors,
            'type' => CRM_Utils_Type::T_INT,
          ),
          'currency' => array(
            'title' => 'Currency',
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'financial_type_id' => array(
            'title' => ts('Financial Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options'  => self::$financial_types,
            'type' => CRM_Utils_Type::T_INT,
          ),
          'frequency_unit' => array(
            'title' => ts('Frequency Unit'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' =>  CRM_Core_OptionGroup::values('recur_frequency_units'),
          ),
          self::$nscd_fid  => array(
            'title' => ts('Next Scheduled Contribution Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'next_scheduled_day' => array(
            'title' => ts('Next Scheduled Day'),
            'operatorType' => CRM_Report_Form::OP_INT,
            'type' => CRM_Utils_Type::T_INT,
          ),
          'cycle_day' => array(
            'title' => ts('Cycle Day'),
            'operatorType' => CRM_Report_Form::OP_INT,
            'type' => CRM_Utils_Type::T_INT,
          ),
          'start_date' => array(
            'title' => ts('Start Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'modified_date' => array(
            'title' => ts('Modified Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'cancel_date' => array(
            'title' => ts('Cancel Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
        ),
        'grouping' => 'series-fields',
      ),
    'civicrm_address' =>
      array(
        'dao' => 'CRM_Core_DAO_Address',
        'fields' =>
        array(
          'street_address' =>
	  array(
	    'default' => TRUE
	   ),	     
          'city' =>
	   array(
	     'default' => TRUE
	  ),	    
          'supplemental_address_1' =>
           array(
	     'default' => TRUE
	  ),
          'postal_code' =>
	  array(
	     'default' => TRUE
	  ),	    
          'state_province_id' =>
          array(
	     'title' => ts('State/Province'),
	     'default' => TRUE,
          ),
          'country_id' =>
          array('title' => ts('Country'),
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_email' =>
      array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' =>
        array(
	  'email' =>
	array(
	  'default' => TRUE,
	  ),
	),
        'grouping' => 'contact-fields',
      ),
      'civicrm_phone' =>
      array(
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' =>
        array('phone' =>
	  array(
	    'default' => TRUE,	    
	  ),
	),
        'grouping' => 'contact-fields',
      ),
      'civicrm_contribution' =>
      array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' =>
        array(
 //         'contribution_id' => array(
 //           'name' => 'id',
 //           'required' => TRUE,
 //           'default' => TRUE,
 //           'csv_display' => TRUE,
 //           'title' => ts('Contribution ID'),
 //           'dbAlias' => "GROUP_CONCAT(DISTINCT contribution_civireport.id ORDER BY contribution_civireport.id SEPARATOR ', ')",
 //       ),
          'receive_date' => array(
            'title' => ts('Receive Date (most recent)'),
            'default' => TRUE,
            'dbAlias' => "MAX(contribution_civireport.receive_date)"),
     //     'total_amount' => array('title' => ts('Amount (most recent)'),
     //       'default' => TRUE,
     //     ),
        ),
        'grouping' => 'contri-fields',
      ),
    );
    parent::__construct();
  }

  function preProcess() {
    $this->assign('reportTitle', ts('Membership Contribution Report'));
    parent::preProcess();
  }

  function select() {
    $select = $this->_columnHeaders = array();

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            if ($tableName == 'civicrm_address') {
              $this->_addressField = TRUE;
            }
            elseif ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
            }
            elseif ($tableName == 'civicrm_phone') {
              $this->_phoneField = TRUE;
            }
            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            if (array_key_exists('title', $field)) {
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            }
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  function from() {
    $this->_from = NULL;

    $this->_from = "
         FROM  civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
               INNER JOIN civicrm_membership {$this->_aliases['civicrm_membership']}
                          ON {$this->_aliases['civicrm_contact']}.id =
                             {$this->_aliases['civicrm_membership']}.contact_id AND {$this->_aliases['civicrm_membership']}.is_test = 0
               LEFT  JOIN civicrm_contribution_recur   {$this->_aliases['civicrm_contribution_recur']}
                          ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution_recur']}.contact_id
               LEFT  JOIN civicrm_membership_status {$this->_aliases['civicrm_membership_status']}
                          ON {$this->_aliases['civicrm_membership_status']}.id =
                             {$this->_aliases['civicrm_membership']}.status_id ";

//    $this->_from .= "
//      LEFT JOIN civicrm_contribution  {$this->_aliases['civicrm_contribution']}
//        ON ({$this->_aliases['civicrm_contribution_recur']}.id = {$this->_aliases['civicrm_contribution']}.contribution_recur_id AND 1 = {$this->_aliases['civicrm_contribution']}.contribution_status_id)";

    $this->_from .= "
      LEFT JOIN civicrm_iats_customer_codes {$this->_aliases['civicrm_iats_customer_codes']}
        ON ({$this->_aliases['civicrm_iats_customer_codes']}.recur_id = {$this->_aliases['civicrm_contribution_recur']}.id)";

    $this->_from .= "
      LEFT JOIN bcndp_revenuesharing_recurring_series {$this->_aliases['bcndp_revenuesharing_recurring_series']}
        ON ({$this->_aliases['bcndp_revenuesharing_recurring_series']}.series_id = {$this->_aliases['civicrm_contribution_recur']}.id)";

    //used when address field is selected
    if ($this->_addressField) {
      $this->_from .= "
             LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
                       ON {$this->_aliases['civicrm_contact']}.id =
                          {$this->_aliases['civicrm_address']}.contact_id AND
                          {$this->_aliases['civicrm_address']}.is_primary = 1 AND
                          {$this->_aliases['civicrm_address']}.location_type_id != 6 AND
                          {$this->_aliases['civicrm_address']}.location_type_id != 21\n";
    }
    //used when email field is selected
    if ($this->_emailField) {
      $this->_from .= "
              LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']}
                        ON {$this->_aliases['civicrm_contact']}.id =
                           {$this->_aliases['civicrm_email']}.contact_id AND
                          {$this->_aliases['civicrm_email']}.is_primary = 1 AND
                          {$this->_aliases['civicrm_email']}.location_type_id != 6 AND
                          {$this->_aliases['civicrm_email']}.location_type_id != 21\n";
    }
    //used when phone field is selected
    if ($this->_phoneField) {
      $this->_from .= "
              LEFT JOIN civicrm_phone {$this->_aliases['civicrm_phone']}
                        ON {$this->_aliases['civicrm_contact']}.id =
                           {$this->_aliases['civicrm_phone']}.contact_id AND
                          {$this->_aliases['civicrm_phone']}.is_primary = 1 AND
                          {$this->_aliases['civicrm_phone']}.location_type_id != 6 AND
                          {$this->_aliases['civicrm_phone']}.location_type_id != 21\n";
    }

    $this->_from .= "
              LEFT JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
                        ON {$this->_aliases['civicrm_contact']}.id =
                           {$this->_aliases['civicrm_contribution']}.contact_id AND
                           {$this->_aliases['civicrm_contribution']}.contribution_status_id = 1\n";
}

  function where() {
    $clauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('operatorType', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
            }
          }

          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }

    if (empty($clauses)) {
      $this->_where = "WHERE ( 1 ) ";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $clauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  function groupBy() {
    $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_contact']}.id, {$this->_aliases['civicrm_membership']}.membership_type_id, {$this->_aliases['civicrm_membership']}.status_id";
  }

  function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_contact']}.sort_name, {$this->_aliases['civicrm_contact']}.id, {$this->_aliases['civicrm_membership']}.membership_type_id";
  }

  function postProcess() {

    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    $sql = $this->buildQuery(TRUE);

    $rows = array();
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound = FALSE;
    $checkList = array();

    foreach ($rows as $rowNum => $row) {

      if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
        // not repeat contact display names if it matches with the one
        // in previous row
        $repeatFound = FALSE;
        foreach ($row as $colName => $colVal) {
          if (CRM_Utils_Array::value($colName, $checkList) &&
            is_array($checkList[$colName]) &&
            in_array($colVal, $checkList[$colName])
          ) {
            $rows[$rowNum][$colName] = "";
            $repeatFound = TRUE;
          }
          if (in_array($colName, $this->_noRepeats)) {
            $checkList[$colName][] = $colVal;
          }
        }
      }

      if (array_key_exists('civicrm_membership_membership_type_id', $row)) {
        if ($value = $row['civicrm_membership_membership_type_id']) {
          $rows[$rowNum]['civicrm_membership_membership_type_id'] = CRM_Member_PseudoConstant::membershipType($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        $rows[$rowNum]['civicrm_contact_sort_name'] &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Contact Summary for this Contact.");
        $entryFound = TRUE;
      }

      // Link to recurring series
      if (($value = CRM_Utils_Array::value('civicrm_contribution_recur_id', $row)) &&
        CRM_Core_Permission::check('access CiviContribute')
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view/contributionrecur",
          "reset=1&id=" . $row['civicrm_contribution_recur_id'] .
          "&cid=" . $row['civicrm_contact_id'] .
          "&action=view&context=contribution&selectedChild=contribute",
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contribution_recur_id_link'] = $url;
        $rows[$rowNum]['civicrm_contribution_recur_id_hover'] = ts("View Details of this Recurring Series.");
        $entryFound = TRUE;
      }

      // handle expiry date
      if ($value = CRM_Utils_Array::value('civicrm_iats_customer_codes_expiry', $row)) {
        if ($rows[$rowNum]['civicrm_iats_customer_codes_expiry'] == '0000') {
          $rows[$rowNum]['civicrm_iats_customer_codes_expiry'] = ' ';
        }
        elseif ($rows[$rowNum]['civicrm_iats_customer_codes_expiry'] != '0000') {
          $rows[$rowNum]['civicrm_iats_customer_codes_expiry'] = '20' . substr($rows[$rowNum]['civicrm_iats_customer_codes_expiry'], 0, 2) . '/' . substr($rows[$rowNum]['civicrm_iats_customer_codes_expiry'], 2, 2);
        }
      }
      // handle contribution status id
      if ($value = CRM_Utils_Array::value('civicrm_contribution_recur_contribution_status_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_recur_contribution_status_id'] = self::$contributionStatus[$value];
      }
      // handle processor id
      if ($value = CRM_Utils_Array::value('civicrm_contribution_recur_payment_processor_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_recur_payment_processor_id'] = self::$processors[$value];
      }


      if (!$entryFound) {
        break;
      }
    }
  }
}

