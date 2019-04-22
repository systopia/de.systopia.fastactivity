{*-------------------------------------------------------+
| SYSTOPIA - Performance Boost for Activities            |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: M. Wire (mjw@mjwconsult.co.uk)                 |
|         B. Endres (endres@systopia.de)                 |
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

{* this template is used for adding/editing other (custom) activities. *}
{if $cdType}
  {include file="CRM/Custom/Form/CustomData.tpl"}
{else}
  <h2>{$activityHeader}</h2>
  {if $activityTypeDescription}
    <div class="help">Description: {$activityTypeDescription}</div>
  {/if}
  <div class="crm-block crm-form-block crm-activity-form-block">

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>

  <table class="form-layout">
    <div>{$form.activity_type_id.html}</div>
    {if $surveyActivity}
      <tr class="crm-activity-form-block-survey">
        <td class="label">{ts}Survey Title{/ts}</td><td class="view-value">{$surveyTitle}</td>
      </tr>
    {/if}

    <tr class="crm-activity-form-block-source_contact_id">
      <td class="label">{$form.source_contact_id.label}</td>
      <td class="view-value">
        {$form.source_contact_id.html}
      </td>
    </tr>
    <tr class="crm-activity-form-block-target_contact_id">
      {if $activityTargetCount}
        <td class="label">{$form.target_contact_id.label}</td>
        <td class="view-value">
          <strong>{$activityTargetCount} {ts}contacts{/ts}</strong><br />
          {ts}Add{/ts}: {$form.target_contact_add_id.html}&nbsp;
          {ts}Remove{/ts}: {$form.target_contact_remove_id.html}
          {if $action eq 1}
            <div class="crm-is-multi-activity-wrapper">
              {$form.is_multi_activity.html}&nbsp;{$form.is_multi_activity.label}
            </div>
          {/if}
        </td>
      {else}
        <td class="label">{$form.target_contact_id.label}</td>
        <td class="view-value">
          {$form.target_contact_id.html}
          {if $action eq 1}
            <div class="crm-is-multi-activity-wrapper">
              {$form.is_multi_activity.html}&nbsp;{$form.is_multi_activity.label}
            </div>
          {/if}
        </td>
      {/if}
    </tr>
    <tr class="crm-activity-form-block-assignee_contact_id">
      <td class="label">
        {$form.assignee_contact_id.label}
      </td>
      <td>
        {$form.assignee_contact_id.html}
        {if !$activityTargetCount}
          <a href="#" class="crm-hover-button" id="swap_target_assignee" title="{ts}Swap Target and Assignee Contacts{/ts}" style="position:relative; bottom: 1em;">
            <span class="icon ui-icon-shuffle"></span>
          </a>
        {/if}
        {if $activityAssigneeNotification}
          <br />
          <span class="description"><span class="icon ui-icon-mail-closed"></span>{ts}A copy of this activity will be emailed to each Assignee.{/ts}</span>
        {/if}
      </td>
    </tr>

    {if $activityTypeFile}
      {include file="CRM/$crmDir/Form/Activity/$activityTypeFile.tpl"}
    {/if}

    <tr class="crm-activity-form-block-subject">
      <td class="label">{$form.subject.label}</td><td class="view-value">{$form.subject.html|crmAddClass:huge}</td>
    </tr>

    {if $campaignEnabled}
    {* CRM-7362 --add campaign to activities *}
    {include file="CRM/Campaign/Form/addCampaignToComponent.tpl"
    campaignTrClass="crm-activity-form-block-campaign_id"}

    {* build engagement level CRM-7775 *}
    {if $buildEngagementLevel}
      <tr class="crm-activity-form-block-engagement_level">
        <td class="label">{$form.engagement_level.label}</td>
        <td class="view-value">{$form.engagement_level.html}</td>
      </tr>
    {/if}
    {/if}

    <tr class="crm-activity-form-block-location">
      <td class="label">{$form.location.label}</td><td class="view-value">{$form.location.html|crmAddClass:huge}</td>
    </tr>
    <tr class="crm-activity-form-block-activity_date_time">
      <td class="label">{$form.activity_date_time.label}</td>
      <td class="view-value">{include file="CRM/common/jcalendar.tpl" elementName=activity_date_time}</td>
    </tr>
    <tr class="crm-activity-form-block-duration">
      <td class="label">{$form.duration.label}</td>
      <td class="view-value">
        {$form.duration.html}
      </td>
    </tr>
    <tr class="crm-activity-form-block-status_id">
      <td class="label">{$form.status_id.label}</td><td class="view-value">{$form.status_id.html}</td>
    </tr>
    <tr class="crm-activity-form-block-details">
      <td class="label">{$form.details.label}</td>
      {if $activityTypeName eq "Print PDF Letter"}
        <td class="view-value">
          {* If using plain textarea, assign class=huge to make input large enough. *}
          {if $defaultWysiwygEditor eq 0}{$form.details.html|crmAddClass:huge}{else}{$form.details.html}{/if}
        </td>
      {else}
        <td class="view-value">
          {* If using plain textarea, assign class=huge to make input large enough. *}
          {if $defaultWysiwygEditor eq 0}{$form.details.html|crmStripAlternatives|crmAddClass:huge}{else}{$form.details.html|crmStripAlternatives}{/if}
        </td>
      {/if}
    </tr>
    <tr class="crm-activity-form-block-priority_id">
      <td class="label">{$form.priority_id.label}</td><td class="view-value">{$form.priority_id.html}</td>
    </tr>
    <tr class="crm-activity-form-block-medium_id">
      <td class="label">{$form.medium_id.label}</td><td class="view-value">{$form.medium_id.html}</td>
    </tr>
    {if $surveyActivity }
      <tr class="crm-activity-form-block-result">
        <td class="label">{$form.result.label}</td><td class="view-value">{$form.result.html}</td>
      </tr>
    {/if}
    {if $form.tag.html}
      <tr class="crm-activity-form-block-tag">
        <td class="label">{$form.tag.label}</td>
        <td class="view-value">
          <div class="crm-select-container">{$form.tag.html}</div>
        </td>
      </tr>
    {/if}

    {if $tagsetInfo.activity}
      <tr class="crm-activity-form-block-tag_set">{include file="CRM/common/Tagset.tpl" tagsetType='activity' tableLayout=true}</tr>
    {/if}

    <tr class="crm-activity-form-block-custom_data">
      <td colspan="2">
        {include file="CRM/common/customDataBlock.tpl"}
      </td>
    </tr>

    <tr class="crm-activity-form-block-attachment">
      <td colspan="2">
        {include file="CRM/Form/attachment.tpl"}
      </td>
    </tr>

    <tr class="crm-activity-form-block-recurring_activity">
      <td colspan="2">
        {include file="CRM/Core/Form/RecurringEntity.tpl" recurringFormIsEmbedded=true}
      </td>
    </tr>

    <tr class="crm-activity-form-block-schedule_followup">
      <td colspan="2">
        <div class="crm-accordion-wrapper collapsed">
          <div class="crm-accordion-header">
            {ts}Schedule Follow-up{/ts}
          </div><!-- /.crm-accordion-header -->
          <div class="crm-accordion-body">
            <table class="form-layout-compressed">
              <tr><td class="label">{ts}Schedule Follow-up Activity{/ts}</td>
                <td>{$form.followup_activity_type_id.html}&nbsp;&nbsp;{ts}on{/ts}
                  {include file="CRM/common/jcalendar.tpl" elementName=followup_date}
                </td>
              </tr>
              <tr>
                <td class="label">{$form.followup_activity_subject.label}</td>
                <td>{$form.followup_activity_subject.html|crmAddClass:huge}</td>
              </tr>
              <tr>
                <td class="label">
                  {$form.followup_assignee_contact_id.label}
                  {edit}
                  {/edit}
                </td>
                <td>
                  {$form.followup_assignee_contact_id.html}
                </td>
              </tr>
            </table>
          </div><!-- /.crm-accordion-body -->
        </div><!-- /.crm-accordion-wrapper -->
        {literal}
        <script type="text/javascript">
            CRM.$(function($) {
                var $form = $('form.{/literal}{$form.formClass}{literal}');
                $('.crm-accordion-body', $form).each( function() {
                    //open tab if form rule throws error
                    if ( $(this).children( ).find('span.crm-error').text( ).length > 0 ) {
                        $(this).parent('.collapsed').crmAccordionToggle();
                    }
                });
                function toggleMultiActivityCheckbox() {
                    $('.crm-is-multi-activity-wrapper').toggle(!!($(this).val() && $(this).val().indexOf(',') > 0));
                }
                $('[name=target_contact_id]', $form).each(toggleMultiActivityCheckbox).change(toggleMultiActivityCheckbox);
                $('#swap_target_assignee').click(function(e) {
                    e.preventDefault();
                    var assignees = $('#assignee_contact_id', $form).select2("data");
                    var targets = $('#target_contact_id', $form).select2("data");
                    $('#assignee_contact_id', $form).select2("data", targets);
                    $('#target_contact_id', $form).select2("data", assignees).change();
                });
            });
        </script>
        {/literal}
      </td>
    </tr>
  </table>
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

  {include file="CRM/Case/Form/ActivityToCase.tpl"}
  </div>{* end of form block*}
{/if}

{if $actionLinks}
  <div class="actionlinks">
    {foreach from=$actionLinks item=links}
      <a href="/{$links.url}?{$links.qs}" class="action-item crm-hover-button no-popup">
        {if $links.icon}{$links.icon}{/if} {$links.name}
      </a>
    {/foreach}
  </div>
{/if}

{literal}
<script type="text/javascript">
  CRM.$(function($) {
    var $form = $('form.{/literal}{$form.formClass}{literal}');

    function validate() {
      var valid = $(':input', '#recurring-entity-block').valid(),
        modified = CRM.utils.initialValueChanged('#recurring-entity-block');
      $('#allowRepeatConfigToSubmit', $form).val(valid && modified ? '1' : '0');
      return valid;
    }

    // Dialog for preview repeat Configuration dates
    function previewDialog() {
      // Set default value for start date on activity forms before generating preview
      if (!$('#repetition_start_date', $form).val() && $('#activity_date_time', $form).val()) {
        $('#repetition_start_date', $form)
          .val($('#activity_date_time', $form).val())
          .next().val($('#activity_date_time', $form).next().val())
          .siblings('.hasTimeEntry').val($('#activity_date_time', $form).siblings('.hasTimeEntry').val());
      }
      var payload = $form.serialize() + '{/literal}&entity_table={$entityTable}&entity_id={$currentEntityId}{literal}';
      CRM.confirm({
        width: '50%',
        url: CRM.url("civicrm/recurringentity/preview", payload)
      }).on('crmConfirm:yes', function() {
        $form.submit();
      });
    }

    $('#_qf_Add_upload-top, #_qf_Add_upload-bottom').click(function (e) {
      if (CRM.utils.initialValueChanged('#recurring-entity-block')) {
        e.preventDefault();
        if (validate()) {
          previewDialog();
        }
      }
    });
  });
</script>
{/literal}
