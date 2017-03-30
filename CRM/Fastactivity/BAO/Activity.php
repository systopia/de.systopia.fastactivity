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
 * $Id$
 */

/**
 * This class is for activity functions.
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
  public static function getActivities(&$params) {
    //step 1: Get the basic activity data
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

    //SELECT distinct acon.activity_id,act.subject FROM `civicrm_activity_contact` acon left join `civicrm_activity` act on acon.activity_id = act.id WHERE acon.contact_id=203
    $whereClause = self::whereClause($params,FALSE);

    // Add limit clause
    if (!empty($params['rowCount']) &&
      $params['rowCount'] > 0
    ) {
      $limit = " LIMIT {$params['offset']}, {$params['rowCount']} ";
    }

    // Add order by clause
    $orderBy = ' ORDER BY act.activity_date_time DESC';
    if (!empty($params['sort'])) {
      $orderBy = ' ORDER BY ' . CRM_Utils_Type::escape($params['sort'], 'String');

      // CRM-16905 - Sort by count cannot be done with sql
      if (strpos($params['sort'], 'count') === 0) {
        $orderBy = $limit = '';
      }
    }

    $caseFilter = self::getCaseFilter();

    $query = "
            SELECT DISTINCT acon.activity_id,act.* 
            FROM  civicrm_activity_contact acon  
            LEFT JOIN civicrm_activity act 
            ON acon.activity_id = act.id 
            {$caseFilter}
            WHERE {$whereClause} 
            {$orderBy}
            {$limit}";

    $dao = CRM_Core_DAO::executeQuery($query, $params, TRUE, 'CRM_Activity_DAO_Activity');

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
      $values[$activityID]['status_id'] = $dao->status_id;
      $values[$activityID]['subject'] = $dao->subject;
      $values[$activityID]['campaign_id'] = $dao->campaign_id;
      $values[$activityID]['is_recurring_activity'] = $dao->is_recurring_activity;

      if ($dao->campaign_id) {
        $values[$activityID]['campaign'] = $allCampaigns[$dao->campaign_id];
      }

      // civicrm_activity_contact: record_type_id: 1 assignee, 2 creator, 3 focus or target.
      $values[$activityID]['assignee_contact_count'] = self::getNamesCount($dao->activity_id, 1);
      $values[$activityID]['source_contact_count'] = self::getNamesCount($dao->activity_id, 2);
      $values[$activityID]['target_contact_count'] = self::getNamesCount($dao->activity_id, 3);
      list($values[$activityID]['assignee_contact_name'], $values[$activityID]['assignee_contact_id']) = self::getNames($dao->activity_id,1, TRUE, $values[$activityID]['assignee_contact_count'], $params);
      list($values[$activityID]['source_contact_name'], $values[$activityID]['source_contact_id']) = self::getNames($dao->activity_id,2, TRUE, $values[$activityID]['source_contact_count'], $params);
      list($values[$activityID]['target_contact_name'], $values[$activityID]['target_contact_id']) = self::getNames($dao->activity_id,3, TRUE, $values[$activityID]['target_contact_count'], $params);

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
      $caseFilter .= " LEFT JOIN  civicrm_case_activity acase ON ( acase.activity_id = act.id ) ";
    }
    return $caseFilter;
  }

  /**
   * Retrieve count of names for activity_id and record_type_id
   *
   * @param int $activityID - ID of activity
   * @param int $recordTypeID - one of 1: Assignee, 2: Source, 3: target
   *
   * @return int
   */
  public static function getNamesCount($activityID, $recordTypeID) {
    $query = "
SELECT count(*) 
FROM civicrm_activity_contact acon 
WHERE acon.activity_id=%1 
AND acon.record_type_id=%2 
AND EXISTS (SELECT con.id FROM civicrm_contact con WHERE con.is_deleted=0)";

    $params = array(
      1 => array($activityID, 'Integer'),
      2 => array($recordTypeID, 'Integer'),
    );
    $count = CRM_Core_DAO::singleValueQuery($query, $params);
    return $count;
  }

  /**
   * Retrieve contact names for activities by activity_id and record_type_id.
   * If $count > 10 we only look for our own contact ID so we can display "me+1000 others"
   *
   * @param int $activityID
   * @param int $recordTypeID
   * @param bool $alsoIDs
   * @param int $count - number of contacts
   * @param array $params
   *
   * @return array
   */
  public static function getNames($activityID, $recordTypeID, $alsoIDs = FALSE, $count, &$params) {
    $myContact = '';
    if ($count > 10) {
      // This query gets slow when searching for 1000s as may be the case for target,
      //  so limit to 10.  Set to 0 to disable limit
      $myContact = 'AND civicrm_activity_contact.contact_id = ' . CRM_Utils_Array::value('contact_id', $params);
    }

    $names = array();
    $ids = array();

    if (empty($activityID)) {
      return $alsoIDs ? array($names, $ids) : $names;
    }

    $query = "
SELECT     contact_a.id, contact_a.sort_name
FROM       civicrm_contact contact_a
INNER JOIN civicrm_activity_contact ON civicrm_activity_contact.contact_id = contact_a.id
WHERE      civicrm_activity_contact.activity_id = %1 
AND        civicrm_activity_contact.record_type_id = %2 
AND        contact_a.is_deleted = 0 
{$myContact}
";
    $queryParams = array(
      1 => array($activityID, 'Integer'),
      2 => array($recordTypeID, 'Integer'),
    );

    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($dao->fetch()) {
      $names[$dao->id] = $dao->sort_name;
      $ids[] = $dao->id;
    }

    return $alsoIDs ? array($names, $ids) : $names;
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
      $clauses[] = "act.is_deleted = 1";
    } else {
      $clauses[] = "act.is_deleted = 0";
    }

    // is_current_revision
    $is_current_revision = CRM_Utils_Array::value('is_current_revision', $params);
    if (empty($is_current_revision)) {
      $clauses[] = "act.is_current_revision = 1";
    } else {
      $clauses[] = "act.is_current_revision = 0";
    }

    // is_test
    $is_test = CRM_Utils_Array::value('is_test', $params);
    if ($is_test == '1') {
      $clauses[] = "act.is_test = 1";
    } else {
      $clauses[] = "act.is_test = 0";
    }

    // context
    $context = CRM_Utils_Array::value('context', $params);
    if ($context != 'activity') {
      $clauses[] = "act.status_id = 1";
    }

    // activity type ID clause
    $activity_type_id = CRM_Utils_Array::value('activity_type_id', $params);
    if (!empty($activity_type_id)) {
      $clauses[] = "act.activity_type_id IN ( " . $activity_type_id . " ) ";
    }

    // exclude by activity type clause
    $activity_type_exclude_id = CRM_Utils_Array::value('activity_type_exclude_id', $params);
    if (!empty($activity_type_exclude_id)) {
      $clauses[] = "act.activity_type_id NOT IN ( " . $activity_type_exclude_id . " ) ";
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
  public static function getActivitiesCount(&$params) {
    $caseFilter = self::getCaseFilter();

    $whereClause = self::whereClause($params, FALSE);

    $query = "SELECT COUNT(DISTINCT acon.activity_id) 
              FROM civicrm_activity_contact acon 
              LEFT JOIN civicrm_activity act 
              ON acon.activity_id = act.id 
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
  public static function getContactActivitySelector(&$params) {
    // format the params
    $params['offset'] = ($params['page'] - 1) * $params['rp'];
    $params['rowCount'] = $params['rp'];
    $params['sort'] = CRM_Utils_Array::value('sortBy', $params);
    $params['caseId'] = NULL;
    $context = CRM_Utils_Array::value('context', $params);

    // get contact activities
    $activities = CRM_Fastactivity_BAO_Activity::getActivities($params);

    // add total
    $params['total'] = CRM_Fastactivity_BAO_Activity::getActivitiesCount($params);

    // format params and add links
    $contactActivities = array();

    if (!empty($activities)) {
      //$activityStatus = CRM_Core_PseudoConstant::activityStatus();
      $activityStatus = CRM_Activity_BAO_Activity::buildOptions('activity_status', 'validate');

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

        $actionLinks = CRM_Fastactivity_Selector_Activity::actionLinks(
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
    // if $contactCount > 4 we only show the current contact ID if found
    if (empty($contacts) && $contactCount <= 4) {
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
    return $result;
  }
}
