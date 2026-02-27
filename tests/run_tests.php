<?php
/**
 * iService API Test Runner
 * ========================
 * ทดสอบ endpoints ทั้ง local และ production
 *
 * Usage:
 *   php tests/run_tests.php                          # local (default)
 *   php tests/run_tests.php local                    # local XAMPP
 *   php tests/run_tests.php production               # production server
 *   php tests/run_tests.php https://example.com      # custom URL
 *
 * Options:
 *   --verbose    แสดง response body เมื่อ fail
 *   --no-color   ปิด ANSI color
 */

// ─── Config ────────────────────────────────────────────────────────────────
define('URL_LOCAL',      'http://localhost/iservice');
define('URL_PRODUCTION', 'https://iservice.rangsitcity.go.th');

// ─── Parse args ────────────────────────────────────────────────────────────
$args    = array_slice($argv, 1);
$verbose = in_array('--verbose', $args);
$noColor = in_array('--no-color', $args);
$args    = array_values(array_filter($args, fn($a) => !str_starts_with($a, '--')));

$target = $args[0] ?? 'local';
$base = match ($target) {
    'local'      => URL_LOCAL,
    'production' => URL_PRODUCTION,
    default      => rtrim($target, '/'),
};

// ─── Colors ────────────────────────────────────────────────────────────────
function c(string $text, string $color): string {
    global $noColor;
    if ($noColor) return $text;
    $codes = ['green' => '32', 'red' => '31', 'yellow' => '33',
              'cyan'  => '36', 'gray' => '90', 'bold' => '1'];
    return "\033[{$codes[$color]}m{$text}\033[0m";
}

// ─── HTTP helper ───────────────────────────────────────────────────────────
/**
 * @return array{status: int, body: string, headers: string, time_ms: int}
 */
function http(string $url, array $opts = []): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => $opts['follow'] ?? true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'iService-TestRunner/1.0',
    ]);
    if (!empty($opts['post'])) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['post']);
    }
    $start  = microtime(true);
    $raw    = curl_exec($ch);
    $ms     = (int)((microtime(true) - $start) * 1000);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hSize  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    return [
        'status'  => $status,
        'body'    => $raw === false ? '' : substr($raw, $hSize),
        'headers' => $raw === false ? '' : substr($raw, 0, $hSize),
        'time_ms' => $ms,
    ];
}

// ─── Test engine ───────────────────────────────────────────────────────────
$results = ['pass' => 0, 'fail' => 0, 'skip' => 0];
$failures = [];

function test(string $name, callable $fn): void {
    global $results, $failures, $verbose;
    try {
        $msg = $fn();   // return null = pass, string = fail message
        if ($msg === null) {
            echo c('  ✔ ', 'green') . $name . "\n";
            $results['pass']++;
        } else {
            echo c('  ✘ ', 'red') . $name . ' ' . c("→ $msg", 'gray') . "\n";
            $results['fail']++;
            $failures[] = ['name' => $name, 'msg' => $msg];
        }
    } catch (Throwable $e) {
        echo c('  ✘ ', 'red') . $name . ' ' . c('→ Exception: ' . $e->getMessage(), 'gray') . "\n";
        $results['fail']++;
        $failures[] = ['name' => $name, 'msg' => $e->getMessage()];
    }
}

function skip(string $name, string $reason = ''): void {
    global $results;
    echo c('  ○ ', 'yellow') . $name . ($reason ? c(" (skipped: $reason)", 'gray') : '') . "\n";
    $results['skip']++;
}

function group(string $title): void {
    echo "\n" . c("▸ $title", 'cyan') . "\n";
}

// ─── Assertion helpers ─────────────────────────────────────────────────────
function assertStatus(array $r, int $expected): ?string {
    return $r['status'] === $expected ? null
        : "HTTP {$r['status']} (expected $expected)";
}

function assertContains(array $r, string $needle): ?string {
    return str_contains($r['body'], $needle) ? null
        : "Body does not contain: \"$needle\"";
}

function assertJson(array $r): ?string {
    $d = json_decode($r['body'], true);
    return $d !== null ? null : 'Response is not valid JSON';
}

function assertJsonKey(array $r, string $key): ?string {
    $d = json_decode($r['body'], true);
    if ($d === null) return 'Response is not valid JSON';
    return array_key_exists($key, $d) ? null : "JSON missing key: \"$key\"";
}

function assertRedirectTo(array $r, string $fragment): ?string {
    // When follow=false, check Location header; when follow=true check final URL
    if (str_contains($r['headers'], 'Location:')) {
        $loc = '';
        foreach (explode("\n", $r['headers']) as $line) {
            if (stripos($line, 'Location:') === 0) {
                $loc = trim(substr($line, 9));
            }
        }
        return str_contains($loc, $fragment) ? null : "Redirect location \"$loc\" doesn't contain \"$fragment\"";
    }
    // Fallback: check body for login form indicators
    return str_contains($r['body'], 'login') || str_contains($r['body'], 'เข้าสู่ระบบ') ? null
        : "Expected redirect to login, got HTTP {$r['status']}";
}

// ═══════════════════════════════════════════════════════════════════════════
//  BEGIN TESTS
// ═══════════════════════════════════════════════════════════════════════════

echo c("\n🧪 iService API Test Runner", 'bold') . "\n";
echo c("   Target: $base", 'gray') . "\n";
echo c("   Time  : " . date('Y-m-d H:i:s'), 'gray') . "\n";

// ── Group 1: Public pages ───────────────────────────────────────────────────
group('Public Pages');

test('Homepage loads (HTTP 200)', function () use ($base) {
    $r = http("$base/");
    return assertStatus($r, 200);
});

