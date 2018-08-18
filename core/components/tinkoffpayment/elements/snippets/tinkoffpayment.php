<?php
$reqValue = $modx->getOption('reqValue', $scriptProperties, 'card');
$vats = $modx->getOption('vats', $scriptProperties, 'none');
$desc_payment = $modx->getOption('description', $scriptProperties, '');
$status_reg = $modx->getOption('status', $scriptProperties, 2);


$modelpath = $modx->getOption("core_path", null, MODX_CORE_PATH) . "components/tinkoffpayment/model/";
$modx->loadClass('TinkoffPayment', $modelpath, true, true);

$TinkoffPayment = new TinkoffPayment($modx);

if ($reqValue == $_POST['payment']) {

    //получаем корзину shopkeeper
    $order_id =  $_SESSION['shk_lastOrder']['id'];
    $order = $modx->getObject('shk_order', $order_id);
    if( !$order ){
        return '';
    }
    $purchases = $modx->getCollection('shk_purchases', array('order_id' => $order->id));

    $amount = 0;
    $items = array();

    if (!empty($purchases)) {
        foreach ($purchases as $item) {
            $amount += $item->price;
            $tmp_item = array(
                'Name' => $item->name,
                'Price' => $item->price * 100,
                'Quantity' => $item->count,
                'Amount' => $item->price * $item->count * 100
            );
            if ($enabledTaxation) $tmp_item['Tax'] = 'vat'.$vats;
            array_push($items, $tmp_item);
        }
    }

    if (!isset($_POST['email'])) {
        $profile = $modx->user->getOne('Profile');
        $email = ($profile) ? $profile->get('email') : '';
    } else {
        $email = $_POST['email'];
    }

    //аргументы для передачи запроса, более подробно: https://oplata.tinkoff.ru/landing/develop/documentation/Init
    $args = array(
        'Amount' => ($amount + $order->get('delivery_price')) * 100, //число в копейках
        'Email' => $email,
        'Items' => $items,
        'Description' => $desc_payment,
        'OrderId' => $order_id
    );

    $result = $TinkoffPayment->sendRequest($args);

    if (!isset($TinkoffPayment->response['error'])) {
        $status = $order->set('status', $status_reg);
        $order->save();
        $modx->sendRedirect($TinkoffPayment->response['paymentUrl']);
    }
}