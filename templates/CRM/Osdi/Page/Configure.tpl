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
	<input type="checkbox" name="update">Update data periodically</input>
	<br>
	<p>API Key:</p> 
	<input type="text" name="apikey">
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
					alert("ASDFASDFASDF");
					console.log(result);
				});
			}
		}
		//CRM.api('importer', 'import', {params}, {success: function});
		e.preventDefault();
	});
</script>
{/literal}

