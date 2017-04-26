{*-------------------------------------------------------+
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
+-------------------------------------------------------*}


{* Include links to enter Activities if session has 'edit' permission *}
{if $action EQ 16 and $permission EQ 'edit' and !$addAssigneeContact and !$addTargetContact}
    <div class="action-link crm-activityLinks" style="text-align: left">{include file="CRM/Activity/Form/ActivityLinks.tpl" as_select=true}</div>
{/if}

{include file="CRM/Fastactivity/Selector/Selector.tpl"}
