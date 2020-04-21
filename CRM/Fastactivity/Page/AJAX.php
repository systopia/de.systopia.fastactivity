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

/**
 * This class contains all the function that are called using AJAX (jQuery)
 *
 * @see based on CRM_Activity_Page_AJAX (CiviCRM LLC)
 *
 */
class CRM_Fastactivity_Page_AJAX {

  public static function getContactActivity() {
    $contactID = CRM_Utils_Type::escape($_POST['contact_id'], 'Integer');
    $context = CRM_Utils_Type::escape(CRM_Utils_Array::value('context', $_GET), 'String');

    $params = $_POST;

    // Load settings
    $params['optionalCols']['campaign_title'] = (bool) CRM_Fastactivity_Settings::getValue('tab_col_campaign_title');
    $params['optionalCols']['duration'] = (bool) CRM_Fastactivity_Settings::getValue('tab_col_duration');
    $params['optionalCols']['case'] = (bool) CRM_Fastactivity_Settings::getValue('tab_col_case');
    $params['optionalCols']['target_contact'] = (bool) CRM_Fastactivity_Settings::getValue('tab_col_target_contact');
    $params['excludeCaseActivities'] = (bool) CRM_Fastactivity_Settings::getValue('tab_exclude_case_activities');

    // Map column Id to the actual SQL query result column we are going to order by
    $sortMapper[] = 'activity_type_id';
    $sortMapper[] = 'activity_subject';
    if ($params['optionalCols']['campaign_title']) {
      $sortMapper[] = 'activity_campaign_title';
    }
    $sortMapper[] = 'source_display_name';
    if ($params['optionalCols']['target_contact']) {
      $sortMapper[] = 'target_display_name';
    }
    $sortMapper[] = 'assignee_display_name';
    $sortMapper[] = 'activity_date_time';
    $sortMapper[] = 'activity.status_id';
    if ($params['optionalCols']['duration']) {
      $sortMapper[] = 'activity_duration';
    }
    if ($params['optionalCols']['case']) {
      $sortMapper[] = 'activity_case_id';
    }

    $sEcho = CRM_Utils_Type::escape($_REQUEST['sEcho'], 'Integer');
    $offset = isset($_REQUEST['iDisplayStart']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayStart'], 'Integer') : 0;
    $rowCount = isset($_REQUEST['iDisplayLength']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayLength'], 'Integer') : 25;
    $sort = isset($_REQUEST['iSortCol_0']) ? CRM_Utils_Array::value(CRM_Utils_Type::escape($_REQUEST['iSortCol_0'], 'Integer'), $sortMapper) : NULL;
    $sortOrder = isset($_REQUEST['sSortDir_0']) ? CRM_Utils_Type::escape($_REQUEST['sSortDir_0'], 'MysqlOrderByDirection') : 'asc';

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
    $userID = CRM_Core_Session::getLoggedInContactID();
    if (Civi::settings()->get('preserve_activity_tab_filter') && $userID) {
      $activityFilter = array(
        'activity_type_id' => empty($params['activity_type_id']) ? '' : CRM_Utils_Type::escape($params['activity_type_id'], 'String'),
        'activity_type_exclude_id' => empty($params['activity_type_exclude_id']) ? '' : CRM_Utils_Type::escape($params['activity_type_exclude_id'], 'String'),
        'activity_date_relative' => empty($params['activity_date_relative']) ? '' : CRM_Utils_Type::escape($params['activity_date_relative'], 'String'),
        'activity_status_id' => empty($params['activity_status_id']) ? '' : CRM_Utils_Type::escape($params['activity_status_id'], 'String'),
        'activity_campaign_id' => empty($params['activity_campaign_id']) ? '' : CRM_Utils_Type::escape($params['activity_campaign_id'], 'String'),
      );
      if (empty($params['activity_date_low'])) {
        $activityFilter['activity_date_low'] = '';
      }
      else {
        $activityFilter['activity_date_relative'] = 0;
        $activityFilter['activity_date_low'] = CRM_Utils_Type::escape($params['activity_date_low'], 'String');
      }
      if (empty($params['activity_date_high'])) {
        $activityFilter['activity_date_high'] = '';
      }
      else {
        $activityFilter['activity_date_relative'] = 0;
        $activityFilter['activity_date_high'] = CRM_Utils_Type::escape($params['activity_date_high'], 'String');
      }

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
    $sortMapper[] = 'activity_type_id';
    $sortMapper[] = 'activity_subject';
    if ($params['optionalCols']['campaign_title']) {
      $sortMapper[] = 'activity_campaign_title';
    }
    $sortMapper[] = 'source_display_name';
    if ($params['optionalCols']['target_contact']) {
      $sortMapper[] = 'target_display_name';
    }
    $sortMapper[] = 'assignee_display_name';
    $sortMapper[] = 'activity_date_time';
    $sortMapper[] = 'status_id';
    if ($params['optionalCols']['duration']) {
      $sortMapper[] = 'duration';
    }

    $selectorElements[] = 'activity_type';
    $selectorElements[] = 'subject';
    if ($params['optionalCols']['campaign_title']) {
      $selectorElements[] = 'campaign';
    }
    $selectorElements[] = 'source_contact';
    if ($params['optionalCols']['target_contact']) {
      $selectorElements[] = 'target_contact';
    }
    $selectorElements[] = 'assignee_contact';
    $selectorElements[] = 'activity_date';
    $selectorElements[] = 'status';
    if ($params['optionalCols']['duration']) {
      $selectorElements[] = 'duration';
    }
    if ($params['optionalCols']['case']) {
      $selectorElements[] = 'activity_case_id';
    }
    $selectorElements[] = 'links';
    $selectorElements[] = 'class';

    header('Content-Type: application/json');
    echo CRM_Utils_JSON::encodeDataTableSelector($activities, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);
    CRM_Utils_System::civiExit();
  }
}
