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

    $outerOrderBy = ' ORDER BY inner_result.activity_date_time DESC, inner_result.activity_id DESC';
    if (!empty($params['outerSort'])) {
      $outerOrderBy = ' ORDER BY ' . CRM_Utils_Type::escape($params['outerSort'], 'String');
      $sortDirection = (strpos(strtolower($outerOrderBy), 'asc') === false) ? 'DESC' : 'ASC';
      $outerOrderBy .= ', inner_result.activity_id ' . $sortDirection;
    }

    // The main query.  This gets all the information (except target counts) for the tabbed activity display
    // We can't do anything with targets (like see if our contact is listed) as it slows down the query too much on large datasets
    list($selectClause, $groupByClause) = self::selectClause($params);
    list($outerSelectClause, $outerJoinQuery, $outerGroupByClause) = self::outerSelectClause($params);

    $innerQuery = "{$selectClause} WHERE {$whereClause} {$groupByClause} {$orderBy} {$limit}";
    $query = "{$outerSelectClause} FROM ({$innerQuery}) inner_result {$outerJoinQuery} {$outerGroupByClause} {$outerOrderBy}";
    CRM_Core_DAO::disableFullGroupByMode();
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    CRM_Core_DAO::reenableFullGroupByMode();

    //get all activity types
    $activityTypes = self::getActivityLabels();

    $values = array();
    while ($dao->fetch()) {
      $activityID = $dao->activity_id;
      $values[$activityID]['activity_id'] = $dao->activity_id;
      $values[$activityID]['activity_type_id'] = $dao->activity_type_id;
      $values[$activityID]['activity_type'] = $activityTypes[$dao->activity_type_id];
      $values[$activityID]['activity_date_time'] = $dao->activity_date_time;
      $values[$activityID]['status_id'] = $dao->activity_status_id;
      if ($params['optionalCols']['duration']) {
        $values[$activityID]['duration'] = $dao->activity_duration;
      }
      if ($params['optionalCols']['case']) {
        $values[$activityID]['activity_case_id'] = $dao->activity_case_id;
      }
      $values[$activityID]['subject'] = $dao->activity_subject;
      if ($params['optionalCols']['campaign_title']) {
        $values[$activityID]['campaign_id'] = $dao->activity_campaign_id;
        $values[$activityID]['campaign'] = $dao->activity_campaign_title;
      }
      $values[$activityID]['activity_parent_id'] = $dao->activity_parent_id;

      // Assign contact counts / names
      $values[$activityID]['assignee_contact_count'] = $dao->assignee_count;
      $values[$activityID]['source_contact_count'] = $dao->source_count;
      if ($params['optionalCols']['target_contact']) {
        $values[$activityID]['target_contact_count'] = $dao->target_count;
        $values[$activityID]['target_contact_id'] = $dao->target_contact_id;
        $values[$activityID]['target_contact_name'] = $dao->target_display_name;
      }
      else {
        $values[$activityID]['target_contact_count'] = -1; // -1 means we didn't count at all
      }
      $values[$activityID]['assignee_contact_name'] = $dao->assignee_display_name;
      $values[$activityID]['source_contact_name'] = $dao->source_display_name;
      $values[$activityID]['assignee_contact_id'] = $dao->assignee_contact_id;
      $values[$activityID]['source_contact_id'] = $dao->source_contact_id;
    }

    return $values;
  }

  public static function outerSelectClause($params) {
    $select[] = 'inner_result.activity_id AS activity_id';
    $groupBy[] = 'inner_result.activity_id';
    $select[] = 'inner_result.activity_type_id AS activity_type_id';
    $select[] = 'inner_result.activity_subject AS activity_subject';
    $select[] = 'inner_result.activity_date_time AS activity_date_time';
    $select[] = 'inner_result.activity_status_id AS activity_status_id';
    $select[] = 'recurring_entity.parent_id AS activity_parent_id';
    $select[] = 'COUNT(DISTINCT(sources.contact_id)) AS source_count';
    $select[] = 'GROUP_CONCAT(COALESCE(source_contact_me.id, source_contact_random.id) SEPARATOR "' . CRM_Core_DAO::VALUE_SEPARATOR . '") AS source_contact_id';
    $select[] = 'GROUP_CONCAT(COALESCE(source_contact_me.display_name, source_contact_random.display_name, "") SEPARATOR "' . CRM_Core_DAO::VALUE_SEPARATOR . '") AS source_display_name';
    $select[] = 'COUNT(DISTINCT(assignees.contact_id)) AS assignee_count';
    $select[] = 'GROUP_CONCAT(COALESCE(assignee_contact_me.id, assignee_contact_random.id) SEPARATOR "' . CRM_Core_DAO::VALUE_SEPARATOR . '") AS assignee_contact_id';
    $select[] = 'GROUP_CONCAT(COALESCE(assignee_contact_me.display_name, assignee_contact_random.display_name, "") SEPARATOR "' . CRM_Core_DAO::VALUE_SEPARATOR . '") AS assignee_display_name';

    $join[] = 'LEFT JOIN civicrm_recurring_entity recurring_entity ON inner_result.activity_id = recurring_entity.entity_id';
    $join[] = 'LEFT JOIN civicrm_activity_contact sources         ON (inner_result.activity_id = sources.activity_id AND sources.record_type_id = 2)';
    $join[] = 'LEFT JOIN civicrm_contact source_contact_random    ON (sources.contact_id = source_contact_random.id AND source_contact_random.is_deleted = 0)';
    $join[] = 'LEFT JOIN civicrm_contact source_contact_me        ON (sources.contact_id = source_contact_me.id AND source_contact_me.id = %1)';
    $join[] = 'LEFT JOIN civicrm_activity_contact assignees       ON (inner_result.activity_id = assignees.activity_id AND assignees.record_type_id = 1)';
    $join[] = 'LEFT JOIN civicrm_contact assignee_contact_random  ON (assignees.contact_id = assignee_contact_random.id AND assignee_contact_random.is_deleted = 0)';
    $join[] = 'LEFT JOIN civicrm_contact assignee_contact_me      ON (assignees.contact_id = assignee_contact_me.id AND assignee_contact_me.id = %1)';

    // base query is complete, handle OPTIONAL columns/filters

    if ($params['optionalCols']['target_contact']) {
      $select[] = 'COUNT(DISTINCT(targets.contact_id)) AS target_count';
      $select[] = 'GROUP_CONCAT(COALESCE(target_contact_me.id, target_contact_random.id) SEPARATOR "' . CRM_Core_DAO::VALUE_SEPARATOR . '") AS target_contact_id';
      $select[] = 'GROUP_CONCAT(COALESCE(target_contact_me.display_name, target_contact_random.display_name, "") SEPARATOR "' . CRM_Core_DAO::VALUE_SEPARATOR . '") AS target_display_name';

      $join[] = 'LEFT JOIN civicrm_activity_contact targets       ON (inner_result.activity_id = targets.activity_id AND targets.record_type_id = 3)';
      $join[] = 'LEFT JOIN civicrm_contact target_contact_random  ON (targets.contact_id = target_contact_random.id AND target_contact_random.is_deleted = 0)';
      $join[] = 'LEFT JOIN civicrm_contact target_contact_me      ON (targets.contact_id = target_contact_me.id AND target_contact_me.id = %1)';
    }

    if ($params['optionalCols']['duration']) {
      $select[] = 'inner_result.activity_duration AS activity_duration';
    }

    if ($params['optionalCols']['case']) {
      $select[] = 'inner_result.activity_case_id AS activity_case_id';
    }

    if ($params['optionalCols']['campaign_title']) {
      $select[] = 'inner_result.activity_campaign_id AS activity_campaign_id';
      $select[] = 'inner_result.activity_campaign_title AS activity_campaign_title';
    }

    // Assemble SELECT clause
    $selectQuery = 'SELECT ';
    $selectQuery .= implode(',', $select);
    // Assemble JOIN clause
    $joinQuery = implode(' ', $join);
    // Assemble GROUP BY clause
    $groupByQuery = 'GROUP BY ';
    $groupByQuery .= implode(',', $groupBy);
    return array($selectQuery, $joinQuery, $groupByQuery);
  }

  public static function selectClause($params) {
    $select[] = 'activity.id AS activity_id';
    $groupBy[] = 'activity.id';
    $select[] = 'activity.activity_type_id AS activity_type_id';
    $select[] = 'activity.subject AS activity_subject';
    $select[] = 'activity.activity_date_time AS activity_date_time';
    $select[] = 'activity.status_id AS activity_status_id';

    $join[] = 'LEFT JOIN civicrm_activity activity                ON acon.activity_id = activity.id';

    // base query is complete, handle OPTIONAL columns/filters

    if ($params['excludeCaseActivities'] || $params['optionalCols']['case']) {
      $join[] = 'LEFT JOIN civicrm_case_activity case_activity ON (activity.id = case_activity.activity_id)';
    }

    if ($params['optionalCols']['duration']) {
      $select[] = 'activity.duration AS activity_duration';
    }

    if ($params['optionalCols']['case']) {
      $select[] = 'case_activity.case_id AS activity_case_id';
    }

    if ($params['optionalCols']['campaign_title']) {
      $select[] = 'activity.campaign_id AS activity_campaign_id';
      $select[] = 'campaign.title AS activity_campaign_title';

      $join[] = 'LEFT JOIN civicrm_campaign campaign                ON (activity.campaign_id = campaign.id)';
    }

    // Assemble SELECT clause
    $selectQuery = 'SELECT ';
    $selectQuery .= implode(',', $select);
    $selectQuery .= ' FROM civicrm_activity_contact acon ';
    $selectQuery .= implode(' ', $join);
    // Assemble GROUP BY clause
    $groupByQuery = 'GROUP BY ';
    $groupByQuery .= implode(',', $groupBy);
    return array($selectQuery, $groupByQuery);
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
    // activity type ID clause
    $activity_type_id = $params['activity_type_id'] ?? NULL;
    $activity_type_exclude_id = $params['activity_type_exclude_id'] ?? NULL;
    $activity_date_relative = $params['activity_date_relative'] ?? NULL;
    $activity_date_low = $params['activity_date_low'] ?? NULL;
    $activity_date_high = $params['activity_date_high'] ?? NULL;
    $activity_status_id = $params['activity_status_id'] ?? NULL;
    $excludeCaseActivities = CRM_Utils_Array::value('excludeCaseActivities', $params, TRUE);

    // contact_id
    $contact_id = $params['contact_id'] ?? NULL;
    if ($contact_id) {
      $clauses[] = "acon.contact_id = %1";
      $params[1] = array($contact_id, 'Integer');
    }

    if (!empty($activity_type_id)) {
      $clauses[] = "activity.activity_type_id IN (%2)";
      $params[2] = [$activity_type_id, 'CommaSeparatedIntegers'];
    }
    if (!empty($activity_type_exclude_id)) {
      $clauses[] = "activity.activity_type_id NOT IN (%3)";
      $params[3] = [$activity_type_exclude_id, 'CommaSeparatedIntegers'];
    }
    if (!empty($activity_status_id)) {
      $clauses[] = "activity.status_id IN (%4)";
      $params[4] = [$activity_status_id, 'CommaSeparatedIntegers'];
    }
    if (!empty($activity_date_relative)) {
      list($from, $to) = CRM_Utils_Date::getFromTo($activity_date_relative, NULL, NULL);
      $clauses[] = 'activity.activity_date_time BETWEEN %5 AND %6';
      $params[5] = [$from, 'String'];
      $params[6] = [$to, 'String'];
    }
    else {
      if (!empty($activity_date_low)) {
        $from = CRM_Utils_Date::processDate($activity_date_low);
        $clauses[] = 'activity.activity_date_time >= %5';
        $params[5] = [$from, 'String'];
      }
      if (!empty($activity_date_high)) {
        $to = CRM_Utils_Date::processDate($activity_date_high);
        $clauses[] = 'activity.activity_date_time <= %6';
        $params[6] = [$to, 'String'];
      }
    }

    $clauses[] = "activity.is_current_revision != 0";
    $clauses[] = "activity.is_deleted = 0";

    if ($excludeCaseActivities) {
      $clauses[] = "case_activity.case_id IS NULL";
    }
    if (!empty($params['optionalCols'])) {
      // optionalCols not set when triggered from fastactivity_civicrm_tabs
      if ($params['optionalCols']['campaign_title']) {
        // campaign ID clause. Match on campaign and all sub-campaigns.
        $activity_campaign_id = $params['activity_campaign_id'] ?? NULL;
        if (!empty($activity_campaign_id)) {
          // Make campaign IDs into array
          $searchCampaignIds = explode(',', $activity_campaign_id);
          if (CRM_Extension_System::singleton()
            ->getMapper()
            ->isActiveModule('campaign')) {
            foreach ($searchCampaignIds as $campaignId) {
              // Get all child campaigns for selected campaign
              // NOTE: This adds a dependency on de.systopia.campaign
              $childCampaignIDs = civicrm_api3('CampaignTree', 'getids', [
                'sequential' => 1,
                'id' => $campaignId,
                'depth' => 3,
              ]);
              if (isset($childCampaignIDs['children'])) {
                foreach ($childCampaignIDs['children'] as $key => $value) {
                  // Add all child campaign IDs to search
                  $searchCampaignIds[] = $key;
                }
              }
            }
          }
          // Convert to string for query
          $activity_campaign_id = implode(',', $searchCampaignIds);
          $clauses[] = "activity.campaign_id IN (%7)";
          $params[7] = [$activity_campaign_id, 'CommaSeparatedIntegers'];
        }
      }
    }

    return implode(' AND ', $clauses);
  }

  /**
   * Get the activity Count.  Used for the count on the tab
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
    $whereClause = self::whereClause($params, FALSE);

    $query = "SELECT COUNT(DISTINCT acon.activity_id)
              FROM civicrm_activity_contact acon
              LEFT JOIN civicrm_activity activity ON acon.activity_id = activity.id ";
    if ($params['excludeCaseActivities']) {
      $query .= 'LEFT JOIN civicrm_case_activity case_activity ON (activity.id = case_activity.activity_id) ';
    }
    $query .= " WHERE {$whereClause}";

    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  /**
   * get a activity_type id => label mapping
   *
   * @param string $optionValueName
   * @return array of labels
   */
  public static function getActivityLabels($optionValueName = 'activity_type') {
    CRM_Core_OptionValue::getValues(array('name' => $optionValueName), $all_types);
    foreach ($all_types as $activity_type_id => $activity_type) {
      $labels[$activity_type['value']] = $activity_type['label'];
    }
    return $labels;
  }

  /**
   * This function removes an array of target contacts from the activity without retrieving all existing targets first
   *
   * @param $activityId
   * @param $targetIds (array of contact Ids)
   */
  public static function removeTargetContacts($activityId, $targetIds) {
    // Run query to see if the contacts are already a target contact
    // Generate list of contacts which we need to remove
    $missingIds = CRM_Fastactivity_BAO_Activity::isNotTargetContact($activityId, $targetIds);
    // Generate list of target contact Ids that exist and should be removed (don't try to remove non-existing Ids)
    $contactIds = array_diff($targetIds, $missingIds);
    // Remove those contacts
    $contactIdsList = implode(',', $contactIds);
    $query = "DELETE FROM `civicrm_activity_contact` WHERE (record_type_id=3) AND (activity_id=$activityId) AND (contact_id IN ($contactIdsList));";
    if (!empty($contactIdsList)) {
      try {
        $dao = CRM_Core_DAO::executeQuery($query);
      }
      catch (Exception $e) {
        Civi::log()->debug('fastactivity addTargetContacts: Error: '.$e->getMessage());
        return array();
      }

      // Return an array of Ids that we deleted
      return $contactIds;
    }
    else {
      // We should always have values to remove, but in case we don't
      return array();
    }
  }

  /**
   * This function adds an array of target contacts to the activity without retrieving existing targets
   *
   * @param $activityId
   * @param $targetIds (array of contact Ids)
   */
  public static function addTargetContacts($activityId, $targetIds) {
    // Run query to see if the contacts are already a target contact
    // Generate list of contacts which we need to remove
    $contactIds = CRM_Fastactivity_BAO_Activity::isNotTargetContact($activityId, $targetIds);
    // Add those contacts
    $query = "INSERT INTO `civicrm_activity_contact` (record_type_id,activity_id,contact_id) VALUES";
    $values = '';
    foreach ($contactIds as $id) {
      if (!empty($values)) { $values .= ','; }
      $values .= "(3,{$activityId},{$id})";
    }
    if (!empty($values)) {
      $values .= ';';
      $query = $query.$values;
      try {
        $dao = CRM_Core_DAO::executeQuery($query);
      }
      catch (Exception $e) {
        Civi::log()->debug('fastactivity addTargetContacts: Error: '.$e->getMessage());
        return array();
      }
      // Return an array of Ids that we added
      return $contactIds;
    }
    else {
      // We should always have values to add, but in case we don't
      return array();
    }
  }

  private static function isNotTargetContact($activityId, $targetIds) {
    if (empty($targetIds) || empty($activityId)) {
      return array();
    }

    // activity_id, contact_id, record_type_id=3
    // Build query to find all matching target contacts
    $query="SELECT DISTINCT(contact_id) FROM `civicrm_activity_contact` WHERE record_type_id=3 and activity_id=%1 and contact_id IN (%2)";
    $params[1] = array($activityId, 'Integer');
    $params[2] = array(implode(',', $targetIds), 'String');

    // Run query
    try {
      $dao = CRM_Core_DAO::executeQuery($query, $params);
    }
    catch (Exception $e) {
      Civi::log()->debug('fastactivity isNotTargetContact: Error: '.$e->getMessage());
      return array();
    }

    // Build list of existing Ids
    $targetExistingIds = array();
    while ($dao->fetch()) {
      // Each $dao->contact_id is a contact that is already a target contact so remove it from array
      $targetExistingIds[] = $dao->contact_id;
    }
    // Diff arrays to get list of target Ids that are not already existing
    $missingTargetIds = array_diff($targetIds, $targetExistingIds);
    return $missingTargetIds;
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
    $params['sort'] = $params['sortBy'] ?? NULL;
    $params['outerSort'] = $params['outerSortBy'] ?? NULL;
    $params['caseId'] = NULL;
    $context = $params['context'] ?? NULL;

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

        $contactActivities[$activityId]['source_contact'] = self::formatContactNames($values['source_contact_name'], $values['source_contact_id'], $values['source_contact_count']);
        $contactActivities[$activityId]['assignee_contact'] = self::formatContactNames($values['assignee_contact_name'], $values['assignee_contact_id'], $values['assignee_contact_count']);

        $contactActivities[$activityId]['activity_date'] = CRM_Utils_Date::customFormat($values['activity_date_time']);
        $contactActivities[$activityId]['status'] = $activityStatus[$values['status_id']];

        if ($params['optionalCols']['target_contact']) {
          $contactActivities[$activityId]['target_contact'] = self::formatContactNames($values['target_contact_name'], $values['target_contact_id'], $values['target_contact_count']);
        }

        if ($params['optionalCols']['duration']) {
          $contactActivities[$activityId]['duration'] = $values['duration'];
        }

        if ($params['optionalCols']['case']) {
          $contactActivities[$activityId]['activity_case_id'] = self::formatCaseLink($params['contact_id'], $values['activity_case_id']);
        }

        if ($params['optionalCols']['campaign_title']) {
          $contactActivities[$activityId]['campaign'] = CRM_Utils_System::href($values['campaign'], "civicrm/a/#/campaign/{$values['campaign_id']}/view");
        }

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
          $values['activity_id'] ?? NULL,
          $params['contact_id'] ?? NULL
        );

        $actionMask = array_sum(array_keys($actionLinks)) & $mask;

        $contactActivities[$activityId]['links'] = CRM_Core_Action::formLink($actionLinks,
          $actionMask,
          array(),
          ts('more'),
          FALSE,
          'activity.tab.row',
          'Activity',
          $values['activity_id']
        );

        if ($values['activity_parent_id']) {
          $contactActivities[$activityId]['is_recurring_activity'] = CRM_Core_BAO_RecurringEntity::getPositionAndCount($values['activity_id'], 'civicrm_activity');
        }
      }
    }

    return $contactActivities;
  }

  /**
   * Format an html "Manage" case link for the activities tab
   * @param int $contactId
   * @param int $caseId
   *
   * @return string
   */
  private static function formatCaseLink($contactId, $caseId) {
    if (empty($caseId)) return '';

    $caseContacts = civicrm_api3('Case', 'getvalue', array(
      'return' => "contact_id",
      'id' => $caseId,
    ));

    $isClientOfCase = FALSE;
    foreach ($caseContacts as $caseContactId) {
      if (!isset($firstCaseContactId)) {
        $firstCaseContactId = $caseContactId;
      }
      if ($contactId == $caseContactId) {
        $isClientOfCase = TRUE;
        break;
      }
    }

    if ($isClientOfCase) {
      $label = "ID: {$caseId}";
    }
    else {
      $contactName = civicrm_api3('Contact', 'getvalue', array(
        'return' => "display_name",
        'id' => $firstCaseContactId,
      ));
      $label = "ID: {$caseId}<br />({$contactName})";
    }
    $url = CRM_Utils_System::url('civicrm/contact/view/case', "reset=1&id={$caseId}&cid={$firstCaseContactId}&action=view");
    $html = "<a href='$url' class='action-item crm-hover-button no-popup' target='_blank'>{$label}</a>";
    return $html;
  }
  /**
   * Format contact names for display in assignee, source, target activity views
   *
   * @param $contacts
   * @param $contactIds
   * @param $contactCount
   * @return string
   */
  private static function formatContactNames($contacts, $contactIds, $contactCount) {
    $contacts = explode(CRM_Core_DAO::VALUE_SEPARATOR, $contacts);
    $contactIds = array_flip(array_flip(explode(CRM_Core_DAO::VALUE_SEPARATOR, $contactIds)));

    // if $contactCount > 4 we only show the current contact ID if found
    if (empty($contacts[0]) && ($contactCount <= 4) && ($contactCount >= 0)) {
      return '<em>n/a</em>';
    }

    $result = '';
    $count = 0;
    foreach ($contactIds as $key => $acID) {
      if (empty($contacts[$key])) {
        continue;
      }
      if ($acID && $count < 5) {
        if ($count) {
          $result .= ";&nbsp;";
        }
        $result .= CRM_Utils_System::href($contacts[$key], 'civicrm/contact/view', "reset=1&cid={$acID}");
        $count++;
      }
    }
    if ($contactCount > 4) {
      if (empty($contacts[0])) {
        $result .= "(" . $contactCount . ' ' . ts('contacts') . ")";
      } else {
        $result .= "<br/>(" .ts('and'). ' ' . $contactCount . ' ' . ts('more') . ")";
      }
    }
    elseif ($contactCount < 0) {
      // We didn't count so display a spinner until we load the data
      $result .= '<div style="text-align: center"><i class="crm-i fa-spinner fa-spin fa-2x fa-fw" role="img" aria-hidden="true"></i>
<span class="sr-only">(Loading...)</span></div>';
    }
    return $result;
  }


  /**
   * This method returns the action links that are given for each search row.
   * currently the action links added for each row are View, Edit, Delete
   *
   * @param int $activityTypeId
   * @param int $activityId
   * @param int $contactId
   * @return array
   */
  public static function actionLinks($activityTypeId, $activityId = NULL, $contactId = NULL) {
    $showView = TRUE;
    $showDelete = TRUE; //FIXME: May want to limit what types of activity can be deleted
    $showUpdate = TRUE;
    $qsUpdate = NULL;
    $actionLinks = array();

    if (empty($activityTypeId)) {
      // this case caused crashes
      return $actionLinks;
    }

    list($activityTypeName, $activityTypeDescription) = CRM_Core_BAO_OptionValue::getActivityTypeDetails($activityTypeId);
    $activityDetails = civicrm_api3('Activity', 'getsingle', [
      'return' => ["activity_type_id", "case_id"],
      'id' => $activityId,
    ]);

    $activityTypeName = CRM_Core_PseudoConstant::getName('CRM_Activity_BAO_Activity', 'activity_type_id', $activityDetails['activity_type_id']);
    // Don't offer a "File On Case" link if already added to a case.
    $skipFileOnCase = FALSE;
    if (!empty($activityDetails['case_id'])) {
      $skipFileOnCase = TRUE;
    }

    $qs = "&reset=1&id=$activityId&cid=$contactId";
    $qsView = "action=view{$qs}";
    $qsUpdate = "action=update{$qs}";
    $qsDelete = "action=delete{$qs}";

    $url = 'civicrm/fastactivity/view';

    if (CRM_Activity_BAO_Activity::checkPermission($activityId, CRM_Core_Action::VIEW)) {
      if ($showView) {
        $actionLinks += array(
          CRM_Core_Action::
          VIEW => array(
            'name' => ts('View'),
            'url' => $url,
            'qs' => $qsView,
            'title' => ts('View Activity'),
            'icon' => '<i class="crm-i fa-eye" aria-hidden="true"></i>',
          ),
        );
      }
    }

    if ($showUpdate) {
      $updateUrl = 'civicrm/fastactivity/add';
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
            'icon' => '<i class="crm-i fa-pencil" aria-hidden="true"></i>',
          ),
        );
      }
    }

    if (!$skipFileOnCase && $activityTypeName &&
      CRM_Case_BAO_Case::checkPermission($activityId, 'File On Case', $activityTypeId)) {
      $actionLinks += array(
        CRM_Core_Action::
        ADD => array(
          'name' => ts('File on Case'),
          'url' => '#',
          'extra' => 'onclick="javascript:fileOnCase( \'file\', ' . $activityId . ', null, this ); return false;"',
          'title' => ts('File on Case'),
        ),
      );
    }

    if (CRM_Core_Permission::check('delete activities')) {
      if ($showDelete) {
        $actionLinks += array(
          CRM_Core_Action::
          DELETE => array(
            'name' => ts('Delete'),
            'url' => $url,
            'qs' => $qsDelete,
            'title' => ts('Delete Activity'),
            'icon' => '<i class="crm-i fa-trash" aria-hidden="true"></i>',
          ),
        );
      }
    }

    return $actionLinks;
  }
}
