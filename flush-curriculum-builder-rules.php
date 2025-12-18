<?php
/**
 * Flush Rewrite Rules for Curriculum Builder
 * Run this once after adding the curriculum builder rewrite rules
 */

// Include WordPress
require_once '../../../../../wp-load.php';

// Flush rewrite rules
flush_rewrite_rules(true);

echo 'Rewrite rules flushed successfully for curriculum builder\!' . PHP_EOL;
?>
