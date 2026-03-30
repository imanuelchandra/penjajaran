<?php

/**
 *
 * Copyright (C) 2007,2008  Arie Nugraha (dicarve@yahoo.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */

/* Item List */

defined('INDEX_AUTH') OR die('Direct access not allowed!');

//require '../../../sysconfig.inc.php';
require LIB.'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-reporting');
// start the session
require SB.'admin/default/session.inc.php';
require SB.'admin/default/session_check.inc.php';
// privileges checking
$can_read = utility::havePrivilege('reporting', 'r');
$can_write = utility::havePrivilege('reporting', 'w');

if (!$can_read) {
    die('<div class="errorBox">'.__('You don\'t have enough privileges to access this area!').'</div>');
}

require SIMBIO.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO.'simbio_GUI/paging/simbio_paging.inc.php';
require SIMBIO.'simbio_GUI/form_maker/simbio_form_element.inc.php';
require SIMBIO.'simbio_DB/datagrid/simbio_dbgrid.inc.php';
require MDLBS.'reporting/report_dbgrid.inc.php';

function httpQuery($query = [])
{
    return http_build_query(array_unique(array_merge($_GET, $query)));
}

$page_title = 'Laporan Rekapitulasi Penjajaran';
$reportView = false;
$num_recs_show = 20;
if (isset($_GET['reportView'])) {
    $reportView = true;
}

