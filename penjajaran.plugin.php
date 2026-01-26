<?php
/**
 * Plugin Name: Penjajaran/Shelving
 * Plugin URI: -
 * Description: Laporan penjajaran/shelving koleksi Perpustakaan
 * Version: 1.0.0
 * Author: -
 * Author URI: -
 */
use SLiMS\Plugins;

$plugin = Plugins::getInstance();

Plugins::getInstance()->registerAutoload(__DIR__);


$pathPenjajaran = __DIR__ . '/pages/daftar_penjajaran.php';
$pathRekapPenjajaran = __DIR__ . '/pages/rekapitulasi_penjajaran.php';
//$path =  __DIR__ . '/pages/inventarisasi.php';
 // Make default group menu
Plugins::group('Penjajaran/Shelving', function() use($pathPenjajaran, $pathRekapPenjajaran) {
            // Scan all file inside module directory as menu
 Plugins::menu('reporting', 'Daftar Penjajaran Koleksi', $pathPenjajaran);
 Plugins::menu('reporting', 'Rekapitulasi Penjajaran Koleksi', $pathRekapPenjajaran);
});