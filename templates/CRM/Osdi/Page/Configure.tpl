<h3>OSDI Launchpad</h3>
<h2>Import</h2>
<form id="OSDIRequestForm" method="post">
	<select name="endpoint">
		<option value="" disabled="disabled" selected="selected">Please select a endpoint</option>
		<option value="1">ActionNetwork</option>
	</select>
	<br>
	<select name="resource">
		<option value="" disabled="disabled" selected="selected">Please select a resource</option>
		<option value="1">Contacts</option>
	</select>
	<br>
	<p>API Key:</p> 
	<input type="text" name="apikey" id="apikey">
	<br>
    <p>Rule ID:</p>
    <input type="text" name="rule" id="rule">
    <br>
    <p>Specify Required Fields:</p>
    <input type="text" name="required" id="required">
	<button>Sync data</button>
</form>
<br>
<h2>Export</h2>
<form id="OSDIExportForm" method="post">
	<select name="endpoint">
		<option value="" disabled="disabled" selected="selected">Please select a endpoint</option>
		<option value="1">ActionNetwork</option>
	</select>
	<br>
	<select name="resource">
		<option value="" disabled="disabled" selected="selected">Please select a resource</option>
		<option value="1">Contacts</option>
	</select>
	<br>
	<p>API Key:</p> 
	<input type="text" name="apikey" id="apikey">
    <br>
    <p>Specify Required Fields:</p>
    <input type="text" name="required" id="required">
	<button>Export data</button>
</form>

{literal}
<script type="text/javascript">

    function isInt(value) {
        return !isNaN(value) && 
            parseInt(Number(value)) == value && 
            !isNaN(parseInt(value, 10));
    }

	CRM.$("#OSDIExportForm").submit(function(e) {
		
		var data = new Object();

		var formResults = CRM.$("#OSDIExportForm").serializeArray().map(function(x){
			data[x.name] = x.value;}
		); 

		if (data["endpoint"] == 1) {
			if (data["resource"] == 1) {
				console.log("calling api");
				CRM.api3('Exporter', 'bulk', {"required": data["required"], "key": data["apikey"], "endpoint": "https://actionnetwork.org/api/v2/people/"}).done(function(result) {
					var returnedCount = '' + result["values"]["count"];
                    if (returnedCount == -1) {
                        alert("no users added. job completed.");
                    } else {
                        alert(returnedCount + " Users successfully added to group. Please add the exporter.bulk job to the list of scheduled jobs.");
                    }
				});
			}
		}

		e.preventDefault();
	});

	CRM.$("#OSDIRequestForm").submit(function(e) {
		
		var data = new Object();

		var formResults = CRM.$("#OSDIRequestForm").serializeArray().map(function(x){
			data[x.name] = x.value;}
		); 

        var rule = -1;
        if (isInt(data["rule"])) { rule = data["rule"]; }

		if (data["endpoint"] == 1) {
			if (data["resource"] == 1) {
				console.log("calling api");
				CRM.api3('Importer', 'import', {"key": data["apikey"], "rule": rule, "required": data["required"]}).done(function(result) {
					var returnedCount = result["values"]["count"];	
					if (returnedCount == 0) {
						alert("Jobs added to queue successfully.");
					} else {
						alert("ERROR: Jobs not added successfully.");
					}
				});
			}
		}

		e.preventDefault();
	});
</script>
{/literal}

