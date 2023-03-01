<select name="id_available_later_value" id="id_available_later_value" class="form-control" style="display: none">
    <option value="" data-value=""></option>
    {foreach $availabilities as $availability}
        <option {if $id_available_later_value|intval == $availability.id_available_later_value|intval}selected="selected"{/if} value="{$availability.id_available_later_value|intval}" data-value="{$availability.name}">{$availability.name}</option>
    {/foreach}
</select>

<script type="text/javascript">
    {literal}
    $(document).ready(function() {
        let originalField =  $('#form_step3_available_later');
        let newField =  $('#id_available_later_value');
        originalField.hide();
        originalField.after(newField);
        newField.show();
    });
    {/literal}
</script>