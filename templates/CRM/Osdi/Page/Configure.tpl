<h3>OSDI Launchpad</h3>
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
	<button>Sync data<button>
</form>
{literal}
<script type="text/javascript">

	CRM.$("#OSDIRequestForm").submit(function(e) {
		
		var data = new Object();

		var formResults = CRM.$("#OSDIRequestForm").serializeArray().map(function(x){
			data[x.name] = x.value;}
		); 

		if (data["endpoint"] == 1) {
			if (data["resource"] == 1) {
				console.log("calling api");
				CRM.api3('Importer', 'import', {"key": data["apikey"]}).done(function(result) {
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

