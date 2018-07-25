<h3>OSDI Job Setup</h3>

<div id="dialog-confirm" title="Delete sync?">
    <p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>These syncs will be permanently deleted and cannot be recovered. Are you sure?</p>
</div>

<div id="dialog-form" title="Create new sync">
  <p class="validateTips">All form fields are required.</p>

  <form>
    <fieldset>
      <label for="name">Name</label>
      <input type="text" name="name" id="name" class="text ui-widget-content ui-corner-all">
      <br>
      <label for="resource">Imported Resource</label>
      <select name="resource" id="resource">
        <option value="0" disabled="disabled">Imported Resource</option>
        <option value="1" selected="selected">Contacts</option>
      </select>
      <br>
      <label for="rootendpoint">Sync Endpoint (root)</label>
      <input type="text" name="rootendpoint" id="rootendpoint" class="text ui-widget-content ui-corner-all" value="localhost/civicrm">
      <br>
      <label for="signupendpoint">Sync Endpoint (Person Signup Helper)</label>
      <input type="text" name="signupendpoint" id="signupendpoint" class="text ui-widget-content ui-corner-all" value="localhost/civicrm/osdi/webhook">
      <br>
      <label for="peopleendpoint">Sync Endpoint (/People Endpoint)</label>
      <input type="text" name="peopleendpoint" id="peopleendpoint" class="text ui-widget-content ui-corner-all" value=localhost/civicrm/osdi/webhook"">
      <br>
      <label for="key">API Key</label>
      <a class="helpicon" title="Group ID Help" onclick='CRM.help("apikey", "apikey for the endpoint. For CiviCRM, generate this key on /civicrm/osdi/config"); return false;'></a>
      <input type="text" name="key" id="key" class="text ui-widget-content ui-corner-all">
      <br>
      <label for="groupid">Group ID (Optional)</label>
      <a class="helpicon" title="Group ID Help" onclick='CRM.help("groupID", "This is the existing ID for a group that you will be importing everyone from the third party group INTO. This is also the existing ID for a group that you will be exporting all specified CiviCRM users from."); return false;'></a>
      <input type="text" name="groupid" id="groupid" class="text ui-widget-content ui-corner-all">
      <br>
      <label for="ruleid">Rule ID (Optional, default blank represents first_name, last_name, email)</label>
      <a class="helpicon" title="Group ID Help" onclick='CRM.help("ruleID", "This is the existing ID for the dedupe rule that all users in this sync will use to find and update deduped users."); return false;'></a>
      <input type="text" name="ruleid" id="ruleid" class="text ui-widget-content ui-corner-all">
      <br>
      <label for="reqfields">Required fields (Optional)</label>
      <a class="helpicon" title="Group ID Help" onclick='CRM.help("required", "This is a comma separated (no spaces) string of all CiviCRM fields that the sync notes is necessary for import / export of contacts."); return false;'></a>
      <input type="text" name="reqfields" id="reqfields" class="text ui-widget-content ui-corner-all">
      <br>
      <label for="syncconfig">Sync Configuration</label>
      <select name="syncconfig" id="syncconfig">
        <option value="0" disabled="disabled">Sync Configuration</option>
        <option value="1" selected="selected">Two-way sync</option>
        <option value="2">Import only</option>
        <option value="3">Export only</option>
      </select>
      <br>
      <label for="timezone">Time Zone</label>
      <select name="timezone" id="timezone">
        <option value="" disabled="disabled">Select a Time Zone</option>
        <option timeZoneId="1" gmtAdjustment="GMT-12:00" useDaylightTime="0" value="-12">(GMT-12:00) International Date Line West</option>
        <option timeZoneId="2" gmtAdjustment="GMT-11:00" useDaylightTime="0" value="-11">(GMT-11:00) Midway Island, Samoa</option>
        <option timeZoneId="3" gmtAdjustment="GMT-10:00" useDaylightTime="0" value="-10">(GMT-10:00) Hawaii</option>
        <option timeZoneId="4" gmtAdjustment="GMT-09:00" useDaylightTime="1" value="-9">(GMT-09:00) Alaska</option>
        <option timeZoneId="5" gmtAdjustment="GMT-08:00" useDaylightTime="1" value="-8">(GMT-08:00) Pacific Time (US & Canada)</option>
        <option timeZoneId="9" gmtAdjustment="GMT-07:00" useDaylightTime="1" value="-7">(GMT-07:00) Mountain Time (US & Canada)</option>
        <option timeZoneId="11" gmtAdjustment="GMT-06:00" useDaylightTime="1" value="-6">(GMT-06:00) Central Time (US & Canada)</option>
        <option timeZoneId="15" gmtAdjustment="GMT-05:00" useDaylightTime="1" value="-5">(GMT-05:00) Eastern Time (US & Canada)</option>
        <option timeZoneId="17" gmtAdjustment="GMT-04:00" useDaylightTime="1" value="-4">(GMT-04:00) Atlantic Time (Canada)</option>
        <option timeZoneId="23" gmtAdjustment="GMT-03:00" useDaylightTime="0" value="-3">(GMT-03:00) Buenos Aires, Georgetown</option>
        <option timeZoneId="26" gmtAdjustment="GMT-02:00" useDaylightTime="1" value="-2">(GMT-02:00) Mid-Atlantic</option>
        <option timeZoneId="27" gmtAdjustment="GMT-01:00" useDaylightTime="0" value="-1">(GMT-01:00) Cape Verde Is.</option>
        <option timeZoneId="30" gmtAdjustment="GMT+00:00" useDaylightTime="1" value="0" selected="selected">(GMT+00:00) Greenwich Mean Time</option>
        <option timeZoneId="31" gmtAdjustment="GMT+01:00" useDaylightTime="1" value="1">(GMT+01:00) Amsterdam, Berlin, Bern, Rome</option>
        <option timeZoneId="37" gmtAdjustment="GMT+02:00" useDaylightTime="1" value="2">(GMT+02:00) Athens, Bucharest, Istanbul</option>
        <option timeZoneId="46" gmtAdjustment="GMT+03:00" useDaylightTime="1" value="3">(GMT+03:00) Moscow, St. Petersburg, Volgograd</option>
        <option timeZoneId="50" gmtAdjustment="GMT+04:00" useDaylightTime="0" value="4">(GMT+04:00) Abu Dhabi, Muscat</option>
        <option timeZoneId="55" gmtAdjustment="GMT+05:00" useDaylightTime="0" value="5">(GMT+05:00) Islamabad, Karachi, Tashkent</option>
        <option timeZoneId="59" gmtAdjustment="GMT+06:00" useDaylightTime="1" value="6">(GMT+06:00) Almaty, Novosibirsk</option>
        <option timeZoneId="62" gmtAdjustment="GMT+07:00" useDaylightTime="0" value="7">(GMT+07:00) Bangkok, Hanoi, Jakarta</option>
        <option timeZoneId="64" gmtAdjustment="GMT+08:00" useDaylightTime="0" value="8">(GMT+08:00) Beijing, Chongqing, Hong Kong</option>
        <option timeZoneId="69" gmtAdjustment="GMT+09:00" useDaylightTime="0" value="9">(GMT+09:00) Osaka, Sapporo, Tokyo, Seoul</option>
        <option timeZoneId="75" gmtAdjustment="GMT+10:00" useDaylightTime="1" value="10">(GMT+10:00) Canberra, Melbourne, Sydney</option>
        <option timeZoneId="79" gmtAdjustment="GMT+11:00" useDaylightTime="1" value="11">(GMT+11:00) Magadan, Solomon Is.</option>
        <option timeZoneId="81" gmtAdjustment="GMT+12:00" useDaylightTime="0" value="12">(GMT+12:00) Fiji, Kamchatka, Marshall Is.</option>
        <option timeZoneId="82" gmtAdjustment="GMT+13:00" useDaylightTime="0" value="13">(GMT+13:00) Nuku'alofa</option>
      </select>
      <br>

      <!-- Allow form submission with keyboard without duplicating the dialog button -->
      <input type="submit" tabindex="-1" style="position:absolute; top:-1000px">
    </fieldset>
  </form>
