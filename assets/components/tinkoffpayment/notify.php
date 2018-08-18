<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config.core.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

$modx = new modx();
$modx->initialize('web');

$data = json_decode(file_get_contents('php://input'), true);

if ($data['Success']) {

    $modelpath = $modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/tinkoffpayment/model/';
    $modx->loadClass('TinkoffPayment', $modelpath, true, true);

    $modx->addPackage('shopkeeper3', $modx->getOption('core_path') . 'components/shopkeeper3/model/');
    $order = $modx->getObject('shk_order', $data['OrderId']);

    if (!$order) exit();

    $TinkoffPayment = new TinkoffPayment($modx);

    $out = $TinkoffPayment->notification($data['Token'], $data);

    if ($out && $data['Status'] = 'CONFIRMED') {
        $new_status = $modx->getOption($TinkoffPayment->namespace . '_change_status');

        $change_status = $order->set('status', $new_status);
        $order->save();

        $modx->invokeEvent('OnSHKChangeStatus', array(
            'order_id' => $data['OrderId'],
            'status' => $new_status
        ));
    }
}

$TinkoffPayment->log($data);
exit('OK');
