<?php

declare(strict_types=1);

/**
 * Generate the sanitized EPFO office snapshot used by EpfoOfficeDirectorySeeder.
 *
 * Usage:
 * php database/seeders/support/generate_epfo_office_snapshot.php ../pf-contacts/contacts-data.json
 */
$sourcePath = $argv[1] ?? dirname(__DIR__, 4).'/pf-contacts/contacts-data.json';
$targetPath = dirname(__DIR__).'/data/epfo-offices.json';

if (! is_file($sourcePath)) {
    fwrite(STDERR, "Source file not found: {$sourcePath}\n");
    exit(1);
}

$sourceJson = file_get_contents($sourcePath);
$contacts = json_decode($sourceJson, true, flags: JSON_THROW_ON_ERROR);

$fieldCodes = [
    'AP', 'BG', 'BRJH', 'CNPD', 'DLUK', 'GJ', 'HR', 'KNGOA', 'KRLD', 'MBBD', 'MBTH',
    'MHEM', 'MPCG', 'NER', 'OR', 'PBHP', 'RJ', 'TL', 'TNEC', 'UPBR', 'WBANDSK',
];

$contacts = array_values(array_filter(
    $contacts,
    fn (array $contact): bool => ($contact['hierarchy_breadcrumbs'][0]['query_param'] ?? null) !== 'GH'
));

if (count($contacts) !== 304) {
    throw new RuntimeException('Expected 304 non-Guest House source records, found '.count($contacts).'.');
}

$queryValue = static function (array $contact): string {
    $query = (string) ($contact['query'] ?? '');
    $raw = str_contains($query, '=') ? substr($query, strpos($query, '=') + 1) : $query;

    return urldecode(str_replace('+', ' ', $raw));
};

$identityMap = [];
foreach ($contacts as $contact) {
    $identityMap[mb_strtolower(trim($queryValue($contact)))] = $contact['query'];
    $identityMap[mb_strtolower(trim((string) $contact['office_name_hierarchical']))] = $contact['query'];
}

$headOffice = null;
foreach ($contacts as $contact) {
    if (($contact['hierarchy_breadcrumbs'][0]['query_param'] ?? null) === 'HO') {
        $headOffice = $contact['query'];
        break;
    }
}

if (! $headOffice) {
    throw new RuntimeException('The Head Office source record is missing.');
}

$slug = static function (string $value): string {
    $value = preg_replace('/^D\.O\s*-\s*/i', '', trim($value));
    $value = preg_replace('/^SSO\s*-\s*/i', '', (string) $value);
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $value) ?: $value;
    $value = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '.', (string) $value));

    return trim(substr(trim($value, '.'), 0, 48), '.');
};

$emailCandidates = static function (?string $value): array {
    if (! $value) {
        return [];
    }

    preg_match_all('/[a-z0-9._%+\-]+@epfindia\.gov\.in/i', $value, $matches);

    return array_values(array_unique(array_map('strtolower', $matches[0] ?? [])));
};

$knownCodes = [
    'q=HEAD+OFFICE' => 'HO-EPFO-DEMO',
    'q=VIGILANCE+WING' => 'VIG-HQ-DEMO',
    'q=INTERNAL+AUDIT+WING' => 'IAW-DEMO',
    'q=NATIONAL+DATA+CENTRE' => 'NDC',
    'q=PDUNASS' => 'PDUNASS-DEMO',
    'q=ZONAL+TRAINING+INSTITUTE,+NORTH+ZONE' => 'ZTI-NORTH-DEMO',
    'q=ZONAL+TRAINING+INSTITUTE,+SOUTH+ZONE' => 'ZTI-SOUTH-DEMO',
    'q=ZONAL+TRAINING+INSTITUTE,+EAST+ZONE' => 'ZTI-EAST-DEMO',
    'q=ZONAL+TRAINING+INSTITUTE,+WEST+ZONE' => 'ZTI-WEST-DEMO',
];

$usedEmails = [];
$offices = [];

