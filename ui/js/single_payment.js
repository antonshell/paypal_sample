/**
 * Created by Antonshell on 15.08.2015.
 */

function getExtraServiceById(id){
    for(var i=0; i<extraServices.length; i++){
        var item = extraServices[i];
        if(item.id == id){
            return item;
        }
    }

    return [];
}

function getExtraServiceLabel(extraService){
    return capitalizeFirstLetter(extraService.service_provider) + ' ' + capitalizeFirstLetter(extraService.service_name);
}

function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function extraServiceChanged(){
    var selectedExtraServiceId = $('#extra_service').val();
    var selectedExtraService = getExtraServiceById(selectedExtraServiceId);

    var quantity = $('#quantity').val();
    var price = Number(selectedExtraService.price).toFixed(2);
    var totalPrice = Number(price * quantity).toFixed(2);
    var extraServiceLabel = getExtraServiceLabel(selectedExtraService);
    var userId = $('#userId').val();

    $('#price').val(price);
    $('#totalPrice').val(totalPrice);

    $('#paypalAmmount').val( price );
    $('#paypalQuantity').val( quantity );
    $('#paypalItemName').val( extraServiceLabel );



    var customField = $('#paypalCustomData');

    //console.log(customField.val());

    //var customData = JSON.parse('{"user_id":314,"payment_type":"subscription","items_count":"4","service_provider":"getscorecard","service_name":"pro","plan_id":"1"}');
    var customData = JSON.parse('{}');
    customData['user_id'] = userId;
    customData['service_provider'] = selectedExtraService.service_provider;
    customData['service_name'] = selectedExtraService.service_name;
    customField.val(JSON.stringify(customData));

    console.log(customData);
    console.log(JSON.stringify(customData));
}

$( document ).ready(function() {
    $('#extra_service').change(function(){
        extraServiceChanged();
    });

    var defaultQuantity = 1;
    $('#quantity').val(defaultQuantity);

    extraServiceChanged();
    $('#PayNowButton').attr('disabled',false);

    onTextChanged = {
        id: "#quantity",
         onChange: function (element){
             //var itemsCount = $(element).val();
             extraServiceChanged();
         }
    };
});

