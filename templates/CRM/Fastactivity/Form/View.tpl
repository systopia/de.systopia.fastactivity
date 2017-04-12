{* HEADER *}

<div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="top"}
</div>

<table class="crm-info-panel">
  <h3>{$activityHeader}</h3>
    {if $activityTypeDescription }
      <div class="help">Description: {$activityTypeDescription}</div>
    {/if}
    {if $surveyActivity}
      <tr class="crm-activity-form-block-survey">
        <td class="label">{ts}Survey Title{/ts}</td><td class="view-value">{$surveyTitle}</td>
      </tr>
    {/if}
  <tr class="crm-activity-form-block-source_contact_id">
    <td class="label">
      <i class="fa fa-user" aria-hidden="true"></i>
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
      <i class="fa fa-user" aria-hidden="true"></i>
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
      <i class="fa fa-users" aria-hidden="true"></i>
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
      <i class="fa fa-calendar" aria-hidden="true"></i>
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
          <i class="fa fa-info" aria-hidden="true"></i>
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
{* FIELD EXAMPLE: OPTION 2 (MANUAL LAYOUT)

  <div>
    <span>{$form.favorite_color.label}</span>
    <span>{$form.favorite_color.html}</span>
  </div>

{* FOOTER *}
<div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
