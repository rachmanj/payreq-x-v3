<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'gl' => [
        'url' => env('GL_URL'),
        'api_key' => env('GL_API_KEY'),
    ],

    'sap_bridge' => [
        'url' => env('SAP_BRIDGE_URL'),
        'api_key' => env('SAP_BRIDGE_API_KEY'),
        'timeout' => env('SAP_BRIDGE_TIMEOUT', 30),
    ],

    'lot' => [
        'base_url' => env('LOT_API_BASE_URL'),
        'search_endpoint' => env('LOT_API_SEARCH_ENDPOINT'),
        'claimed_search_endpoint' => env('LOT_API_CLAIMED_SEARCH_ENDPOINT'),
        'claim_endpoint' => env('LOT_API_CLAIM_ENDPOINT'),
        'detail_endpoint' => env('LOT_API_DETAIL_ENDPOINT', 'api/official-travels/detail'),
        'timeout' => env('LOT_API_TIMEOUT', 30),
    ],

    'sap' => [
        'server_url' => env('SAP_SERVER_URL'),
        'db_name' => env('SAP_DB_NAME'),
        'user' => env('SAP_USER'),
        'password' => env('SAP_PASSWORD'),
        'ar_invoice' => [
            'default_payment_terms' => env('SAP_AR_INVOICE_DEFAULT_PAYMENT_TERMS', 15),
            'default_revenue_account' => env('SAP_AR_INVOICE_DEFAULT_REVENUE_ACCOUNT', '41101'),
            'default_ar_account' => env('SAP_AR_INVOICE_DEFAULT_AR_ACCOUNT', '491'),
            'default_item_code' => env('SAP_AR_INVOICE_DEFAULT_ITEM_CODE', 'SERVICE'),
            'default_department_code' => env('SAP_AR_INVOICE_DEFAULT_DEPARTMENT_CODE', '60'),
            'default_wtax_code' => env('SAP_AR_INVOICE_DEFAULT_WTAX_CODE', ''),
            'wtax_percentage' => env('SAP_AR_INVOICE_WTAX_PERCENTAGE', 2), // Default 2%
            
            // Faktur Pajak fields (system-wide)
            'faktur_pajak' => [
                'authorized_name_invoice' => env('SAP_AR_INVOICE_AUTHORIZED_NAME_INVOICE', ''),
                'authorized_name_faktur_pajak' => env('SAP_AR_INVOICE_AUTHORIZED_NAME_FP', ''),
                'kode_transaksi_fp' => env('SAP_AR_INVOICE_KODE_TRANSAKSI_FP', '01'),
            ],
            
            // Bank account fields (system-wide)
            'bank_accounts' => [
                'usd' => [
                    'bank_name' => env('SAP_AR_INVOICE_BANK_NAME_USD', ''),
                    'bank_account' => env('SAP_AR_INVOICE_BANK_ACCOUNT_USD', ''),
                ],
                'idr' => [
                    'bank_name' => env('SAP_AR_INVOICE_BANK_NAME_IDR', ''),
                    'bank_account' => env('SAP_AR_INVOICE_BANK_ACCOUNT_IDR', ''),
                ],
            ],
        ],
    ],

];
