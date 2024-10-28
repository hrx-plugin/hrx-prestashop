<div class="hrx-action-wrapper">

    {* cancel order  *}
    {if isset($hrxbtn_status) && $hrxbtn_status == 'new' || $hrxbtn_status == 'ready'}
        <span class="cancel-order float-left">
            <a class="btn btn-danger" data-order="{$hrxbtn_id_order}">
                {l s="Cancel order" mod='hrxdelivery'}
            </a>
        </span>
    {/if}
    

    {* create shipment  *}
    {if isset($hrxbtn_status) && $hrxbtn_status == ''}
        <span class="create-order">
            <a class="btn btn-success" data-order="{$hrxbtn_id_order}">
                {l s="Create shipment" mod='hrxdelivery'}
            </a>
        </span>
    {/if}

    {* edit order *}
    {if isset($hrxbtn_is_table) && $hrxbtn_is_table == true && isset($hrxbtn_status) && $hrxbtn_status == ''}
        <span class="change-order-modal float-left">
            <a class="btn btn-default" data-order="{$hrxbtn_id_order}">
                {l s="Edit" mod='hrxdelivery'}
            </a>
        </span>
    {/if}

    {* print labels *}
    {if isset($hrxbtn_status) && $hrxbtn_status != 'cancel' && $hrxbtn_status != ''}
        {if $hrxbtn_require_return_label}
        <div class="{if isset($hrxbtn_is_table) && $hrxbtn_is_table == true}dropdown{else}dropup{/if} print-btn">
            <button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown">
                {l s="Print" mod='hrxdelivery'}&nbsp;<span class="caret"></span>
            </button>
            <ul class="dropdown-menu">
                <li>
                    <a href="#" class="hrx-print-shipment-label"  data-order="{$hrxbtn_id_order}">
                        {l s="Shipment label" mod='hrxdelivery'}
                    </a>
                </li>
                <li>
                    <a href="#" class="hrx-print-return-label" data-order="{$hrxbtn_id_order}">
                        {l s="Return label" mod='hrxdelivery'}
                    </a>
                </li>
            </ul>
        </div>
        {else}
            <a href="#" class="hrx-print-shipment-label btn btn-warning"  data-order="{$hrxbtn_id_order}">
                {l s="Print label" mod='hrxdelivery'}
            </a>
        {/if}
    {/if}

    {* make order ready  *}
    {if isset($hrxbtn_status) && $hrxbtn_status == 'new'}
        <span class="make-order-ready">
            <a class="btn btn-success" data-order="{$hrxbtn_id_order}">
                {l s="Mark as ready" mod='hrxdelivery'}
            </a>
        </span>
    {/if}
    {if isset($hrxbtn_is_table) && $hrxbtn_is_table == false && isset($hrxbtn_status) && $hrxbtn_status == ''}   
        {if $hrxbtn_or_pickup} 
            <button name="hrxdelivery_update_terminal_list" id="hrxdelivery_update_terminal_list_btn" class="btn btn-primary">{l s="Update terminal list" mod='hrxdelivery'}</button>
        {/if}
        <button name="hrxdelivery_save_cart_info" id="hrxdelivery_save_cart_info_btn" class="btn btn-primary">{l s="Save" mod='hrxdelivery'}</button>
    {/if}
</div>