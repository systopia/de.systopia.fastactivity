<?php

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Fastactivity_Form_View extends CRM_Core_Form {

  public $_currentlyViewedContactId;
  public $_activityId;
  public $_activityTypeId;
  public $_activityTypeName;
  public $_activitySubject;
  public $_activityStatusId;
  public $_activityStatus;
  public $_activityDetails;
  public $_activitySourceContacts;
  public $_activityAssigneeContacts;
  public $_activityTargetContacts;
  public $_groupTree;

  public function preprocess()
  {
    // Get currently viewed contact ID
    $this->_currentlyViewedContactId = $this->get('contactId');
    if (!$this->_currentlyViewedContactId) {
      $this->_currentlyViewedContactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    }
    $this->assign('contactId', $this->_currentlyViewedContactId);

    // Get action
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this);

    if ($this->_action & CRM_Core_Action::DELETE) {
      if (!CRM_Core_Permission::check('delete activities')) {
        CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
      }
    }

    // Get activity ID
    if ($this->_action != CRM_Core_Action::ADD) {
      $this->_activityId = CRM_Utils_Request::retrieve('id', 'Positive', $this);
      if (!isset($this->_activityId)) {
        CRM_Core_Error::statusBounce('No activity ID');
      }
    }
    $this->assign('activityId', $this->_activityId);

    //check for required permissions, CRM-6264
    if ($this->_activityId &&
      in_array($this->_action, array(
        CRM_Core_Action::UPDATE,
        CRM_Core_Action::VIEW,
      )) &&
      !CRM_Activity_BAO_Activity::checkPermission($this->_activityId, $this->_action)
    ) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }
    if (($this->_action & CRM_Core_Action::VIEW) &&
      CRM_Activity_BAO_Activity::checkPermission($this->_activityId, CRM_Core_Action::UPDATE)
    ) {
      $this->assign('permission', 'edit');
    }

    $activityRecord = civicrm_api3('Activity', 'getsingle', array(
      'sequential' => 1,
      'id' => $this->_activityId,
    ));

    // Get activity type name
    $activityStatus = CRM_Activity_BAO_Activity::buildOptions('activity_status_id', 'validate');
    $priorities = CRM_Core_PseudoConstant::get('CRM_Activity_DAO_Activity', 'priority_id');

    $this->_activityTypeId = $activityRecord['activity_type_id'];
    if ($this->_activityTypeId) {
      //set activity type name and description to template
      list($this->_activityTypeName, $activityTypeDescription) = CRM_Core_BAO_OptionValue::getActivityTypeDetails($this->_activityTypeId);
      $this->assign('activityTypeName', $this->_activityTypeName);
      $this->assign('activityTypeDescription', $activityTypeDescription);
    }

    $this->assign('activitySubject', isset($activityRecord['subject']) ? $activityRecord['subject'] : null);
    $this->assign('activityDetails', isset($activityRecord['details']) ? $activityRecord['details'] : null);
    $this->_activityStatusId = $activityRecord['status_id'];
    $this->assign('activityStatusId', $this->_activityStatusId);
    $this->assign('activityStatus', $activityStatus[$this->_activityStatusId]);

    $this->_activitySourceContacts = self::getSourceContacts($this->_activityId);
    $this->_activityAssigneeContacts = self::getAssigneeContacts($this->_activityId);
    $this->_activityTargetContacts = self::getTargetContacts($this->_activityId);

    $this->assign('activitySourceContacts', $this->_activitySourceContacts);
    $this->assign('activityAssigneeContacts', $this->_activityAssigneeContacts);
    $this->assign('activityTargetContacts', $this->_activityTargetContacts);

    $this->assign('activityDateTime', $activityRecord['activity_date_time']);
    $this->assign('activityPriority', isset($activityRecord['priority_id']) ? $priorities[$activityRecord['priority_id']] : null);

    if (isset($activityRecord['medium_id'])) {
      $activityMedium = CRM_Activity_BAO_Activity::buildOptions('medium_id', 'validate');
      $this->assign('mediumId', $activityMedium[$activityRecord['medium_id']]);
    }
    $this->assign('customDataType', 'Activity');
    $this->assign('customDataSubType', $this->_activityTypeId);

    // Get custom fields
    $this->_groupTree = CRM_Core_BAO_CustomGroup::getTree('Activity', $this,
      $this->_activityId, 0, $this->_activityTypeId
    );

    // Set title
    if ($this->_currentlyViewedContactId) {
      $displayName = CRM_Contact_BAO_Contact::displayName($this->_currentlyViewedContactId);
      // Check if this is default domain contact CRM-10482
      if (CRM_Contact_BAO_Contact::checkDomainContact($this->_currentlyViewedContactId)) {
        $displayName .= ' (' . ts('default organization') . ')';
      }
      CRM_Utils_System::setTitle($displayName . ' - ' . $this->_activityTypeName);
    }
    else {
      CRM_Utils_System::setTitle(ts('Activity: '. $this->_activityTypeName));
    }

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
   * If target contacts > 20 we just return 'count' of contacts. If < 20 we return all names as well.
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

    if ($contactCount > 20) {
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

  public function buildQuickForm() {
    if (isset($this->_groupTree)) {
      CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $this->_groupTree);
    }
    // form should be frozen for view mode
    $this->freeze();

    $buttons = array();
    $buttons[] = array(
      'type' => 'cancel',
      'name' => ts('Done'),
    );
    $this->addButtons($buttons);

    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();
    $options = $this->getColorOptions();
    CRM_Core_Session::setStatus(ts('You picked color "%1"', array(
      1 => $options[$values['favorite_color']],
    )));
    parent::postProcess();
  }
}
