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

<div class="crm-activity-selector-{$context}">
  <div class="crm-accordion-wrapper crm-search_filters-accordion {if !$activity_tab_filter_open}collapsed{/if}">
    <div class="crm-accordion-header">
    {ts}Filters{/ts}</a>
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
      <div class="no-border form-layout-compressed" id="searchOptions">
        <div class="crm-contact-form-block-activity_type_id">
          <div class="crm-block crm-form-block crm-activity-search-form-block">
          <table class="form-layout-compressed">
            <tr>
              <td class="{if $form.activity_type_id.value.0 && $activity_tab_filter}value-highlight{/if}">
                {$form.activity_type_id.label}<br /> {$form.activity_type_id.html|crmAddClass:medium}
              </td>
              <td class="{if $form.activity_type_exclude_id.value.0 && $activity_tab_filter}value-highlight{/if}">
                {$form.activity_type_exclude_id.label}<br /> {$form.activity_type_exclude_id.html|crmAddClass:medium}
              </td>
              <td class="{if $activity_tab_filter}{if $form.activity_date_relative.value.0 || $form.activity_date_low.value || $form.activity_date_high.value}value-highlight{/if}{/if}" colspan="2" >

                  {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="activity_date" from='_low' to='_high' label='<label>Date</label>'}

              </td>
              <td class="{if $form.activity_status_id.value.0 && $activity_tab_filter}value-highlight{/if}">
                {$form.activity_status_id.label}<br /> {$form.activity_status_id.html|crmAddClass:medium}
              </td>
              {if $optionalCols.campaign_title}
                <td class="{if $form.activity_campaign_id.value.0 && $activity_tab_filter}value-highlight{/if}">
                  {$form.activity_campaign_id.label}<br /> {$form.activity_campaign_id.html|crmAddClass:medium}
                </td>
              {/if}
            </tr>
          </table>
          </div>
        </div>
    </div><!-- /.crm-accordion-body -->
  </div><!-- /.crm-accordion-wrapper -->
  <table class="contact-activity-selector-{$context}">
    <thead>
    <tr>
      <th class='crm-contact-activity-activity_type'>{ts}Type{/ts}</th>
      <th class='crm-contact-activity_subject'>{ts}Subject{/ts}</th>
      {if $optionalCols.campaign_title}
        <th class='crm-contact-activity-activity_campaign'>{ts}Campaign{/ts}</th>
      {/if}
      <th class='crm-contact-activity-source_contact'>{ts}Added By{/ts}</th>
      {if $optionalCols.target_contact}
        <th class='crm-contact-activity-target_contact nosort'>{ts}With{/ts}</th>
      {/if}
      <th class='crm-contact-activity-assignee_contact'>{ts}Assigned{/ts}</th>
      <th class='crm-contact-activity-activity_date'>{ts}Date{/ts}</th>
      <th class='crm-contact-activity-activity_status'>{ts}Status{/ts}</th>
      {if $optionalCols.duration}
        <th class='crm-contact-activity-duration nosort'>{ts}Duration{/ts}</th>
      {/if}
      {if $optionalCols.case}
        <th class='crm-contact-activity-case nosort'>{ts}Case{/ts}</th>
      {/if}
      <th class='crm-contact-activity-links nosort'>&nbsp;</th>
      <th class='hiddenElement'>&nbsp;</th>
    </tr>
    </thead>
  </table>
