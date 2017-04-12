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
 * This class is for activity functions.
 *
 * @see based on CRM_Activity_BAO_Activity (CiviCRM LLC)
 */
class CRM_Fastactivity_BAO_Activity extends CRM_Activity_DAO_Activity {

  /**
   * Get the list Activities.
   *
   * @param array $input
   *   Array of parameters.
   *    Keys include
   *    - contact_id  int            contact_id whose activities we want to retrieve
   *    - offset      int            which row to start from ?
   *    - rowCount    int            how many rows to fetch
   *    - sort        object|array   object or array describing sort order for sql query.
   *    - admin       boolean        if contact is admin
   *    - caseId      int            case ID
   *    - context     string         page on which selector is build
   *    - activity_type_id int|string the activitiy types we want to restrict by
   *
   * @return array
   *   Relevant data object values of open activities
   */
  public static function getContactActivities(&$params) {
    //Get bulk email activity type (used to modify activity display for bulk email)
    $bulkActivityTypeID = CRM_Core_OptionGroup::getValue(
      'activity_type',
      'Bulk Email',
      'name'
    );

    //CRM-3553, need to check user has access to target groups.
    $mailingIDs = CRM_Mailing_BAO_Mailing::mailingACLIDs();
    $accessCiviMail = (
      (CRM_Core_Permission::check('access CiviMail')) ||
      (CRM_Mailing_Info::workflowEnabled() &&
        CRM_Core_Permission::check('create mailings'))
    );

    $whereClause = self::whereClause($params,FALSE);

    // Add limit clause
    if (!empty($params['rowCount']) &&
      $params['rowCount'] > 0
    ) {
      $limit = " LIMIT {$params['offset']}, {$params['rowCount']} ";
    }

    // Add order by clause
    $orderBy = ' ORDER BY activity.activity_date_time DESC';
    if (!empty($params['sort'])) {
      $orderBy = ' ORDER BY ' . CRM_Utils_Type::escape($params['sort'], 'String');
    }
    // Exclude activities associated with cases
    $caseFilter = self::getCaseFilter();

    // The main query.  This gets all the information (except target counts) for the tabbed activity display
    $query = "
SELECT
  activity.id AS activity_id,
  activity.activity_type_id                                                          AS activity_type_id,
  activity.subject                                                                   AS activity_subject,
  activity.activity_date_time                                                        AS activity_date_time,
  activity.status_id                                                                 AS activity_status_id,
  COUNT(DISTINCT(sources.contact_id))                                                AS source_count,
  COALESCE(source_contact_me.id, source_contact_random.id)                           AS source_contact_id,
  COALESCE(source_contact_me.display_name, source_contact_random.display_name)       AS source_display_name,
  COUNT(DISTINCT(assignees.contact_id))                                              AS assignee_count,
  COALESCE(assignee_contact_me.id, assignee_contact_random.id)                       AS assignee_contact_id,
  COALESCE(assignee_contact_me.display_name, assignee_contact_random.display_name)   AS assignee_display_name,
  COALESCE(target_contact_me.id, target_contact_random.id)                           AS target_contact_id,
  COALESCE(target_contact_me.display_name, target_contact_random.display_name)       AS target_display_name
FROM civicrm_activity_contact acon
LEFT JOIN civicrm_activity activity                ON acon.activity_id = activity.id
LEFT JOIN civicrm_activity_contact sources         ON (activity.id = sources.activity_id AND sources.record_type_id = 2)
LEFT JOIN civicrm_contact source_contact_random    ON (sources.contact_id = source_contact_random.id AND source_contact_random.is_deleted = 0)
LEFT JOIN civicrm_contact source_contact_me        ON (sources.contact_id = source_contact_me.id AND source_contact_me.id = %1)
LEFT JOIN civicrm_activity_contact assignees       ON (activity.id = assignees.activity_id AND assignees.record_type_id = 1)
LEFT JOIN civicrm_contact assignee_contact_random  ON (assignees.contact_id = assignee_contact_random.id AND assignee_contact_random.is_deleted = 0)
LEFT JOIN civicrm_contact assignee_contact_me      ON (assignees.contact_id = assignee_contact_me.id AND assignee_contact_me.id = %1)
LEFT JOIN civicrm_activity_contact targets         ON (activity.id = targets.activity_id AND targets.record_type_id = 1)
LEFT JOIN civicrm_contact target_contact_random    ON (targets.contact_id = target_contact_random.id AND target_contact_random.is_deleted = 0)
LEFT JOIN civicrm_contact target_contact_me        ON (targets.contact_id = target_contact_me.id AND target_contact_me.id = %1)
{$caseFilter}
WHERE {$whereClause}
GROUP BY activity.id
{$orderBy}
{$limit}";

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    //get all activity types
    $activityTypes = CRM_Activity_BAO_Activity::buildOptions('activity_type_id', 'validate');

    //get all campaigns.
    $allCampaigns = CRM_Campaign_BAO_Campaign::getCampaigns(NULL, NULL, FALSE, FALSE, FALSE, TRUE);
    $values = array();
    while ($dao->fetch()) {
      $activityID = $dao->activity_id;
      $values[$activityID]['activity_id'] = $dao->activity_id;
      $values[$activityID]['source_record_id'] = $dao->source_record_id;
      $values[$activityID]['activity_type_id'] = $dao->activity_type_id;
      $values[$activityID]['activity_type'] = $activityTypes[$dao->activity_type_id];
      $values[$activityID]['activity_date_time'] = $dao->activity_date_time;
      $values[$activityID]['status_id'] = $dao->activity_status_id;
      $values[$activityID]['subject'] = $dao->activity_subject;
      $values[$activityID]['campaign_id'] = $dao->campaign_id;
      $values[$activityID]['is_recurring_activity'] = $dao->is_recurring_activity;

      if ($dao->campaign_id) {
        $values[$activityID]['campaign'] = $allCampaigns[$dao->campaign_id];
      }

      // Assign contact counts / names
      $values[$activityID]['assignee_contact_count'] = $dao->assignee_count;
      $values[$activityID]['source_contact_count'] = $dao->source_count;
      $values[$activityID]['target_contact_count'] = -1; // -1 means we didn't count at all
      $values[$activityID]['assignee_contact_name'][$dao->assignee_contact_id] = $dao->assignee_display_name;
      $values[$activityID]['source_contact_name'][$dao->source_contact_id] = $dao->source_display_name;
      $values[$activityID]['target_contact_name'][$dao->target_contact_id] = $dao->target_display_name;
      $values[$activityID]['assignee_contact_id'] = $dao->assignee_contact_id;
      $values[$activityID]['source_contact_id'] = $dao->source_contact_id;

      // if deleted, wrap in <del>
      if ($dao->is_deleted) {
        $dao->contact_name = "<del>{$dao->contact_name}</del>";
      }


      if (!$bulkActivityTypeID || ($bulkActivityTypeID != $dao->activity_type_id)) {
        if (!empty($caseFilter)) {
          // case related fields
          $values[$activityID]['case_id'] = $dao->case_id;
          $values[$activityID]['case_subject'] = $dao->case_subject;
        }
      }
      else {
        $values[$activityID]['recipients'] = ts('(%1 recipients)', array(1 => $values[$activityID]['target_contact_count']));
        $values[$activityID]['mailingId'] = FALSE;
        if (
          $accessCiviMail &&
          ($mailingIDs === TRUE || in_array($dao->source_record_id, $mailingIDs))
        ) {
          $values[$activityID]['mailingId'] = TRUE;
        }
      }
    }

    return $values;
  }

