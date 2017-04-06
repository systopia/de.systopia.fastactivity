<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2017
 *
 */

/**
 * This class contains all the function that are called using AJAX (jQuery)
 */
class CRM_Fastactivity_Page_AJAX {

  public static function getContactActivity() {
    $starttime = microtime(true);//DEBUG
    $contactID = CRM_Utils_Type::escape($_POST['contact_id'], 'Integer');
    $context = CRM_Utils_Type::escape(CRM_Utils_Array::value('context', $_GET), 'String');

    $sortMapper = array(
      0 => 'activity_type',
      1 => 'subject',
      2 => 'source_contact_name',
      3 => '',
      4 => '',
      5 => 'activity_date_time',
      6 => 'status_id',
    );

    $sEcho = CRM_Utils_Type::escape($_REQUEST['sEcho'], 'Integer');
    $offset = isset($_REQUEST['iDisplayStart']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayStart'], 'Integer') : 0;
    $rowCount = isset($_REQUEST['iDisplayLength']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayLength'], 'Integer') : 25;
    $sort = isset($_REQUEST['iSortCol_0']) ? CRM_Utils_Array::value(CRM_Utils_Type::escape($_REQUEST['iSortCol_0'], 'Integer'), $sortMapper) : NULL;
    $sortOrder = isset($_REQUEST['sSortDir_0']) ? CRM_Utils_Type::escape($_REQUEST['sSortDir_0'], 'MysqlOrderByDirection') : 'asc';

    $params = $_POST;
    if ($sort && $sortOrder) {
      $params['sortBy'] = $sort . ' ' . $sortOrder;
    }

    $params['page'] = ($offset / $rowCount) + 1;
    $params['rp'] = $rowCount;

    $params['contact_id'] = $contactID;
    $params['context'] = $context;

    // get the contact activities
    $activities = CRM_Fastactivity_BAO_Activity::getContactActivitiesSelector($params);

    foreach ($activities as $key => $value) {
      //Check if recurring activity
      if (!empty($value['is_recurring_activity'])) {
        $repeat = $value['is_recurring_activity'];
        $activities[$key]['activity_type'] .= '<br/><span class="bold">' . ts('Repeating (%1 of %2)', array(1 => $repeat[0], 2 => $repeat[1])) . '</span>';
      }
    }

    // store the activity filter preference CRM-11761
    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');
    if ($userID) {
      //flush cache before setting filter to account for global cache (memcache)
      $domainID = CRM_Core_Config::domainID();
      $cacheKey = CRM_Core_BAO_Setting::inCache(
        CRM_Core_BAO_Setting::PERSONAL_PREFERENCES_NAME,
        'activity_tab_filter',
        NULL,
        $userID,
        TRUE,
        $domainID,
        TRUE
      );
      if ($cacheKey) {
        CRM_Core_BAO_Setting::flushCache($cacheKey);
      }

      $activityFilter = array(
        'activity_type_id' => empty($params['activity_type_id']) ? '' : CRM_Utils_Type::escape($params['activity_type_id'], 'String'),
        'activity_type_exclude_filter_id' => empty($params['activity_type_exclude_id']) ? '' : CRM_Utils_Type::escape($params['activity_type_exclude_id'], 'String'),
      );

      CRM_Core_BAO_Setting::setItem(
        $activityFilter,
        CRM_Core_BAO_Setting::PERSONAL_PREFERENCES_NAME,
        'activity_tab_filter',
        NULL,
        $userID,
        $userID
      );
    }

    $iFilteredTotal = $iTotal = $params['total'];
    $selectorElements = array(
      'activity_type',
      'subject',
      'source_contact',
      'target_contact',
      'assignee_contact',
      'activity_date',
      'status',
      'links',
      'class',
    );

    $time_elapsed_secs = microtime(true) - $starttime; //DEBUG
    CRM_Core_Error::debug_log_message('Fastactivity AJAX getContactActivity exec time: '.$time_elapsed_secs); //DEBUG

    header('Content-Type: application/json');
    echo CRM_Utils_JSON::encodeDataTableSelector($activities, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);
    CRM_Utils_System::civiExit();
  }
}
