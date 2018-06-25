<h3>OSDI Field Matching</h3>

<form id="MappingForm" method="post">
    <select name="endpoint">
        <option value="" disabled="disabled" selected="selected">Please select an endpoint</option>
        <option value="1">Action Network</option>
        <option value="2">CiviCRM</option>
    </select>

    <table style="width:100%">
    {foreach from=$names item=name}
        <tr>
            <td>
                <input type="text" id={$name} value={$name}>
            </td>
        </tr>
    {/foreach}
    {foreach from=$values item=name}
        <tr>
            <td>
                <input type="text" id={$name} value={$name}>
            </td>
        </tr>
    {/foreach}
    </table>

    <button>Submit Form</button>
</form>
{literal}
<script type="text/javascript">
</script>
{/literal}



