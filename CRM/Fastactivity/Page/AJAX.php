<?php
/*-------------------------------------------------------+
| SYSTOPIA - Performance Boost for Activities            |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: M. Wire (mjw@mjwconsult.co.uk)                 |
|         B. Endres (endres@systopia.de)                 |
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

/**
 * This class contains all the function that are called using AJAX (jQuery)
 *
 * @see based on CRM_Activity_Page_AJAX (CiviCRM LLC)
 *
 */
class CRM_Fastactivity_Page_AJAX {

  public static function getContactActivity() {
    $starttime = microtime(true);//DEBUG
    $contactID = CRM_Utils_Type::escape($_POST['contact_id'], 'Integer');
    $context = CRM_Utils_Type::escape(CRM_Utils_Array::value('context', $_GET), 'String');

    // Map column Id to the actual SQL query result column we are going to order by
    $sortMapper = array(
      0 => 'activity_type_id',
      1 => 'activity_subject',
      2 => 'activity_campaign_title',
      3 => 'source_display_name',
      4 => 'target_display_name',
      5 => 'assignee_display_name',
      6 => 'activity_date_time',
      7 => 'status_id',
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
      'campaign',
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
