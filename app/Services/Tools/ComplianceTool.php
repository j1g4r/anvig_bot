<?php

namespace App\Services\Tools;

use Illuminate\Support\Facades\File;

class ComplianceTool implements ToolInterface
{
    public function name(): string
    {
        return 'compliance_check';
    }

    public function description(): string
    {
        return 'Scan content or files for compliance risks like PII (Personally Identifiable Information) or check software licenses.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['scan_pii', 'check_licenses'],
                    'description' => 'The compliance action to perform.',
                ],
                'target' => [
                    'type' => 'string',
                    'description' => 'For scan_pii: The text content OR file path to scan. For check_licenses: The directory path to audit.',
                ],
            ],
            'required' => ['action', 'target'],
        ];
    }

    public function execute(array $arguments): string
    {
        $action = $arguments['action'];
        $target = $arguments['target'];

        if ($action === 'scan_pii') {
            return $this->scanPii($target);
        }

        if ($action === 'check_licenses') {
            return $this->checkLicenses($target);
        }

        return "Unknown action: $action";
    }

    protected function scanPii(string $target): string
    {
        // If target is a file, read it
        $content = $target;
        if (file_exists($target)) {
            $content = file_get_contents($target);
        }

        $risks = [];

        // Email Pattern
        if (preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $content, $matches)) {
            $risks['emails'] = array_unique($matches[0]);
        }

        // IPv4 Pattern
        if (preg_match_all('/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', $content, $matches)) {
            $risks['ips'] = array_unique($matches[0]);
        }

        // Credit Card (Simple Luhn-like sequence check, 13-16 digits)
        if (preg_match_all('/\b(?:\d[ -]*?){13,16}\b/', $content, $matches)) {
            // Filter out obvious non-CCs (like timestamps)
            $ccs = array_filter($matches[0], fn($n) => preg_match('/^\d+$/', str_replace(['-', ' '], '', $n)));
            if (!empty($ccs)) {
                $risks['credit_cards_potential'] = array_map(fn($c) => substr($c, 0, 4) . '********', $ccs); // Redact
            }
        }

        if (empty($risks)) {
            return "✅ COMPLIANCE PASSED: No PII (Emails, IPs, Credit Cards) detected in the target.";
        }

        return "⚠️ COMPLIANCE RISK DETECTED:\n" . json_encode($risks, JSON_PRETTY_PRINT);
    }

    protected function checkLicenses(string $path): string
    {
        if (!is_dir($path)) {
            return "Error: Target directory '$path' not found.";
        }

        $report = [];

        // Check LICENSE file
        $licenseFiles = glob($path . '/LICENSE*');
        if (!empty($licenseFiles)) {
            $report['root_license'] = basename($licenseFiles[0]);
        } else {
            $report['root_license'] = 'MISSING ⚠️';
        }

        // PHP Composer
        if (file_exists($path . '/composer.json')) {
            $json = json_decode(file_get_contents($path . '/composer.json'), true);
            $report['composer_license'] = $json['license'] ?? 'Not specified in composer.json';
        }

        // Node NPM
        if (file_exists($path . '/package.json')) {
            $json = json_decode(file_get_contents($path . '/package.json'), true);
            $report['npm_license'] = $json['license'] ?? 'Not specified in package.json';
        }

        return "🔍 LICENSE AUDIT for $path:\n" . json_encode($report, JSON_PRETTY_PRINT);
    }
}
