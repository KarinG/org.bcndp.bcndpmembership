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
class CRM_Bcndpmembership_Form_Report_bcndpMembership extends CRM_Report_Form {

  protected $_addressField = FALSE;

  protected $_emailField = FALSE;

  protected $_phoneField = FALSE;

  protected $_summary = NULL;

  protected $_customGroupExtends = array('Membership','Individual','Contact');
  protected $_customGroupGroupBy = FALSE; function __construct() {
    $this->_columns = array(
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
//            'no_display' => TRUE,
            'title' => ' Contact ID',
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
          'birth_date' =>
          array('title' => ts('Birth date'),
            'no_repeat' => TRUE,
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
          'birth_date' =>
          array('operatorType' => CRM_Report_Form::OP_DATE),
          'id' =>
          array('title' => ts('Contact ID'),
            'type' => CRM_Utils_Type::T_INT,
          ),
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
          'is_deleted' => array(
            'title' => ts('Deleted'),
            'type' => CRM_Utils_Type::T_BOOLEAN,
            'default' => 0,
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
            //'default' => TRUE,
          ),
          'membership_end_date' => array('title' => ts('End Date'),
            'default' => TRUE,
          ),
          'join_date' => array('title' => ts('Join Date'),
            //'default' => TRUE,
          ),
        ),
        'filters' => array(
          'join_date' =>
          array('title' => ts('Join Date'), 'operatorType' => CRM_Report_Form::OP_DATE),
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
      'civicrm_address' =>
      array(
        'dao' => 'CRM_Core_DAO_Address',
        'grouping' => 'tax-fields',
        'fields' =>
        array(
          'street_address' =>
          array('default' => TRUE),
          'supplemental_address_1' =>
          array('default' => TRUE),
          'city' =>
          array('default' => TRUE),
          'postal_code' =>
          array('default' => TRUE),
          'state_province_id' =>
          array('title' => ts('State/Province'),
            'default' => TRUE),
        ),
        'order_bys' =>
        array('state_province_id' => array('title' => 'State/Province'),
          'city' => array('title' => 'City'),
          'postal_code' => array('title' => 'Postal Code'),
        ),
      ),
      'civicrm_email' =>
      array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' =>
        array(
          'email' =>
           array('default' => TRUE),
         ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_phone' =>
      array(
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' =>
        array(
          'phone' =>
           array('default' => TRUE),
       ),
        'grouping' => 'contact-fields',
      ),

    );
    parent::__construct();
  }

  function preProcess() {
    $this->assign('reportTitle', ts('Membership Detail Report'));
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
               LEFT  JOIN civicrm_membership_status {$this->_aliases['civicrm_membership_status']}
                          ON {$this->_aliases['civicrm_membership_status']}.id =
                             {$this->_aliases['civicrm_membership']}.status_id ";


    //used when address field is selected
    if ($this->_addressField) {
      $this->_from .= "
              LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
                        ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_address']}.contact_id AND
                           {$this->_aliases['civicrm_address']}.is_primary = 1 AND
                           {$this->_aliases['civicrm_address']}.location_type_id != 6 AND
                           {$this->_aliases['civicrm_address']}.location_type_id != 21\n";
    }
    //used when email field is selected
    if ($this->_emailField) {
      $this->_from .= "
              LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']}
                        ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id AND
                           {$this->_aliases['civicrm_email']}.is_primary = 1 AND
                           {$this->_aliases['civicrm_email']}.location_type_id != 6 AND
                           {$this->_aliases['civicrm_email']}.location_type_id != 21\n";
    }
    //used when phone field is selected
    if ($this->_phoneField) {
      $this->_from .= "
              LEFT JOIN civicrm_phone {$this->_aliases['civicrm_phone']}
                        ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id AND
                           {$this->_aliases['civicrm_phone']}.is_primary = 1 AND
                           {$this->_aliases['civicrm_phone']}.location_type_id != 6 AND
                           {$this->_aliases['civicrm_phone']}.location_type_id != 21\n";
    }
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
    //$this->_groupBy = " GROUP BY {$this->_aliases['civicrm_contact']}.id, {$this->_aliases['civicrm_membership']}.membership_type_id";
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

      if (!$entryFound) {
        break;
      }
    }
  }
}

