<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 =>
  array (
    'name' => 'CRM_Bcndpmembership_Form_Report_bcndpMembership',
    'entity' => 'ReportTemplate',
    'params' =>
    array (
      'version' => 3,
      'label' => 'BCNDP Membership - Report',
      'description' => 'BCNDP Membership (org.bcndp.bcndpmembership)',
      'class_name' => 'CRM_Bcndpmembership_Form_Report_bcndpMembership',
      'report_url' => 'org.bcndp.bcndpmembership/bcndpmembership',
      'component' => 'CiviMember',
    ),
  ),
);
