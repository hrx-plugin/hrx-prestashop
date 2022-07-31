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
<div class="hrx">
    <div class="response">
    </div>
</div>
<form id="configuration_form_3" class="defaultForm form-horizontal hrxdelivery" action="{$action}" method="post" enctype="multipart/form-data" novalidate="">
    <input type="hidden" name="submithrxdeliveryprice" value="1">
    <div class="panel" id="fieldset_0_1">
        <div class="panel-heading">
            {$legend}
        </div>
        <div class="form-wrapper">
            <div class="form-group">
                <label class="control-label {if $version17}col-lg-4{else}col-lg-3{/if}">{$config_fields['use_tax_table']['label']}</label>
                <div class="col-lg-8">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="{$config_fields['use_tax_table']['name']}" id="{$config_fields['use_tax_table']['name']}_on" value="1" {if {$config_fields['use_tax_table']['value']} }checked="checked"{/if}>
                        <label for="{$config_fields['use_tax_table']['name']}_on">{l s="Enabled"}</label>
                        <input type="radio" name="{$config_fields['use_tax_table']['name']}" id="{$config_fields['use_tax_table']['name']}_off" value="" {if {$config_fields['use_tax_table']['value']} == false }checked="checked"{/if}>
                        <label for="{$config_fields['use_tax_table']['name']}_off">{l s="Disabled"}</label>
                        <a class="slide-button btn"></a>
                    </span>
                    <p class="help-block">
                        {$config_fields['use_tax_table']['description']}
                    </p>
                </div>
            </div>

            <div class="form-group delivery-settings">
                <label class="control-label {if $version17}col-lg-4{else}col-lg-3{/if} dimension-group">{$config_fields['tax']['label']}</label>
                
                <div class="col-lg-8 dimensions-wrapper">
                    <div class="form-group">
                        <div class="dimension">
                            <input type="text" name="{$config_fields['tax']['name']}" id="{$config_fields['tax']['name']}" value="{$config_fields['tax']['value']}">
                            {if $config_fields['or_amount']['value']}
                                <i class="unit">{$currency}</i>
                            {else}
                                <i class="unit">%</i>
                            {/if}
                        </div>
                        <p class="help-block">{$config_fields['tax']['description']}</p>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label {if $version17}col-lg-4{else}col-lg-3{/if}">{$config_fields['or_amount']['label']}</label>
                <div class="col-lg-8">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="{$config_fields['or_amount']['name']}" id="{$config_fields['or_amount']['name']}_on" value="1" {if {$config_fields['or_amount']['value']} }checked="checked"{/if}>
                        <label for="{$config_fields['or_amount']['name']}_on">{l s="Enabled"}</label>
                        <input type="radio" name="{$config_fields['or_amount']['name']}" id="{$config_fields['or_amount']['name']}_off" value="" {if {$config_fields['or_amount']['value']} == false}checked="checked"{/if}>
                        <label for="{$config_fields['or_amount']['name']}_off">{l s="Disabled"}</label>
                        <a class="slide-button btn"></a>
                    </span>
                    <p class="help-block">
                        {$config_fields['or_amount']['description']}
                    </p>
                </div>
            </div>

            {* <!-- change price table -->
            <div class="form-group">
                <label class="control-label {if $version17}col-lg-4{else}col-lg-3{/if}">{l s="Change price table" mod="hrxdelivery"}</label>
                <div class="col-lg-8">
                    <div class="btn-update-wrapper">
                        <button type="button" id="configuration_form_change_price_table" class="btn btn-primary">
                            {l s="Change price table" mod="hrxdelivery"}
                        </button>
                    </div>
                </div>
            </div> *}

            <!-- price table -->
            <div class="form-group">
                <label class="control-label {if $version17}col-lg-4{else}col-lg-3{/if}">{l s="Shipping price table"}</label>
                <div class="col-lg-8">
                    <table class="table" id="shipping-price-table">
                        <thead>
                            <tr>
                                <th>To</th>
                                <th>0-2.99 kg</th>
                                <th>3-4.99 kg</th>
                                <th>5-9.99 kg</th>
                                <th>10-14.99 kg</th>
                                <th>15-19.99 kg</th>
                                <th>20-30 kg</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $shipping_price_table as $row}
                            <tr data-country="{$row['country']}">
                                <td>{$row['country']}</td>
                                <td class="price" data-price="price0_3" contenteditable>{$row['price0_3']}</td>
                                <td class="price" data-price="price3_5" contenteditable>{$row['price3_5']}</td>
                                <td class="price" data-price="price5_10" contenteditable>{$row['price5_10']}</td>
                                <td class="price" data-price="price10_15" contenteditable>{$row['price10_15']}</td>
                                <td class="price" data-price="price15_20" contenteditable>{$row['price15_20']}</td>
                                <td class="price" data-price="price20_30" contenteditable>{$row['price20_30']}</td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
        
        <!-- /.form-wrapper -->
        <div class="panel-footer">
            <button type="submit" value="1" id="configuration_form_submit_btn_3" name="submithrxdeliveryprice" class="btn btn-default pull-right">
            <i class="process-icon-save"></i>{$button.text}
            </button>
        </div>
    </div>
</form>