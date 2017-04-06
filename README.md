# de.systopia.fastactivity
CiviCRM Extension for high performance activity features

## Current status

Currently this extension implements a "FastActivity" tab which uses an entirely new BAO class (CRM_Fastactivity_BAO_Activity) to query the database.
This is implemented in the "standard" CiviCRM format with a separate whereClause function which is not present in the original CRM_Activity_BAO_Activity class.  This should allow for easy implementation of extended filtering in the future.

## Features
-A new filter is provided which uses a multi-select to allow filtering on multiple activity types.

## Not implemented
-The built-in View/Edit activity dialog also needs to be replaced with one that uses the CRM_Fastactivity_BAO_Activity class to benefit from the performance improvements.
