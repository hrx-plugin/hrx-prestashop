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
<form id="configuration_form_2" class="defaultForm form-horizontal hrxdelivery" action="{$action}" method="post" enctype="multipart/form-data" novalidate="">
    <input type="hidden" name="submithrxdeliverydelivery" value="1">
    <div class="panel" id="fieldset_0_1">
        <div class="panel-heading">
            {$legend}
        </div>
        <div class="form-wrapper">
                <div class="form-group delivery-settings">
                    <label class="control-label {if $version17}col-lg-4{else}col-lg-3{/if} dimension-group">{$dimensions_group}</label>
                    
                    <div class="col-lg-8 dimensions-wrapper">
                        {foreach from=$dimensions_fields item=field}
                            <div class="form-group{if isset($field.class)} {$field.class}{/if}">
                                <label class="control-label col-lg-4">{$field.label}</label>
                                <div class="dimension">
                                    <input type="text" name="{$field.name}" id="{$field.name}" value="{$field.value}">
                                    <i class="unit">{$field.unit}</i>
                                </div>
                                {if isset($field.description)}
                                    <p class="help-block">{$field.description}</p>
                                {/if}
                            </div>
                        {/foreach}
                    </div>
                </div>

        </div>
        
        <!-- /.form-wrapper -->
        <div class="panel-footer">
            <button type="submit" value="1" id="configuration_form_submit_btn_2" name="submithrxdeliverydelivery" class="btn btn-default pull-right">
            <i class="process-icon-save"></i>{$button.text}
            </button>
        </div>
    </div>
</form>