<?php
// FarmFlow XAMPP Entry Point
// Serves the compiled React application

$config = require __DIR__ . '/config.php';

$distHtml = $config['app']['dist_html'];

if (file_exists($distHtml)) {
    echo file_get_contents($distHtml);
} else {
    echo file_get_contents($config['app']['fallback_html']);
}
?>
