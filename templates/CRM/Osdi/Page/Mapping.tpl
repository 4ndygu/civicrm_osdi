<h3>OSDI Field Matching</h3>

<form id="MappingForm" method="post">
    <select id="EndpointSelector" name="endpoint">
        <option value="" disabled="disabled" selected="selected">Please select an endpoint</option>
        <option value="1">Action Network</option>
        <option value="2">CiviCRM</option>
    </select>
    <p>If CiviCRM selected, please enter the (https preferred) url to your /civicrm endpoint</p>
    <input id="EndpointInputter" type="text" name="civiendpoint">

    <table style="width:100%">
    <tr>
        <th>ID</th>
        <th>Civi</th>
        <th>External</th>
    </tr>
    {foreach from=$names item=name}
        <tr>
            <td>
                <p>{$name.id}</p>
            </td>
            <td>
                <p>{$name.first}</p>
            </td>
            <td>
                <input type="text" name={$name.first} value={$name.second}>
            </td>
        </tr>
    {/foreach}
    </table>

    <button>Submit Form</button>
</form>
{literal}
<script type="text/javascript">

    if(typeof(String.prototype.trim) === "undefined")
    {
        String.prototype.trim = function()
        {
            return String(this).replace(/^\s+|\s+$/g, '');
        };
    }

    var preexisting = new Object();

    CRM.$(document).ready(function() {
        var formResults = CRM.$("#MappingForm").serializeArray().map(function(x){
            preexisting[x.name] = x.value;
        })
        preexisting["endpoint"] = "";
        console.log("this is the loaded object");
        console.log(preexisting);
    });

    // change up the UI on select / input change
    CRM.$('#EndpointSelector').change(function(){
        if(CRM.$('#EndpointSelector').val() == '1'){
        } else if (CRM.$('#EndpointSelector').val() == '2') {

        }
    });

    // submit funcs
    CRM.$("#MappingForm").submit(function(e) {
        e.preventDefault();

        var data = new Object();

        var formResults = CRM.$("#MappingForm").serializeArray().map(function(x){
            data[x.name] = x.value;
        });
        console.log(data);

        var changes = new Object();
        for (var property in data) {
            console.log(property);
            if (preexisting[property].trim() != data[property].trim()) {
                changes[property] = data[property];
            }
        }

        var endpoint = "";
        if (data["endpoint"] == 1) {
            endpoint = "actionnetwork";
        } else if (data["endpoint"] == 2) {
            endpoint = data["civiendpoint"];
        }

        CRM.api3('Mapping', 'Set', {
            "changes": JSON.stringify(changes),
            "data": JSON.stringify(data),
            "endpoint" : endpoint
        }).done(function(result) {
            if (result["values"]["message"] = "new item initialized") {
                alert("New Group initialized with these configs.");
            } else if (result["values"]["message"] = "updated") {
                alert("New Configs in the group are updated.");
            }
        });
    });
</script>
{/literal}



