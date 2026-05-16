<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Core\RequestContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientController extends Controller
{
    public function index(RequestContext $context)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $type = request()->query('type');
        $status = request()->query('status');
        $search = trim((string) request()->query('search'));

        $clients = Client::where('company_id', $companyId)
            ->type($type)
            ->status($status)
            ->search($search)
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('clients.index', compact('clients', 'type', 'status', 'search'));
    }

    public function export(Request $request, RequestContext $context)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $type = $request->query('type');
        $status = $request->query('status');
        $search = trim((string) $request->query('search'));

        $clients = Client::where('company_id', $companyId)
            ->type($type)
            ->status($status)
            ->search($search)
            ->orderBy('name')
            ->get();

        $csvData = "Name,Type,Email,Phone,Status,Outstanding,Company,Contact Person,VAT,Tax,Registration,Created At\n";
        foreach ($clients as $client) {
            $csvData .= sprintf(
                '"%s","%s","%s","%s","%s",%s,"%s","%s","%s","%s","%s","%s"\n',
                str_replace('"', '""', $client->name),
                ucfirst($client->type),
                $client->email ?? '',
                $client->phone ?? '',
                ucfirst($client->status),
                number_format($client->outstanding, 2),
                str_replace('"', '""', $client->name),
                str_replace('"', '""', $client->contact_person ?? ''),
                $client->vat_number ?? '',
                $client->tax_number ?? '',
                $client->registration_number ?? '',
                $client->created_at ? $client->created_at->toDateString() : ''
            );
        }

        $filename = 'clients_' . date('Ymd_His') . '.csv';

        return response($csvData, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function create()
    {
        return view('clients.create');
    }

    public function store(Request $request, RequestContext $context)
    {
        $validated = $request->validate([
            'type' => 'required|in:individual,company',
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:1000',
            'vat_number' => 'nullable|string|max:100',
            'tax_number' => 'nullable|string|max:100',
            'registration_number' => 'nullable|string|max:100',
        ]);

        $validated['company_id'] = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        Client::create($validated);
        return redirect('/clients')->with('success', 'Client created successfully.');
    }

    public function show($id, RequestContext $context)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $client = Client::where('company_id', $companyId)->with('invoices')->findOrFail($id);
        return view('clients.show', compact('client'));
    }

    public function edit($id, RequestContext $context)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $client = Client::where('company_id', $companyId)->findOrFail($id);
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, $id, RequestContext $context)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $client = Client::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'type' => 'required|in:individual,company',
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:1000',
            'vat_number' => 'nullable|string|max:100',
            'tax_number' => 'nullable|string|max:100',
            'registration_number' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        $client->update($validated);
        return redirect('/clients')->with('success', 'Client updated successfully.');
    }

    public function destroy($id, RequestContext $context)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $client = Client::where('company_id', $companyId)->findOrFail($id);
        $client->update(['status' => 'inactive']);

        return redirect('/clients')->with('success', 'Client disabled (status set to inactive).');
    }

    public function toggleStatus($id, RequestContext $context)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $client = Client::where('company_id', $companyId)->findOrFail($id);
        $newStatus = $client->status === 'active' ? 'inactive' : 'active';
        $client->update(['status' => $newStatus]);

        return redirect('/clients')->with('success', 'Client status changed to ' . $newStatus . '.');
    }
}

