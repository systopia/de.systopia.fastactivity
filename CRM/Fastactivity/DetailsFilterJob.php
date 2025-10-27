<?php
/*-------------------------------------------------------+
| SYSTOPIA FastActivity                                  |
| Copyright (C) 2021 SYSTOPIA                            |
| Author: J. Schuppe (schuppe@systopia.de)               |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

use CRM_Fastactivity_ExtensionUtil as E;

class CRM_Fastactivity_DetailsFilterJob {

  /**
   * @var int[] $activityIds
   */
  protected $activityIds;

  /**
   * @var string $title
   *   The Job title.
   */
  public $title;

  /**
   * CRM_Fastactivity_DetailsFilterJob constructor.
   *
   * @param $activity_ids
   */
  public function __construct($activity_ids) {
    $this->activityIds = $activity_ids;
    $this->title = E::ts(
      'Filtering details for %1 activities',
      [1 => count($activity_ids)]
    );
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function run() {
    foreach ($this->activityIds as $activityId) {
      $activity = civicrm_api3(
        'Activity',
        'getsingle',
        ['id' => $activityId],
        ['return' => 'details']
      );
      civicrm_api3(
        'Activity',
        'create',
        [
          'id' => $activityId,
          'details' => CRM_Utils_String::htmlToText($activity['details']),
        ]
      );
    }

    return TRUE;
  }

}
