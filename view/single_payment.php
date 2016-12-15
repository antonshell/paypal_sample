<?php
/**
 * Created by PhpStorm.
 * User: Antonshell
 * Date: 15.08.2015
 * Time: 17:59
 */

$payPal = new PaypalSinglePayment();
$userService = new UserService();

// get extraservices
$extraServices = ExtraService::getList();

//get selected user
$userId = $userService->getSelectedUser();

// get payments history
$payments = ExtraServiceUsers::getPaymentHistory($userId);

$config = Config::get();
?>

<script type="text/javascript">
    var extraServices = <?php echo json_encode($extraServices); ?>;
</script>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="extra_service">Extra Service</label>
            <select id="extra_service" class="form-control">
                <?php foreach($extraServices as $extraService): ?>
                    <option value="<?php echo $extraService['id']; ?>">
                        <?php echo ucfirst($extraService['service_provider']) . ' ' . ucfirst($extraService['service_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="quantity">Quantity</label>
            <input type="text" id="quantity" class="form-control" value="">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="price">Price - $</label>
            <input type="text" id="price" class="form-control" value="" readonly>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="totalPrice">Total - $</label>
            <input type="text" id="totalPrice" class="form-control" value="" readonly>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">

        <input type="hidden" id="service_provider">
        <input type="hidden" id="service_name">
        <input type="hidden" id="userId" value="<?php echo $userId; ?>">

        <form action="<?php echo $payPal->payNowButtonUrl; ?>" method="post">
            <input type="hidden" name="cmd" value="_xclick">
            <input type="hidden" name="business" value="<?php echo $payPal->receiverEmail; ?>">
            <input id="paypalItemName" type="hidden" name="item_name" value="">
            <input id="paypalQuantity" type="hidden" name="quantity" value="">
            <input id="paypalAmmount" type="hidden" name="amount" value="">
            <input type="hidden" name="no_shipping" value="1">
            <input type="hidden" name="return" value="<?php echo $config['app_url']; ?>/?page=single_payment">

            <input id="paypalCustomData" type="hidden" name="custom" value='<?php //echo json_encode($paypalCustomData); ?>'>

            <input type="hidden" name="currency_code" value="USD">
            <input type="hidden" name="lc" value="US">
            <input type="hidden" name="bn" value="PP-BuyNowBF">

            <button id="PayNowButton" type="submit" class="btn btn-primary pull-left" disabled>
                <i class="fa fa-paypal"></i> &nbsp; Pay Now
            </button>
        </form>
    </div>
</div>

<br>

<?php if(count($payments)): ?>
    <div class="row">
        <div class="table-responsive">
            <table class="table  table-bordered ">
                <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Service Provider</th>
                    <th>Service Name</th>
                    <th>Payment Date</th>
                    <th>Payer</th>
                    <th>Amount</th>
                    <th>Quantity</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($payments as $item): ?>
                    <?php $paymentDate = date('Y-m-d H:i',strtotime($item['payment_date'])); ?>
                    <tr>
                        <td><?php echo $item['txn_id'] ?></td>
                        <td><?php echo $item['service_provider'] ?></td>
                        <td><?php echo $item['service_name'] ?></td>
                        <td><?php echo $paymentDate ?></td>
                        <td><?php echo $item['payer_email'] ?></td>
                        <td><?php echo $item['mc_gross'].' '.$item['mc_currency'] ?></td>
                        <td><?php echo $item['quantity'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>