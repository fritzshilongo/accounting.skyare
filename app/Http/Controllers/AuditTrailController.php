<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Core\RequestContext;
use App\Core\Database;
use App\Models\Company;

class AuditTrailController extends Controller
{
    private function isLicenseIssuer(RequestContext $context): bool
    {
        return $context->isLicenseIssuer();
    }

    public function index(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $companyId = (int) $company['company_id'];
        $requestedCompanyId = (int) $request->query('company_id', 0);

        if ($requestedCompanyId > 0 && $this->isLicenseIssuer($context)) {
            $requestedCompany = (new Company($db->pdo()))->findById($requestedCompanyId);
            if ($requestedCompany) {
                $company = $requestedCompany;
                $companyId = $requestedCompanyId;
            } else {
                return response('Company not found', 404);
            }
        }

        $logs = [];

        $action = trim((string) $request->query('action', ''));
        $method = strtoupper(trim((string) $request->query('method', '')));
        $search = trim((string) $request->query('search', ''));
        $from = trim((string) $request->query('from', ''));
        $to = trim((string) $request->query('to', ''));

        try {
            $pdo = $db->pdo();
            $sql = 'SELECT * FROM audit_logs WHERE company_id = :company_id';
            $params = ['company_id' => $companyId];

            if ($action !== '') {
                $sql .= ' AND action_key = :action';
                $params['action'] = $action;
            }

            if (in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                $sql .= ' AND action_key = :method_action';
                $params['method_action'] = 'request.' . strtolower($method);
            }

            if ($search !== '') {
                $sql .= ' AND (details LIKE :search1 OR entity_id LIKE :search2 OR entity_type LIKE :search3)';
                $params['search1'] = '%' . $search . '%';
                $params['search2'] = '%' . $search . '%';
                $params['search3'] = '%' . $search . '%';
            }

            if ($from !== '') {
                $sql .= ' AND created_at >= :from_date';
                $params['from_date'] = $from . ' 00:00:00';
            }

            if ($to !== '') {
                $sql .= ' AND created_at <= :to_date';
                $params['to_date'] = $to . ' 23:59:59';
            }

            $sql .= ' ORDER BY created_at DESC LIMIT 250';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $logs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Audit logs fetch error: " . $e->getMessage());
        }

        return view('audit.index', [
            'company' => $company,
            'logs' => $logs,
            'filters' => [
                'action' => $action,
                'method' => $method,
                'search' => $search,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }
}
