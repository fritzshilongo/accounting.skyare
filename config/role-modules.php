<?php

return [
    'admin' => ['*'],
    'manager' => [
        'customers',
        'products',
        'invoices',
        'inventory',
        'audit_trail',
        'sales',
        'estimates',
        'credit_management',
    ],
    'creditor' => [
        'customers',
        'credit_management',
        'reports',
    ],
    'staff' => [
        'customers',
        'products',
        'invoices',
        'inventory',
        'audit_trail',
    ],
    'viewer' => [
        'customers',
        'products',
        'invoices',
        'inventory',
        'audit_trail',
        'reports',
    ],
];
