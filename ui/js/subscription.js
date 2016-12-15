function getSubscriptionPlanById(id){
    for(var i=0; i<subscriptionPlans.length; i++){
        var item = subscriptionPlans[i];
        if(item.id == id){
            return item;
        }
    }

    return [];
}

function getSubscriptionPlanLabel(subscriptionPlan){
    return capitalizeFirstLetter(subscriptionPlan.service_provider) + ' ' + capitalizeFirstLetter(subscriptionPlan.service_name);
}

function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function subscriptionParamsChanged(){
    var selectedSubscriptionPlanId = $('#subscriptionPlan').val();
    var selectedSubscriptionPlan = getSubscriptionPlanById(selectedSubscriptionPlanId);

    var quantity = $('#usersCount').val();
    var price = Number(selectedSubscriptionPlan.price).toFixed(2);
    var totalPrice = Number(price * quantity).toFixed(2);
    var subscriptionPlanLabel = getSubscriptionPlanLabel(selectedSubscriptionPlan);
    var userId = $('#userId').val();

    $('#totalPrice').val(totalPrice);

    $('#subscriptionAmount').val( totalPrice );
    $('#item_name').val( subscriptionPlanLabel );

    var customData = JSON.parse('{}');
    customData['user_id'] = userId;
    customData['payment_type'] = 'subscription';
    customData['items_count'] = quantity;
    customData['service_provider'] = selectedSubscriptionPlan.service_provider;
    customData['service_name'] = selectedSubscriptionPlan.service_name;
    customData['plan_id'] = selectedSubscriptionPlan.id;

    $('#customData').val(JSON.stringify(customData));
}

$( document ).ready(function(){
    $('#subscriptionPlan').change(function(){
        subscriptionParamsChanged();
        $('#subscribeButton').show();
    });

    subscriptionParamsChanged();
    $('#subscribeButton').attr('disabled',false);

    onTextChanged = {
        id: "#usersCount",
        onChange: function (element){
            //var itemsCount = $(element).val();
            subscriptionParamsChanged();
            $('#subscribeButton').show();
        }
    };

    $('#cancelSubscribtionButton').click(function(element){
        $(this).attr('disabled',true);
        var serviceProvider = $('#serviceProvider').val();
        $.ajax({
            url: '/cancel_subscription',
            type: 'POST',
            dataType : 'JSON',
            //context: form,
            data : {
                'serviceProvider' : serviceProvider
            },
            success: function(res){
                location.reload();
            },
            error: function(errorThrown){},
            complete: function(){}
        });
    });

    $('#createSubscription').submit(function (evt) {
        evt.preventDefault();
        evt.returnValue = false;

        var form = $(this);

        /*var customData = $('#customData').val();
        customData = JSON.parse(customData);*/

        var totalPrice = $('#subscriptionAmount').val();

        var subscriptionAction = $('#subscriptionAction').val();

        if(totalPrice == 0){
            $.ajax({
                url: '/?page=cancel_subscription',
                type: 'POST',
                dataType : 'JSON',
                data : {
                    'planId' : customData.plan_id,
                    'itemsCount' : customData.items_count,
                    'serviceProvider' : customData.service_provider
                },
                success: function(res)
                {
                    location.reload();
                },
                error: function(errorThrown){},
                complete: function() {}
            });
        }
        else if(subscriptionAction == 'updateSubscription'){
            $.ajax({
                url: '/cancel_subscription',
                type: 'POST',
                dataType : 'JSON',
                context: form,
                data : {
                    'serviceProvider' : customData.service_provider
                },
                success: function(res){},
                error: function(errorThrown){},
                complete: function() {
                    // make sure that you are no longer handling the submit event; clear handler
                    $(this).off('submit');
                    // actually submit the form
                    $(this).submit();
                }
            });
        }
        else if(subscriptionAction == 'createSubscription'){
            $(this).off('submit');
            // actually submit the form
            $(this).submit();
        }
        else{
            alert('Error. Wrong subscription action');
        }
    });
});