</div>

<div id="accordion">
  {foreach from=$jobs item=job}
    <h3>{$job.name}</h3>
    <div>
      <p>Sync type: 2-way sync</p>
      <p>Import Job ID: {$job.id_import}</p>
      <p>Joblog: {$job.id_import_log}</p>
      <a href="/civicrm/admin/joblog?jid={$job.id_import}&reset=1">View full Job log here</a>
      <p>Export Job ID: {$job.id_export}</p>
      <p>Joblog: {$job.id_export_log}</p>
      <a href="/civicrm/admin/joblog?jid={$job.id_export}&reset=1">View full Job log here</a>
      <p>Group: {$job.group_id}, Name: {$job.group_name}</p>
      <p>Rule: {$job.rule_id}, Fields: {$job.rule_fields}</p>
      <button id="edit_{$job.name}" name="{$job.id_import}_{$job.id_export}">edit</button>
        <button id="delete_{$job.name}" name="{$job.id_import}_{$job.id_export}">delete</button>
        <button id="mapping_{$job.endpointname}" name="mapping_{$job.endpointname}">edit mapping</button>
        <a class="helpicon" title="Group ID Help" onclick='CRM.help("mapping", "This will allow you to edit the mappings for your sync from CiviCRM fields to OSDI fields."); return false;'></a>
    </div>
  {/foreach}
