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

    $(document).on('click', '#hrxdelivery_save_cart_info_btn', function(e) {
        e.preventDefault();
        saveHrxOrder();
    });

    $(document).on('click', '#hrxdelivery_update_terminal_list_btn', function(e) {
        e.preventDefault();
        updateTerminalList();
    });

    $(document).on('click', '.change-order-modal', function(e) {
        e.preventDefault();
        createOrderModal();
    });

    $(document).on('click', '.create-order a', function(e) {
        e.preventDefault();
        var id_order = $(this).attr('data-order');
        createOrder(id_order);
    });

    $(document).on('click', '.cancel-order a', function(e) {
        e.preventDefault();
        var id_order = $(this).attr('data-order');
        cancelOrder(id_order);
    });

    //print shipment label
    $(document).on('click', 'a.hrx-print-shipment-label', function(e) {
        e.preventDefault();
        var id_order = $(this).attr('data-order');
        printLabel(id_order, 'shipment');
    });

    //print return label
    $(document).on('click', 'a.hrx-print-return-label', function(e) {
        e.preventDefault();
        var id_order = $(this).attr('data-order');
        printLabel(id_order, 'return');
    });

    //update order state to ready
    $(document).on('click', '.make-order-ready a', function(e) {
        e.preventDefault();
        var id_order = $(this).attr('data-order');
        updateReadyState(id_order, 'return');
    });

    //update price table
    $('#configuration_form_3').submit(function(event) {

        event.preventDefault(); //this will prevent the default submit
       
        var table = {};
        
        var rows = $('#shipping-price-table').find('tbody tr');
        rows.each(function(){
            var country = $(this).attr('data-country');
            var data = {};
            var cells = $(this).find('td.price');

            cells.each(function(){
                var attribute = $(this).attr('data-price');
                var value = $(this).html();
                data[attribute] = value;
            });
            table[country] = data;
        });
        
        $.ajax({
            type: "POST",
            url: hrxdelivery_update_price_table,
            dataType: "json",
            data: {'data' : table},
            success: function (res) {
                if (typeof res.errors != 'undefined') {
                    showTableResponse(res.errors, 'danger');
                } else {
                    $('#configuration_form_3').unbind('submit').submit(); // continue the submit unbind preventDefault
                }
            }
        });
         
    });

});


function printLabel(id_order, type)
{
    var form_data = [];
    form_data.push({'name' : 'ajax', 'value' : 1});
    form_data.push({'name' : 'id_order', 'value' : id_order});
    form_data.push({'name' : 'type', 'value' : type});

    $.ajax({
        type: "POST",
        url: hrxdelivery_print_label_url,
        dataType: "json",
        data: form_data,
        success: function (res) {
            if (typeof res.errors != 'undefined') {
                showTableResponse(res.errors, 'danger');
            } else {
                showTableResponse(res.success[0], 'success');
                if(typeof res.data['url'] != undefined){
                    // file is a File object, this will also take a blob
                    const dataUrl = res.data['url'];
                    // Open the window
                    const pdfWindow = window.open(dataUrl);
                    // Call print on it
                    pdfWindow.print();
                }
            }
        },
    });
}

function saveHrxOrder() 
{
    var form_data = $('#hrx_order_form').serializeArray();
    form_data.push({'name' : 'ajax', 'value' : 1});
    form_data.push({'name' : 'id_order', 'value' : id_order});

    $('#hrxdelivery_save_cart_info_bt').addClass('disable-me');

    $.ajax({
        type: "POST",
        url: hrxdelivery_save_order_url,
        dataType: "json",
        data: form_data,
        success: function (res) {
            if (typeof res.errors != 'undefined') {
                showResponse(res.errors, 'danger');
            } else {
                showResponse(res.success[0], 'success');
                
                row = $('.table.hrx_order').find('[data-order="' + id_order + '"]').closest('tr');
                if(typeof res.data['terminal'] != 'undefined'){
                    console.log(row.find('.column-terminal'));
                    row.find('.column-terminal').html(res.data['terminal']);
                }
                if(typeof res.data['warehouse'] != 'undefined'){
                    row.find('.column-warehouse').html(res.data['warehouse']);
                }
            }
            $('#hrxdelivery_save_cart_info_bt').removeClass('disable-me');
        },
    });
}

