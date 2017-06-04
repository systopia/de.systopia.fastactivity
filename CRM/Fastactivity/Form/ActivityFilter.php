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
 * This class generates form components for Activity Filter
 * The activity filter is displayed on the "FastActivities" Tab and allows for selection/removal of multiple
 * activity types.
 *
 * @see based on CRM_Activity_Form_ActivityFilter (CiviCRM LLC)
 */
class CRM_Fastactivity_Form_ActivityFilter extends CRM_Core_Form {
  public function buildQuickForm() {
    // add activity search filter
    $this->addSelect('activity_type_id',
      array('entity' => 'activity', 'label' => 'Activity Type(s)', 'multiple' => 'multiple', 'option_url' => NULL, 'placeholder' => ts('- any -'))
    );
    CRM_Campaign_BAO_Campaign::addCampaignInComponentSearch($this, 'activity_campaign_id');
  }

  /**
   * @return array
   */
  public function setDefaultValues() {
    // CRM-11761 retrieve user's activity filter preferences
    $defaults = array();
    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');
    if ($userID) {
      $defaults = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::PERSONAL_PREFERENCES_NAME,
        'activity_tab_filter',
        NULL,
        NULL,
        $userID
      );
    }
    return $defaults;
  }
}
