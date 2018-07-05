### Installation

In order to install the app, simply put this code in the root of the extensions directory. Then, use `composer install` in order to install the dependencies on this application.

When the extension is installed, three things will be installed with it:

- a default mapping from civicrm to OSDI
- a default mapping from OSDI to civicrm
- a custom group called osditags

There are three things that you should do on a fresh install in order to make this extension work optimally:

#### 1. Setting a server time zone

If you scroll down the /civicrm/osdi/configure endpoint, you'll come across a dropdown menu that looks like this:
![timezone config image](https://raw.githubusercontent.com/4ndygu/civicrm_osdi/master/civicrm_timezone_config.png)

This sets the time zone on the *server* where the civicrm instance is running. Please first select a timezone and configure it to your server settings.

#### 2. Generating an API Key and enabling permissions

Undernearth the timezone configuration button, there should be a button that says "Update API Key". You must click it first in order to save the API key. If you do, you should get a notification that looks something like this:

![button config image](https://raw.githubusercontent.com/4ndygu/civicrm_osdi/master/civicrm_key_response.png)

The red bar will be an API key. You can refresh the API key by simply clicking it again. In order to POST out to the /civicrm/osdi/webhook endpoint, you must supply this key under the OSDI-API-Token header.

In order to access the webhook (required for Civi to Civi sync), you must enable "webhook permissions". Go to `Administer -> Users and Permissions -> Permissions (Access Control) -> Drupal Access Control. Then, enable the `OSDI sample application: allow webhook posts` permissions for anonymous users.

#### 3. Configure a Mapping

This is technically also optional, but it may be useful if you want a different way for certain Civi fields to map to different OSDI objects for different endpoints. The endpoint for editing the mapping endpoint is at `/civicrm/osdi/mapping`. You will be presented with a page that contains the default contact to OSDI endpoint, which looks like this: 

![osdi mapping image](https://raw.githubusercontent.com/4ndygu/civicrm_osdi/master/civicrm_mapping.png)

You can load a groupat first by either moving the dropdown menu to ActionNetwork, or by filling out the endpoint in the textbox and moving the dropdown menu to CiviCRM. If a mapping is already loaded, the corresponding fields will load on the page. If the mapping does not exist, the browser will alert you, and you can click the Submit Form button at the end to generate the group.

Then, you may edit the fields as you wish and update with the Submit Form button. If you want to generate a subfield in an OSDI JSON object, you must user the `|` character as a special character. For instance, a mapping from `custom_12 -> custom_fields|xxxxx` will first generate a custom_fields array if it does not exist in the created OSDI contact, then place the value of the custom_12 field with the `xxxxx` key. 

#### 4. (Optional) Refresh for mapping to consider custom fields.

On the bottom of the /mapping page, there is a button that is labeled `Update Endpoint Mappings`. If you have new extensions in the future that add more custom fields or in any other way have new custom fields, you can click this button to update the default mappings (and all other mappings that are newly created) with these custom fields.

This function is called every time the extension syncs with a new Civi endpoint, as well as the first time it connects to ActionNetwork.

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