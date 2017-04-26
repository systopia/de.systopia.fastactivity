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

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function fastactivity_civicrm_config(&$config) {
  _fastactivity_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param array $files
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function fastactivity_civicrm_xmlMenu(&$files) {
  _fastactivity_civix_civicrm_xmlMenu($files);
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
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function fastactivity_civicrm_uninstall() {
  _fastactivity_civix_civicrm_uninstall();
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
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function fastactivity_civicrm_disable() {
  _fastactivity_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function fastactivity_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _fastactivity_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function fastactivity_civicrm_managed(&$entities) {
  _fastactivity_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * @param array $caseTypes
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function fastactivity_civicrm_caseTypes(&$caseTypes) {
  _fastactivity_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function fastactivity_civicrm_angularModules(&$angularModules) {
_fastactivity_civix_civicrm_angularModules($angularModules);
}


/*function fastactivity_civicrm_tabset($tabsetName, &$tabs, $context) {
  // FIXME: For CiviCRM 4.7 we can use this hook instead.
}*/

/**
 * Replace the existing activities tab
 * @param $tabs
 * @param $contactID
 */
function fastactivity_civicrm_tabs ( &$tabs, $contactID ) {
  $params = array('contact_id' => $contactID);
  /*
   * FIXME: Uncomment this to replace "Activities" tab
  $tabId = 0;
  foreach ($tabs as $tab) {
    if (!empty($tab['title']) && $tab['title'] == 'Activities') {
      $tabs[$tabId]['url'] = CRM_Utils_System::url('civicrm/contact/view/fastactivity', "?reset=1&cid={$contactID}");
      $tabs[$tabId]['count'] = CRM_Fastactivity_BAO_Activity::getContactActivitiesCount($params);
      break;
    }
    $tabId++;
  }
  */
  //FIXME remove this when we replace "Activities" tab (commented) above
  $tabs[] = array('title' => 'FastActivities',
                  'class' => 'livePage',
                  'id' => 'fastactivity',
                  'url' => CRM_Utils_System::url('civicrm/contact/view/fastactivity', "reset=1&cid={$contactID}"),
                  'weight' => 50,
                  'count' => CRM_Fastactivity_BAO_Activity::getContactActivitiesCount($params),
                 );
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function fastactivity_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _fastactivity_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_coreResourceList
 *
 * @param array $list
 * @param string $region
 */
function fastactivity_civicrm_coreResourceList(&$list, $region) {
  Civi::resources()
    ->addStyleFile('de.systopia.fastactivity', 'css/fastactivity.css', 0, 'page-header');
}