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
    <div class="panel">
        <div class="panel-heading">
            {l s="Courier delivery locations" mod='hrxdelivery'}
        </div>

        <div class="form-wrapper">    
            <div class="form-group delivery-settings" data-locations-wrapper>
                <label class="control-label col-md-3 dimension-group">{l s="Receive HRX courier countries" mod='hrxdelivery'}</label>
                    
                <div class="col-md-5">
                    <div class="btn-update-wrapper">
                        <button type="button" id="configuration_form_update_courier_locations" class="btn btn-primary" 
                            data-url="{$hrx_update_courier_url}" 
                            data-starting="{l s="Receiving courier countries... " mod='hrxdelivery'}" 
                            data-done="{l s="Received countries successfully... Total:" mod='hrxdelivery'}"
                            data-type="courier"
                        >
                            {l s="Get countries" mod='hrxdelivery'}
                        </button>

                        <div data-locations-loader class="hrx-loader"></div>
                    </div>

                    <div data-locations-progress></div>
                </div>

                <div class="col-md-4 text-right">
                    <a href="{$hrx_courier_tab_url}" class="btn btn-success">{l s="HRX Locations Courier" mod='hrxdelivery'}</a>
                </div>
                   
            </div>
        </div>

        <div class="well">
            <ul data-available-countries data-type="courier">

            </ul>
        </div>
    </div>
</div>

<div class="form-horizontal">
    <div class="panel">
        <div class="panel-heading">
            {l s="Terminal delivery locations" mod='hrxdelivery'}
        </div>

        <div class="form-wrapper">
            <div class="form-group delivery-settings" data-locations-wrapper>
                <label class="control-label col-md-3 dimension-group">{l s="Receive HRX terminal list" mod='hrxdelivery'}</label>
                    
                <div class="col-md-5">
                    <div class="btn-update-wrapper">
                        <button type="button" id="configuration_form_update_terminals" class="btn btn-primary" 
                            data-url="{$hrx_update_terminals_url}" 
                            data-starting="{l s="Receiving terminals... " mod='hrxdelivery'}" 
                            data-done="{l s="Received terminals successfully... Total:" mod='hrxdelivery'}"
                            data-type="terminal"
                        >
                            {l s="Get terminals" mod='hrxdelivery'}
                        </button>

                        <div data-locations-loader class="hrx-loader"></div>
                    </div>

                    <div data-locations-progress></div>
                </div>

                <div class="col-md-4 text-right">
                    <a href="{$hrx_terminal_tab_url}" class="btn btn-success">{l s="HRX Locations Terminal" mod='hrxdelivery'}</a>
                </div>     
            </div>
        </div>

        <div class="well">
            <ul data-available-countries data-type="terminal">
            
            </ul>
        </div>
    </div>
</div>