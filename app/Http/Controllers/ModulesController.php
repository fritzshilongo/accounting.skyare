<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Core\RequestContext;

class ModulesController extends Controller
{
    public function index(Request $request, RequestContext $context)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        if ($context->isLicenseIssuer()) {
            return redirect('/settings/license');
        }

        $modules = [
            [
                'id' => 'invoicing',
                'name' => 'Invoicing',
                'description' => 'Create and manage invoices',
                'icon' => 'fa-file-invoice',
                'route' => '/invoices',
                'status' => 'active',
            ],
            [
                'id' => 'estimates',
                'name' => 'Estimates',
                'description' => 'Manage quotes and estimates',
                'icon' => 'fa-file-alt',
                'route' => '/estimates',
                'status' => 'active',
            ],
            [
                'id' => 'clients',
                'name' => 'Clients',
                'description' => 'Manage client information',
                'icon' => 'fa-users',
                'route' => '/clients',
                'status' => 'active',
            ],
            [
                'id' => 'products',
                'name' => 'Products',
                'description' => 'Manage products and services',
                'icon' => 'fa-boxes',
                'route' => '/products',
                'status' => 'active',
            ],
            [
                'id' => 'payments',
                'name' => 'Payments',
                'description' => 'Record and track payments',
                'icon' => 'fa-credit-card',
                'route' => '/payments',
                'status' => 'active',
            ],
            [
                'id' => 'inventory',
                'name' => 'Inventory',
                'description' => 'Manage inventory and stock',
                'icon' => 'fa-warehouse',
                'route' => '/inventory',
                'status' => 'active',
            ],
            [
                'id' => 'expenses',
                'name' => 'Expenses',
                'description' => 'Track business expenses',
                'icon' => 'fa-receipt',
                'route' => '/expenses',
                'status' => 'active',
            ],
            [
                'id' => 'audit',
                'name' => 'Audit Trail',
                'description' => 'View system audit logs',
                'icon' => 'fa-search',
                'route' => '/audit',
                'status' => 'active',
            ],
            [
                'id' => 'credit',
                'name' => 'Credit / Loans',
                'description' => 'Issue credit facilities, manage repayments, and monitor your loan portfolio',
                'icon' => 'fa-hand-holding-dollar',
                'route' => '/credit-management',
                'status' => 'active',
            ],
            [
                'id' => 'credit-customers',
                'name' => 'Credit Customers',
                'description' => 'Manage credit clients, contact details, and customer records',
                'icon' => 'fa-user-shield',
                'route' => '/credit-customers',
                'status' => 'active',
            ],
        ];

        return view('modules.index', [
            'company' => $company,
            'modules' => $modules,
        ]);
    }
}