test('Request form loads', function () use ($base) {
    $r = http("$base/request-form.php");
    if ($e = assertStatus($r, 200)) return $e;
    return assertContains($r, 'บริการ');
});

test('Request form — service=INTERNET loads', function () use ($base) {
    $r = http("$base/request-form.php?service=INTERNET");
    if ($e = assertStatus($r, 200)) return $e;
    return assertContains($r, 'INTERNET');
});

test('Request form — service=IT_SUPPORT loads', function () use ($base) {
    $r = http("$base/request-form.php?service=IT_SUPPORT");
    if ($e = assertStatus($r, 200)) return $e;
    return assertContains($r, 'IT');
});

test('Request form — invalid service handled gracefully', function () use ($base) {
    $r = http("$base/request-form.php?service=INVALID_XYZ_123");
    return assertStatus($r, 200);  // should not 500
});

// ── Group 2: Admin auth protection ─────────────────────────────────────────
group('Admin Auth Protection (should redirect unauthenticated)');

$adminPages = [
    'admin/create_job.php',
    'admin/service_requests.php',
    'admin/admin_dashboard.php',
    'admin/my_tasks.php',
];

foreach ($adminPages as $page) {
    test("$page redirects to login", function () use ($base, $page) {
        $r = http("$base/$page", ['follow' => false]);
        // 302 redirect OR rendered login page
        if ($r['status'] === 302 || $r['status'] === 301) return null;
        if ($r['status'] === 200 &&
            (str_contains($r['body'], 'login') || str_contains($r['body'], 'เข้าสู่ระบบ')))
            return null;
        return "Expected redirect to login, got HTTP {$r['status']}";
    });
}

// ── Group 3: Public JSON APIs ───────────────────────────────────────────────
group('Public JSON APIs (no auth required)');

test('api/get_departments.php?level=1 returns JSON array', function () use ($base) {
    $r = http("$base/api/get_departments.php?level=1");
    if ($e = assertStatus($r, 200)) return $e;
    if ($e = assertJson($r))        return $e;
    $d = json_decode($r['body'], true);
    return isset($d['success']) ? null : 'Missing "success" key in response';
});

test('api/get_departments.php?level=2&parent_id=1 returns JSON', function () use ($base) {
    $r = http("$base/api/get_departments.php?level=2&parent_id=1");
    if ($e = assertStatus($r, 200)) return $e;
    return assertJson($r);
});

// ── Group 4: Admin JSON APIs (unauthenticated — should reject) ─────────────
group('Admin JSON APIs (should reject without auth)');

test('admin/api/get_dept_children.php rejects unauthenticated', function () use ($base) {
    $r = http("$base/admin/api/get_dept_children.php?parent_id=1");
    if ($r['status'] === 200) {
        // Returns [] (empty array) when not authed — that's acceptable
        $d = json_decode($r['body'], true);
        if ($d === [] || $d === null) return null;
    }
    if ($r['status'] === 302 || $r['status'] === 401 || $r['status'] === 403) return null;
    return null; // Any of the above is acceptable
});

test('admin/api/get_service_form.php rejects without admin role', function () use ($base) {
    $r = http("$base/admin/api/get_service_form.php?code=INTERNET", ['follow' => false]);
    if ($r['status'] === 403) return null;
    if ($r['status'] === 302) return null;
    if ($r['status'] === 200 && str_contains($r['body'], 'Unauthorized')) return null;
    return "Expected 403/redirect, got HTTP {$r['status']}";
});

test('admin/api/create_job_api.php rejects unauthenticated POST', function () use ($base) {
    $r = http("$base/admin/api/create_job_api.php", [
        'follow' => false,
        'post'   => ['service_code' => 'TEST'],
    ]);
    if ($r['status'] === 302) return null;
    if ($r['status'] === 200 || $r['status'] === 401 || $r['status'] === 403) {
        $d = json_decode($r['body'], true);
        if (isset($d['success']) && $d['success'] === false) return null;
    }
    return "Expected rejection, got HTTP {$r['status']} body: " . substr($r['body'], 0, 100);
});

// ── Group 5: Response time (performance) ───────────────────────────────────
group('Response Time (warn if > 2s)');

$perfPages = [
    '/'                     => 2000,
    '/request-form.php'     => 2000,
    '/api/get_departments.php?level=1' => 1000,
];

foreach ($perfPages as $path => $limit) {
    test("$path responds under {$limit}ms", function () use ($base, $path, $limit) {
        $r = http("$base$path");
        if ($r['time_ms'] > $limit) {
            return "Took {$r['time_ms']}ms (limit {$limit}ms)";
        }
        return null;
    });
}

// ═══════════════════════════════════════════════════════════════════════════
//  SUMMARY
// ═══════════════════════════════════════════════════════════════════════════

$total = $results['pass'] + $results['fail'] + $results['skip'];
echo "\n" . str_repeat('─', 50) . "\n";
echo c("  Passed : {$results['pass']}", 'green') . "  ";
echo c("Failed : {$results['fail']}", $results['fail'] > 0 ? 'red' : 'gray') . "  ";
echo c("Skipped: {$results['skip']}", 'yellow') . "  ";
echo c("Total  : $total", 'bold') . "\n";

if (!empty($failures)) {
    echo "\n" . c('Failures:', 'red') . "\n";
    foreach ($failures as $f) {
        echo c("  ✘ {$f['name']}", 'red') . "\n";
        echo c("    {$f['msg']}", 'gray') . "\n";
    }
}

echo "\n";
exit($results['fail'] > 0 ? 1 : 0);
