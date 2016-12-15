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
    var userId = $('#userId').val( extraServiceLabel );

    $('#price').val(price);
    $('#totalPrice').val(totalPrice);

    $('#paypalAmmount').val( price );
    $('#paypalQuantity').val( quantity );
    $('#paypalItemName').val( extraServiceLabel );

    var customData = [];
    customData['user_id'] = userId;
    customData['service_provider'] = selectedExtraService.service_provider;
    customData['service_name'] = selectedExtraService.service_name;
    $('#paypalCustomData').val(JSON.stringify(customData));
}

$( document ).ready(function() {
    $('#extra_service').change(function(){
        extraServiceChanged(extraServices);
    });

    var defaultQuantity = 1;
    $('#quantity').val(defaultQuantity);

    extraServiceChanged(extraServices);
    $('#PayNowButton').attr('disabled',false);

     onTextChanged = {
        id: "#quantity",
         onChange: function (element){
             //var itemsCount = $(element).val();
             extraServiceChanged();
         }
     };
});

