<?php

return [
    'TinkoffPayment' => [
        'file' => 'tinkoffpayment',
        'description' => '',
        'properties' => [
            'reqValue' => [
                'xtype' => 'textfield',
                'value' => 'card',
            ],
            'vats' => [
                'xtype' => 'numberfield',
                'value' => 0
            ],
            'enabledTaxation' => [
                'xtype' => 'combo-boolean',
                'value' => '0',
            ],
            'description' => [
                'xtype' => 'textfield',
                'value' => '',
            ],
            'status' => [
                'xtype' => 'numberfield',
                'value' => 2,
            ]
        ],
    ]
];