if (!$reportView) {
?>
    <!-- filter -->
    <div class="per_title">
        <h2><?php echo __('Laporan Rekapitulasi Penjajaran'); ?></h2>
    </div>
    <div class="infoBox">
        <?php echo __('Report Filter'); ?>
    </div>
    <div class="sub_section">
        <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" target="reportView" class="form-inline">
            <input type="hidden" name="id" value="<?= $_GET['id']??'' ?>"/>
            <input type="hidden" name="mod" value="<?= $_GET['mod']??'' ?>"/>
      
                        <label><?php echo __('Tanggal Penjajaran'); ?></label>
                        <div id="range">
                            <input type="text" name="tglMulaiPenjajaran">
                            <span><?= __('to') ?></span>
                            <input type="text" name="tglSelesaiPenjajaran">
                        </div>

            <input type="submit" name="applyFilter" value="<?php echo __('Apply Filter'); ?>" class="btn btn-primary" />
            <input type="hidden" name="reportView" value="true" />
        </form>
    </div>
    <script type="text/javascript">hideRows('filterForm', 1);</script>
    <!-- filter end -->
    <iframe name="reportView" id="reportView" src="<?php echo $_SERVER['PHP_SELF'].'?' .httpQuery(['reportView' => 'true']); ?>" frameborder="0" style="width: 100%; height: 500px;"></iframe>
    <script>
        $(document).ready(function(){
            const elem = document.getElementById('range');
            const dateRangePicker = new DateRangePicker(elem, {
                language: '<?= substr($sysconf['default_lang'], 0,2) ?>',
                format: 'yyyy-mm-dd',
            });
        })
    </script>
<?php
} else {
    ob_start();
	$xls_rc = 0;
	$xls_cc = 0;
    $row_class = 'alterCellPrinted';

     $criteria = '(site IS NOT NULL AND site != \'\')';
     
     if (isset($_GET['tglMulaiPenjajaran']) AND !empty($_GET['tglMulaiPenjajaran']) && isset($_GET['tglSelesaiPenjajaran']) AND !empty($_GET['tglSelesaiPenjajaran'])) {
        $penjajaranDateStart = $dbs->escape_string(trim($_GET['tglMulaiPenjajaran']));
        $penjajaranDateEnd = $dbs->escape_string(trim($_GET['tglSelesaiPenjajaran']));
        $criteria .= ' AND (DATE(last_update) >= \'' . $penjajaranDateStart . '\' AND DATE(last_update) <= \'' . $penjajaranDateEnd . '\')';
    }

    $output = '<table border="1">';
    // // header
    $output .= '<thead><tr>
        <th>'.__('Unit Rak dan Kolom').'</th>
        <th>'.__('Baris Rak').'</th>
        <th>'.__('Lokasi Rak').'</th>
        <th>'.__('Eksemplar').'</th></tr></thead>';
	$xlsrows = array($xls_rc => array(__('Unit Rak dan Kolom'),__('Baris Rak'),__('Lokasi Rak'),__('Eksemplar')));
	$xls_rc++;
    
     $lokasiRak_q = $dbs->query("
                                SELECT DISTINCT CAST(rak AS CHAR)
                                FROM
                                (
                                    SELECT
                                    site,
                                    last_update, 
                                    SUBSTRING_INDEX(site, '-', 1) AS rak 
                                    FROM 
                                        item
                                ) AS itemrak
                                WHERE site IS NOT NULL AND site != '' ORDER BY rak ASC;
                                ");
       
     $output .= '<tbody>';
     $dataKolomBaris = array(array());
     while ($lokasiRak_d = $lokasiRak_q->fetch_row()) {
        if ($lokasiRak_d[0]) {
          
            $output .= '<tr class="table-warning">';
            
            $output .=  '<th>'.$lokasiRak_d[0].'</th>';

            $output .=  '<th>&nbsp;</th>';

            $output .=  '<th>&nbsp;</th>';
            
            $jumlah_item_q = $dbs->query("
                                SELECT DISTINCT COUNT(item_id) AS jumlah_item
                                FROM
                                (
                                    SELECT 
                                    item_id,
                                    site,
                                    last_update,
                                    SUBSTRING_INDEX(site, '-', 1) AS rak 
                                    FROM 
                                        item
                                ) AS itemrak
                                WHERE SUBSTRING_INDEX(site, '-', 1)='".$lokasiRak_d[0]."' AND $criteria ORDER BY rak ASC;
                                ");
            $jumlah_item_d = $jumlah_item_q->fetch_row();
            $output .=  '<th>'.$jumlah_item_d[0].'</th>';

            $xlsrows[$xls_rc] = array($lokasiRak_d[0],' ',' ',$jumlah_item_d[0]);
			$xls_rc++;

            $output .= '</tr>';

            $kolomRak_q = $dbs->query("
                                    SELECT DISTINCT kolom, baris, rak
                                    FROM
                                    (
                                        SELECT
                                        site,
                                        last_update,
                                        SUBSTRING_INDEX(SUBSTRING_INDEX(site, '-', 2), '-', -1) AS kolom,
                                        SUBSTRING_INDEX(SUBSTRING_INDEX(site, '-', 3), '-', -1) AS baris,
                                        SUBSTRING_INDEX(site, '-', 3) AS rak
                                        FROM 
                                            item
                                    ) AS itemkolom
                                    WHERE SUBSTRING_INDEX(site, '-', 1)='".$lokasiRak_d[0]."' AND site IS NOT NULL AND site != '' ORDER BY kolom ASC;
                                ");

            
            while ($kolomRak_d = $kolomRak_q->fetch_assoc()) {
     
                 $output .= '<tr>';

                 $output .= '<th>'.$kolomRak_d['kolom'].'</th>';
                 $output .= '<th>'.$kolomRak_d['baris'].'</th>';
                 $output .= '<th>'.$kolomRak_d['rak'].'</th>';
                

               // count by item
                $item_q = $dbs->query("
                                        SELECT DISTINCT COUNT(item_id) AS jumlah_item
                                        FROM
                                        (
                                            SELECT
                                                item_id,
                                                site,
                                                last_update
                                            FROM 
                                                item
                                        ) AS itembaris
                                        WHERE site='".$kolomRak_d['rak']."' AND $criteria ORDER BY site ASC;
                        ");
                    $item_d = $item_q->fetch_row();

                    $output .=  '<td>'.$item_d[0].'</td>';
                  
                 $xlsrows[$xls_rc] = array($kolomRak_d['kolom'],$kolomRak_d['baris'],$kolomRak_d['rak'],$item_d[0]);
			     $xls_rc++;

                $output .= '</tr>';

               
             }
             
        }
     }
    
   $output .= '<tr class="table-warning">';
   $output .=  '<th>&nbsp;</th>';
   $output .=  '<th>&nbsp;</th>';
   $output .=  '<th>Total :</th>';
    
   $total_q = $dbs->query("
                                SELECT DISTINCT COUNT(item_id) AS jumlah_item
                                FROM
                                (
                                    SELECT
                                    item_id, 
                                    site,
                                    last_update
                                    FROM 
                                        item
                                ) AS itemrak
                                WHERE $criteria ORDER BY site ASC;
                                ");
   $total_d = $total_q->fetch_row();
   $output .=  '<th>'.$total_d[0].'</th>';

   $xlsrows[$xls_rc] = array(' ',' ','Total ',$total_d[0]);
   $xls_rc++;

   $output .= '</tr>';

   $output .= '</tbody>';
   $output .= '</table>';

    // print out
    echo '<div class="mb-2">'.__('Rekapitulasi Penjajaran').' 
    <a href="#" class="s-btn btn btn-default printReport" onclick="window.print()">'.__('Print Current Page').'</a>
    <a href="' . AWB . 'modules/reporting/xlsoutput.php" class="s-btn btn btn-default">'.__('Export to spreadsheet format').'</a></div>'."\n";
    echo $output;

	unset($_SESSION['xlsquery']); 
	$_SESSION['xlsdata'] = $xlsrows;
	$_SESSION['tblout'] = "recap_list";
	// echo '<p><a href="../xlsoutput.php" class="s-btn btn btn-default">'.__('Export to spreadsheet format').'</a></p>';
    $content = ob_get_clean();
    // include the page template
    require SB.'/admin/'.$sysconf['admin_template']['dir'].'/printed_page_tpl.php';
}