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
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Fastactivity_Form_View extends CRM_Fastactivity_Form_Base {

  protected $_currentlyViewedContactId;
  protected $_activityId;
  protected $_activityTypeId;
  protected $_activityTypeName;
  protected $_activitySubject;
  protected $_activityStatusId;
  protected $_activityStatus;
  protected $_activityDetails;
  protected $_activitySourceContacts;
  protected $_activityAssigneeContacts;
  protected $_activityTargetContacts;
  protected $_groupTree;

  public function preProcess()
  {
    // Array to hold details of activity for template
    $activityDetails = array();

    // Get currently viewed contact ID
    $this->_currentlyViewedContactId = $this->get('contactId');
    if (!$this->_currentlyViewedContactId) {
      $this->_currentlyViewedContactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    }
    $activityDetails['contactId'] = $this->_currentlyViewedContactId;

    // Get action
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this);

    // Get activity ID
    if ($this->_action != CRM_Core_Action::ADD) {
      $this->_activityId = CRM_Utils_Request::retrieve('id', 'Positive', $this);
      if (!isset($this->_activityId)) {
        CRM_Core_Error::statusBounce('No activity ID');
      }
    }

    $activityDetails['activityId'] = $this->_activityId;

    // Check if user has edit and/or view permissions for this activity
    if ($this->_activityId && in_array($this->_action, array(
        CRM_Core_Action::UPDATE,
        CRM_Core_Action::VIEW,
      ))
      && !CRM_Activity_BAO_Activity::checkPermission($this->_activityId, $this->_action)) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }

    // Check if user has "delete activities" permission
    if ($this->_action & CRM_Core_Action::DELETE) {
      if (!CRM_Core_Permission::check('delete activities')) {
        CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
      }
    }

    // Get activity record
    $activityRecord = civicrm_api3('Activity', 'getsingle', array(
      'sequential' => 1,
      'id' => $this->_activityId,
    ));

    // Get Activity Status
    $activityStatus = CRM_Activity_BAO_Activity::buildOptions('activity_status_id', 'validate');
    $this->_activityStatusId = $activityRecord['status_id'];
    $activityDetails['statusId'] = $this->_activityStatusId;
    $activityDetails['status'] = $activityStatus[$this->_activityStatusId];
    // Get Activity Date/Time
    $activityDetails['dateTime'] = $activityRecord['activity_date_time'];
    // Get Activity Type Name
    $this->_activityTypeId = $activityRecord['activity_type_id'];
    if ($this->_activityTypeId) {
      //set activity type name and description to template
      list($this->_activityTypeName, $activityTypeDescription) = CRM_Core_BAO_OptionValue::getActivityTypeDetails($this->_activityTypeId);
      $activityDetails['typeName'] = $this->_activityTypeName;
      $activityDetails['typeDescription'] = $activityTypeDescription;
    }
    // Get activity subject
    $this->_activitySubject = isset($activityRecord['subject']) ? $activityRecord['subject'] : null;
    $activityDetails['subject'] = $this->_activitySubject;

    if ($this->_action & CRM_Core_Action::DELETE) {
      // Don't need to load any more info about the activity
      // But we still display activity type, status and date for the user to confirm delete.
      $this->setActivityTitle(ts('Delete Activity'));
      $this->setActivityHeader(ts('Are you sure you want to delete this activity?'));
    }
    else {
      $priorities = CRM_Core_PseudoConstant::get('CRM_Activity_DAO_Activity', 'priority_id');
      $activityDetails['activityDetails'] = isset($activityRecord['details']) ? $activityRecord['details'] : null;

      $this->_activitySourceContacts = self::getSourceContacts($this->_activityId);
      $this->_activityAssigneeContacts = self::getAssigneeContacts($this->_activityId);
      $this->_activityTargetContacts = self::getTargetContacts($this->_activityId);

      $activityDetails['sourceContacts'] = $this->_activitySourceContacts;
      $activityDetails['assigneeContacts'] = $this->_activityAssigneeContacts;
      $activityDetails['targetContacts'] = $this->_activityTargetContacts;

      $activityDetails['priority'] = isset($activityRecord['priority_id']) ? $priorities[$activityRecord['priority_id']] : null;

      if (isset($activityRecord['medium_id'])) {
        $activityMedium = CRM_Activity_BAO_Activity::buildOptions('medium_id', 'validate');
        $activityDetails['medium'] = $activityMedium[$activityRecord['medium_id']];
      }

      // Add campaign details
      $activityDetails = self::addCampaignDetails($activityRecord, $activityDetails);

      $this->assign('customDataType', 'Activity');
      $this->assign('customDataSubType', $this->_activityTypeId);

      // Get custom fields
      $this->_groupTree = CRM_Core_BAO_CustomGroup::getTree('Activity', $this,
        $this->_activityId, 0, $this->_activityTypeId
      );

      $this->setActivityHeader();

      // when custom data is included in this page
      if (!empty($_POST['hidden_custom'])) {
        // we need to set it in the session for the below code to work
        // CRM-3014
        //need to assign custom data subtype to the template
        $this->set('type', 'Activity');
        $this->set('subType', $this->_activityTypeId);
        $this->set('entityId', $this->_activityId);
        CRM_Custom_Form_CustomData::preProcess($this, NULL, $this->_activityTypeId, 1, 'Activity', $this->_activityId);
        CRM_Custom_Form_CustomData::buildQuickForm($this);
        CRM_Custom_Form_CustomData::setDefaultValues($this);
      }
    }
    $this->assign('activity', $activityDetails);
    $this->setActivityTitle();
  }

  public function buildQuickForm() {
    $actionLinks = CRM_Fastactivity_BAO_Activity::actionLinks($this->_activityTypeId, $this->_activityId);
    if (isset($actionLinks[$this->_action])) {
      unset ($actionLinks[$this->_action]);
    }
    $this->assign('actionLinks', $actionLinks);

    if ($this->_action & CRM_Core_Action::DELETE) {
      // Delete activity action
      $this->addButtons(array(
          array(
            'type' => 'next',
            'name' => ts('Delete'),
            'isDefault' => TRUE,
          ),
          array(
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ),
        )
      );
      return;
    }
    else {
      // View activity action
      if (isset($this->_groupTree)) {
        CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $this->_groupTree);
      }
      // form should be frozen for view mode
      //$this->freeze();

      $buttons = array();
      $buttons[] = array(
        'type' => 'cancel',
        'name' => ts('Done'),
      );
      $this->addButtons($buttons);

      parent::buildQuickForm();
    }
  }

  public function postProcess() {
    $session = CRM_Core_Session::singleton();

    if (isset($this->_activityId)) {
      if ($this->_action & CRM_Core_Action::DELETE) {
        try {
          $result = civicrm_api3('Activity', 'delete', array(
            'sequential' => 1,
            'id' => $this->_activityId,
          ));
        }
        catch (Exception $e) {
          // Delete will fail if, for example the activity is already deleted
          $errorMsg = $e->getMessage();
          CRM_Core_Session::setStatus($errorMsg . ' (id='.$this->_activityId.')', ts('Error deleting Activity'), 'error');
          $session->replaceUserContext($session->readUserContext());
          return;
        }
        CRM_Core_Session::setStatus(ts('Activity has been deleted.'), ts('Activity Deleted'), 'success');
        $session->replaceUserContext($session->readUserContext());
        return;
      }
    }
    parent::postProcess();
  }

  /**
   * Get an array of source contacts ('id' => contact_id, 'name' => display_name)
   * @param $activityId
   * @return array
   */
  public function getSourceContacts($activityId) {
    return self::getContacts($activityId, "Activity Source");
  }

  /**
   * Get an array of assignee contacts ('id' => contact_id, 'name' => display_name)
   * @param $activityId
   * @return array
   */
  public function getAssigneeContacts($activityId) {
    return self::getContacts($activityId, "Activity Assignees");
  }

  /**
   * Get an array of target contacts ('id' => contact_id, 'name' => display_name)
   * If target contacts > MAX_TARGET_CONTACTS we just return 'count' of contacts. If < MAX_TARGET_CONTACTS we return all names as well.
   * @param $activityId
   * @return array
   */
  public function getTargetContacts($activityId) {
    $contactType = "Activity Targets";

    $contacts = array();
    $contactCount = civicrm_api3('ActivityContact', 'getcount', array(
      'sequential' => 1,
      'activity_id' => $activityId,
      'record_type_id' => $contactType,
    ));
    $contacts['count'] = $contactCount;

    if ($contactCount > CRM_Fastactivity_Form_Add::MAX_TARGETCONTACTS) {
      return $contacts;
    }
    else {
      $contacts = self::getContacts($activityId, $contactType);
      return $contacts;
    }
  }

  /**
   * Shared function that gets contact names/Ids and count as an array.
   * @param $activityId
   * @param $contactType
   * @return array
   */
  public function getContacts($activityId, $contactType) {
    $contacts = civicrm_api3('ActivityContact', 'get', array(
      'sequential' => 1,
      'activity_id' => $activityId,
      'record_type_id' => $contactType,
    ));
    if (isset($contacts['count']) && ($contacts['count'] > 0)) {
      foreach ($contacts['values'] as $contact) {
        $contactList[] = array('id' => $contact['contact_id'], 'name' => CRM_Contact_BAO_Contact::displayName($contact['contact_id']));
      }
      $contactList['count'] = $contacts['count'];
      return $contactList;
    }
    else {
      $contacts['count'] = 0;
      return $contacts;
    }
  }

  public function addCampaignDetails($activityRecord, $activityDetails) {
    if (isset($activityRecord['campaign_id'])) {
      $activityDetails['campaignId'] = $activityRecord['campaign_id'];
      // Get campaign title
      try {
        $campaignRecord = civicrm_api3('Campaign', 'getsingle', array(
          'return' => "title",
          'id' => $activityRecord['campaign_id'],
        ));
      }
      catch (Exception $e) {
        // Campaign not found
        $campaignRecord['title'] = "Unknown";
      }
      $activityDetails['campaign'] = $campaignRecord['title'];
    }
    // Add "stars" for engagement level
    $activityDetails['engagementLevelStars'] = '';
    if (isset($activityRecord['engagement_level'])) {
      $activityDetails['engagementLevel'] = $activityRecord['engagement_level'];
      for ($i=0; $i < $activityRecord['engagement_level']; $i++) {
        // We could do this in smarty but civicrm doesn't support smarty "for"
        $activityDetails['engagementLevelStars'] .= '<i class="crm-i fa-star"></i>';
      }
    }

    return $activityDetails;
  }
}
