<?php
/*-------------------------------------------------------+
| SYSTOPIA - Performance Boost for Activities            |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: M. Wire (mjw@mjwconsult.co.uk)                 |
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

require_once 'fastactivity.civix.php';
use CRM_Fastactivity_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function fastactivity_civicrm_config(&$config) {
  _fastactivity_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function fastactivity_civicrm_install() {
  _fastactivity_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function fastactivity_civicrm_enable() {
  _fastactivity_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_tabset()
 *
 * Replace the existing activities tab
 */
function fastactivity_civicrm_tabset($tabsetName, &$tabs, $context) {
  if ($tabsetName == 'civicrm/contact/view' && !empty($context['contact_id'])) {
    // this is the contact summary view
    $is_active = (bool) Civi::settings()->get('fastactivity_replace_tab');
    if ($is_active) {
      // ..and the tab replacement is active. gather some data:
      $contactID = (int) $context['contact_id'];
      $tab_weight = (int) Civi::settings()->get('fastactivity_replace_tab_weight');
      $count_parameters = [
        'contact_id'            => $contactID,
        'excludeCaseActivities' => (bool) Civi::settings()->get('tab_exclude_case_activities')
      ];

      // and inject that tab
      $tabs[] = [
          'title'  => E::ts('Activities'),
          'class'  => 'livePage',
          'id'     => 'fastactivity',
          'url'    => CRM_Utils_System::url('civicrm/contact/view/fastactivity', "reset=1&cid={$contactID}"),
          'weight' => $tab_weight ? $tab_weight : 40,
          'icon'   => 'crm-i fa-tasks',
          'count'  => CRM_Fastactivity_BAO_Activity::getContactActivitiesCount($count_parameters),
      ];
    }
  }
}

/**
 * Implements hook_coreResourceList
 *
 * @param array $list
 * @param string $region
 */
function fastactivity_civicrm_coreResourceList(&$list, $region) {
  CRM_Core_Resources::singleton()
    ->addStyleFile('de.systopia.fastactivity', 'css/fastactivity.css', 0, 'page-header');
}

/**
 * Implements hook_civicrm_check().
 * Not implemented in Civi 4.6
 */
function fastactivity_civicrm_check(&$messages) {
  require_once(E::path() . '/CRM/Fastactivity/Settings.php');
  if ((bool) CRM_Fastactivity_Settings::getValue('tab_col_campaign_title')) {
    // Make sure campaign extension is loaded
    if (!CRM_Extension_System::singleton()
      ->getMapper()
      ->isActiveModule('campaign')) {
      $messages[] = new CRM_Utils_Check_Message(
        'fastactivity_campaign',
        ts('FastActivity uses campaigntree API. Please install the extension "de.systopia.campaign".'),
        ts('CampaignTree API Required'),
        \Psr\Log\LogLevel::CRITICAL
      );
    }
  }

  // Make sure fontawesome extension is loaded
  if (!CRM_Extension_System::singleton()->getMapper()->isActiveModule('fontawesome')
    && version_compare(CRM_Utils_System::version(), '4.7', '<')) {
    $messages[] = new CRM_Utils_Check_Message(
      'fastactivity_fontawesome',
      ts('FastActivity uses FontAwesome extension to display icons. Please install the extension "uk.co.mjwconsult.fontawesome".'),
      ts('FontAwesome extension required'),
      \Psr\Log\LogLevel::WARNING
    );
  }
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
 */
function fastactivity_civicrm_navigationMenu(&$menu) {
  $item[] =  array (
    'name'       => 'Fast Activities Tab',
    'url'        => 'civicrm/admin/fastactivity',
    'permission' => 'administer CiviCRM',
    'operator'   => NULL,
    'separator'  => NULL,
  );
  _fastactivity_civix_insert_navigation_menu($menu, 'Administer/Customize Data and Screens', $item[0]);
}

/**
 * Replace activity view and edit links in search results
 *
 * @param $op
 * @param $objectName
 * @param $objectId
 * @param $links
 * @param $mask
 * @param $values
 */
function fastactivity_civicrm_links($op, $objectName, $objectId, &$links, &$mask, &$values) {
  $replace_search_links = (bool) CRM_Fastactivity_Settings::getValue('fastactivity_replace_search');
  if ($replace_search_links && $op == 'activity.selector.row') {
    foreach ($links as &$link) {
      if ($link['name'] == 'View') {
        $link['url'] = 'civicrm/fastactivity/view';
      }
      elseif ($link['name'] == 'Edit') {
        $link['url'] = 'civicrm/fastactivity/add';
      }
    }
  }
}

/**
 * Implements hook_civicrm_pre().
 *
 * @url https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_pre/
 */
function fastactivity_civicrm_pre($op, $objectName, $id, &$params) {
  if ($objectName == 'Activity' && $op == 'create') {
    // Run details field contents for activities of configured types through a
    // text filter for reducing the record size.
    $activity_type_ids = Civi::settings()->get('fastactivity_filter_details_activity_types') ?: [];
    if (in_array($params['activity_type_id'], $activity_type_ids)) {
      $params['details'] = CRM_Utils_String::htmlToText($params['details']);
    }
  }
}

/**
 * Implements hook_civicrm_searchTasks().
 *
 * @url https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_searchTasks/
 */
function fastactivity_civicrm_searchTasks($objectType, &$tasks)
{
  // add "Filter activity details" task to activity search result actions.
  if ($objectType == 'activity') {
    $tasks[] = [
      'title' => E::ts('Filter activity details'),
      'class' => 'CRM_Fastactivity_Form_Task_DetailsFilter',
      'result' => false,
    ];
  }
}
