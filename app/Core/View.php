<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = []): void
    {
        $viewPath = __DIR__ . '/../Views/' . $view . '.php';
        if (!is_file($viewPath)) {
            http_response_code(500);
            echo 'View not found.';
            return;
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewPath;
        $output = (string) ob_get_clean();

        echo self::injectCompanySwitcher($output, $data);
    }

    private static function injectCompanySwitcher(string $output, array $data): string
    {
        $session = $_SESSION ?? [];

        if (!is_array($session) || !isset($session['user']) || stripos($output, '</body>') === false) {
            return $output;
        }

        $companies = $data['available_companies'] ?? ($session['available_companies'] ?? []);
        if (!is_array($companies) || $companies === []) {
            return $output;
        }

        $currentCompanyId = (int) ($data['company']['company_id'] ?? ($session['user']['company_id'] ?? 0));
        $switcher = self::renderCompanySwitcher($companies, $currentCompanyId);

        return preg_replace('/<\/body>/i', $switcher . '</body>', $output, 1) ?? $output;
    }

    private static function renderCompanySwitcher(array $companies, int $currentCompanyId): string
    {
        $options = '<option value="">Switch company...</option>';

        foreach ($companies as $company) {
            if (!is_array($company)) {
                continue;
            }

            $companyId = (int) ($company['company_id'] ?? 0);
            $name = htmlspecialchars((string) ($company['company_name'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8');
            $subdomain = trim((string) ($company['subdomain'] ?? ''));
            $label = $subdomain !== '' ? $name . ' (' . htmlspecialchars($subdomain, ENT_QUOTES, 'UTF-8') . ')' : $name;
            $url = htmlspecialchars((string) ($company['tenant_url'] ?? ''), ENT_QUOTES, 'UTF-8');
            $selected = $companyId === $currentCompanyId ? ' selected' : '';

            $options .= '<option value="' . $url . '"' . $selected . '>' . $label . '</option>';
        }

        return <<<HTML
<div style="position:fixed;top:16px;right:16px;z-index:9999;background:#ffffff;border:1px solid rgba(0,0,0,0.08);border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.12);padding:12px 14px;min-width:280px;">
    <label for="global-company-switcher" style="display:block;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;margin-bottom:6px;">Company</label>
    <select id="global-company-switcher" onchange="if (this.value) window.location.href = this.value;" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:10px;background:#fff;">
        {$options}
    </select>
</div>
HTML;
    }
}
