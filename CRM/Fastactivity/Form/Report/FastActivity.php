<?php
/*-------------------------------------------------------+
| SYSTOPIA - Performance Boost for Activities            |
| Copyright (C) 2019 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

use CRM_Fastactivity_ExtensionUtil as E;

/**
 * Simplified activity report with an improved performance
 *
 * Class CRM_Fastactivity_Form_Report_FastActivity
 */
class CRM_Fastactivity_Form_Report_FastActivity extends CRM_Report_Form {

  protected $activityTypes = [];
  protected $activityStatus = [];

  function __construct() {
    // load activity types
    $type_query = civicrm_api3('OptionValue', 'get', [
        'option_group_id' => 'activity_type',
        'option.limit'    => 0,
        'return'          => 'value,label']);
    foreach ($type_query['values'] as $type) {
      $this->activityTypes[$type['value']] = $type['label'];
    }

    $status_query = civicrm_api3('OptionValue', 'get', [
        'option_group_id' => 'activity_status',
        'option.limit'    => 0,
        'return'          => 'value,label']);
    foreach ($status_query['values'] as $status) {
      $this->activityStatus[$status['value']] = $status['label'];
    }


    $this->_columns = array(
      'civicrm_activity' => array(
            'dao' => 'CRM_Activity_DAO_Activity',
            'fields' => array(
                'id'                 => array(
                    'no_display' => TRUE,
                    'title'      => E::ts('Activity ID'),
                    'required'   => TRUE,
                ),
                'target_sort_name' => array(
                    'title'   => E::ts('Target Contact'),
                    'default' => TRUE,
                    'type'    => CRM_Utils_Type::T_STRING,
                ),
                'source_record_id'   => array(
                    'no_display' => TRUE,
                    'required'   => TRUE,
                ),
                'activity_type_id'   => array(
                    'title'    => E::ts('Activity Type'),
                    'required' => FALSE,
                    'type'     => CRM_Utils_Type::T_STRING,
                ),
                'activity_subject'   => array(
                    'title'   => E::ts('Subject'),
                    'default' => FALSE,
                ),
                'activity_date_time' => array(
                    'title'    => E::ts('Activity Date'),
                    'required' => TRUE,
                    'default'  => TRUE,
                ),
                'status_id'          => array(
                    'title'   => E::ts('Activity Status'),
                    'default' => TRUE,
                    'type'    => CRM_Utils_Type::T_STRING,
                ),
                'assignee_sort_name' => array(
                    'title'   => E::ts('Assignee Contact'),
                    'default' => TRUE,
                    'type'    => CRM_Utils_Type::T_STRING,
                ),
            ),
            'filters' => array(
                'activity_date_time' => array(
                    'default'      => 'this.month',
                    'operatorType' => CRM_Report_Form::OP_DATE,
                ),
                'activity_subject'   => array(
                    'title' => E::ts('Activity Subject')
                ),
                'activity_type_id'   => array(
                    'title'        => E::ts('Activity Type'),
                    'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                    'options'      => $this->activityTypes,
                ),
                'status_id'          => array(
                    'title'        => E::ts('Activity Status'),
                    'type'         => CRM_Utils_Type::T_STRING,
                    'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                    'options'      => CRM_Core_PseudoConstant::activityStatus(),
                ),
                'assignee_ids' => array(
                    'title'   => E::ts('Assigned To'),
                    'default' => FALSE,
                    'type'    => CRM_Utils_Type::T_STRING,
                    'attributes' => [
                        'multiple' => TRUE,
                        'entity'   => 'Contact',
                    ],
                    'operatorType' => CRM_Report_Form::OP_ENTITYREF,
                ),
                'source_ids' => array(
                    'title'   => E::ts('Created By'),
                    'default' => FALSE,
                    'type'    => CRM_Utils_Type::T_STRING,
                    'attributes' => [
                        'multiple' => 1,
                        'entity'   => 'Contact',
                    ],
                    'operatorType' => CRM_Report_Form::OP_ENTITYREF,
                ),
                'priority_id'        => array(
                    'title'        => E::ts('Activity Priority'),
                    'type'         => CRM_Utils_Type::T_STRING,
                    'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                    'options'      => CRM_Core_PseudoConstant::get('CRM_Activity_DAO_Activity', 'priority_id'),
                ),
            ),
            'order_bys' => array(
                'activity_date_time' => array(
                    'title'          => E::ts('Activity Date'),
                    'default_weight' => '1',
                    'dbAlias'        => "activity_date_time",
                ),
            ),
            'alias' => 'activity',
        ),
    );
    parent::__construct();
  }

  public function buildQuery($applyLimit = TRUE) {
    $sql = parent::buildQuery($applyLimit);
    //CRM_Core_Error::debug_log_message("SQL: $sql");
    return $sql;
  }

  function preProcess() {
    $this->assign('reportTitle', E::ts('Fast Activity Report'));
    parent::preProcess();
  }

