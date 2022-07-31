<script type="text/javascript">
    var id_order = '{$id_order}';
</script>

<div id="hrx-order-modal-wrapper">
    <div class="bootstrap modal fade" id="hrx-modal">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <a class="close" data-dismiss="modal" >&times;</a>
                    <h3 class="modal-title">
                        {l s='Order #%1$d' sprintf=$id_order mod='mijorapriorityproducts'}

                        <div class="col-6 tracking-number">
                            {if isset($tracking)}
                                <a href="{$tracking.url}" target="_blank">{$tracking.number}</a>
                            {/if}
                        </div>
                    </h3>
                </div>
                
                <div class="modal-body">

                    <form method="post" id="hrx_order_form">
                       
                        <div class="card-body hrx">
                            <div class="response mb-3"></div>
                            <div class="">

                                {* terminal settings *}
                                
                                <div class="form-group delivery-settings">
                                    <label class="control-label col-lg-2 dimension-group">{l s="Parcel terminal" mod="hrxdelivery"}</label>
                                    <div class="col-lg-9 dimensions-wrapper" id="terminals">
                                        {if isset($terminals) && !empty($terminals)}
                                            <select name="delivery_location_id" id="delivery_location_id" class="custom-select">
                                                {foreach from=$terminals item=field}
                                                    <option value="{$field.id}" {if isset($selected_terminal.id) && $field.id == $selected_terminal.id}selected{/if}>
                                                        {$field.address}, {$field.city}, {$field.country}
                                                    </option>
                                                {/foreach}
                                            </select>
                                        {else}
                                            <div class="alert alert-warning" role="alert">
                                                {if isset($selected_terminal.address)}
                                                    {l s="Customer selelcted:"} {$selected_terminal.address}, {$selected_terminal.city}, {$selected_terminal.country}. <br>
                                                {/if}
                                                {l s="There are no terminals for the specified shipment sizes."}
                                            </div>
                                        {/if}
                                    </div>
                                </div>
                                

                                <input type="hidden" name="terminal-info" id="terminal-info" >

                                {* terminal settings *}
                                {if isset($warehouses) && !empty($warehouses)}
                                <div class="form-group delivery-settings">
                                    <label class="control-label col-lg-2 dimension-group">{l s="Warehouse" mod="hrxdelivery"}</label>
                                    <div class="col-lg-9 dimensions-wrapper">
                                        <select name="pickup_location_id" id="pickup_location_id" class="custom-select">
                                            {foreach from=$warehouses item=field}
                                                <option value="{$field.id_warehouse}" {if $field.id_warehouse == $selected_warehouse}selected{/if}>
                                                    {$field.name}, {$field.address}, {$field.city}, {$field.country}
                                                </option>
                                            {/foreach}
                                        </select>
                                    </div>
                                </div>
                                {else}
                                    <div class="alert alert-warning" role="alert">
                                        {l s="There are no warehouses"}
                                    </div>
                                {/if}

                                {* dimension settings *}
                                <div class="form-group delivery-settings">
                                    <label class="control-label col-lg-2 dimension-group">{l s="Parcel size" mod="hrxdelivery"}</label>
                                    <div class="col-lg-9 dimensions-wrapper">
                                        {foreach from=$dimensions_fields item=field}
                                            <div class="form-group{if isset($field.class)} {$field.class}{/if}">
                                                <label class="control-label">{$field.label}</label>
                                                <div class="dimension">
                                                    <input class="form-control" type="text" name="{$field.name}" id="{$field.name}" value="{$field.value}">
                                                    <i class="unit">{$field.unit}</i>
                                                </div>
                                                {if isset($field.description)}
                                                    <p class="help-block">{$field.description}</p>
                                                {/if}
                                            </div>
                                        {/foreach}
                                    </div>
                                </div>

                                {* weight settings *}
                                <div class="form-group delivery-settings">
                                    <label class="control-label col-lg-2 dimension-group">{l s="Parcel weight" mod="hrxdelivery"}</label>
                                    
                                    <div class="col-lg-9 dimensions-wrapper">
                                        
                                        <div class="form-group{if isset($weight.class)} {$weight.class}{/if}">
                                            <label class="control-label">{$weight.label}</label>
                                            <div class="dimension">
                                                <input class="form-control" type="text" name="{$weight.name}" id="{$weight.name}" value="{$weight.value}">
                                                <i class="unit">{$weight.unit}</i>
                                            </div>
                                            {if isset($weight.description)}
                                                <p class="help-block">{$weight.description}</p>
                                            {/if}
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </form>

                </div>

                <div class="modal-footer">
                    {$actions}
                </div>

            </div>
        </div>
    </div>
</div>

