/**
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/
$(document).ready(function() {

    //disable button if parcel is not selected
    var delivery_option = parseInt(($('.delivery-options input[type=radio]:checked').val()));

    if(delivery_option == hrx_carrier_pickup && $('#hrx-selected-terminal').val() == '')
    {
        $('button[name="confirmDeliveryOption"').prop('disabled', true);
    }

    $(document).on('click', '.delivery-options input[type=radio]', function()
    {
        var id = parseInt($(this).attr('value'));

        if(id != hrx_carrier_pickup)
        {
            $('button[name="confirmDeliveryOption"').prop('disabled', false);
        }
        else
        {
            $('button[name="confirmDeliveryOption"').prop('disabled', true);
        }
    });
});

function registeSelectedrHrxTerminal(action, terminal_id) {
    $.ajax({
        url: hrxdelivery_front_controller_url,
        cache: false,
        dataType: 'json',
        data: {
            'init': 'hrx_terminal',
            'action': action,
            'terminal_id' : terminal_id,
        }
    });
}