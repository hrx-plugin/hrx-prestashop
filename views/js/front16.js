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

$(document).ready(function() 
{
    //move extra carrier content near carrier
    moveExtraContent();

    //if carrier is not selected, hide extra content
    if(parseInt($('[id^="delivery_option"]:checked').val()) != parseInt(hrx_carrier_pickup)){
        $('.hrx-pp-container').hide();
    } 
    
    var delivery_option = parseInt($('.delivery_options input[type=radio]:checked').val());

    if(delivery_option == hrx_carrier_pickup && $('#hrx-selected-terminal').val() == '')
    {
        $('button[name="processCarrier"').prop('disabled', true);
    }

    // reloadCarrier content after quick order refresh
    if(typeof page_name != "undefined" && page_name == 'order-opc')
    {
        $( document ).ajaxComplete(function( event, xhr, settings ) {
            var selected_option = parseInt($('.delivery_options input[type=radio]:checked').val());
            if ( typeof settings.data !== 'undefined' && settings.data.includes('updateCarrierAndGetPayments') && selected_option == hrx_carrier_pickup ) {
                hrx_custom_modal();
                moveExtraContent();
            }
        });
    }

});


$(document).on('click', 'body#order .delivery_options input[type=radio]', function()
{
    var id = parseInt($(this).attr('value'));
    var delivery_option = parseInt($('.delivery_options input[type=radio]:checked').val());
    
    if(id != hrx_carrier_pickup)
    {
        $('.hrx-pp-container').hide();
        $('button[name="processCarrier"').prop('disabled', false);
    }
    else
    {
        $('.hrx-pp-container').show();
        if(delivery_option == hrx_carrier_pickup && $('#hrx-selected-terminal').val() == '')
        {
            $('button[name="processCarrier"').prop('disabled', true);
        }
    }
});

function moveExtraContent()
{
    var option = $('.delivery_option_radio :input[value="' + hrx_carrier_pickup + ',"]');
    var optionHolder = option.parents('.delivery_option');
    var extraContentPlace =  optionHolder.find('table');
    $('.hrx-pp-container').insertAfter(extraContentPlace);
}

