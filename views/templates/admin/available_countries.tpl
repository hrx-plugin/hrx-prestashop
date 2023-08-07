{if isset($hrx_available_countries) && $hrx_available_countries}
    {foreach $hrx_available_countries as $country}
        <li>{$country.name}<span class="label label-info">{$country.iso_code}</span></li>
    {/foreach}
{else}
    <li>{l s="Please update locations" mod='hrxdelivery'}</li>
{/if}