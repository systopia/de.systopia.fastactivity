{* HEADER *}

<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="top"}
</div>

{if $action eq 8} {* delete activity *}
  <table class="crm-info-panel">
    <h3><i class="crm-i fa-question-circle" aria-hidden="true"></i>
      {$activityHeader}
    </h3>
    <tr class="crm-activity-form-block-activity_type">
      <td class="label">Type</td>
      <td class="view-value">{$activityTypeName}</td>
    </tr>
    {if $activitySubject}
      <tr class="crm-activity-form-block-activity_subject">
        <td class="label">Subject</td>
        <td class="view-value">{$activitySubject}</td>
      </tr>
    {/if}
    <tr class="crm-activity-form-block-activity_date_time">
      <td class="label">
        <i class="crm-i fa-calendar" aria-hidden="true"></i>
        Date
      </td>
      <td class="view-value">{$activityDateTime|crmDate}</td>
    </tr>
    <tr class="crm-activity-form-block-activity_status">
      <td class="label">Status</td>
      <td class="view-value">{$activityStatus}</td>
    </tr>
  </table>
{else}
  <table class="crm-info-panel">
    <h3>{$activityHeader}</h3>
    {if $activityTypeDescription }
      <div class="help">Description: {$activityTypeDescription}</div>
    {/if}
    <tr class="crm-activity-form-block-source_contact_id">
      <td class="label">
        <i class="crm-i fa-user" aria-hidden="true"></i>
        Added By
      </td>
      <td class="view-value">
        {counter start=1 assign=count}
        {foreach from=$activitySourceContacts item=contact}
          {if $contact.id}
            {assign var=contactId value=$contact.id}
            {capture assign=contactViewURL}{crmURL p='civicrm/contact/view' q="reset=1&cid=$contactId"}{/capture}
            <a href="{$contactViewURL}">{$contact.name}</a>{if $count lt $activitySourceContacts.count},{/if}
          {/if}
          {counter}
        {/foreach}
      </td>
    </tr>
    <tr class="crm-activity-form-block-assignee_contact_id">
      <td class="label">
        <i class="crm-i fa-user" aria-hidden="true"></i>
        Assigned To
      </td>
      <td class="view-value">
        {counter start=1 assign=count}
        {foreach from=$activityAssigneeContacts item=contact}
          {if $contact.id}
            {assign var=contactId value=$contact.id}
            {capture assign=contactViewURL}{crmURL p='civicrm/contact/view' q="reset=1&cid=$contactId"}{/capture}
            <a href="{$contactViewURL}">{$contact.name}</a>{if $count lt $activityAssigneeContacts.count},{/if}
          {/if}
          {counter}
        {/foreach}
      </td>
    </tr>
    <tr class="crm-activity-form-block-target_contact_id">
      <td class="label">
        <i class="crm-i fa-users" aria-hidden="true"></i>
        With
      </td>
      <td class="view-value">
        {if $activityTargetContacts|@count lt $activityTargetContacts.count}
          {$activityTargetContacts.count} contacts
        {else}
          {counter start=1 assign=count}
          {foreach from=$activityTargetContacts item=contact}
            {if $contact.id}
              {assign var=contactId value=$contact.id}
              {capture assign=contactViewURL}{crmURL p='civicrm/contact/view' q="reset=1&cid=$contactId"}{/capture}
              <a href="{$contactViewURL}">{$contact.name}</a>{if $count lt $activityTargetContacts.count},{/if}
            {/if}
            {counter}
          {/foreach}
        {/if}
      </td>
    </tr>
    <tr class="crm-activity-form-block-activity_date_time">
      <td class="label">
        <i class="crm-i fa-calendar" aria-hidden="true"></i>
        Date
      </td>
      <td class="view-value">{$activityDateTime|crmDate}</td>
    </tr>
    <tr class="crm-activity-form-block-activity_status">
      <td class="label">Status</td>
      <td class="view-value">{$activityStatus}</td>
    </tr>
    {if $activityPriority}
      <tr class="crm-activity-form-block-activity_priority">
        <td class="label">Priority</td>
        <td class="view-value">{$activityPriority}</td>
      </tr>
    {/if}
    {if $activityMedium}
      <tr class="crm-activity-form-block-activity_medium">
        <td class="label">Medium</td>
        <td class="view-value">{$mediumId}</td>
      </tr>
    {/if}
    {if $activitySubject}
      <tr class="crm-activity-form-block-activity_subject">
        <td class="label">Subject</td>
        <td class="view-value">{$activitySubject}</td>
      </tr>
    {/if}
    {if $activityDetails}
      <tr class="crm-activity-form-block-activity_details">
        <td class="label">
          <i class="crm-i fa-info" aria-hidden="true"></i>
          Details
        </td>
        <td class="view-value">{$activityDetails}</td>
      </tr>
    {/if}
    {foreach from=$viewCustomData item=customGroup}
      <tr class="crm-activity-form-block-custom_data">
        <td colspan="2">
          {foreach from=$customGroup item=customFields}
            <div class="crm-accordion-wrapper collapsed">
              <div class="crm-accordion-header">{$customFields.title}</div>
              <div class="crm-accordion-body">
                <table class="crm-info-panel">

                  {foreach from=$customFields.fields item=fields}
                    <tr>
                      <td class="label">{$fields.field_title}</td>
                      <td class="view-value">{$fields.field_value}</td>
                    </tr>
                  {/foreach}
                </table>
              </div>
            </div>
          {/foreach}
        </td>
      </tr>
    {/foreach}
  </table>
{/if}

{if $actionLinks}
  <div class="actionlinks">
    {foreach from=$actionLinks item=links}
      {if $links.icon}{$links.icon}{/if}
      <a href="/{$links.url}?{$links.qs}" class="action-item">{$links.name}</a>&nbsp;
    {/foreach}
  </div>
{/if}


{* FOOTER *}
<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
