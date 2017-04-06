<?php
/**
 * This class generates form components for Activity Filter
 *
 */
class CRM_Fastactivity_Form_ActivityFilter extends CRM_Core_Form {
  public function buildQuickForm() {
    // add activity search filter
    $this->addSelect('activity_type_id',
      array('entity' => 'activity', 'label' => 'Activity Type(s)', 'multiple' => 'multiple', 'option_url' => NULL, 'placeholder' => ts('- any -'))
    );
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