  public static function getCaseFilter() {
    //filter case activities - CRM-5761
    $caseFilter = '';
    $components = CRM_Activity_BAO_Activity::activityComponents();
    if (!in_array('CiviCase', $components)) {
      $caseFilter .= " LEFT JOIN  civicrm_case_activity acase ON ( acase.activity_id = activity.id ) ";
    }
    return $caseFilter;
  }

  /**
   * Generate permissioned where clause for activity search.
   * @param array $params
   * @param bool $sortBy
   * @param bool $excludeHidden
   *
   * @return string
   */
  public static function whereClause(&$params, $sortBy = TRUE, $excludeHidden = TRUE) {
    // is_deleted
    $is_deleted = CRM_Utils_Array::value('is_deleted', $params);
    if ($is_deleted == '1') {
      $clauses[] = "activity.is_deleted = 1";
    } else {
      $clauses[] = "activity.is_deleted = 0";
    }

    // is_current_revision
    $is_current_revision = CRM_Utils_Array::value('is_current_revision', $params);
    if (empty($is_current_revision)) {
      $clauses[] = "activity.is_current_revision = 1";
    } else {
      $clauses[] = "activity.is_current_revision = 0";
    }

    // is_test
    $is_test = CRM_Utils_Array::value('is_test', $params);
    if ($is_test == '1') {
      $clauses[] = "activity.is_test = 1";
    } else {
      $clauses[] = "activity.is_test = 0";
    }

    // context
    $context = CRM_Utils_Array::value('context', $params);
    if ($context != 'activity') {
      $clauses[] = "activity.status_id = 1";
    }

    // activity type ID clause
    $activity_type_id = CRM_Utils_Array::value('activity_type_id', $params);
    if (!empty($activity_type_id)) {
      $clauses[] = "activity.activity_type_id IN ( " . $activity_type_id . " ) ";
    }

    // exclude by activity type clause
    $activity_type_exclude_id = CRM_Utils_Array::value('activity_type_exclude_id', $params);
    if (!empty($activity_type_exclude_id)) {
      $clauses[] = "activity.activity_type_id NOT IN ( " . $activity_type_exclude_id . " ) ";
    }

    // contact_id
    $contact_id = CRM_Utils_Array::value('contact_id', $params);
    if ($contact_id) {
      $clauses[] = "acon.contact_id = %1";
      $params[1] = array($contact_id, 'Integer');
    }

    // Exclude case activities
    $components = CRM_Activity_BAO_Activity::activityComponents();
    if (!in_array('CiviCase', $components)) {
      $clauses[] = 'acase.id IS NULL';
    }

    //FIXME Do we need a permission clause?

    return implode(' AND ', $clauses);
  }

