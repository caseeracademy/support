<?php

/**
 * Quick fix script to repair .env file with unquoted DEPARTMENTS/POSITIONS
 * Run this on your server: php fix-env.php
 */

$envPath = __DIR__ . '/.env';

if (!file_exists($envPath)) {
    echo "❌ .env file not found!\n";
    exit(1);
}

$envContent = file_get_contents($envPath);

// Fix DEPARTMENTS line - quote it if not already quoted
if (preg_match('/^DEPARTMENTS=(.*?)(?:\r?\n|$)/m', $envContent, $match)) {
    $value = trim($match[1]);
    if (!preg_match('/^["\'].*["\']$/', $value)) {
        // Not quoted, quote it
        $quoted = '"' . str_replace('"', '\\"', $value) . '"';
        $envContent = preg_replace(
            '/^DEPARTMENTS=.*/m',
            'DEPARTMENTS=' . $quoted,
            $envContent
        );
        echo "✅ Fixed DEPARTMENTS line\n";
    }
}

// Fix POSITIONS line - quote it if not already quoted
if (preg_match('/^POSITIONS=(.*?)(?:\r?\n|$)/m', $envContent, $match)) {
    $value = trim($match[1]);
    if (!preg_match('/^["\'].*["\']$/', $value)) {
        // Not quoted, quote it
        $quoted = '"' . str_replace('"', '\\"', $value) . '"';
        $envContent = preg_replace(
            '/^POSITIONS=.*/m',
            'POSITIONS=' . $quoted,
            $envContent
        );
        echo "✅ Fixed POSITIONS line\n";
    }
}

// Write back to file
file_put_contents($envPath, $envContent);

echo "✅ .env file fixed! Now run: php artisan config:clear\n";

