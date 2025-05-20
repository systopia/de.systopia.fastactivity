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
class CRM_Fastactivity_Form_Add extends CRM_Fastactivity_Form_Base {
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
  protected $_activityTargetCount;
  public $_groupTree;
  protected $_values;

  const MAX_TARGETCONTACTS = 20;

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
    // Remove print document activity type
    $unwanted = CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE, "AND v.name = 'Print PDF Letter'");
    $activityTypes = array_diff_key(CRM_Core_PseudoConstant::ActivityType(FALSE), $unwanted);

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
      'activity_type_id' => array(
        'type' => 'select',
        'label' => ts('Activity Type'),
        'required' => TRUE,
        'onchange' => "CRM.buildCustomData( 'Activity', this.value );",
        'attributes' => array('' => '- ' . ts('select activity') . ' -') + $activityTypes,
        'extra' => array('class' => 'crm-select2'),
      ),
      'priority_id' => array(
        'type' => 'select',
        'required' => TRUE,
      ),
      'medium_id' => array(
        'type' => 'select',
        'required' => FALSE,
      ),
      'source_contact_id' => array(
        'type' => 'entityRef',
        'label' => ts('Added By'),
        'required' => FALSE,
      ),
      'target_contact_count' => array(
        'type' => 'hidden',
        'label' => ts('target_contact_count'),
        'required' => FALSE,
      ),
      'target_contact_id' => array(
        'type' => 'entityRef',
        'label' => ts('With Contacts'),
        'attributes' => array('multiple' => TRUE, 'create' => TRUE),
      ),
      'target_contact_add_id' => array(
        'type' => 'entityRef',
        'label' => ts('Add to With Contacts'),
        'attributes' => array('multiple' => TRUE, 'create' => TRUE),
      ),
      'target_contact_remove_id' => array(
        'type' => 'entityRef',
        'label' => ts('Remove from With Contacts'),
        'attributes' => array('multiple' => TRUE, 'create' => FALSE),
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
        'attributes' => array('' => '- ' . ts('select activity') . ' -') + $activityTypes,
        'extra' => array('class' => 'crm-select2'),
      ),
      // Add optional 'Subject' field for the Follow-up Activity, CRM-4491
      'followup_activity_subject' => array(
        'type' => 'text',
        'label' => ts('Subject'),
        'attributes' => CRM_Core_DAO::getAttribute('CRM_Activity_DAO_Activity',
          'subject'
        ),
      ),
    );
  }

  public function preProcess() {
    // AJAX query for custom data is called to civicrm/fastactivity/add
    // This handles that query and returns the edit form block for customData
    $this->_cdType = CRM_Utils_Array::value('type', $_GET);
    $this->assign('cdType', FALSE);
    if ($this->_cdType) {
      $this->assign('cdType', TRUE);
      return CRM_Custom_Form_CustomData::preProcess($this);
    }

    CRM_Core_Form_RecurringEntity::preProcess('civicrm_activity');

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
    if (empty($this->_currentlyViewedContactId)) {
      CRM_Core_Error::statusBounce(ts('You must specify a contact ID'));
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

    // Assign values for use by buildCustomData functions
    $this->assign('customDataType', 'Activity');
    $this->assign('customDataSubType', $this->_activityTypeId);

    $this->setActivityHeader();
    $this->setActivityTitle();

    if (!empty($this->_submitValues)) {
      $this->_values = $this->_submitValues;
      if (isset($this->_submitValues['target_contact_count']) && $this->_submitValues['target_contact_count'] > $this::MAX_TARGETCONTACTS) {
        $this->_activityTargetCount = $this->_submitValues['target_contact_count'];
        $this->assign('activityTargetCount', $this->_activityTargetCount);
      }
    }
    elseif (!empty($this->_values)) {
      // Do nothing
    }
    else {
      $this->_values = array();
      if (isset($this->_activityId) && $this->_activityId) {
        $params = array('id' => $this->_activityId);
        try {
          $activityRecord = civicrm_api3('Activity', 'getsingle', array(
            'sequential' => 1,
            //'return' => "subject, duration, location, details, status_id, activity_type_id, priority_id, source_contact_id, campaign_id, engagement_level",
            'id' => $this->_activityId,
          ));
          $this->_values = $activityRecord;

          $assigneeContacts = civicrm_api3('ActivityContact', 'get', array(
            'sequential' => 1,
            'activity_id' => $this->_activityId,
            'record_type_id' => "Activity Assignees",
          ));
          if (!empty($assigneeContacts['count'])) {
            foreach ($assigneeContacts['values'] as $contact) {
              $this->_values['assignee_contact_id'][] = $contact['contact_id'];
            }
          }

          $targetContactCount = civicrm_api3('ActivityContact', 'getcount', array(
            'sequential' => 1,
            'activity_id' => $this->_activityId,
            'record_type_id' => "Activity Targets",
          ));
          if (!empty($targetContactCount)) {
            $this->_activityTargetCount = $targetContactCount;
            // Don't show contacts, just a count
            if ($targetContactCount > $this::MAX_TARGETCONTACTS) {
              // Only assign count if > MAX_TARGETCONTACTS so we can use this in smarty template to decide what to display
              $this->assign('activityTargetCount', $this->_activityTargetCount);
            }
            if ($targetContactCount <= $this::MAX_TARGETCONTACTS) {
              // Retrieve all the target contacts
              $targetContacts = civicrm_api3('ActivityContact', 'get', array(
                'sequential' => 1,
                'activity_id' => $this->_activityId,
                'record_type_id' => "Activity Targets",
              ));
              if (!empty($targetContacts['count'])) {
                foreach ($targetContacts['values'] as $contact) {
                  $this->_values['target_contact_id'][] = $contact['contact_id'];
                }
              }
            }
          }
        }
        catch (Exception $e) {
          // Get activity will fail if not found
          $errorMsg = $e->getMessage();
          CRM_Core_Error::statusBounce($errorMsg . ' (id='.$this->_activityId.')', ts('Activity not found'), 'error');
          return;
        }
      }
      //$this->set('values', $this->_values);
    }

    $this->setFields();

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

    // Get custom fields
    $this->_groupTree = CRM_Core_BAO_CustomGroup::getTree('Activity', $this,
      $this->_activityId, 0, $this->_activityTypeId);

    $session = CRM_Core_Session::singleton();
    $this->context = CRM_Utils_System::url('civicrm/contact/view', "reset=1&selectedChild=fastactivity&cid={$this->_currentlyViewedContactId}");
    $session->pushUserContext($this->context);
    $this->controller->_destination = $this->context;
  }

  public function buildQuickForm() {
    $civiVersion = CRM_Core_BAO_Domain::version();

    if ($this->_cdType) {
      // AJAX query for custom data is called to civicrm/fastactivity/add
      // This handles that query and returns the edit form block for customData
      return CRM_Custom_Form_CustomData::buildQuickForm($this);
    }

    // Get action links to display at bottom of activity (not for new activity as nothing to view/edit/delete!).
    if (!in_array($this->_action, array(CRM_Core_Action::ADD))) {
      $actionLinks = CRM_Fastactivity_BAO_Activity::actionLinks($this->_activityTypeId, $this->_activityId);
      if (isset($actionLinks[$this->_action])) {
        // Don't show our own action
        unset ($actionLinks[$this->_action]);
      }
      $this->assign('actionLinks', $actionLinks);
    }

    // Add fields defined by setFields
    foreach ($this->_fields as $field => $values) {
      if (!empty($this->_fields[$field])) {
        $attribute = CRM_Utils_Array::value('attributes', $values);
        $required = !empty($values['required']);
        if ($values['type'] == 'wysiwyg') {
          if (version_compare($civiVersion, '4.7', '<')) {
            $this->addWysiwyg($field, $values['label'], $attribute, $required);
          }
          else {
            $this->add('wysiwyg', $field, $values['label'], $attribute, $required);
          }
        }
        elseif ($values['type'] == 'select') {
          if ($field == 'activity_type_id' && ($this->_action == CRM_Core_Action::UPDATE)) {
            // Don't allow changing activity type in edit mode
            $this->add('hidden', $field, $values['label']);
          }
          elseif ($field == 'followup_activity_type_id') {
            $this->addElement('select', $field, $values['label'], $attribute);
          }
          else {
            if (version_compare($civiVersion, '4.7', '<')) {
              $attribute['entity'] = 'Activity';
            }
            $this->addSelect($field, $attribute, $required, CRM_Utils_Array::value('extra', $values));
          }
        }
        elseif ($values['type'] == 'entityRef') {
          $this->addEntityRef($field, $values['label'], $attribute, $required);
        }
        else {
          $this->add($values['type'], $field, $values['label'], $attribute, $required, CRM_Utils_Array::value('extra', $values));
        }
      }
    }

    $config = CRM_Core_Config::singleton();
    $campaignEnabled = in_array("CiviCampaign", $config->enableComponents);
    if ($campaignEnabled) {
      // Add campaign elements
      self::buildFormElementsCampaign();
    }
    $this->assign('campaignEnabled', $campaignEnabled);

    // this option should be available only during add mode
    if ($this->_action == CRM_Core_Action::ADD) {
      $this->add('advcheckbox', 'is_multi_activity', ts('Create a separate activity for each contact.'));
    }

    // Add activity Date Time
    $this->add('datepicker','activity_date_time', ts('Date'), TRUE, array('formatType' => 'activityDateTime'));

    //add followup date
    $this->add('datepicker', 'followup_date', ts('in'), FALSE, array('formatType' => 'activityDateTime'));

    // Only admins can change the activity source contact
    if (!CRM_Core_Permission::check('administer CiviCRM')) {
      $this->getElement('source_contact_id')->freeze();
    }

    // Add form elements for repeating options
    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD)) {
      CRM_Core_Form_RecurringEntity::buildQuickForm($this);
    }

    // Assign custom data type and subtype to the template to allow custom data to be shown
    $this->assign('customDataType', 'Activity');
    $this->assign('customDataSubType', $this->_activityTypeId);
    // Assign entityID for use by the ConfirmRepeatMode.tpl that displays the options for repeat.
    $this->assign('entityID', $this->_activityId);

    // TODO: Look at tags stuff - do we need it?
    CRM_Core_BAO_Tag::getTags('civicrm_activity', $tags, NULL,
      '&nbsp;&nbsp;', TRUE);

    if (!empty($tags)) {
      $this->add('select', 'tag', ts('Tags'), $tags, FALSE,
        array('id' => 'tags', 'multiple' => 'multiple', 'class' => 'crm-select2 huge')
      );
    }

    // TODO: Do we want this or delete it?
    // we need to hide activity tagset for special activities
    $specialActivities = array('Open Case');

    if (!in_array($this->_activityTypeName, $specialActivities)) {
      // build tag widget
      $parentNames = CRM_Core_BAO_Tag::getTagSet('civicrm_activity');
      CRM_Core_Form_Tag::buildQuickForm($this, $parentNames, 'civicrm_activity', $this->_activityId);
    }

    // Add buttons to form
    $this->addButtons(array(
        array(
          'type' => 'upload',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );

    // Add form validation rules
    $this->addFormRule(array('CRM_Fastactivity_Form_Add', 'formRule'), $this);
    // Validate duration
    $this->addRule('duration',
      ts('Please enter the duration as number of minutes (integers only).'), 'positiveInteger'
    );

    // Get notification preferences
    if (Civi::settings()->get('activity_assignee_notification')) {
      $this->assign('activityAssigneeNotification', TRUE);
    }
    else {
      $this->assign('activityAssigneeNotification', FALSE);
    }
  }

  /**
   * Add campaign elements to form
   */
  public function buildFormElementsCampaign() {
    // Add select element for campaign
    CRM_Campaign_BAO_Campaign::addCampaign($this, CRM_Utils_Array::value('campaign_id', $this->_values));

    // Add element for engagement level
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

  /**
   * Global form rule.
   * Copied straight from CRM_Activity_Form_Activity
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param $self
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $self) {
    // TODO: Do we want to remove any of these rules?
    // skip form rule if deleting
    if (CRM_Utils_Array::value('_qf_Activity_next_', $fields) == 'Delete') {
      return TRUE;
    }
    $errors = array();
    if (CRM_Utils_Array::value('activity_type_id', $fields) == 3 &&
      CRM_Utils_Array::value('status_id', $fields) == 1
    ) {
      $errors['status_id'] = ts('You cannot record scheduled email activity.');
    }
    elseif (CRM_Utils_Array::value('activity_type_id', $fields) == 4 &&
      CRM_Utils_Array::value('status_id', $fields) == 1
    ) {
      $errors['status_id'] = ts('You cannot record scheduled SMS activity.');
    }

    if (!empty($fields['followup_activity_type_id']) && empty($fields['followup_date'])) {
      $errors['followup_date_time'] = ts('Followup date is a required field.');
    }
    //Activity type is mandatory if subject or follow-up date is specified for an Follow-up activity, CRM-4515
    if ((!empty($fields['followup_activity_subject']) || !empty($fields['followup_date'])) && empty($fields['followup_activity_type_id'])) {
      $errors['followup_activity_subject'] = ts('Follow-up Activity type is a required field.');
    }
    return $errors;
  }

  public function postProcess($params = NULL) {
    // store the submitted values in an array
    if (!$params) {
      $params = $this->_submitValues;
    }

    //set activity type id
    if (empty($params['activity_type_id'])) {
      $params['activity_type_id'] = $this->_activityTypeId;
    }

    // Get custom data
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

    // Get activity Id
    if (isset($this->_activityId)) {
      $params['id'] = $this->_activityId;
    }

    // add attachments as needed
    CRM_Core_BAO_File::formatAttachment($params,
      $params,
      'civicrm_activity',
      $this->_activityId
    );

    // Handle multi activities
    // TODO: Do we try and handle multi-activities in this extension?
    // TODO: Probably yes, but only when creating a new activity?
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

    // Don't pass in target_contact_id array if > MAX_TARGETCONTACTS as it will be empty and we don't want to clear association
    if (!empty($params['target_contact_count']) && $params['target_contact_count'] > $this::MAX_TARGETCONTACTS) {
      unset($params['target_contact_id']);
    }

    // BAO::create requires that target_contact_id is passed in. API does not so we must use API here or we lose target contacts
    //   when count > MAX_TARGETCONTACTS
    $activity = civicrm_api3('Activity', 'create', $params);

    // add tags if exists
    $tagParams = array();
    if (!empty($params['tag'])) {
      foreach ($params['tag'] as $tag) {
        $tagParams[$tag] = 1;
      }
    }

    // target_contact_add_id may contain a list of contacts to add, target_contact_remove_id may contain a list of contacts to remove
    // Process target_contact_add_id
    if (!empty($params['target_contact_add_id'])) {
      $targetsAdded = CRM_Fastactivity_BAO_Activity::addTargetContacts($activity['id'], explode(',', $params['target_contact_add_id']));
      if (count($targetsAdded) > 0) {
        CRM_Core_Session::setStatus('Added contact Ids: '. implode(',', $targetsAdded) . ' to "With Contact" for activity ' . $activity['id']);
      }
    }

    // Process target_contact_remove_id
    if (!empty($params['target_contact_remove_id'])) {
      $targetsRemoved = CRM_Fastactivity_BAO_Activity::removeTargetContacts($activity['id'], explode(',', $params['target_contact_remove_id']));
      if (count($targetsRemoved) > 0) {
        CRM_Core_Session::setStatus('Removed contact Ids: '. implode(',', $targetsRemoved) . ' from "With Contact" for activity ' . $activity['id']);
      }
    }

    //save static tags
    CRM_Core_BAO_EntityTag::create($tagParams, 'civicrm_activity', $activity['id']);

    //save free tags
    if (isset($params['activity_taglist']) && !empty($params['activity_taglist'])) {
      CRM_Core_Form_Tag::postProcess($params['activity_taglist'], $activity['id'], 'civicrm_activity', $this);
    }

    // CRM-9590
    if (!empty($params['is_multi_activity'])) {
      $this->_activityIds[] = $activity['id'];
    }
    else {
      $this->_activityId = $activity['id'];
    }

    // create follow up activity if needed
    $followupStatus = '';
    $followupActivity = NULL;
    if (!empty($params['followup_activity_type_id'])) {
      $followupActivity = CRM_Activity_BAO_Activity::createFollowupActivity($activity['id'], $params);
      $followupStatus = ts('A followup activity has been scheduled.');
    }

    // send copy to assignee contacts.CRM-4509
    $mailStatus = '';

    if (Civi::settings()->get('activity_assignee_notification')) {
      $activityIDs = array($activity['id']);
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
          $attachments = CRM_Core_BAO_File::getEntityFile('civicrm_activity', $activity['id']);

          // FIXME: Check if this works now $activity is not an object
          $ics = new CRM_Activity_BAO_ICalendar($activity['values']);
          $ics->addAttachment($attachments, $mailToContacts);

          // CRM-8400 add param with _currentlyViewedContactId for URL link in mail
          CRM_Case_BAO_Case::sendActivityCopy(NULL, $activity['id'], $mailToContacts, $attachments, NULL);

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

    // Set subject for confirmation message
    $subject = '';
    if (!empty($params['subject'])) {
      $subject = "'" . $params['subject'] . "'";
    }

    // Set activity type name for confirmation message
    $typeName = '';
    if (isset($params['activity_type_id'])) {
      list($typeName, $activityTypeDescription) = CRM_Core_BAO_OptionValue::getActivityTypeDetails($params['activity_type_id']);
    }
    // Set activity state for confirmation message
    $state = '';
    if ($this->_action == CRM_Core_Action::ADD) {
      $state = 'created';
    }
    elseif ($this->_action == CRM_Core_Action::UPDATE) {
      $state = 'updated';
    }
    CRM_Core_Session::setStatus(ts('%1 Activity %2 has been %3. %4 %5',
      array(
        1 => $typeName,
        2 => $subject,
        3 => $state,
        4 => $followupStatus,
        5 => $mailStatus,
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
      return CRM_Custom_Form_CustomData::setDefaultValues($this);
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

      $defaults['target_contact_count'] = $this->_activityTargetCount;

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

    if (empty($defaults['priority_id'])) {
      $priority = CRM_Core_PseudoConstant::get('CRM_Activity_DAO_Activity', 'priority_id');
      $defaults['priority_id'] = array_search('Normal', $priority);
    }
    if (empty($defaults['status_id'])) {
      $defaults['status_id'] = CRM_Core_OptionGroup::getDefaultValue('activity_status');
    }
    return $defaults;
  }
}