  /**
   * Get the activity Count.
   *
   * @param array $input
   *   Array of parameters.
   *    Keys include
   *    - contact_id  int            contact_id whose activities we want to retrieve
   *    - admin       boolean        if contact is admin
   *    - caseId      int            case ID
   *    - context     string         page on which selector is build
   *    - activity_type_id int|string the activity types we want to restrict by
   *
   * @return int
   *   count of activities
   */
  public static function getContactActivitiesCount(&$params) {
    $caseFilter = self::getCaseFilter();

    $whereClause = self::whereClause($params, FALSE);

    $query = "SELECT COUNT(DISTINCT acon.activity_id)
              FROM civicrm_activity_contact acon
              LEFT JOIN civicrm_activity activity
              ON acon.activity_id = activity.id
              {$caseFilter} ";
    $query .= " WHERE {$whereClause}";

    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  /**
   * Wrapper for ajax activity selector.
   *
   * @param array $params
   *   Associated array for params record id.
   *
   * @return array
   *   Associated array of contact activities
   */
  public static function getContactActivitiesSelector(&$params) {
    // format the params
    $params['offset'] = ($params['page'] - 1) * $params['rp'];
    $params['rowCount'] = $params['rp'];
    $params['sort'] = CRM_Utils_Array::value('sortBy', $params);
    $params['caseId'] = NULL;
    $context = CRM_Utils_Array::value('context', $params);

    // get contact activities
    $activities = CRM_Fastactivity_BAO_Activity::getContactActivities($params);

    // add total
    $params['total'] = CRM_Fastactivity_BAO_Activity::getContactActivitiesCount($params);

    // format params and add links
    $contactActivities = array();

    if (!empty($activities)) {
      //$activityStatus = CRM_Core_PseudoConstant::activityStatus();
      $activityStatus = CRM_Activity_BAO_Activity::buildOptions('activity_status_id', 'validate');

      // check logged in user for permission
      $page = new CRM_Core_Page();
      CRM_Contact_Page_View::checkUserPermission($page, $params['contact_id']);
      $permissions = array($page->_permission);
      if (CRM_Core_Permission::check('delete activities')) {
        $permissions[] = CRM_Core_Permission::DELETE;
      }

      $mask = CRM_Core_Action::mask($permissions);

      foreach ($activities as $activityId => $values) {
        $contactActivities[$activityId]['activity_type'] = $values['activity_type'];
        $contactActivities[$activityId]['subject'] = $values['subject'];

        $contactActivities[$activityId]['source_contact'] = self::formatContactNames($values['source_contact_name'], $values['source_contact_count']);
        //$contactActivities[$activityId]['target_contact'] = '???';
        $contactActivities[$activityId]['target_contact'] = self::formatContactNames($values['target_contact_name'], $values['target_contact_count']);
        $contactActivities[$activityId]['assignee_contact'] = self::formatContactNames($values['assignee_contact_name'], $values['assignee_contact_count']);

        if (isset($values['mailingId']) && !empty($values['mailingId'])) {
          $contactActivities[$activityId]['target_contact'] = CRM_Utils_System::href($values['recipients'],
            'civicrm/mailing/report/event',
            "mid={$values['source_record_id']}&reset=1&event=queue&cid={$params['contact_id']}&context=activitySelector");
        }
        elseif (!empty($values['recipients'])) {
          $contactActivities[$activityId]['target_contact'] = $values['recipients'];
        }

        $contactActivities[$activityId]['activity_date'] = CRM_Utils_Date::customFormat($values['activity_date_time']);
        $contactActivities[$activityId]['status'] = $activityStatus[$values['status_id']];

        // add class to this row if overdue
        $contactActivities[$activityId]['class'] = '';
        if (CRM_Utils_Date::overdue(CRM_Utils_Array::value('activity_date_time', $values))
          && CRM_Utils_Array::value('status_id', $values) == 1
        ) {
          $contactActivities[$activityId]['class'] = 'status-overdue';
        }
        else {
          $contactActivities[$activityId]['class'] = 'status-ontime';
        }

        // build links
        $contactActivities[$activityId]['links'] = '';
        $accessMailingReport = FALSE;
        if (!empty($values['mailingId'])) {
          $accessMailingReport = TRUE;
        }

        $actionLinks = self::actionLinks(
          CRM_Utils_Array::value('activity_type_id', $values),
          CRM_Utils_Array::value('source_record_id', $values),
          $accessMailingReport,
          CRM_Utils_Array::value('activity_id', $values)
        );

        $actionMask = array_sum(array_keys($actionLinks)) & $mask;

        $contactActivities[$activityId]['links'] = CRM_Core_Action::formLink($actionLinks,
          $actionMask,
          array(
            'id' => $values['activity_id'],
            'cid' => $params['contact_id'],
            'cxt' => $context,
            'caseid' => CRM_Utils_Array::value('case_id', $values),
          ),
          ts('more'),
          FALSE,
          'activity.tab.row',
          'Activity',
          $values['activity_id']
        );

        if ($values['is_recurring_activity']) {
          $contactActivities[$activityId]['is_recurring_activity'] = CRM_Core_BAO_RecurringEntity::getPositionAndCount($values['activity_id'], 'civicrm_activity');
        }
      }
    }

    return $contactActivities;
  }

  /**
   * Format contact names for display in assignee, source, target activity views
   *
   * @param $contacts
   * @param $contactCount
   * @return string
   */
  public static function formatContactNames($contacts, $contactCount) {
    // Clear out any empty array values
    $contacts = array_filter($contacts);
    // if $contactCount > 4 we only show the current contact ID if found
    if (empty($contacts) && ($contactCount <= 4) && ($contactCount >= 0)) {
      return '<em>n/a</em>';
    }

    $result = '';
    $count = 0;
    foreach ($contacts as $acID => $acName) {
      if ($acID && $count < 5) {
        if ($count) {
          $result .= ";&nbsp;";
        }
        $result .= CRM_Utils_System::href($acName, 'civicrm/contact/view', "reset=1&cid={$acID}");
        $count++;
      }
    }
    if ($contactCount > 4) {
      if (empty($contacts)) {
        $result .= "(" . $contactCount . ' ' . ts('contacts') . ")";
      } else {
        $result .= "<br/>(" .ts('and'). ' ' . $contactCount . ' ' . ts('more') . ")";
      }
    }
    elseif ($contactCount < 0) {
      // We didn't count so display a spinner until we load the data
        $result .= '<div style="text-align: center"><i class="crm-i fa-spinner fa-spin fa-2x fa-fw"></i>
<span class="sr-only">(Loading...)</span></div>';
    }
    return $result;
  }

  /**
   * This method returns the action links that are given for each search row.
   * currently the action links added for each row are
   *
   * - View
   *
   * @param int $activityTypeId
   * @param int $sourceRecordId
   * @param bool $accessMailingReport
   * @param int $activityId
   * @param null $key
   * @param null $compContext
   *
   * @return array
   */
  public static function actionLinks(
    $activityTypeId,
    $sourceRecordId = NULL,
    $accessMailingReport = FALSE,
    $activityId = NULL,
    $key = NULL,
    $compContext = NULL) {
    static $activityActTypes = NULL;
    //CRM-14277 added addtitional param to handle activity search
    $extraParams = "&searchContext=activity";

    $extraParams .= ($key) ? "&key={$key}" : NULL;
    if ($compContext) {
      $extraParams .= "&compContext={$compContext}";
    }

    $showView = TRUE;
    $showUpdate = $showDelete = FALSE;
    $qsUpdate = NULL;

    if (!$activityActTypes) {
      $activeActTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'name', TRUE);
    }
    $activityTypeName = CRM_Utils_Array::value($activityTypeId, $activeActTypes);

    //CRM-7607
    //lets allow to have normal operation for only activity types.
    //when activity type is disabled or no more exists give only delete.
    switch ($activityTypeName) {
      case 'Event Registration':
      case 'Change Registration':
        $url = 'civicrm/contact/view/participant';
        $qsView = "action=view&reset=1&id={$sourceRecordId}&cid=%%cid%%&context=%%cxt%%{$extraParams}";
        break;

      case 'Contribution':
        $url = 'civicrm/contact/view/contribution';
        $qsView = "action=view&reset=1&id={$sourceRecordId}&cid=%%cid%%&context=%%cxt%%{$extraParams}";
        break;

      case 'Payment':
      case 'Refund':
        $participantId = CRM_Core_DAO::getFieldValue('CRM_Event_BAO_ParticipantPayment', $sourceRecordId, 'participant_id', 'contribution_id');
        if (!empty($participantId)) {
          $url = 'civicrm/contact/view/participant';
          $qsView = "action=view&reset=1&id={$participantId}&cid=%%cid%%&context=%%cxt%%{$extraParams}";
        }
        break;

      case 'Membership Signup':
      case 'Membership Renewal':
      case 'Change Membership Status':
      case 'Change Membership Type':
        $url = 'civicrm/contact/view/membership';
        $qsView = "action=view&reset=1&id={$sourceRecordId}&cid=%%cid%%&context=%%cxt%%{$extraParams}";
        break;

      case 'Pledge Reminder':
      case 'Pledge Acknowledgment':
        $url = 'civicrm/contact/view/activity';
        $qsView = "atype={$activityTypeId}&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=%%cxt%%{$extraParams}";
        break;

      case 'Email':
      case 'Bulk Email':
        $url = 'civicrm/activity/view';
        $delUrl = 'civicrm/activity';
        $qsView = "atype={$activityTypeId}&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=%%cxt%%{$extraParams}";
        if ($activityTypeName == 'Email') {
          $showDelete = TRUE;
        }
        break;

      case 'Inbound Email':
        $url = 'civicrm/contact/view/activity';
        $qsView = "atype={$activityTypeId}&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=%%cxt%%{$extraParams}";
        break;

      case 'Open Case':
      case 'Change Case Type':
      case 'Change Case Status':
      case 'Change Case Start Date':
        $showUpdate = $showDelete = FALSE;
        $url = 'civicrm/activity';
        $qsView = "atype={$activityTypeId}&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=%%cxt%%{$extraParams}";
        $qsUpdate = "atype={$activityTypeId}&action=update&reset=1&id=%%id%%&cid=%%cid%%&context=%%cxt%%{$extraParams}";
        break;

      default:
        $url = 'civicrm/activity';
        $showView = $showDelete = $showUpdate = TRUE;
        $qsView = "atype={$activityTypeId}&action=view&reset=1&id=%%id%%&cid=%%cid%%&context=%%cxt%%{$extraParams}";
        $qsUpdate = "atype={$activityTypeId}&action=update&reset=1&id=%%id%%&cid=%%cid%%&context=%%cxt%%{$extraParams}";

        //when type is not available lets hide view and update.
        if (empty($activityTypeName)) {
          $showView = $showUpdate = FALSE;
        }
        break;
    }

    $qsDelete = "atype={$activityTypeId}&action=delete&reset=1&id=%%id%%&cid=%%cid%%&context=%%cxt%%{$extraParams}";

    $actionLinks = array();

    if ($showView) {
      $actionLinks += array(
        CRM_Core_Action::
        VIEW => array(
          'name' => ts('View'),
          'url' => $url,
          'qs' => $qsView,
          'title' => ts('View Activity'),
        ),
      );
    }

    if ($showUpdate) {
      $updateUrl = 'civicrm/activity/add';
      if ($activityTypeName == 'Email') {
        $updateUrl = 'civicrm/activity/email/add';
      }
      elseif ($activityTypeName == 'Print PDF Letter') {
        $updateUrl = 'civicrm/activity/pdf/add';
      }
      if (CRM_Activity_BAO_Activity::checkPermission($activityId, CRM_Core_Action::UPDATE)) {
        $actionLinks += array(
          CRM_Core_Action::
          UPDATE => array(
            'name' => ts('Edit'),
            'url' => $updateUrl,
            'qs' => $qsUpdate,
            'title' => ts('Update Activity'),
          ),
        );
      }
    }

    if (
      $activityTypeName &&
      CRM_Case_BAO_Case::checkPermission($activityId, 'File On Case', $activityTypeId)
    ) {
      $actionLinks += array(
        CRM_Core_Action::
        ADD => array(
          'name' => ts('File on Case'),
          'url' => '#',
          'extra' => 'onclick="javascript:fileOnCase( \'file\', \'%%id%%\', null, this ); return false;"',
          'title' => ts('File on Case'),
        ),
      );
    }

    if ($showDelete) {
      if (!isset($delUrl) || !$delUrl) {
        $delUrl = $url;
      }
      $actionLinks += array(
        CRM_Core_Action::
        DELETE => array(
          'name' => ts('Delete'),
          'url' => $delUrl,
          'qs' => $qsDelete,
          'title' => ts('Delete Activity'),
        ),
      );
    }

    if ($accessMailingReport) {
      $actionLinks += array(
        CRM_Core_Action::
        BROWSE => array(
          'name' => ts('Mailing Report'),
          'url' => 'civicrm/mailing/report',
          'qs' => "mid={$sourceRecordId}&reset=1&cid=%%cid%%&context=activitySelector",
          'title' => ts('View Mailing Report'),
        ),
      );
    }

    return $actionLinks;
  }
}