</div>

<button id="addjob">Add Job</button>
{literal}
<script type="text/javascript">

    var edit = 0;

    import_id = "";
    export_id = "";
    export_once_id = "";

    CRM.$("#accordion").accordion();

    dialog = CRM.$( "#dialog-form" ).dialog({
        autoOpen: false,
        height: 450,
        width: 500,
        modal: true,
        buttons: {
            "Create a sync": addJob,
            Cancel: function() {
                dialog.dialog( "close" );
            }
        },
        close: function() {
            dialog.dialog( "close" );
        }
    });

    dialogdelete = CRM.$( "#dialog-confirm" ).dialog({
        autoOpen: false,
        height: 400,
        width: 350,
        modal: true,
        buttons: {
            "Delete": function() {

                CRM.api3('OSDIJob', 'Clear', {
                    "id_import": import_id,
                    "id_export": export_id,
                }).done(function(result) {
                    console.log(result);
                    if ("error_message" in result["values"]) {
                        alert(result["values"]["error_message"]);
                    }
                });

                // refresh
                location.reload();

                // call the osdijob delete function first
                dialogdelete.dialog( "close" );
            },
            Cancel: function() {
                dialogdelete.dialog( "close" );
            }        },
        close: function() {
            dialogdelete.dialog( "close" );
        }
    });

    function addJob() {
        jobname = CRM.$("#name");
        resource = CRM.$("select#resource option:checked");
        rootendpoint = CRM.$("#rootendpoint");
        signupendpoint = CRM.$("#signupendpoint");
        peopleendpoint = CRM.$("#peopleendpoint");
        groupid = CRM.$("#groupid");
        ruleid = CRM.$("#ruleid");
        reqfields = CRM.$("#reqfields");
        key = CRM.$("#key");
        syncconfig = CRM.$("select#syncconfig option:checked");
        timezone = CRM.$("select#timezone option:checked");

        // validation logic
        CRM.api3('OSDIJob', 'Add', {
            "name": jobname.val(),
            "resource": resource.val(),
            "rootendpoint": rootendpoint.val(),
            "signupendpoint": signupendpoint.val(),
            "peopleendpoint": peopleendpoint.val(),
            "groupid": groupid.val(),
            "ruleid": ruleid.val(),
            "reqfields": reqfields.val(),
            "key": key.val(),
            "syncconfig": syncconfig.val(),
            "timezone": timezone.val(),
            "edit": edit
        }).done(function(result) {
            console.log(result);
            if ("error_message" in result) {
                alert(result["error_message"]);
            }
            if ("values" in result) {
                if ("error_message" in result["values"]) alert(result["values"]["error_message"]);
            }
        });

        if (!edit) {
            // redirect to mapping
            window.location.href = "/civicrm/osdi/mapping?change=1&endpoint=" + rootendpoint.val();

        }

        dialog.dialog( "close" );
    }

    CRM.$('#addjob').click(function(e) {
        edit = 0;
        dialog.dialog( "open" );
    });

    CRM.$('[id^="edit_"]').click(function() {
        jobname = CRM.$("#name");
        resource = CRM.$("select#resource option:checked");
        rootendpoint = CRM.$("#rootendpoint");
        signupendpoint = CRM.$("#signupendpoint");
        peopleendpoint = CRM.$("#peopleendpoint");
        groupid = CRM.$("#groupid");
        ruleid = CRM.$("#ruleid");
        reqfields = CRM.$("#reqfields");
        key = CRM.$("#key");
        syncconfig = CRM.$("select#syncconfig option:checked");
        timezone = CRM.$("select#timezone option:checked");

        // open edit dialogue after noting that we're gonna edit
        edit = 1;

        // jobcode represents if you're im/export or both
        jobcode = 0;

        ids = CRM.$(this).attr("name").split("_");
        // set the IDs
        import_id = ids[0];
        export_id = ids[1];

        // load what you can from import
        CRM.api3('Job', 'Get', {
            "id": import_id,
            "sequential": 1
        }).done(function(result) {
            if (result["values"].length != 0) {
                if (jobcode == 0) jobcode = 2;
                else jobcode = 1;

                // then set the name
                names = result["values"][0]["name"].split("_");
                jobname.val(names[2]);

                parameterarray = new Object();
                parameters = result["values"][0]["parameters"].split("\n");
                for (param in parameters) {
                    paramparts = parameters[param].split("=");
                    parameterarray[paramparts[0]] = paramparts[1];
                }

                console.log("zone");
                console.log(parameterarray["zone"]);
                console.log(parseInt(parameterarray["zone"], 10) + 13);

                CRM.$("#syncconfig")[0].selectedIndex=jobcode;
                if ("rule" in parameterarray) ruleid.val(parameterarray["rule"]);
                if ("group" in parameterarray) groupid.val(parameterarray["group"]);
                if ("zone" in parameterarray) CRM.$("#timezone")[0].selectedIndex
                    = parseInt(parameterarray["zone"], 10) + 13;
                if ("required" in parameterarray) reqfields.val(parameterarray["required"]);
                rootendpoint.val(parameterarray["endpoint"]);
                key.val(parameterarray["key"]);
            }
        });

        // load what you can from export
        CRM.api3('Job', 'Get', {
            "id": export_id,
            "sequential": 1
        }).done(function(result) {
            if (result["values"].length != 0) {
                console.log(jobcode);
                if (jobcode == 0) jobcode = 3;
                else jobcode = 1;

                parameterarray = new Object();
                parameters = result["values"][0]["parameters"].split("\n");
                for (param in parameters) {
                    paramparts = parameters[param].split("=");
                    parameterarray[paramparts[0]] = paramparts[1];
                }

                CRM.$("#syncconfig")[0].selectedIndex=jobcode;
                signupendpoint.val(parameterarray["endpoint"]);
                console.log(parameterarray);
                console.log(parameterarray["updateendpoint"]);
                peopleendpoint.val(parameterarray["updateendpoint"]);
            }
        });

        CRM.$("#resource")[0].selectedIndex=1;

        dialog.dialog( "open" );
    });

    CRM.$('[id^="delete_"]').click(function() {
        ids = CRM.$(this).attr("name").split("_");
        // set the IDs
        import_id = ids[0];
        export_id = ids[1];
        export_once_id = "";

        // do something
        dialogdelete.dialog( "open" );

        // delete should refresh the page
    });

    CRM.$('[id^="mapping_"]').click(function() {
        endpoints = CRM.$(this).attr("name").split("_");

        window.location.href = "/civicrm/osdi/mapping?change=1&endpoint=" + endpoints[1];
        // delete should refresh the page
    });

</script>
{/literal}
