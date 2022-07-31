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
<div class="form-horizontal">
    <div class="panel" id="fieldset_0_1">
        <div class="panel-heading">
            {$legend}
        </div>
        <div class="form-wrapper">
                <div class="form-group delivery-settings">

                    <label class="control-label {if $version17}col-lg-4{else}col-lg-3{/if} dimension-group">{l s="Receive HRX terminal list"}</label>
                    
                    <div class="col-lg-8">
                        <div class="btn-update-wrapper">
                            <button type="button" id="configuration_form_update_terminals" class="btn btn-primary" data-url="{$hrx_update_terminals_url}" data-starting="{l s="Receiving terminals... "}" data-done="{l s="Received terminals successfully... Total:"}" >
                                {l s="Get terminals"}
                            </button>

                            <div id="hrx-terminal-loader" class="loader"></div>
                        </div>

                        <div id="hrx-terminal-progress"></div>
                    </div>
                   
                </div>

        </div>
         
    </div>
</div>