</div>
{include file="CRM/Case/Form/ActivityToCase.tpl" contactID=$contactId}
{if $activity_tab_filter}
  {literal}
  <style>
    .crm-activity-selector-{/literal}{$context}{literal} .value-highlight .select2-choices,
    .crm-activity-selector-{/literal}{$context}{literal} .value-highlight .select2-choice,
    .crm-activity-selector-{/literal}{$context}{literal} .value-highlight .select2-arrow,
    .crm-activity-selector-{/literal}{$context}{literal} .value-highlight .crm-absolute-date-range input {
      background-color: #FFF6D8 !important;
    }
  </style>
  {/literal}
{/if}
{literal}
<script type="text/javascript">
var {/literal}{$context}{literal}oTable;
CRM.$(function($) {
  var context = {/literal}"{$context}"{literal};
  var filterSearchOnLoad = false;
  if (context == 'activity') {
    filterSearchOnLoad = true;
  }
  buildContactActivities{/literal}{$context}{literal}( filterSearchOnLoad );

  $('.crm-activity-selector-'+ context +' :input').change( function( ) {
    buildContactActivities{/literal}{$context}{literal}( true );
  });

    function buildContactActivities{/literal}{$context}{literal}( filterSearch ) {
    if ( filterSearch && {/literal}{$context}{literal}oTable ) {
      {/literal}{$context}{literal}oTable.fnDestroy();
    }

    var context = {/literal}"{$context}"{literal};
    var columns = '';
    var sourceUrl = {/literal}'{crmURL p="civicrm/ajax/contactfastactivity" h=0 q="snippet=4&context=$context&cid=$contactId"}'{literal};

    var ZeroRecordText = {/literal}'{ts escape="js"}No matches found{/ts}'{literal};
    if ( $('.crm-activity-selector-'+ context +' select#activity_type_filter_id').val( ) ) {
      ZeroRecordText += {/literal}'{ts escape="js"} for Activity Type = "{/ts}'{literal} +  $('.crm-activity-selector-'+ context +' select#activity_type_filter_id :selected').text( ) + '"';
    }
    else {
      ZeroRecordText += '.';
    }

    {/literal}{$context}{literal}oTable = $('.contact-activity-selector-' + context ).dataTable({
      "bFilter"    : false,
      "bAutoWidth" : false,
      "aaSorting"  : [],
      "aoColumns"  : [
        {sClass:'crm-contact-activity-activity_type'},
        {sClass:'crm-contact-activity_subject'},
        {/literal}{if $optionalCols.campaign_title}{literal}
          {sClass:'crm-contact-activity-activity_campaign'},
        {/literal}{/if}{literal}
        {sClass:'crm-contact-activity-source_contact'},
        {/literal}{if $optionalCols.target_contact}{literal}
          {sClass:'crm-contact-activity-target_contact'},
        {/literal}{/if}{literal}
        {sClass:'crm-contact-activity-assignee_contact'},
        {sClass:'crm-contact-activity-activity_date'},
        {sClass:'crm-contact-activity-activity_status'},
        {/literal}{if $optionalCols.duration}{literal}
          {sClass:'crm-contact-activity-duration'},
        {/literal}{/if}{literal}
        {/literal}{if $optionalCols.case}{literal}
          {sClass:'crm-contact-activity-case'},
        {/literal}{/if}{literal}
        {sClass:'crm-contact-activity-links', bSortable:false},
        {sClass:'hiddenElement', bSortable:false}
      ],
      "bProcessing": true,
      "sPaginationType": "full_numbers",
      "sDom"       : '<"crm-datatable-pager-top"lfp>rt<"crm-datatable-pager-bottom"ip>',
      "bServerSide": true,
      "bJQueryUI": true,
      "sAjaxSource": sourceUrl,
      "iDisplayLength": 25,
      "oLanguage": {
        "sZeroRecords":  ZeroRecordText,
        "sProcessing":   {/literal}"{ts escape='js'}Processing...{/ts}"{literal},
        "sLengthMenu":   {/literal}"{ts escape='js'}Show _MENU_ entries{/ts}"{literal},
        "sInfo":         {/literal}"{ts escape='js'}Showing _START_ to _END_ of _TOTAL_ entries{/ts}"{literal},
        "sInfoEmpty":    {/literal}"{ts escape='js'}Showing 0 to 0 of 0 entries{/ts}"{literal},
        "sInfoFiltered": {/literal}"{ts escape='js'}(filtered from _MAX_ total entries){/ts}"{literal},
        "sSearch":       {/literal}"{ts escape='js'}Search:{/ts}"{literal},
        "oPaginate": {
          "sFirst":    {/literal}"{ts escape='js'}First{/ts}"{literal},
          "sPrevious": {/literal}"{ts escape='js'}Previous{/ts}"{literal},
          "sNext":     {/literal}"{ts escape='js'}Next{/ts}"{literal},
          "sLast":     {/literal}"{ts escape='js'}Last{/ts}"{literal}
        }
      },
      "fnDrawCallback": function() { setSelectorClass{/literal}{$context}{literal}( context ); },
      "fnServerData": function ( sSource, aoData, fnCallback ) {
          aoData.push( {name:'contact_id', value: {/literal}{$contactId}{literal}},
        {name:'admin',   value: {/literal}'{$admin}'{literal}}
        );

        if ( filterSearch ) {
          aoData.push(
            {name:'activity_type_id', value: $('.crm-activity-selector-'+ context +' select#activity_type_id').val()},
            {name:'activity_type_exclude_id', value: $('.crm-activity-selector-'+ context +' select#activity_type_exclude_id').val()},
            {name:'activity_date_relative', value: $('.crm-activity-selector-'+ context +' select#activity_date_relative').val()},
            {name:'activity_date_low', value: $('.crm-activity-selector-'+ context +' input#activity_date_low').val()},
            {name:'activity_date_high', value: $('.crm-activity-selector-'+ context +' input#activity_date_high').val()},
            {name:'activity_status_id', value: $('.crm-activity-selector-'+ context +' select#activity_status_id').val()}
            {/literal}{if $optionalCols.campaign_title}{literal}
            ,{name:'activity_campaign_id', value: $('.crm-activity-selector-'+ context +' select#campaigns').val()}
            {/literal}{/if}{literal}
          );
        }
        $.ajax( {
          "dataType": 'json',
          "type": "POST",
          "url": sSource,
          "data": aoData,
          "success": fnCallback,
          // CRM-10244
          "dataFilter": function(data, type) { return data.replace(/[\n\v\t]/g, " "); }
        });
      }
    });
  }

  function setSelectorClass{/literal}{$context}{literal}( context ) {
    $('.contact-activity-selector-' + context + ' td:last-child').each( function( ) {
      $(this).parent().addClass($(this).text() );
    });
  }
});
</script>
{/literal}
