#!/usr/bin/env php
<?php

// Test database connectivity directly
echo "Testing database connectivity...\n";

$dsn = 'pgsql:host=127.0.0.1;port=5432;dbname=agri';
$user = 'agri';
$pass ='secret';

try {
    $pdo = new PDO($dsn, $user, $pass);
    echo "✓ Database connected\n\n";
    
    // Test a simple count query
    echo "Testing user count query...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    echo "✓ Total users: $count\n\n";
    
    // Test capability query (simple)
    echo "Testing user_capabilities simple count...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM user_capabilities WHERE can_sell = true AND status = 'active'");
    $sellerCount = $stmt->fetchColumn();
    echo "✓ Users with can_sell=true: $sellerCount\n\n";
    
    // Test product count with farmer listing
    echo "Testing products with active farmer listings...\n";
    $start = microtime(true);
    $stmt = $pdo->query("SELECT COUNT(DISTINCT products.id) as product_count FROM products INNER JOIN farmer_listings ON products.id = farmer_listings.product_id WHERE farmer_listings.is_active = true");
    $productCount = $stmt->fetchColumn();
    $elapsed = microtime(true) - $start;
    echo "✓ Products with active listings: $productCount (took {$elapsed}s)\n\n";
    
    // Test the OLD slow query pattern (withCount + whereHas equivalent)
    echo "Testing OLD SLOW query pattern (nested subqueries)...\n";
    $start = microtime(true);
    $stmt = $pdo->prepare("
        SELECT DISTINCT users.id, users.name
        FROM users
        INNER JOIN user_capabilities ON users.id = user_capabilities.user_id
        WHERE user_capabilities.can_sell = true
        AND user_capabilities.status = 'active'
        AND EXISTS (
            SELECT 1 FROM farmer_listings
            INNER JOIN products ON farmer_listings.product_id = products.id
            WHERE farmer_listings.user_id = users.id
            AND farmer_listings.is_active = true
        )
    ");
    $stmt->execute();
    $oldResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $oldElapsed = microtime(true) - $start;
    echo "Old query - Found {$" . count($oldResults) . "} sellers (took {$oldElapsed}s)\n\n";
    
    // Test the NEW FAST query
    echo "Testing NEW FAST query (direct count)...\n";
    $start = microtime(true);
    $stmt = $pdo->query("SELECT COUNT(*) FROM user_capabilities WHERE can_sell = true AND status = 'active'");
    $newCount = $stmt->fetchColumn();
    $newElapsed = microtime(true) - $start;
    echo "New query - Count: $newCount (took {$newElapsed}s)\n\n";
    
    echo "Comparison:\n";
    echo "Old pattern: {$oldElapsed}s\n";
    echo "New pattern: {$newElapsed}s\n";
    echo "Speedup: " . (($oldElapsed / $newElapsed) ?: 1) . "x faster\n";
    
} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
