# CiviCRM Fast Activities

CiviCRM Extension for high performance activity features

## Current status

Currently this extension implements a "FastActivity" tab which uses an entirely
new BAO class (CRM_Fastactivity_BAO_Activity) to query the database.
This is implemented in the "standard" CiviCRM format with a separate whereClause
function which is not present in the original CRM_Activity_BAO_Activity class.
This should allow for easy implementation of extended filtering in the future.

## Installation

For Release 1.4 and above (which includes a link to "File on Case" for
activities) you need to apply https://github.com/civicrm/civicrm-core/pull/12620
to CiviCRM core if you are using a release earlier than 5.6.
 
## Configuration

Settings such as which columns to display can be configured under
*Administer*  →  *Customize Data and Screens*  →  *Fast Activities Tab*

## Features
- A new filter is provided on the contact activities tab which uses a
    multi-select to allow filtering on multiple activity types.
- An add/remove filter is added on edit activity for "With Contact" when > 20 to
    allow editing this field on large records.
- A new filter is provided on the contact activities tab which uses a
    multi-select to allow filtering on campaigns and their sub-campaigns.
- Case activities can optionally be shown on the contact activities tab.
- Some columns in the activities tab can optionally be shown/hidden:
    - Target Contact 
    - Duration
    - Campaign Title
    - Case

## Requirements

- Requires campaign extension (for campaigntree API) to search for activities of
    child campaigns in contact activity tab.
- 4.6 Only: Requires fontawesome extension for displaying icons (for various
    icons / engagement level):
    https://github.com/mattwire/uk.co.mjwconsult.fontawesome
