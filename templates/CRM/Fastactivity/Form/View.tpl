{* HEADER *}

<div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="top"}
</div>

<table class="crm-info-panel">

    {if $activityTypeDescription }
      <div class="help">Description: {$activityTypeDescription}</div>
    {/if}
  <h3>{$activityTypeName}</h3>

    {if $surveyActivity}
      <tr class="crm-activity-form-block-survey">
        <td class="label">{ts}Survey Title{/ts}</td><td class="view-value">{$surveyTitle}</td>
      </tr>
    {/if}
  <tr class="crm-activity-form-block-source_contact_id">
    <td class="label">Added By</td>
    <td class="view-value">{$activitySourceContactName}</td>
  </tr>
  <tr class="crm-activity-form-block-assignee_contact_id">
    <td class="label">Assigned To</td>
    <td class="view-value">{$assigneeContactName}</td>
  </tr>
  <tr class="crm-activity-form-block-activity_date_time">
    <td class="label">Date</td>
    <td class="view-value">{$activityDateTime|crmDate}</td>
  </tr>
  <tr class="crm-activity-form-block-activity_status">
    <td class="label">Status</td>
    <td class="view-value">{$activityStatus}</td>
  </tr>
  <tr class="crm-activity-form-block-activity_priority">
    <td class="label">Priority</td>
    <td class="view-value">{$activityPriority}</td>
  </tr>
    {if $activitySubject}
      <tr class="crm-activity-form-block-activity_subject">
        <td class="label">Subject</td>
        <td class="view-value">{$activitySubject}</td>
      </tr>
    {/if}
    {if $activityDetails}
  <tr class="crm-activity-form-block-activity_details">
    <td class="label">Details</td>
    <td class="view-value">{$activityDetails}</td>
  </tr>
{/if}

{foreach from=$viewCustomData item=customGroup}
  <tr class="crm-activity-form-block-custom_data">
    <td colspan="2">
    {foreach from=$customGroup item=customFields}
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
