<div class="hrx-pp-container">
   {if isset($notifications.error.hrx_terminal)}
        <div class="alert alert-danger" role="alert" data-alert="danger">
                {$notifications.error.hrx_terminal}
        </div>
    {/if}

    {* <select name="delivery_location_id" id="hrx_delivery_location_id" class="custom-select">
        {foreach from=$terminals item=field}
            <option value="{$field.id}" {if $field.id == $selected_terminal}selected{/if} 
                data-address="{$field.address}" data-zip="{$field.zip}" data-city="{$field.city}"  data-country="{$field.country}">
                {$field.address}, {$field.city}, {$field.country}
            </option>
        {/foreach}
    </select> *}
    
    {* <input type="hidden" id="hrx-pickup-country" name="hrx-pickup-country" value="{$country_code}"/> *}
     <script>
        var hrx_imgs_url = "{$images_url}";
        var hrx_country_code = "{$country_code}";
        var hrx_postal_code = "{$postcode}";
        var hrx_city = "{$city}";
        var hrx_available_countries = {$available_countries|@json_encode nofilter};
        
        document.addEventListener("DOMContentLoaded", function(event) {
            hrx_custom_modal();
        });
    </script>

    <input type="hidden" id="hrx-selected-terminal" name="hrx-selected-terminal" value="{$selected_terminal}"/>
    <div id="hrx-pickup-select-modal"></div>

</div>