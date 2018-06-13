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
);
