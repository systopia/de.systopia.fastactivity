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
 * Main page for viewing activities
 *
 * @see based on CRM_Activity_Page_Tab (CiviCRM LLC)
 */
class CRM_Fastactivity_Page_Tab extends CRM_Core_Page {

  /**
   * Browse all activities for a particular contact.
   *
   */
  public function browse() {
    $this->assign('admin', FALSE);
    $this->assign('context', 'activity');

    // Load settings
    $params['optionalCols']['campaign_title'] = (bool) CRM_Fastactivity_Settings::getValue('tab_col_campaign_title');
    $params['optionalCols']['duration'] = (bool) CRM_Fastactivity_Settings::getValue('tab_col_duration');
    $params['optionalCols']['case'] = (bool) CRM_Fastactivity_Settings::getValue('tab_col_case');
    $params['optionalCols']['target_contact'] = (bool) CRM_Fastactivity_Settings::getValue('tab_col_target_contact');
    $this->assign('optionalCols', $params['optionalCols']);

    // Create controller for the activity filter
    // This is a multi-select dropdown which allows to filter on multiple activity types
    $controller = new CRM_Core_Controller_Simple(
      'CRM_Fastactivity_Form_ActivityFilter',
      ts('Activity Filter'),
      NULL,
      FALSE, FALSE, TRUE
    );
    $controller->set('contactId', $this->_contactId);
    $controller->setEmbedded(TRUE);
    $controller->run();
  }

  /**
   * Heart of the viewing process. The runner gets all the meta data for
   * the contact and calls the appropriate type of page to view.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $this->assign('contactId', $this->_contactId);

    // check logged in url permission
    CRM_Contact_Page_View::checkUserPermission($this);

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->assign('action', $this->_action);

    // also create the form element for the activity links box
    // This provides links in the "new activity" dropdown.
    // FIXME: This uses a civicrm core function at the moment. We may want to implement our own in fastactivity?
    $controller = new CRM_Core_Controller_Simple(
      'CRM_Activity_Form_ActivityLinks',
      ts('Activity Links'),
      NULL,
      FALSE, FALSE, TRUE
    );
    $controller->setEmbedded(TRUE);
    $controller->run();
  }

  /**
   * Run the activities "tab" page
   *
   * @throws \CRM_Core_Exception
   */
  public function run() {
    $action = CRM_Utils_Request::retrieve('action', 'String', $this);
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    //do check for view operation.
    if ($this->_id &&
      in_array($action, array(CRM_Core_Action::VIEW))
    ) {
      if (!CRM_Fastactivity_BAO_Activity::checkPermission($this->_id, $action)) {
        CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm', 'reset=1'));
        CRM_Core_Error::statusBounce(ts('You do not have the necessary permission to access this page.'));
      }
    }

    // we should call contact view, preprocess only for activity in contact summary
    $this->preProcess();
    $this->browse();

    parent::run();
  }
}
