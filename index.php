<?php
require_once 'vendor/autoload.php';

use app\classes\CCT;


$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();


$servidor = getenv('DB_HOST');
$usuario = getenv('DB_USER');
$senha = getenv('DB_PASS');
$banco = getenv('DB_DATABASE');

$credencialBase = [
    'BD_SERVIDOR'   => $servidor,
    'BD_USUARIO'    => $usuario,
    'BD_SENHA'      => $senha,
    'BD_BANCO'      => $banco,
];

$data = new CCT\cctv2($credencialBase);
$certData = $data->getData();
$senha = $certData['password'];
$cct = new CCT\CCT(
    $certData['ambient'],
    $certData['certificate'],
    'Tha@1109'
);

$token = $cct->autenticacao();
$data->updateToken($certData['id'], $token);

$dados = [
    'message_header' => [
        'id' => 'ZAA6W7RE',
        'name' => 'House Waybill',
        'type_code' => 703,
        'issue_datetime' => '2025-06-06T12:01:04Z',
        'purpose_code' => 'update',
        'version_id' => '3.00',
        'sender_party' => [
            'primary_id' => 'SEA CARGO AIR CARGO LOGISTICS INC',
            'scheme_id' => 'C'
        ],
        'recipient_party' => [
            'primary_id' => 'LINK PARTNER GESTAO DE COMERCIO',
            'scheme_id' => 'C'
        ]
    ],
    'business_header' => [
        'id' => 'ZAA6W7RE',
        'signatory_consignor' => 'LINK PARTNER GESTAO DE COMERCIO',
        'signatory_carrier' => [
            'actual_datetime' => '2025-06-06T12:01:04Z',
            'signatory' => 'Joao arriverdaci'
        ]
    ],
    'master_consignment' => [
        'gross_weight' => [
            'weight' => 6362.00,
            'unit_code' => 'KGM'
        ],
        'transport_contract' => [
            'id' => '047-08178251'
        ],
        'origin_location' => [
            'id' => 'GRU',
            'name' => 'Guarulhos International Airport'
        ],
        'final_destination' => [
            'id' => 'SJK',
            'name' => 'Sao Jose Dos Campos'
        ],
        'origin' => 'YYZ',
        'destination' => 'CNF',
        'house_consignment' => [
            'nil_carriage_value' => "false",
            'nil_customs_value' => "false",
            'nil_insurance_value' => "false",
            'total_charge_prepaid' => "true",
            'valuation_total_charge' => [
                'amount' => 0,
                'currency' => 'USD'
            ],
            'declared_value' => [
                'amount' => 0,
                'currency' => 'USD'
            ],
            'declared_value_customs' => [
                'amount' => 0,
                'currency' => 'USD'
            ],
            'insurance_value' => [
                'amount' => 0,
                'currency' => 'USD'
            ],
            'weight_total_charge' => [
                'amount' => 0,
                'currency' => 'USD'
            ],
            'tax_total_charge' => [
                'amount' => 0,
                'currency' => 'USD'
            ],
            'total_disbursement_prepaid' => "true",
            'agent_total_disbursement' => [
                'amount' => 100,
                'currency' => 'USD'
            ],
            'carrier_total_disbursement' => [
                'amount' => 10,
                'currency' => 'USD'
            ],
            'total_prepaid' => [
                'amount' => 30,
                'currency' => 'USD'
            ],
            'total_collect' => [
                'amount' => 0,
                'currency' => 'USD'
            ],
            'house_gross_weight' => [
                'value' => 60.000,
                'unit_code' => 'KGM'
            ],
            'totalQuantity' => 1,
            'total_piece_quantity' => 10,
            'summary_description' => 'Electronics and clothing',
            'freight_rate_type' => 'PPD',
            'consignor' => [
                'name' => 'ABC Electronics Inc.',
                'address' => [
                    'postcode' => '10001',
                    'street' => '123 Tech Street',
                    'city' => 'New York',
                    'country' => 'US'
                ],
                'contact' => [
                    'phone' => '+1 212 555 1234'
                ]
            ],
            'consignee' => [
                'name' => 'XYZ Imports Ltd.',
                'address' => [
                    'postcode' => 'W1A 1AA',
                    'street' => '456 Commerce Road',
                    'city' => 'London',
                    'country' => 'GB'
                ],
                'contact' => [
                    'phone' => '+44 20 7946 0958'
                ]
            ],
            'customs_notes' => [
                [
                    'content_code' => 'T',
                    'content' => 'CUSTOMSWAREHOUSE8941101',
                    'subject_code' => 'CCL',
                    'country' => 'BR'
                ]
            ],
            'package_quantity' => 1,
            'items' => [
                [
                    'sequence' => 0,
                    'type_code' => [
                        'value' => '',
                        'list_agency' => 76
                    ],
                    'gross_weight' => [
                        'value' => 10.5,
                        'unit_code' => 'KGM'
                    ],
                    'gross_volume' => [
                        'value' => 0.5,
                        'unit_code' => 'MTQ'
                    ],
                    'package_quantity' => 1,
                    'piece_quantity' => 2,
                    'description' => 'Laptop computers',
                    'freight_rate' => [
                        'chargeable_weight' => [
                            'value' => 10.5,
                            'unit_code' => 'KGM'
                        ],
                        'applied_amount' => [
                            'value' => 150.00,
                            'currency' => 'USD'
                        ]
                    ]
                ],
                [
                    'sequence' => 1,
                    'type_code' => [
                        'value' => '',
                        'list_agency' => 76
                    ],
                    'gross_weight' => [
                        'value' => 5.75,
                        'unit_code' => 'KGM'
                    ],
                    'gross_volume' => [
                        'value' => 0.25,
                        'unit_code' => 'MTQ'
                    ],
                    'package_quantity' => 1,
                    'piece_quantity' => 3,
                    'description' => 'Mobile phones',
                    'freight_rate' => [
                        'chargeable_weight' => [
                            'value' => 5.75,
                            'unit_code' => 'KGM'
                        ],
                        'applied_amount' => [
                            'value' => 75.00,
                            'currency' => 'USD'
                        ]
                    ]
                ]
            ]
        ]
    ]
];


function gerarXfzb($dados){
    $geraManifesto = new CCT\geraManifesto();
    $xml = $geraManifesto->geraXfzb($dados);
    echo $xml;
}

function gerarXfhl($dados){
    $geraManifesto = new CCT\geraManifesto();

    $dados['message_header']['name']            = 'Cargo Manifest';
    $dados['message_header']['type_code']       = 785;
    $dados['message_header']['purpose_code']    = 'creation';
    $dados['message_header']['version_id']      = 'Cargo Manifest';

   $xml = $geraManifesto->geraXfhl($dados);
   echo $xml;
}

gerarXfhl($dados);