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
                <input type="text" id={$name.first} name={$name.first} value={$name.second}>
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
        //alert(CRM.$('#EndpointSelector').val());
        if(CRM.$('#EndpointSelector').val() == '2') {
            if (CRM.$('#EndpointInputter').val().trim() != "") {
                var querystring = ''.concat('OSDI_', CRM.$('#EndpointInputter').val());
                CRM.api3('Mapping', 'get', {
                    "name": querystring
                }).done(function(result) {
                    console.log(result);
                    if (result["values"].length == 0) {
                        alert("This group doesn't exist yet. Create it first!");
                    } else {
                        CRM.api3('MappingField', 'get', {
                            "mapping_id": result["id"],
                            "sequential": 1
                        }).done(function(result2) {
                            // replace the values in the table
                            console.log(result2);
                            for (var property in result2["values"]) {
                                if (!result2["values"].hasOwnProperty(property)) continue;

                                preexisting[result2["values"][property]["name"]] = result2["values"][property]["value"];
                                console.log(property);
                                itemid = ''.concat('#', result2["values"][property]["name"]);
                                CRM.$(itemid).val(result2["values"][property]["value"]);
                                console.log(preexisting);
                            }
                        });
                    }
                });
            }
        } else if (CRM.$('#EndpointSelector').val() == '1') {
            //load AN
            CRM.api3('Mapping', 'get', {
                "name": "OSDI_actionnetwork"
            }).done(function(result) {
                console.log(result);
                if (result["values"].length == 0) {
                    alert("This group doesn't exist yet. Create it first!");
                } else {
                    CRM.api3('MappingField', 'get', {
                        "mapping_id": result["id"],
                        "sequential": 1
                    }).done(function(result2) {
                        // replace the values in the table
                        console.log(result2);
                        for (var property in result2["values"]) {
                            if (!result2["values"].hasOwnProperty(property)) continue;

                            preexisting[result2["values"][property]["name"]] = result2["values"][property]["value"];
                            console.log(property);
                            itemid = ''.concat('#', result2["values"][property]["name"]);
                            CRM.$(itemid).val(result2["values"][property]["value"]);
                        }
                        console.log(preexisting);
                    });
                }
            });

        }
    });

    // submit funcs
    CRM.$("#MappingForm").submit(function(e) {
        e.preventDefault();

        var data = new Object();

        var formResults = CRM.$("#MappingForm").serializeArray().map(function(x){
            data[x.name] = x.value;
        });

        var changes = new Object();
        for (var property in data) {
            console.log(preexisting[property].trim())
            console.log(data[property].trim())
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
                alert("Updated.");
            } else if (result["values"]["message"] = "updated") {
                alert("Updated.");
            }
        });

        var formResults = CRM.$("#MappingForm").serializeArray().map(function(x){
            preexisting[x.name] = x.value;
        });

    });
</script>
{/literal}