foreach ($contacts as $sourceIndex => $contact) {
    $top = $contact['hierarchy_breadcrumbs'][0]['query_param'] ?? null;
    $depth = count($contact['hierarchy_breadcrumbs'] ?? []);
    $name = trim((string) $contact['office_name_hierarchical']);

    [$category, $type, $directoryDepth] = match (true) {
        $top === 'HO' => ['head_office', 'HEAD_OFFICE', 0],
        $top === 'VW' => ['vigilance_wing', 'VIGILANCE_HQ', 1],
        $top === 'IAW' => ['internal_audit_wing', 'INTERNAL_AUDIT_WING', 1],
        $top === 'NDC' => ['national_data_centre', 'NDC', 1],
        $top === 'ApexBodies' => ['pdunass_natrss', 'PDUNASS', 1],
        $top === 'PDUNASS' && str_starts_with(strtolower($name), 'sub zonal') => ['sub_zonal_training_institute', 'ZTI', 2],
        $top === 'PDUNASS' => ['zonal_training_institute', 'ZTI', 2],
        $top === 'HH' => ['holiday_home', 'HOLIDAY_HOME', 1],
        in_array($top, $fieldCodes, true) && $depth === 1 => ['zonal_office', 'ZONAL_OFFICE', 1],
        in_array($top, $fieldCodes, true) && $depth === 2 => ['regional_office', 'REGIONAL_OFFICE', 2],
        str_starts_with(strtoupper($name), 'SSO') => ['special_state_office', 'SPECIAL_STATE_OFFICE', 3],
        default => ['district_office', 'DISTRICT_OFFICE', 3],
    };

    if ($category === 'head_office') {
        $parentSourceKey = null;
    } elseif (in_array($category, [
        'zonal_office', 'vigilance_wing', 'internal_audit_wing', 'national_data_centre',
        'pdunass_natrss', 'holiday_home',
    ], true)) {
        $parentSourceKey = $headOffice;
    } elseif (in_array($category, ['zonal_training_institute', 'sub_zonal_training_institute'], true)) {
        $parentSourceKey = $identityMap['pdunass'] ?? null;
    } else {
        $parentIdentity = mb_strtolower(trim((string) end($contact['hierarchy_breadcrumbs'])['query_param']));
        $parentSourceKey = $identityMap[$parentIdentity] ?? null;
    }

    if ($category !== 'head_office' && ! $parentSourceKey) {
        throw new RuntimeException("Unable to resolve parent for {$name} ({$contact['query']}).");
    }

    $prefix = match ($category) {
        'head_office' => 'HO',
        'zonal_office' => 'ZO',
        'regional_office' => 'RO',
        'district_office' => 'DO',
        'special_state_office' => 'SSO',
        'vigilance_wing' => 'VIG',
        'internal_audit_wing' => 'IAW',
        'national_data_centre' => 'NDC',
        'pdunass_natrss' => 'PDUNASS',
        'zonal_training_institute' => 'ZTI',
        'sub_zonal_training_institute' => 'SZTI',
        'holiday_home' => 'HH',
    };

    $code = $knownCodes[$contact['query']]
        ?? $prefix.'-'.strtoupper(substr(str_replace('.', '-', $slug($name)), 0, 36)).'-'.strtoupper(substr(sha1($contact['query']), 0, 7));

    $candidates = $emailCandidates($contact['office']['office_email'] ?? null);
    $preferredPrefix = match ($category) {
        'regional_office' => 'ro.',
        'district_office' => 'do.',
        'special_state_office' => 'sso.',
        default => null,
    };

    $managerEmail = null;
    if ($preferredPrefix) {
        foreach ($candidates as $candidate) {
            if (str_starts_with(strstr($candidate, '@', true), $preferredPrefix)) {
                $managerEmail = $candidate;
                break;
            }
        }
    } else {
        $managerEmail = $candidates[0] ?? null;
    }

    $managerEmail ??= match ($category) {
        'head_office' => 'inventory.ho@epfindia.gov.in',
        'zonal_office' => 'zo.'.$slug($name).'@epfindia.gov.in',
        'regional_office' => 'ro.'.$slug($name).'@epfindia.gov.in',
        'district_office' => 'do.'.$slug($name).'@epfindia.gov.in',
        'special_state_office' => 'sso.'.$slug($name).'@epfindia.gov.in',
        'vigilance_wing' => 'cvo@epfindia.gov.in',
        'internal_audit_wing' => 'acc.audit@epfindia.gov.in',
        'national_data_centre' => 'acc.is@epfindia.gov.in',
        'pdunass_natrss' => 'natrss@epfindia.gov.in',
        'zonal_training_institute' => 'zti.'.$slug($name).'@epfindia.gov.in',
        'sub_zonal_training_institute' => 'szti.'.$slug($name).'@epfindia.gov.in',
        'holiday_home' => 'holidayhome.'.$slug($name).'@epfindia.gov.in',
    };

    if (isset($usedEmails[$managerEmail])) {
        $localPart = strstr($managerEmail, '@', true);
        $managerEmail = $localPart.'.'.substr(sha1($contact['query']), 0, 7).'@epfindia.gov.in';
    }
    $usedEmails[$managerEmail] = true;

    $siteCode = $category === 'national_data_centre' ? 'NDC_HQ' : substr($code.'-SITE', 0, 190);

    $offices[] = [
        'source_key' => $contact['query'],
        'parent_source_key' => $parentSourceKey,
        'source_index' => $sourceIndex,
        'directory_depth' => $directoryDepth,
        'code' => $code,
        'name' => $name,
        'category' => $category,
        'unit_type' => $type,
        'address' => trim((string) ($contact['office']['office_address'] ?? '')),
        'source_office_email' => trim((string) ($contact['office']['office_email'] ?? '')) ?: null,
        'manager_email' => $managerEmail,
        'site_code' => $siteCode,
        'location_code' => substr($code.'-OFFICE', 0, 190),
    ];
}

usort($offices, fn (array $left, array $right): int => [$left['directory_depth'], $left['source_index']] <=> [$right['directory_depth'], $right['source_index']]);

if (count(array_unique(array_column($offices, 'manager_email'))) !== 304) {
    throw new RuntimeException('Generated manager email addresses are not unique.');
}

$payload = [
    'source' => '../pf-contacts/contacts-data.json',
    'source_sha256' => hash('sha256', $sourceJson),
    'excluded_categories' => ['guest_house'],
    'record_count' => count($offices),
    'offices' => $offices,
];

if (! is_dir(dirname($targetPath))) {
    mkdir(dirname($targetPath), 0777, true);
}

file_put_contents(
    $targetPath,
    json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL
);

fwrite(STDOUT, "Generated {$payload['record_count']} offices at {$targetPath}\n");
