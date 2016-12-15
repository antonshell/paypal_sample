<?php

$config = Config::get();
$payPal = new PaypalSubscription($config);
$userService = new UserService();

//get selected user
$userId = $userService->getSelectedUserId();

$service_provider = 'getscorecard';
$activeSubscription = Subscription::getActiveSubscriptions($userId,$service_provider);

$subscriptionsArchive = Subscription::getSubscriptionsArchive($userId,$service_provider);

//$subscriptionPlan = new GetscorecardSubscriptionPlanPro();
$subscriptionPlan = new SubscriptionPlan();

//@TODO remake
//$service_provider = 'getscorecard';
//$plans = SubscriptionPlan::getGetscorecardSubscriptionPlans($service_provider);
$plans = SubscriptionPlan::getSubscriptionPlans($service_provider);

//$subscriptionsTempStatus = Subscription::getSubscriptionTemporaryStatus();

$usersCountDefault = 2;
$usersCount = ($activeSubscription != null) ? $activeSubscription['items_count'] : $usersCountDefault;
?>


<script type="text/javascript">
var subscriptionPlans = <?php echo json_encode($plans); ?>;
var serviceProvider = '<?php echo $service_provider; ?>';
</script>
<script src="ui/js/subscription.js"></script>
<script src="ui/js/textChanged.js"></script>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="subscriptionPlan">Subscription plan:</label>
            <select id="subscriptionPlan" name="subscriptionPlan" class="form-control">
                <?php foreach($plans as $plan): ?>
                    <?php

                    $selected = ($activeSubscription != null && $plan['id'] == $activeSubscription['plan_id']) ? ' selected' : ' ';
                    //$selected = '';
                    ?>

                    <option value="<?php echo $plan['id']; ?>" <?php echo $selected; ?> >
                        <?php echo ucfirst($plan['service_name']) . ' - $ ' . (int)$plan['price'] . ' / USER / MONTH'; ?>
                    </option>
                <?php endforeach; ?>
            </select> <!--<i></i>-->
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <label for="usersCount">Users:</label>
        <input id="usersCount" type="text" name="usersCount" value="<?php echo $usersCount; ?>" class="form-control">
    </div>

    <div class="col-md-3">
        <label for="totalPrice">Total Price:</label>
        <input id="totalPrice" type="text" name="totalPrice" value="" class="form-control" readonly>
    </div>
</div>

<br>

<div class="row">
    <div class="col-md-3">
        <input type="hidden" id="userId" value="<?php echo $userId; ?>">

        <form id="createSubscription" action="<?php echo $payPal->payNowButtonUrl; ?>" method="post" target="_top">
            <input type="hidden" name="cmd" value="_xclick-subscriptions">
            <input type="hidden" name="business" value="<?php echo $payPal->receiverEmail; ?>">
            <input type="hidden" name="lc" value="GB">

            <!--<input type="hidden" name="item_name" value="GetScorecard Pro">-->
            <input type="hidden" name="item_name" value="">
            <input type="hidden" name="no_note" value="1">
            <input type="hidden" name="no_shipping" value="1">

            <input type="hidden" name="src" value="1">
            <!--<input id="subscriptionAmount" type="hidden" name="a3" value="<?php /*echo (double)$subscriptionPlan['price'] * $subscriptionCustomData['items_count'] */?>">-->
            <input id="subscriptionAmount" type="hidden" name="a3" value="">
            <input type="hidden" name="p3" value="1">
            <input type="hidden" name="t3" value="M">

            <input type="hidden" name="return" value="<?php echo $config['app_url']; ?>/subscription/?status=success">

            <!--<input id="customData" type="hidden" name="custom" value='<?php /*echo json_encode($subscriptionCustomData); */?>'>-->
            <!--<input id="customData" type="hidden" name="custom" value="">-->
            <input id="customData" type="hidden" name="custom" value="">
            <input type="hidden" name="currency_code" value="USD">

            <?php
            if($activeSubscription){
                ?>
                <input type="hidden" id="subscriptionAction" value="updateSubscription">
                <button id="subscribeButton" type="submit" class="btn btn-primary pull-left" style="display: none;" disabled>
                    Update Subscription
                </button>
                <?php
            }
            else{
                ?>
                <input type="hidden" id="subscriptionAction" value="createSubscription">
                <button id="subscribeButton" type="submit" class="btn btn-primary pull-left" disabled>
                    Subscribe
                </button>
                <?php
            }
            ?>
        </form>

    </div>
    <div class="col-md-3">
        <?php if($activeSubscription): ?>
            <!--<form action="/?page=update_subscription" method="post">
                <input type="hidden" name="action" value="cancelSubscription">
                <input type="hidden" name="serviceProvider" value="<?php /*echo $service_provider; */?>">
                <button id="cancelSubscribtionButton" type="submit" class="btn btn-danger pull-right">
                    Cancel Subscription
                    <?php /*//echo ($scenario === 'modifySubscription') ? 'Change Subscription' : 'Subscribe'; */?>
                </button>
            </form>-->

            <input type="hidden" id="serviceProvider" value="<?php echo $service_provider; ?>">
            <button id="cancelSubscribtionButton" type="button" class="btn btn-danger pull-right">
                Cancel Subscription
            </button>
        <?php endif; ?>
    </div>
</div>

<br>

<?php if(count($subscriptionsArchive)): ?>
    <div class="row">
        <div class="table-responsive">
            <table class="table  table-bordered ">
                <thead>
                <tr>
                    <th>Subscription ID</th>
                    <th>Service Provider</th>
                    <th>Service Name</th>
                    <th>Status</th>
                    <th>Users Number</th>
                    <th>Price</th>
                    <th>Total Price</th>
                    <th>Period</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($subscriptionsArchive as $item): ?>
                    <tr>
                        <td><?php echo $item['subscription_id']; ?></td>
                        <td><?php echo $item['service_provider']; ?></td>
                        <td><?php echo $item['service_name']; ?></td>
                        <td><?php echo $item['status']; ?></td>
                        <td><?php echo $item['items_count']; ?></td>
                        <td><?php echo $item['price']; ?></td>
                        <td><?php echo $item['price'] * $item['items_count']; ?></td>
                        <td><?php echo $item['period']; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>