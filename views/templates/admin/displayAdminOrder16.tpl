{**
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 *  @author    Mijora
 *  @copyright 2013-2022 Mijora
 *  @license   license.txt
 *}
<script type="text/javascript">
    var id_order = '{$id_order}';
</script>
<div id="hrx-delivery" class="row hrx">
    <div class="col-lg-6 d-print-none">
        <div class="panel">
            <form method="post" id="hrx_order_form">
                <input type="hidden" id="kind" name="kind" value="{$kind}">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-lg-6 card-header-title">
                            <img src="{$image}"/>
                            <h4>{l s="Hrx delivery" mod='hrxdelivery'}</h4>
                        </div>
                        
                        <div class="col-lg-6 tracking-number">
                            {if isset($tracking) && $tracking.number != ''}
                                {l s="Tracking number" mod='hrxdelivery'} <a href="{$tracking.url}" target="_blank">{$tracking.number}</a>
                            {/if}
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="response alert mb-3"></div>
                    <div class="form-wrapper">

                        {* terminal settings *}
                        
                        <div class="form-group delivery-settings {if !isset($selected_terminal)} hidden {/if}">
                            <label class="control-label col-lg-4 col-2 dimension-group">{l s="Parcel terminal" mod='hrxdelivery'}</label>
                            <div class="col-lg-7 dimensions-wrapper" id="terminals">
                                {if isset($terminals) && !empty($terminals)}
                                <select name="delivery_location_id" id="delivery_location_id" class="custom-select">
                                    {foreach from=$terminals item=field}
                                        <option value="{$field.id}" {if isset($selected_terminal->id_terminal) && $field.id == $selected_terminal->id_terminal}selected{/if}>
                                            {$field.address}, {$field.city}, {$field.country}
                                        </option>
                                    {/foreach}
                                </select>
                                {else}
                                    <div class="alert alert-warning" role="alert">
                                        {if isset($selected_terminal->address)}
                                            {l s="Customer selelcted:" mod='hrxdelivery'} {$selected_terminal->address}, {$selected_terminal->city}, {$selected_terminal->country}
                                            <br>
                                        {/if}
                                        {l s="There are no terminals for the specified shipment sizes." mod='hrxdelivery'}
                                    </div>
                                {/if}
                            </div>
                        </div>

                        {* terminal settings *}
                        
                        <div class="form-group delivery-settings">
                            <label class="control-label col-lg-4 col-2 dimension-group">{l s="Warehouse" mod='hrxdelivery'}</label>
                            <div class="col-lg-7 dimensions-wrapper">
                                {if isset($warehouses) && !empty($warehouses)}
                                    <select name="pickup_location_id" id="pickup_location_id" class="custom-select">
                                        <option value="0" selected="true" disabled="disabled">{$select_warehouse}</option>
                                        {foreach from=$warehouses item=field}
                                            <option value="{$field.id_warehouse}" {if $field.id_warehouse == $selected_warehouse}selected{/if}>
                                                {$field.name}, {$field.address}, {$field.city}, {$field.country}
                                            </option>
                                        {/foreach}
                                    </select>
                                {else}
                                    <div class="alert alert-warning" role="alert">
                                        {l s="There are no warehouses. You must register warehouse in the HRX system and update the data in prestashop by selecting Shipping -> HRX warehouses -> Update warehouses" mod='hrxdelivery'}
                                    </div>
                                {/if}
                            </div>
                        </div>

                        <input type="hidden" name="terminal-info" id="terminal-info">

                        {* dimension settings *}
                        <div class="form-group delivery-settings">
                            <label class="control-label col-lg-4 col-2 dimension-group">{l s="Parcel size" mod='hrxdelivery'}</label>
                            <div class="col-lg-7 dimensions-wrapper">
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
                            <label class="control-label col-lg-4 col-2 dimension-group">{l s="Parcel weight" mod='hrxdelivery'}</label>
                            
                            <div class="col-lg-7 dimensions-wrapper">
                                
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

                <div class="card-footer text-right">
                    {$actions}
                </div>
            </form>
        </div>
    </div>
</div>