  function from() {
    $this->_from = "FROM civicrm_activity {$this->_aliases['civicrm_activity']}";

    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');

    if (!empty($this->_formValues['assignee_ids_value'])) {
      $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
      $this->_from .= " LEFT JOIN civicrm_activity_contact fa_assignee ON fa_assignee.activity_id = {$this->_aliases['civicrm_activity']}.id AND fa_assignee.record_type_id = {$assigneeID}";
    }

    if (!empty($this->_formValues['source_ids_value'])) {
      $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
      $this->_from .= " LEFT JOIN civicrm_activity_contact fa_source ON fa_source.activity_id = {$this->_aliases['civicrm_activity']}.id AND fa_source.record_type_id = {$sourceID}";
    }

    if (!empty($this->_formValues['fields']['target_sort_name'])) {
      $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);
      $this->_from .= " LEFT JOIN civicrm_activity_contact fa_target_link ON fa_target_link.activity_id = {$this->_aliases['civicrm_activity']}.id AND fa_target_link.record_type_id = {$targetID}";
      $this->_from .= " LEFT JOIN civicrm_contact fa_target_contact ON fa_target_contact.id = fa_target_link.contact_id";
    }

    if (!empty($this->_formValues['fields']['assignee_sort_name'])) {
      $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
      $this->_from .= " LEFT JOIN civicrm_activity_contact fa_assignee_link ON fa_assignee_link.activity_id = {$this->_aliases['civicrm_activity']}.id AND fa_assignee_link.record_type_id = {$assigneeID}";
      $this->_from .= " LEFT JOIN civicrm_contact fa_assignee_contact ON fa_assignee_contact.id = fa_assignee_link.contact_id";
    }
  }

    /**
   * Add field specific select alterations.
   *
   * @param string $tableName
   * @param string $tableKey
   * @param string $fieldName
   * @param array $field
   *
   * @return string
   */
  function selectClause(&$tableName, $tableKey, &$fieldName, &$field) {
    if ($fieldName == 'target_sort_name') {
      $this->_columnHeaders['target_sort_name']['title']       = CRM_Utils_Array::value('title', $field);
      $this->_columnHeaders['target_sort_name']['type']        = CRM_Utils_Array::value('type', $field);
      $this->_columnHeaders['target_contact_id']['no_display'] = TRUE;
      return "fa_target_contact.sort_name AS target_sort_name, fa_target_contact.id AS target_contact_id";

    } elseif ($fieldName == 'assignee_sort_name') {
      $this->_columnHeaders['assignee_sort_name']['title'] = CRM_Utils_Array::value('title', $field);
      $this->_columnHeaders['assignee_sort_name']['type'] = CRM_Utils_Array::value('type', $field);
      $this->_columnHeaders['assignee_contact_id']['no_display'] = TRUE;
      return "fa_assignee_contact.sort_name AS assignee_sort_name, fa_assignee_contact.id AS assignee_contact_id";

    } else {
      return parent::selectClause($tableName, $tableKey, $fieldName, $field);
    }
  }

  /**
   * Add field specific where alterations.
   *
   * This can be overridden in reports for special treatment of a field
   *
   * @param array $field Field specifications
   * @param string $op Query operator (not an exact match to sql)
   * @param mixed $value
   * @param float $min
   * @param float $max
   *
   * @return null|string
   */
  public function whereClause(&$field, $op, $value, $min, $max) {
    if ($field['name'] == 'assignee_ids') {
      if (!empty($this->_formValues['assignee_ids_value'])) {
        return "fa_assignee.contact_id {$this->_formValues['assignee_ids_op']} ({$this->_formValues['assignee_ids_value']})";
      } else {
        return NULL;
      }
    } elseif ($field['name'] == 'source_ids') {
      if (!empty($this->_formValues['source_ids_value'])) {
        return "fa_source.contact_id {$this->_formValues['source_ids_op']} ({$this->_formValues['source_ids_value']})";
      } else {
        return NULL;
      }
    } else {
      return parent::whereClause($field, $op, $value, $min, $max);
    }
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows
    foreach ($rows as $rowNum => $row) {
      if (!empty($row['civicrm_activity_activity_type_id'])) {
        $rows[$rowNum]['civicrm_activity_activity_type_id'] = $this->activityTypes[$row['civicrm_activity_activity_type_id']];
      }

      if (!empty($row['civicrm_activity_status_id'])) {
        $rows[$rowNum]['civicrm_activity_status_id'] = $this->activityStatus[$row['civicrm_activity_status_id']];
      }

      if (!empty($row['target_sort_name']) && !empty($row['target_contact_id'])) {
        $url = CRM_Utils_System::url("civicrm/contact/view", 'reset=1&cid=' . $row['target_contact_id'], $this->_absoluteUrl);
        $rows[$rowNum]['target_sort_name_link'] = $url;
        $rows[$rowNum]['target_sort_name_hover'] = E::ts("View Contact Summary for this Contact.");
      }
      
      if (!empty($row['assignee_sort_name']) && !empty($row['assignee_contact_id'])) {
        $url = CRM_Utils_System::url("civicrm/contact/view", 'reset=1&cid=' . $row['assignee_contact_id'], $this->_absoluteUrl);
        $rows[$rowNum]['assignee_sort_name_link'] = $url;
        $rows[$rowNum]['assignee_sort_name_hover'] = E::ts("View Contact Summary for this Contact.");
      }
    }
  }
}
