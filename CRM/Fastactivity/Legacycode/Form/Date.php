<?php
/*-------------------------------------------------------+
| SYSTOPIA - LEGACY CODE INLINE-REPLACEMENTS             |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

/**
 * This class offers in-line code replacements for deprecated/dropped functions
 *  of the CRM_Core_OptionGroup class
 */
class CRM_Fastactivity_Legacycode_Form_Date {

  /**
   * Remark: copied verbatim from CRM_Core_Form_Date in CiviCRM@v5.60
   *
   * Retrieve the date range - relative or absolute and assign it to the form.
   *
   * @param CRM_Core_Form $form
   *   The form the dates should be added to.
   * @param string $fieldName
   * @param int $count
   * @param string $from
   * @param string $to
   * @param string $fromLabel
   * @param bool $required
   * @param array $operators
   *   Additional value pairs to add.
   * @param string $dateFormat
   * @param bool|string $displayTime
   * @param array $attributes
   */
  public static function buildDateRange(
    &$form, $fieldName, $count = 1,
    $from = '_from', $to = '_to', $fromLabel = 'From:',
    $required = FALSE, $operators = [],
    $dateFormat = 'searchDate', $displayTime = FALSE,
    $attributes = ['class' => 'crm-select2']
  ) {
    $selector
      = self::returnDateRangeSelector(
      $form, $fieldName, $count,
      $from, $to, $fromLabel,
      $required, $operators,
      $dateFormat, $displayTime
    );
    self::addDateRangeToForm(
      $form, $fieldName, $selector,
      $from, $to, $fromLabel,
      $required, $dateFormat, $displayTime,
      $attributes
    );
  }

  /**
   * Remark: copied verbatim from CRM_Core_Form_Date in CiviCRM@v5.60
   *
   * Build the date range array that will provide the form option values.
   *
   * It can be - relative or absolute.
   *
   * @param CRM_Core_Form $form
   *   The form object that we are operating on.
   * @param string $fieldName
   * @param int $count
   * @param string $from
   * @param string $to
   * @param string $fromLabel
   * @param bool $required
   * @param array $operators
   *   Additional Operator Selections to add.
   * @param string $dateFormat
   * @param bool $displayTime
   *
   * @return array
   *   Values for Selector
   */
  public static function returnDateRangeSelector(
    &$form, $fieldName, $count = 1,
    $from = '_from', $to = '_to', $fromLabel = 'From:',
    $required = FALSE, $operators = [],
    $dateFormat = 'searchDate', $displayTime = FALSE
  ) {
    $selector = [
      '' => ts('- any -'),
      0 => ts('Choose Date Range'),
    ];
    // CRM-16195 Pull relative date filters from an option group
    $selector = $selector + CRM_Core_OptionGroup::values('relative_date_filters');

    if (is_array($operators)) {
      $selector = array_merge($selector, $operators);
    }

    $config = CRM_Core_Config::singleton();
    //if fiscal year start on 1 jan then remove fiscal year task
    //form list
    if ($config->fiscalYearStart['d'] == 1 & $config->fiscalYearStart['M'] == 1) {
      unset($selector['this.fiscal_year']);
      unset($selector['previous.fiscal_year']);
    }
    return $selector;
  }

  /**
   * Build the date range - relative or absolute.
   *
   * Remark: copied verbatim from CRM_Core_Form_Date in CiviCRM@v5.60
   *
   * @param CRM_Core_Form $form
   *   The form object that we are operating on.
   * @param string $fieldName
   * @param array $selector
   *   Array of option values to add.
   * @param string $from
   *   Label.
   * @param string $to
   * @param string $fromLabel
   * @param bool $required
   * @param string $dateFormat
   * @param bool $displayTime
   * @param array $attributes
   */
  public static function addDateRangeToForm(
    &$form,
    $fieldName,
    $selector,
    $from,
    $to,
    $fromLabel,
    $required,
    $dateFormat,
    $displayTime,
    $attributes
  ) {
    $form->add('select',
               "{$fieldName}_relative",
               ts('Relative Date Range'),
               $selector,
               $required,
               $attributes
    );

    self::addDateRange($form, $fieldName, $from, $to, $fromLabel, $dateFormat, $displayTime);
  }

  public static function addDateRange($form, $name, $from = '_from', $to = '_to', $label = 'From:', $dateFormat = 'searchDate', $required = FALSE, $displayTime = FALSE) {
    if ($displayTime) {
      $form->add('datepicker', $name . $from, $label, [], $required, ['time' => true]);
      $form->add('datepicker', $name . $to, ts('To:'), [], $required, ['time' => true]);
    }
    else {
      $form->add('datepicker', $name . $from, $label, [], $required, ['time' => false]);
      $form->add('datepicker', $name . $to, ts('To:'), [], $required, ['time' => false]);
    }
  }
}
