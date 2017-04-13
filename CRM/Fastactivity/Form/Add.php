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
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Fastactivity_Form_Add extends CRM_Core_Form {
  protected $_currentlyViewedContactId;
  protected $_currentUserId;
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
  public $_groupTree;
  protected $_values;

  /**
   * The array of form field attributes.
   *
   * @var array
   */
  protected $_fields;
  /**
   * The _fields var can be used by sub class to set/unset/edit the
   * form fields based on their requirement
   */
  public function setFields() {
    $this->_fields = array(
      'subject' => array(
        'type' => 'text',
        'label' => ts('Subject'),
        'attributes' => CRM_Core_DAO::getAttribute('CRM_Activity_DAO_Activity',
          'subject'
        ),
      ),
      'duration' => array(
        'type' => 'text',
        'label' => ts('Duration'),
        'attributes' => array('size' => 4, 'maxlength' => 8),
        'required' => FALSE,
      ),
      'location' => array(
        'type' => 'text',
        'label' => ts('Location'),
        'attributes' => CRM_Core_DAO::getAttribute('CRM_Activity_DAO_Activity', 'location'),
        'required' => FALSE,
      ),
      'details' => array(
        'type' => 'wysiwyg',
        'label' => ts('Details'),
        // forces a smaller edit window
        'attributes' => array('rows' => 4, 'cols' => 60),
        'required' => FALSE,
      ),
      'status_id' => array(
        'type' => 'select',
        'required' => TRUE,
      ),
      'priority_id' => array(
        'type' => 'select',
        'required' => TRUE,
      ),
      'source_contact_id' => array(
        'type' => 'entityRef',
        'label' => ts('Added By'),
        'required' => FALSE,
      ),
      'target_contact_id' => array(
        'type' => 'entityRef',
        'label' => ts('With Contact'),
        'attributes' => array('multiple' => TRUE, 'create' => TRUE),
      ),
      'assignee_contact_id' => array(
        'type' => 'entityRef',
        'label' => ts('Assigned to'),
        'attributes' => array(
          'multiple' => TRUE,
          'create' => TRUE,
          'api' => array('params' => array('is_deceased' => 0)),
        ),
      ),
      'followup_assignee_contact_id' => array(
        'type' => 'entityRef',
        'label' => ts('Assigned to'),
        'attributes' => array(
          'multiple' => TRUE,
          'create' => TRUE,
          'api' => array('params' => array('is_deceased' => 0)),
        ),
      ),
      'followup_activity_type_id' => array(
        'type' => 'select',
        'label' => ts('Followup Activity'),
        'attributes' => array('' => '- ' . ts('select activity') . ' -') + CRM_Core_PseudoConstant::ActivityType(FALSE),
        'extra' => array('class' => 'crm-select2'),
      ),
      // Add optional 'Subject' field for the Follow-up Activiity, CRM-4491
      'followup_activity_subject' => array(
        'type' => 'text',
        'label' => ts('Subject'),
        'attributes' => CRM_Core_DAO::getAttribute('CRM_Activity_DAO_Activity',
          'subject'
        ),
      ),
    );

    if ($printPDF = CRM_Utils_Array::key('Print PDF Letter', $this->_fields['followup_activity_type_id']['attributes'])) {
      unset($this->_fields['followup_activity_type_id']['attributes'][$printPDF]);
    }
  }

  public function preProcess()
  {
    // AJAX query for custom data is called to civicrm/fastactivity/add
    // This handles that query and returns the edit form block for customData
    $this->_cdType = CRM_Utils_Array::value('type', $_GET);
    $this->assign('cdType', FALSE);
    if ($this->_cdType) {
      $this->assign('cdType', TRUE);
      CRM_Custom_Form_CustomData::preProcess($this);
      return;
    }

    // Check if we should be accessing this page
    $allowedActions = array(CRM_Core_Action::ADD, CRM_Core_Action::UPDATE);
    if (!in_array($this->_action, $allowedActions)) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }

    // Get currently logged in user ID
    $session = CRM_Core_Session::singleton();
    $this->_currentUserId = $session->get('userID');

    // Get currently viewed contact ID
    $this->_currentlyViewedContactId = $this->get('contactId');
    if (!$this->_currentlyViewedContactId) {
      $this->_currentlyViewedContactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    }
    $this->assign('contactId', $this->_currentlyViewedContactId);

    // Get action
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this);

    // Get activity ID
    if ($this->_action != CRM_Core_Action::ADD) {
      $this->_activityId = CRM_Utils_Request::retrieve('id', 'Positive', $this);
      if (!isset($this->_activityId)) {
        CRM_Core_Error::statusBounce('No activity ID');
      }
    }
    $this->assign('activityId', $this->_activityId);

    // Check if user has edit permissions for this activity
    if ($this->_activityId && in_array($this->_action, array(
        CRM_Core_Action::UPDATE,
      ))
      && !CRM_Activity_BAO_Activity::checkPermission($this->_activityId, $this->_action)) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }

    if ($this->_action != CRM_Core_Action::ADD) {
      // Only retrieve activity data for view/delete/edit activity
      // Get activity record
      $activityRecord = civicrm_api3('Activity', 'getsingle', array(
        'sequential' => 1,
        'id' => $this->_activityId,
      ));
      // Get Activity Type Name
      $this->_activityTypeId = $activityRecord['activity_type_id'];
      if ($this->_activityTypeId) {
        //set activity type name and description to template
        list($this->_activityTypeName, $activityTypeDescription) = CRM_Core_BAO_OptionValue::getActivityTypeDetails($this->_activityTypeId);
        $this->assign('activityTypeName', $this->_activityTypeName);
        $this->assign('activityTypeDescription', $activityTypeDescription);
      }
    }

    if (!in_array($this->_action, array(CRM_Core_Action::ADD, CRM_Core_Action::UPDATE))) {
      // Get Activity Status
      $activityStatus = CRM_Activity_BAO_Activity::buildOptions('activity_status_id', 'validate');
      $this->_activityStatusId = $activityRecord['status_id'];
      $this->assign('activityStatusId', $this->_activityStatusId);
      $this->assign('activityStatus', $activityStatus[$this->_activityStatusId]);
      // Get Activity Date/Time
      $this->assign('activityDateTime', $activityRecord['activity_date_time']);
      // Get activity subject
      $this->_activitySubject = isset($activityRecord['subject']) ? $activityRecord['subject'] : null;
      $this->assign('activitySubject', $this->_activitySubject);

      $priorities = CRM_Core_PseudoConstant::get('CRM_Activity_DAO_Activity', 'priority_id');
      $this->assign('activityDetails', isset($activityRecord['details']) ? $activityRecord['details'] : null);

      $this->_activitySourceContacts = self::getSourceContacts($this->_activityId);
      $this->_activityAssigneeContacts = self::getAssigneeContacts($this->_activityId);
      $this->_activityTargetContacts = self::getTargetContacts($this->_activityId);

      $this->assign('activitySourceContacts', $this->_activitySourceContacts);
      $this->assign('activityAssigneeContacts', $this->_activityAssigneeContacts);
      $this->assign('activityTargetContacts', $this->_activityTargetContacts);

      $this->assign('activityPriority', isset($activityRecord['priority_id']) ? $priorities[$activityRecord['priority_id']] : null);

      if (isset($activityRecord['medium_id'])) {
        $activityMedium = CRM_Activity_BAO_Activity::buildOptions('medium_id', 'validate');
        $this->assign('mediumId', $activityMedium[$activityRecord['medium_id']]);
      }
    }

    // Assign values for use by buildCustomData functions
    $this->assign('customDataType', 'Activity');
    $this->assign('customDataSubType', $this->_activityTypeId);

    // Get custom fields
    $this->_groupTree = CRM_Core_BAO_CustomGroup::getTree('Activity', $this,
      $this->_activityId, 0, $this->_activityTypeId);

    if (!empty($_POST['hidden_custom'])) {
      // This ensures we don't lose custom data values on page reload (eg. if formrule fails)
      //need to assign custom data subtype to the template
      $this->set('type', 'Activity');
      $this->set('subType', $this->_activityTypeId);
      $this->set('entityId', $this->_activityId);
      CRM_Custom_Form_CustomData::preProcess($this, NULL, $this->_activityTypeId, 1, 'Activity', $this->_activityId);
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      CRM_Custom_Form_CustomData::setDefaultValues($this);
    }

    self::setActivityHeader();
    self::setActivityTitle();

    $this->_values = $this->get('values');
    if (!is_array($this->_values)) {
      $this->_values = array();
      if (isset($this->_activityId) && $this->_activityId) {
        $params = array('id' => $this->_activityId);
        CRM_Activity_BAO_Activity::retrieve($params, $this->_values);
      }
      $this->set('values', $this->_values);
    }

    $this->setFields();
  }

  public function buildQuickForm() {
    if ($this->_cdType) {
      // AJAX query for custom data is called to civicrm/fastactivity/add
      // This handles that query and returns the edit form block for customData
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      return;
    }

    if (!in_array($this->_action, array(CRM_Core_Action::ADD))) {
      $actionLinks = CRM_Fastactivity_BAO_Activity::actionLinks($this->_activityTypeId, $this->_activityId);
      if (isset($actionLinks[$this->_action])) {
        unset ($actionLinks[$this->_action]);
      }
      $this->assign('actionLinks', $actionLinks);
    }

    // FIXME: Start Copy from Form/Activity.php

    $element = &$this->add('select', 'activity_type_id', ts('Activity Type'),
      array('' => '- ' . ts('select') . ' -') + $this->_fields['followup_activity_type_id']['attributes'],
      FALSE, array(
        'onchange' => "CRM.buildCustomData( 'Activity', this.value );",
        'class' => 'crm-select2 required',
      )
    );

    //freeze for update mode.
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $element->freeze();
    }

    //Call to RecurringEntity buildQuickForm for add/update mode
    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD)) {
      CRM_Core_Form_RecurringEntity::buildQuickForm($this);
    }

    foreach ($this->_fields as $field => $values) {
      if (!empty($this->_fields[$field])) {
        $attribute = CRM_Utils_Array::value('attributes', $values);
        $required = !empty($values['required']);

        if ($values['type'] == 'wysiwyg') {
          $this->addWysiwyg($field, $values['label'], $attribute, $required);
        }
        elseif ($values['type'] == 'select' && empty($attribute)) {
          $this->addSelect($field, array('entity' => 'activity'), $required);
        }
        elseif ($values['type'] == 'entityRef') {
          $this->addEntityRef($field, $values['label'], $attribute, $required);
        }
        else {
          $this->add($values['type'], $field, $values['label'], $attribute, $required, CRM_Utils_Array::value('extra', $values));
        }
      }
    }

    self::buildFormElementsCampaign();

    // this option should be available only during add mode
    if ($this->_action != CRM_Core_Action::UPDATE) {
      $this->add('advcheckbox', 'is_multi_activity', ts('Create a separate activity for each contact.'));
    }

    $this->addRule('duration',
      ts('Please enter the duration as number of minutes (integers only).'), 'positiveInteger'
    );
    $this->addDateTime('activity_date_time', ts('Date'), TRUE, array('formatType' => 'activityDateTime'));

    //add followup date
    $this->addDateTime('followup_date', ts('in'), FALSE, array('formatType' => 'activityDateTime'));

    // Only admins can change the activity source
    if (!CRM_Core_Permission::check('administer CiviCRM')) {
      $this->getElement('source_contact_id')->freeze();
    }

    //need to assign custom data type and subtype to the template
    $this->assign('customDataType', 'Activity');
    $this->assign('customDataSubType', $this->_activityTypeId);
    $this->assign('entityID', $this->_activityId);

    CRM_Core_BAO_Tag::getTags('civicrm_activity', $tags, NULL,
      '&nbsp;&nbsp;', TRUE);

    if (!empty($tags)) {
      $this->add('select', 'tag', ts('Tags'), $tags, FALSE,
        array('id' => 'tags', 'multiple' => 'multiple', 'class' => 'crm-select2 huge')
      );
    }

    // we need to hide activity tagset for special activities
    $specialActivities = array('Open Case');

    if (!in_array($this->_activityTypeName, $specialActivities)) {
      // build tag widget
      $parentNames = CRM_Core_BAO_Tag::getTagSet('civicrm_activity');
      CRM_Core_Form_Tag::buildQuickForm($this, $parentNames, 'civicrm_activity', $this->_activityId);
    }

    // if we're viewing, we're assigning different buttons than for adding/editing
    if ($this->_action & CRM_Core_Action::VIEW) {
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
    }
    else {
      $message = array(
        'completed' => ts('Are you sure? This is a COMPLETED activity with the DATE in the FUTURE. Click Cancel to change the date / status. Otherwise, click OK to save.'),
        'scheduled' => ts('Are you sure? This is a SCHEDULED activity with the DATE in the PAST. Click Cancel to change the date / status. Otherwise, click OK to save.'),
      );
      $js = array('onclick' => "return activityStatus(" . json_encode($message) . ");");
      $this->addButtons(array(
          array(
            'type' => 'upload',
            'name' => ts('Save'),
            'js' => $js,
            'isDefault' => TRUE,
          ),
          array(
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ),
        )
      );
    }

    $this->addFormRule(array('CRM_Activity_Form_Activity', 'formRule'), $this);

    if (CRM_Core_BAO_Setting::getItem(
      CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'activity_assignee_notification'
    )
    ) {
      $this->assign('activityAssigneeNotification', TRUE);
    }
    else {
      $this->assign('activityAssigneeNotification', FALSE);
    }
  }

  public function buildFormElementsCampaign() {
    //CRM-7362 --add campaigns.
    CRM_Campaign_BAO_Campaign::addCampaign($this, CRM_Utils_Array::value('campaign_id', $this->_values));

    //add engagement level CRM-7775
    $buildEngagementLevel = FALSE;
    if (CRM_Campaign_BAO_Campaign::isCampaignEnable() &&
      CRM_Campaign_BAO_Campaign::accessCampaign()
    ) {
      $buildEngagementLevel = TRUE;
      $this->addSelect('engagement_level', array('entity' => 'activity'));
      $this->addRule('engagement_level',
        ts('Please enter the engagement index as a number (integers only).'),
        'positiveInteger'
      );
    }
    $this->assign('buildEngagementLevel', $buildEngagementLevel);

    // check for survey activity
    $this->_isSurveyActivity = FALSE;

    if ($this->_activityId && CRM_Campaign_BAO_Campaign::isCampaignEnable() &&
      CRM_Campaign_BAO_Campaign::accessCampaign()
    ) {

      $this->_isSurveyActivity = CRM_Campaign_BAO_Survey::isSurveyActivity($this->_activityId);
      if ($this->_isSurveyActivity) {
        $surveyId = CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity',
          $this->_activityId,
          'source_record_id'
        );
        $responseOptions = CRM_Campaign_BAO_Survey::getResponsesOptions($surveyId);
        if ($responseOptions) {
          $this->add('select', 'result', ts('Result'),
            array('' => ts('- select -')) + array_combine($responseOptions, $responseOptions)
          );
        }
        $surveyTitle = NULL;
        if ($surveyId) {
          $surveyTitle = CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Survey', $surveyId, 'title');
        }
        $this->assign('surveyTitle', $surveyTitle);
      }
    }
    $this->assign('surveyActivity', $this->_isSurveyActivity);
  }

  public function postProcess($params = NULL) {
    // store the submitted values in an array
    if (!$params) {
      $params = $this->controller->exportValues($this->_name);
    }

    //set activity type id
    if (empty($params['activity_type_id'])) {
      $params['activity_type_id'] = $this->_activityTypeId;
    }

    if (!empty($params['hidden_custom']) &&
      !isset($params['custom'])
    ) {
      $customFields = CRM_Core_BAO_CustomField::getFields('Activity', FALSE, FALSE,
        $this->_activityTypeId
      );
      $customFields = CRM_Utils_Array::crmArrayMerge($customFields,
        CRM_Core_BAO_CustomField::getFields('Activity', FALSE, FALSE,
          NULL, NULL, TRUE
        )
      );
      $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
        $customFields,
        $this->_activityId,
        'Activity'
      );
    }

    // store the date with proper format
    $params['activity_date_time'] = CRM_Utils_Date::processDate($params['activity_date_time'], $params['activity_date_time_time']);

    // format params as arrays
    foreach (array('target', 'assignee', 'followup_assignee') as $name) {
      if (!empty($params["{$name}_contact_id"])) {
        $params["{$name}_contact_id"] = explode(',', $params["{$name}_contact_id"]);
      }
      else {
        $params["{$name}_contact_id"] = array();
      }
    }

    // get ids for associated contacts
    if (!$params['source_contact_id']) {
      $params['source_contact_id'] = $this->_currentUserId;
    }

    if (isset($this->_activityId)) {
      $params['id'] = $this->_activityId;
    }

    // add attachments as needed
    CRM_Core_BAO_File::formatAttachment($params,
      $params,
      'civicrm_activity',
      $this->_activityId
    );

    $activity = array();
    if (!empty($params['is_multi_activity']) &&
      !CRM_Utils_Array::crmIsEmptyArray($params['target_contact_id'])
    ) {
      $targetContacts = $params['target_contact_id'];
      foreach ($targetContacts as $targetContactId) {
        $params['target_contact_id'] = array($targetContactId);
        // save activity
        $activity[] = $this->processActivity($params);
      }
    }
    else {
      // save activity
      $activity = $this->processActivity($params);
    }

    $activityIds = empty($this->_activityIds) ? array($this->_activityId) : $this->_activityIds;
    foreach ($activityIds as $activityId) {
      // set params for repeat configuration in create mode
      $params['entity_id'] = $activityId;
      $params['entity_table'] = 'civicrm_activity';
      if (!empty($params['entity_id']) && !empty($params['entity_table'])) {
        $checkParentExistsForThisId = CRM_Core_BAO_RecurringEntity::getParentFor($params['entity_id'], $params['entity_table']);
        if ($checkParentExistsForThisId) {
          $params['parent_entity_id'] = $checkParentExistsForThisId;
          $scheduleReminderDetails = CRM_Core_BAO_RecurringEntity::getReminderDetailsByEntityId($checkParentExistsForThisId, $params['entity_table']);
        }
        else {
          $params['parent_entity_id'] = $params['entity_id'];
          $scheduleReminderDetails = CRM_Core_BAO_RecurringEntity::getReminderDetailsByEntityId($params['entity_id'], $params['entity_table']);
        }
        if (property_exists($scheduleReminderDetails, 'id')) {
          $params['schedule_reminder_id'] = $scheduleReminderDetails->id;
        }
      }
      $params['dateColumns'] = array('activity_date_time');

      // Set default repetition start if it was not provided.
      if (empty($params['repetition_start_date'])) {
        $params['repetition_start_date'] = $params['activity_date_time'];
      }

      // unset activity id
      unset($params['id']);
      $linkedEntities = array(
        array(
          'table' => 'civicrm_activity_contact',
          'findCriteria' => array(
            'activity_id' => $activityId,
          ),
          'linkedColumns' => array('activity_id'),
          'isRecurringEntityRecord' => FALSE,
        ),
      );
      CRM_Core_Form_RecurringEntity::postProcess($params, 'civicrm_activity', $linkedEntities);
    }

    return array('activity' => $activity);
  }

  /**
   * Process activity creation.
   *
   * @param array $params
   *   Associated array of submitted values.
   *
   * @return self|null|object
   */
  protected function processActivity(&$params) {
    $activityAssigned = array();
    $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    // format assignee params
    if (!CRM_Utils_Array::crmIsEmptyArray($params['assignee_contact_id'])) {
      //skip those assignee contacts which are already assigned
      //while sending a copy.CRM-4509.
      $activityAssigned = array_flip($params['assignee_contact_id']);
      if ($this->_activityId) {
        $assigneeContacts = CRM_Activity_BAO_ActivityContact::getNames($this->_activityId, $assigneeID);
        $activityAssigned = array_diff_key($activityAssigned, $assigneeContacts);
      }
    }

    $activity = CRM_Activity_BAO_Activity::create($params);

    // add tags if exists
    $tagParams = array();
    if (!empty($params['tag'])) {
      foreach ($params['tag'] as $tag) {
        $tagParams[$tag] = 1;
      }
    }

    //save static tags
    CRM_Core_BAO_EntityTag::create($tagParams, 'civicrm_activity', $activity->id);

    //save free tags
    if (isset($params['activity_taglist']) && !empty($params['activity_taglist'])) {
      CRM_Core_Form_Tag::postProcess($params['activity_taglist'], $activity->id, 'civicrm_activity', $this);
    }

    // CRM-9590
    if (!empty($params['is_multi_activity'])) {
      $this->_activityIds[] = $activity->id;
    }
    else {
      $this->_activityId = $activity->id;
    }

    // create follow up activity if needed
    $followupStatus = '';
    $followupActivity = NULL;
    if (!empty($params['followup_activity_type_id'])) {
      $followupActivity = CRM_Activity_BAO_Activity::createFollowupActivity($activity->id, $params);
      $followupStatus = ts('A followup activity has been scheduled.');
    }

    // send copy to assignee contacts.CRM-4509
    $mailStatus = '';

    if (CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'activity_assignee_notification')
    ) {
      $activityIDs = array($activity->id);
      if ($followupActivity) {
        $activityIDs = array_merge($activityIDs, array($followupActivity->id));
      }
      $assigneeContacts = CRM_Activity_BAO_ActivityAssignment::getAssigneeNames($activityIDs, TRUE, FALSE);

      if (!CRM_Utils_Array::crmIsEmptyArray($params['assignee_contact_id'])) {
        $mailToContacts = array();

        //build an associative array with unique email addresses.
        foreach ($activityAssigned as $id => $dnc) {
          if (isset($id) && array_key_exists($id, $assigneeContacts)) {
            $mailToContacts[$assigneeContacts[$id]['email']] = $assigneeContacts[$id];
          }
        }

        if (!CRM_Utils_array::crmIsEmptyArray($mailToContacts)) {
          //include attachments while sending a copy of activity.
          $attachments = CRM_Core_BAO_File::getEntityFile('civicrm_activity', $activity->id);

          $ics = new CRM_Activity_BAO_ICalendar($activity);
          $ics->addAttachment($attachments, $mailToContacts);

          // CRM-8400 add param with _currentlyViewedContactId for URL link in mail
          CRM_Case_BAO_Case::sendActivityCopy(NULL, $activity->id, $mailToContacts, $attachments, NULL);

          $ics->cleanup();

          $mailStatus .= ts("A copy of the activity has also been sent to assignee contacts(s).");
        }
      }

      // Also send email to follow-up activity assignees if set
      if ($followupActivity) {
        $mailToFollowupContacts = array();
        foreach ($assigneeContacts as $values) {
          if ($values['activity_id'] == $followupActivity->id) {
            $mailToFollowupContacts[$values['email']] = $values;
          }
        }

        if (!CRM_Utils_array::crmIsEmptyArray($mailToFollowupContacts)) {
          $ics = new CRM_Activity_BAO_ICalendar($followupActivity);
          $attachments = CRM_Core_BAO_File::getEntityFile('civicrm_activity', $followupActivity->id);
          $ics->addAttachment($attachments, $mailToFollowupContacts);

          CRM_Case_BAO_Case::sendActivityCopy(NULL, $followupActivity->id, $mailToFollowupContacts, $attachments, NULL);

          $ics->cleanup();

          $mailStatus .= '<br />' . ts("A copy of the follow-up activity has also been sent to follow-up assignee contacts(s).");
        }
      }
    }

    // set status message
    $subject = '';
    if (!empty($params['subject'])) {
      $subject = "'" . $params['subject'] . "'";
    }

    CRM_Core_Session::setStatus(ts('Activity %1 has been saved. %2 %3',
      array(
        1 => $subject,
        2 => $followupStatus,
        3 => $mailStatus,
      )
    ), ts('Saved'), 'success');

    return $activity;
  }

  /**
   * Set default values for the form. For edit/view mode
   * the default values are retrieved from the database
   *
   * Most of this is based on CRM_Activity_Form_Activity
   *
   * @return void|array
   */
  public function setDefaultValues() {
    if ($this->_cdType) {
      // AJAX query for custom data is called to civicrm/fastactivity/add
      // This handles that query and returns the edit form block for customData
      CRM_Custom_Form_CustomData::setDefaultValues($this);
      return;
    }

    $defaults = $this->_values + CRM_Core_Form_RecurringEntity::setDefaultValues();
    // if we're editing...
    if (isset($this->_activityId)) {
      if (empty($defaults['activity_date_time'])) {
        list($defaults['activity_date_time'], $defaults['activity_date_time_time']) = CRM_Utils_Date::setDateDefaults(NULL, 'activityDateTime');
      }
      elseif ($this->_action & CRM_Core_Action::UPDATE) {
        $this->assign('current_activity_date_time', $defaults['activity_date_time']);
        list($defaults['activity_date_time'],
          $defaults['activity_date_time_time']
          ) = CRM_Utils_Date::setDateDefaults($defaults['activity_date_time'], 'activityDateTime');
        list($defaults['repetition_start_date'], $defaults['repetition_start_date_time']) = CRM_Utils_Date::setDateDefaults($defaults['activity_date_time'], 'activityDateTime');
      }

      // Fixme: why are we getting the wrong keys from upstream?
      $defaults['target_contact_id'] = CRM_Utils_Array::value('target_contact', $defaults);
      $defaults['assignee_contact_id'] = CRM_Utils_Array::value('assignee_contact', $defaults);

      // set default tags if exists
      $defaults['tag'] = CRM_Core_BAO_EntityTag::getTag($this->_activityId, 'civicrm_activity');
    }
    else {
      // if it's a new activity, we need to set default values for associated contact fields
      $this->_sourceContactId = $this->_currentUserId;
      $this->_targetContactId = $this->_currentlyViewedContactId;

      $defaults['source_contact_id'] = $this->_sourceContactId;
      $defaults['target_contact_id'] = $this->_targetContactId;

      list($defaults['activity_date_time'], $defaults['activity_date_time_time'])
        = CRM_Utils_Date::setDateDefaults(NULL, 'activityDateTime');
    }

    if ($this->_activityTypeId) {
      $defaults['activity_type_id'] = $this->_activityTypeId;
    }

    if (!empty($this->_contactIds)) {
      $defaults['target_contact_id'] = $this->_contactIds;
    }

    // TODO: With Contacts defaults with add/remove entityRef
    // CRM-15472 - 50 is around the practial limit of how many items a select2 entityRef can handle
    /*if (!empty($defaults['target_contact_id'])) {
      $count = count(is_array($defaults['target_contact_id']) ? $defaults['target_contact_id'] : explode(',', $defaults['target_contact_id']));
      if ($count > 50) {
        $this->freeze(array('target_contact_id'));
      }
    }*/

    if ($this->_action & (CRM_Core_Action::DELETE | CRM_Core_Action::RENEW)) {
      $this->assign('delName', CRM_Utils_Array::value('subject', $defaults));
    }

    if (empty($defaults['priority_id'])) {
      $priority = CRM_Core_PseudoConstant::get('CRM_Activity_DAO_Activity', 'priority_id');
      $defaults['priority_id'] = array_search('Normal', $priority);
    }
    if (empty($defaults['status_id'])) {
      $defaults['status_id'] = CRM_Core_OptionGroup::getDefaultValue('activity_status');
    }
    return $defaults;
  }

  /**
   * Set the title for the View Activity Form
   * @param null $title
   */
  public function setActivityTitle($title = null) {
    // Set title
    if (!empty($title)) {
      // If we've been given a title use it
      CRM_Utils_System::setTitle($title);
    }
    else {
      // Otherwise generate a title based on activity details
      if ($this->_currentlyViewedContactId) {
        $displayName = CRM_Contact_BAO_Contact::displayName($this->_currentlyViewedContactId);
        // Check if this is default domain contact CRM-10482
        if (CRM_Contact_BAO_Contact::checkDomainContact($this->_currentlyViewedContactId)) {
          $displayName .= ' (' . ts('default organization') . ')';
        }
        CRM_Utils_System::setTitle($displayName . ' - ' . $this->_activityTypeName);
      } else {
        CRM_Utils_System::setTitle(ts('Activity: ' . $this->_activityTypeName));
      }
    }
  }

  /**
   * Set the header that appears at the top of the activity display.
   * @param null $header
   */
  public function setActivityHeader($header = null) {
    if (!empty($header)) {
      // Use passed in header if we are given it
      $this->assign('activityHeader', $header);
    }
    else {
      // Otherwise generate a header based on activity details
      $header = $this->_activityTypeName;
      if (isset($this->_activitySubject)) {
        $header .= ': ' . $this->_activitySubject;
      }
      $this->assign('activityHeader', $header);
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
}