function updateTerminalList()
{
    var form_data = $('#hrx_order_form').serializeArray();
    form_data.push({'name' : 'ajax', 'value' : 1});
    form_data.push({'name' : 'id_order', 'value' : id_order});

    $.ajax({
        type: "POST",
        url: hrxdelivery_update_terminal_list,
        dataType: "json",
        data: form_data,
        success: function (res) {
            if (typeof res.errors != 'undefined') {
                showResponse(res.errors, 'danger');
            } else if(res.terminals){
                $('#terminals').html(res.terminals);
            }
        },
    });
}

function showResponse(msg, type) {
    $('.hrx .response').html('').removeClass('alert-danger alert-success');
    $('.hrx .response').addClass('alert alert-' + type);

    if($('.hrx .response').find('ol').length == 0)
        $('.hrx .response').append('<ol></ol>');

    // Clean html tags
    if(Array.isArray(msg))
        msg = msg[0];
    msg = msg.replace(/<\/?[^>]+(>|$)/g, "");
    $('.hrx .response').find('ol').append(`<li>${msg}</li>`);
    $('.hrx .response').show();
}

function addOverlay() {
    removeOverlay();
    $('body').append(`
        <div id="vp-loading-overlay">
            <div class="lds-ellipsis">
                <div></div>
                <div></div>
                <div></div>
                <div></div>
            </div>
        </div>`
    );
}

function removeOverlay() {
    $('#vp-loading-overlay').remove();
}

function createOrderModal() {
    var link;
    if(event.target.tagName == 'I')
        link = $(event.target.parentElement);
    else
        link = $(event.target);

    var id_order = link.data('order');

    if($('#hrx-order-modal-wrapper').length != 0)
    {
        $('#hrx-modal').modal('hide');
        $('#hrx-order-modal-wrapper').remove();
    }

    addOverlay();

    $.ajax({
        type: "POST",
        url: hrx_delivery_order_modal_url,
        data: {
            'id_order' : id_order,
        },
        success: function (res) {

            res = JSON.parse(res);
            
            if (typeof res.errors != 'undefined') {
                if(Array.isArray(res.errors))
                {
                    res.errors.forEach((error) => {
                        showResponse(error, 'danger');
                    });
                }
                else
                {
                    showResponse(res.errors, 'danger');
                }
                return false;
            } else if(res.modal){
                $('#form-hrx_order').append(res.modal);
            }
        },
        complete: function(jqXHR, status) {
            $('#hrx-modal').modal('show');
            removeOverlay();
        }
    });
}

function createOrder(id_order)
{
    var form_data = [];
    form_data.push({'name' : 'ajax', 'value' : 1});
    form_data.push({'name' : 'id_order', 'value' : id_order});

    $('.create-order a').addClass('disable-me');

    $.ajax({
        type: "POST",
        url: hrxdelivery_create_order_url,
        dataType: "json",
        data: form_data,
        success: function (res) 
        {
            if (typeof res.errors != 'undefined') {
                showTableResponse(res.errors, 'danger');
            } else {
                showTableResponse(res.success, 'success');

                if($('.table.hrx_order').length == 0)
                {
                    $('#hrx-delivery .card-footer').html(res.actions);
                }
                else
                {
                    row = $('.table.hrx_order').find('[data-order="' + id_order + '"]').closest('tr');
    
                    row.find('.column-osname').html(res.data['status']);
                    row.find('.column-tracking_number').html(res.data['tracking_number']);
                    row.find('.id_order_1').html(res.actions);


                    $('#hrx-order-modal-wrapper .modal-footer').html(res.actions);
                }
            }
            $('.create-order a').removeClass('disable-me');
        },
    });
}

