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

use CRM_Eventmessages_ExtensionUtil as E;

/**
 * Filter activity detail field contents
 */
class CRM_Fastactivity_Form_Task_DetailsFilter extends CRM_Activity_Form_Task {

  const BATCH_SIZE = 25;
  
  function buildQuickForm() {
    CRM_Utils_System::setTitle(E::ts('Filter activity details'));
    $this->addDefaultButtons(E::ts('Filter activity details'));
  }

  function postProcess() {
    // Initialize a queue.
    $queue = CRM_Queue_Service::singleton()->create(
      [
        'type' => 'Sql',
        'name' => 'fastactivity_task_filter_activity_details_' . CRM_Core_Session::singleton(
          )->getLoggedInContactID(),
        'reset' => TRUE,
      ]
    );

    // Add items to the queue.
    foreach (array_chunk($this->_activityHolderIds, self::BATCH_SIZE) as $activity_ids) {
      $queue->createItem(
        new CRM_Fastactivity_DetailsFilterJob($activity_ids)
      );
    }

    // Start a runner on the queue.
    $runner = new CRM_Queue_Runner(
      [
        'title' => E::ts('Filtering activity details'),
        'queue' => $queue,
        'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
        'onEndUrl' => CRM_Core_Session::singleton()->readUserContext(),
      ]
    );
    $runner->runAllViaWeb();
  }

}
