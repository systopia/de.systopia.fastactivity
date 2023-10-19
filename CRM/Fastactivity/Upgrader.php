<?php
/*-------------------------------------------------------+
| SYSTOPIA - Performance Boost for Activities            |
| Copyright (C) 2019 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de                  |
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

use CRM_Fastactivity_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Fastactivity_Upgrader extends CRM_Extension_Upgrader_Base {

  /**
   * Update the tab status
   */
  public function postInstall() {
    // adjust the tab status
    CRM_Fastactivity_Page_Tab::updateTabStatus();
  }

}
