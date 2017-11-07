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
 * Class CRM_Fastactivity_Utils
 *
 * Utilities used by other parts of Fastactivity extension
 */
abstract class CRM_Fastactivity_Form_Base extends CRM_Core_Form {
  /**
   * Set the title for the View Activity Form
   * @param null $title
   */
  public function setActivityTitle($title = null) {
    // Set title
    if (empty($title)) {
      // If we were passed a title we'll use it, otherwise create below:
      if (empty($this->_activityId)) {
        // No activity Id = new activity
        $title = 'New Activity';
      }
      elseif ($this->_currentlyViewedContactId) {
        // Otherwise generate a title based on activity details
        $displayName = CRM_Contact_BAO_Contact::displayName($this->_currentlyViewedContactId);
        // Check if this is default domain contact CRM-10482
        if (CRM_Contact_BAO_Contact::checkDomainContact($this->_currentlyViewedContactId)) {
          $displayName .= ' (' . ts('default organization') . ')';
        }
        $title = $displayName . ' - ' . $this->_activityTypeName;
      }
      else {
        $title = ts('Activity: ' . $this->_activityTypeName);
      }
    }
    CRM_Utils_System::setTitle($title);
  }

  /**
   * Set the header that appears at the top of the activity display.
   * @param $form
   * @param null $header
   */
  public function setActivityHeader($header = null) {
    // Use passed in header if we are given it
    if (empty($header)) {
      if (empty($this->_activityId)) {
        // We still want the header to display
        $header = '&nbsp;';
      }
      else {
        // Otherwise generate a header based on activity details
        $header = $this->_activityTypeName;
        if (isset($this->_activitySubject)) {
          $header .= ': ' . $this->_activitySubject;
        }
      }
    }
    $this->assign('activityHeader', $header);
  }

  /**
   * Explicitly declare the entity api name.
   *
   * @return string
   */
  public function getDefaultEntity() {
    return 'Activity';
  }

}
