### Installation

In order to install the app, simply put this code in the root of the extensions directory. Then, use `composer install` in order to install the dependencies on this application.

When the extension is installed, three things will be installed with it:

- a default mapping from civicrm to OSDI
- a default mapping from OSDI to civicrm
- a custom group called osditags

There are three things that you should do on a fresh install in order to make this extension work optimally:

#### 1. Setting a server time zone

#### 2. Generating an API Key

#### 3. Configure a Mapping

#### 4. (Optional) Refresh for mapping to consider custom fields.

### how to set up sync

In order to implement 2-way sync, we have implemented 4 main functions.

- one time import 
- one time export 
- scheduled job import
- scheduled job import

### setting up "one time" jobs

After installing the extension, the configuration page can be spotted at `Contacts -> Import` via OSDI. The config page should look like this:

![config image](https://raw.githubusercontent.com/4ndygu/civicrm_osdi/master/civicrm_osdi_configure.png "config image")

The endpoint and resources can be selected from a dropdown menu. Please offer the APIKey of the appropriate Action Network resource in the API Key field. 

In the Rule ID field, users can supply a dedupe rule ID, and the extension will call the supervised rule at the end of the one time import and export. This is deprecated for now - as a design decision, we chose to dedupe on name and email on insert. As a CiviCRM user, you can call dedupe rules yourself after import / export at the `Contact -> Find and Merge Duplicate Contacts` menu. Please do reach my handle at @everykittysdaydream if you explicitly would like this functionality back. 

In the Group ID rule, you can specify the group you want to export from or the group you want to import into. Please specify the group ID number.

In specify required fields, you can specify a space_delimited string. In Import, this will check if specific fields in Action Network are present in the incoming data. In Export, this will check if specific fields in CiviCRM are present in the incoming data.

#### actually importing

After you call import, you must schedule the job and then execute it. You can schedule the job by calling out to the Importer.Schedule endpoint. There are no parameters to be made. In order to set up the import pipeline, you must go to `/civicrm/admin/job` and configure Importer.Schedule as a cron job to be run at an interval of your discretion. This will add all tasks to a queue.

After you call schedule, you have to run the tasks in the queue. You can do this by calling out to the OSDIQueue.run endpoint. There are no parameters to be made. In order to set up the import pipeline, you must go to `/civicrm/admin/job` and configure OSDIQueue.run as a cron job to be run at an interval of your discretion. This will run everybody in the queue.

Currently, the architecture is constructed this way because I cannot configure the queue to only run a few elements without throwing a `failed to obtain next task` error. Please do let me know if you have gotten past this!

#### actually exporting 

The first time you press the export button on the configuration page, you are 
actually just loading up the task by calling the Exporter.bulk endpoint. To 
actually start exporting via the Person Signup Helper, you must call it more 
times, preferably in a scheduled job. Every subsequent call of the API with the
given key and endpoint will attempt to export 100 contacts. 

I realize as I write this that this is not particularly good UI and will make a 
note to fix this.

### setting up update jobs

Update jobs exist so they can be called as a scheduled job, presumably every 
day, that will update all newly modified contacts into both the external 
endpoint and the CiviCRM instance. 

#### setting up import updates

I have provided an Updater.update endpoint that does the same thing as the 
original /config field. This is to be set as a scheduled job that runs daily. 
Here are the parameters:

    key - this is the api key
    endpoint - this is the root of the external api, such as https://actionnetwork.org/api/v2/
    rule - this functions the same as the RuleID from earlier
    group - this is analogous to the groupID from the config page

Like the bulk import, you must call Importer.Schedule and OSDIQueue.run for 
these jobs to actually end up as CiviCRM contacts. If all jobs are configured 
via scheduled jobs, the pipeline should work.

#### setting up export updates

To update your exports, you can call Exporter.bulk again with certain 
parameters. We provide the following extra parameters:

    key - this is the api key
    endpoint - this is the root of the api's people endpoint, such as https://actionnetwork.org/api/v2/people/
    endpoint_root - this is the root of the api endpoint proper. Via the UI, this is generated automatically
    group - this is analogous to the groupID from the config page
    allow_restart - if 0, the job will not restart when completed. if 1, the job will.
    updatejob - set this to 1 if you want to update
    updateendpoint - set this to the endpoint of the external party's person signup helper
    required - this is the same as the space-delimited Required field 

You can set update as a scheduled job that runs daily.

### A generic OSDI-compliant endpoint.

You can find it at `/civicrm/osdi/response?object=contact`. This is currently 
still in alpha, but it allows users to page through all users with emails, 
first, and last names. I suppose everything here is in alpha, but this endpoint 
is currently in even more alpha than the above task.