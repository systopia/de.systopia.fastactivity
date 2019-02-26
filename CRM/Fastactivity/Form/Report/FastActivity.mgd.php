<?php
/*-------------------------------------------------------+
| SYSTOPIA - Performance Boost for Activities            |
| Copyright (C) 2019 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
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

return array (
  0 => 
  array (
    'name' => 'CRM_Fastactivity_Form_Report_FastActivity',
    'entity' => 'ReportTemplate',
    'params' => 
    array (
        'version'     => 3,
        'label'       => E::ts('Fast Activity Report'),
        'description' => E::ts('Specialised high performance activity report.'),
        'class_name'  => 'CRM_Fastactivity_Form_Report_FastActivity',
        'report_url'  => 'de.systopia.fastactivity/fastactivity',
        'component'   => '',
    ),
  ),
);
