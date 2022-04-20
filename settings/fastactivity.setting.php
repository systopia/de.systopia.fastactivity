<?php
/*--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
+--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 +-------------------------------------------------------------------*/

return array(
  'fastactivity_replace_tab' => array(
      'group_name' => 'FastActivity Settings',
      'group' => 'fastactivity',
      'name' => 'fastactivity_replace_tab',
      'type' => 'Boolean',
      'html_type' => 'Checkbox',
      'default' => 0,
      'add' => '4.6',
      'is_domain' => 1,
      'is_contact' => 0,
      'description' => 'Replace the (slow) default Contact Activity Tab',
      'html_attributes' => array(),
  ),

  'fastactivity_replace_tab_weight' => [
      'group_name' => 'FastActivity Settings',
      'group' => 'fastactivity',
      'name' => 'fastactivity_replace_tab_weight',
      'type' => 'Integer',
      'html_type' => 'Text',
      'default' => 40,
      'add' => '4.6',
      'is_domain' => 1,
      'is_contact' => 0,
      'description' => 'Fast Activity Tab Weight',
      'html_attributes' => array(),
  ],

  'fastactivity_replace_search' => [
    'group_name' => 'FastActivity Settings',
    'group' => 'fastactivity',
    'name' => 'fastactivity_replace_search',
    'type' => 'Boolean',
    'html_type' => 'Checkbox',
    'default' => 0,
    'add' => '4.6',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Replace view/edit links in search and reports',
    'html_attributes' => [],
  ],

  'fastactivity_preserve_activity_tab_filter' => [
    'group_name' => 'FastActivity Settings',
    'group' => 'fastactivity',
    'name' => 'fastactivity_preserve_activity_tab_filter',
    'type' => 'Boolean',
    'html_type' => 'Checkbox',
    'default' => 0,
    'add' => '4.6',
    'is_domain' => 1,
    'is_contact' => 1,
    'description' => 'Preserve individual filter settings in Activity tab',
    'html_attributes' => [],
  ],

  'fastactivity_activity_tab_filter_open' => [
      'group_name' => 'FastActivity Settings',
      'group' => 'fastactivity',
      'name' => 'fastactivity_activity_tab_filter_open',
      'type' => 'Boolean',
      'html_type' => 'Checkbox',
      'default' => 0,
      'add' => '4.6',
      'is_domain' => 1,
      'is_contact' => 0,
      'description' => 'Should the filter tab be open by default?',
      'html_attributes' => [],
  ],

  'fastactivity_tab_col_duration' => array(
    'group_name' => 'FastActivity Settings',
    'group' => 'fastactivity',
    'name' => 'fastactivity_tab_col_duration',
    'type' => 'Boolean',
    'html_type' => 'Checkbox',
    'default' => 0,
    'add' => '4.6',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Display Duration column in contact tab',
    'html_attributes' => array(),
  ),

  'fastactivity_tab_col_target_contact' => array(
    'group_name' => 'FastActivity Settings',
    'group' => 'fastactivity',
    'name' => 'fastactivity_tab_col_target_contact',
    'type' => 'Boolean',
    'html_type' => 'Checkbox',
    'default' => 0,
    'add' => '4.6',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Display Target Contact column in contact tab',
    'html_attributes' => array(),
  ),

  'fastactivity_tab_col_campaign_title' => array(
    'group_name' => 'FastActivity Settings',
    'group' => 'fastactivity',
    'name' => 'fastactivity_tab_col_campaign_title',
    'type' => 'Boolean',
    'html_type' => 'Checkbox',
    'default' => 0,
    'add' => '4.6',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Display Campaign Title column in contact tab',
    'html_attributes' => array(),
  ),

  'fastactivity_tab_col_case' => array(
    'group_name' => 'FastActivity Settings',
    'group' => 'fastactivity',
    'name' => 'fastactivity_tab_col_case',
    'type' => 'Boolean',
    'html_type' => 'Checkbox',
    'default' => 0,
    'add' => '4.6',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Display Case column in contact tab',
    'html_attributes' => array(),
  ),

  'fastactivity_tab_exclude_case_activities' => array(
    'group_name' => 'FastActivity Settings',
    'group' => 'fastactivity',
    'name' => 'fastactivity_tab_exclude_case_activities',
    'type' => 'Boolean',
    'html_type' => 'Checkbox',
    'default' => 1,
    'add' => '4.6',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Exclude Case Activities from the activity tab',
    'html_attributes' => array(),
  ),
  'fastactivity_filter_details_activity_types' => array(
    'group_name' => 'FastActivity Settings',
    'group' => 'fastactivity',
    'name' => 'fastactivity_filter_details_activity_types',
    'type' => 'Integer',
    'html_type' => 'select',
    'pseudoconstant' => ['optionGroupName' => 'activity_type'],
    'default' => NULL,
    'add' => '5.0',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => 'Activity types to filter details for',
    'description' => 'Activity types for which to run the details field contents through a text filter for reducing storage size.',
    'html_attributes' => [
      'class' => 'crm-select2',
      'multiple' => 1,
    ],
  ),
);
