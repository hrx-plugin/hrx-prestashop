<div class="hrx-action-wrapper">

    {* cancel order  *}
    {if isset($is_table) && $is_table == false}
        {if isset($status) && $status == 'new' || $status == 'ready'}
            <span class="cancel-order float-left">
                <a class="btn btn-danger" data-order="{$id_order}">
                    {l s="Cancel order" mod="hrxdelivery"}
                </a>
            </span>
        {/if}
    {/if}

    {* create shipment  *}
    {if isset($status) && $status == ''}
        <span class="create-order">
            <a class="btn btn-success" data-order="{$id_order}">
                {l s="Create shipment" mod="hrxdelivery"}
            </a>
        </span>
    {/if}

    {* edit order *}
    {if isset($is_table) && $is_table == true && isset($status) && $status == ''}
        <span class="change-order-modal float-left">
            <a class="btn btn-default" data-order="{$id_order}">
                {l s="Edit" mod="hrxdelivery"}
            </a>
        </span>
    {/if}

    {* print labels *}
    {if isset($status) && $status != 'cancel' && $status != ''}
        {if $require_return_label}
        <div class="{if isset($is_table) && $is_table == true}dropdown{else}dropup{/if} print-btn">
            <button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown">
                {l s="Print" mod="hrxdelivery"}&nbsp;<span class="caret"></span>
            </button>
            <ul class="dropdown-menu">
                <li>
                    <a href="#" class="hrx-print-shipment-label"  data-order="{$id_order}">
                        {l s="Shipment label" mod="hrxdelivery"}
                    </a>
                </li>
                <li>
                    <a href="#" class="hrx-print-return-label" data-order="{$id_order}">
                        {l s="Return label" mod="hrxdelivery"}
                    </a>
                </li>
            </ul>
        </div>
        {else}
            <a href="#" class="hrx-print-shipment-label btn btn-warning"  data-order="{$id_order}">
                {l s="Print label" mod="hrxdelivery"}
            </a>
        {/if}
    {/if}

    {* make order ready  *}
    {if isset($status) && $status == 'new'}
        <span class="make-order-ready">
            <a class="btn btn-success" data-order="{$id_order}">
                {l s="Mark as ready" mod="hrxdelivery"}
            </a>
        </span>
    {/if}

    {if isset($is_table) && $is_table == false && isset($status) && $status == ''}    
        <button name="hrxdelivery_update_terminal_list" id="hrxdelivery_update_terminal_list_btn" class="btn btn-primary">{l s="Update terminal list" mod='hrxdelivery'}</button>
        <button name="hrxdelivery_save_cart_info" id="hrxdelivery_save_cart_info_btn" class="btn btn-primary"><i class="icon-save"></i> {l s="Save" mod='hrxdelivery'}</button>
    {/if}
</div>