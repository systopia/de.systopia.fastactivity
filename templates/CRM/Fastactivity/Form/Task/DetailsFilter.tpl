{*-------------------------------------------------------+
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
+-------------------------------------------------------*}

{crmScope extensionKey='de.systopia.fastactivity'}
  <div class="help">
    <p>{ts}This task will filter the <em>Details</em>
        field contents of selected activities, removing all HTML tags,
        resulting an a plain text version of the field. This action cannot be
        undone.{/ts}</p>
  </div>

  <div class="crm-form-block">
    <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
  </div>
{/crmScope}
