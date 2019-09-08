# com.example.osdi

This is an extension for importing from and exporting to OSDI endpoints from CiviCRM. We suport syncs for Contact resources between CiviCRM to CiviCRM instances, as well as ActionNetwork to CiviCRM instances. Syncs are updated every day automatically through jobs that take groups of contacts on a remote instance of CiviCRM / ActionNetwork and ensure that these contacts are store and up to date on the host CiviCRM instance. All actions are negotiated through the OSDI implementation, and this extension provides an OSDI API endpoint to access contact instances through an OSDI format. 

For a demo video, look [here](https://drive.google.com/file/d/1Xlcxlr52bE4hvSUPRXw0hT6Qm_zmBUVO/view).

For more information on how to use or extend this application, please check the `docs` folder.

This extension to CiviCRM was built by Andy Gu for Google Summer of Code 2018. This repository's single purpose is to contain Andy Gu's submission to GSoC 2018. 

[![Coverage Status](https://coveralls.io/repos/github/4ndygu/civicrm_osdi/badge.svg?branch=master)](https://coveralls.io/github/4ndygu/civicrm_osdi?branch=master)

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

* PHP v5.4+
* CiviCRM (*FIXME: Version number*)

## Installation (Web UI)

This extension has not yet been published for installation via the web UI.

## Installation (CLI, Zip)

Sysadmins and developers may download the `.zip` file for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
cd <extension-dir>
cv dl com.example.osdi@https://github.com/4ndygu/civicrm_osdi/archive/master.zip
```

## Installation (CLI, Git)

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone https://github.com/FIXME/com.example.osdi.git
cv en osdi
```

## Usage

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

The import job above only adds the job *to be imported* in a queue that sits in `$_SESSION["extractors"]`. In order to continue with the job, you have to schedule it.

To continue scheduling the job and getting contacts into CiviCRM, you need to set up two endpoints: Importer.Schedule and OSDIQueue.Run
To do this, you can navigate to the `/civicrm/admin/job?reset=1` endpoint and press "Add New Scheduled Job". There are two jobs to configure. You can name them whatever you like, but make sure the two jobs use the Importer entity + Schedule action, and the OSDIQueue entity and run action, respectively. There are no parameters to these two tasks.

![job image](https://github.com/4ndygu/civicrm_osdi/blob/master/civicrm_job_schedule.png?raw=true "job image")

You can schedule the job by calling out to the Importer.Schedule endpoint. There are no parameters to be made. In order to set up the import pipeline, you must go to `/civicrm/admin/job` and configure Importer.Schedule as a cron job to be run at an interval of your discretion. This will add all tasks to a queue.

These jobs can be timed in cron, but can also be tested by pressing `more -> Execute now`.

The Scheduler will return information in the following format:

    'email' => [
        'valid' => True or false,
        'new' => True or false,
    ]

Valid determines if the imported contact was valid, or if it contains a first name, last name, and email plus whatever space_delimited OSDI keys that you deem are necessary. New determines if the imported contact is newer than whatever matching contact sits in CiviCRM already. If there is no matching contact, this automatically returns true. The scheduler loads all contacts as separate jobs in a CiviCRM Queue class.

If the Scheduler returns the information "no variable set", that means that the scheduler is empty. You can import more jobs like above. 

After you call schedule, you have to run the tasks in the queue. You can do this by calling out to the OSDIQueue.run endpoint. There are no parameters to be made. In order to set up the import pipeline, you must go to `/civicrm/admin/job` and configure OSDIQueue.run as a cron job to be run at an interval of your discretion. This will run everybody in the queue.

The OSDIQueue.run job will run *everybody* in the queue. 

Currently, the architecture is constructed this way because I cannot configure the queue to only run a few elements without throwing a `failed to obtain next task` error. Please do let me know if you have gotten past this!

#### actually exporting 

The first time you press the export button on the configuration page, you are actually just loading up the task by calling the Exporter.bulk endpoint. To actually start exporting via the Person Signup Helper, you must call it more times, preferably in a scheduled job. Every subsequent call of the API with the given key and endpoint will attempt to export 100 contacts. 

I realize as I write this that this is not particularly good UI and will make a note to fix this.

### setting up update jobs

Update jobs exist so they can be called as a scheduled job, presumably every day, that will update all newly modified contacts into both the external endpoint and the CiviCRM instance. 

#### setting up import updates

I have provided an Updater.update endpoint that does the same thing as the original /config field. This is to be set as a scheduled job that runs daily. Here are the parameters:

    key - this is the api key
    endpoint - this is the root of the external api, such as https://actionnetwork.org/api/v2/
    rule - this functions the same as the RuleID from earlier
    group - this is analogous to the groupID from the config page

Like the bulk import, you must call Importer.Schedule and OSDIQueue.run for these jobs to actually end up as CiviCRM contacts. If all jobs are configured via scheduled jobs, the pipeline should work.

#### setting up export updates

To update your exports, you can call Exporter.bulk again with certain parameters. We provide the following extra parameters:

    key - this is the api key
    endpoint - this is the root of the api's people endpoint, such as https://actionnetwork.org/api/v2/people/
    group - this is analogous to the groupID from the config page
    updatejob - set this to 1 if you want to update
    updateendpoint - set this to the endpoint of the external party's person signup helper
    required - this is the same as the space-delimited Required field 

You can set update as a scheduled job that runs daily.

### A generic OSDI-compliant endpoint.

You can find it at `/civicrm/osdi/response?object=contact`. This is currently still in alpha, but it allows users to page through all users with emails, first, and last names. I suppose everything here is in alpha, but this endpoint is currently in even more alpha than the above task.
## Known Issues

(* FIXME *)