function updateReadyState(id_order)
{
    var form_data = [];
    form_data.push({'name' : 'ajax', 'value' : 1});
    form_data.push({'name' : 'id_order', 'value' : id_order});

    $('.make-order-ready a').addClass('disable-me');
    
    $.ajax({
        type: "POST",
        url: hrxdelivery_update_ready_state,
        dataType: "json",
        data: form_data,
        success: function (res) 
        {
            if (typeof res.errors != 'undefined') {
                showTableResponse(res.errors, 'danger');
            } else {
                showTableResponse(res.success[0], 'success');

                if($('.table.hrx_order').length == 0)
                {
                    $('#hrx-delivery .card-footer').html(res.actions);
                }
                else
                {
                    row = $('.table.hrx_order').find('[data-order="' + id_order + '"]').closest('tr');

                    row.find('.column-osname').html(res.data['status']);
                    row.find('.id_order_1').html(res.actions);
                }
                
            }
            $('.make-order-ready a').removeClass('disable-me');
        },
    });
}

function cancelOrder(id_order)
{
    var form_data = [];
    form_data.push({'name' : 'ajax', 'value' : 1});
    form_data.push({'name' : 'id_order', 'value' : id_order});

    $('.cancel-order a').addClass('disable-me');
    
    $.ajax({
        type: "POST",
        url: hrxdelivery_cancel_order_url,
        dataType: "json",
        data: form_data,
        success: function (res) 
        {
            if (typeof res.errors != 'undefined') {
                showTableResponse(res.errors, 'danger');
            } else {
                showTableResponse(res.success[0], 'success');

                row = $('.table.hrx_order').find('[data-order="' + id_order + '"]').closest('tr');
                row.find('.column-id_order_1 .create-order').remove();

                row.find('.column-osname').html(res.data['status']);
                $('.cancel-order a').remove();
            }
            $('.cancel-order a').removeClass('disable-me');
        }
    });
}

function showTableResponse(msg, type) 
{
    if($('#form-hrx_order').length == 0){
        showResponse(msg, type);
        return;
    }
    if($('#form-hrx_order .table-response.response').length == 0)
        $('#form-hrx_order').prepend('<div class="table-response response alert"></div>');
    else
        $('#form-hrx_order .table-response.response').addClass('alert').html('');

    $('#form-hrx_order .table-response.response').removeClass('alert-danger alert-success');
    $('#form-hrx_order .table-response.response').addClass('alert-' + type);

    if($('#form-hrx_order .table-response.response').find('ol').length == 0)
        $('#form-hrx_order .table-response.response').append('<ol></ol>');

    // Clean html tags
    if(Array.isArray(msg))
        msg = msg[0];
    msg = msg.replace(/<\/?[^>]+(>|$)/g, "");
    $('#form-hrx_order .table-response.response').find('ol').append(`<li>${msg}</li>`);
    
}

//terminal updating
$(document).ready(function(){

    callNextStep = function(elt, url) {
        $('.status').show();
        $.ajax({
            dataType: "json",
            async: true,
            url: url,
            method: 'GET',
            success: function(data) {

                if (typeof data.error != 'undefined') {
                    $('#hrx-terminal-progress').html(data.error);
                    $('#hrx-terminal-loader').hide();
                }
                else
                {
                    if (typeof(data.url) != 'undefined' && data.url !== false && typeof(data.counter) != 'undefined') 
                    {
                        $('#hrx-terminal-progress').html($(elt).data('starting') + ' ' + data.counter);
                        callNextStep(elt, data.url);
                    } 
                    else
                    {
                        $('#hrx-terminal-progress').html($(elt).data('done') + ' ' + data.counter);
                        $('#hrx-terminal-loader').hide();
                        elt.attr('disabled', false);
                    }
                }
                
            },
            error: function(xhr, textStatus, errorThrown) {
                console.log(xhr);
            },
        });
    }

    $(document).on('click', '#configuration_form_update_terminals', function(e){
        e.preventDefault();
        $('#hrx-terminal-loader').show();
        $(this).attr('disabled', true);
        $('#hrx-terminal-progress').html($(this).data('starting'));

        callNextStep($(this), $(this).data('url'));
    });

});