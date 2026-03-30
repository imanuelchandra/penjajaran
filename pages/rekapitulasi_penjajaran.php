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

// key to authenticate
// define('INDEX_AUTH', '1');

// // main system configuration

// // IP based access limitation
// require LIB . 'ip_based_access.inc.php';
// do_checkIP('smc');
// do_checkIP('smc-reporting');
// // start the session
// require SB . 'admin/default/session.inc.php';
// require SB . 'admin/default/session_check.inc.php';
// // privileges checking
// $can_read = utility::havePrivilege('reporting', 'r');
// $can_write = utility::havePrivilege('reporting', 'w');

// if (!$can_read) {
//     die('<div class="errorBox">' . __('You don\'t have enough privileges to access this area!') . '</div>');
// }
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

require SIMBIO . 'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO . 'simbio_GUI/paging/simbio_paging.inc.php';
require SIMBIO . 'simbio_GUI/form_maker/simbio_form_element.inc.php';
require SIMBIO . 'simbio_DB/datagrid/simbio_dbgrid.inc.php';
require MDLBS . 'reporting/report_dbgrid.inc.php';

// function httpQuery($query = [])
// {
//     return http_build_query(array_unique(array_merge($_GET, $query)));
// }

// $page_title = 'Items/Copies Report';
// $reportView = false;
// $num_recs_show = 20;
// if (isset($_GET['reportView'])) {
//     $reportView = true;
// }

function httpQuery($query = [])
{
    return http_build_query(array_unique(array_merge($_GET, $query)));
}

$page_title = 'Laporan Rekapitulasi Katalogisasi';
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
        <form method="get" action="<?= $_SERVER['PHP_SELF'] . '?' . httpQuery() ?>" target="reportView">
            <input type="hidden" name="id" value="<?= $_GET['id']??'' ?>"/>
            <input type="hidden" name="mod" value="<?= $_GET['mod']??'' ?>"/>
            <div id="filterForm">
                <div class="form-group divRow">
                    <label><?php echo __('Judul/ISBN'); ?></label>
                    <?php echo simbio_form_element::textField('text', 'judul', '', 'class="form-control col-4"'); ?>
                </div>
                <div class="form-group divRow">
                    <label><?php echo __('GMD'); ?></label>
                    <?php
                    $gmd_q = $dbs->query('SELECT gmd_id, gmd_name FROM mst_gmd');
                    $gmd_options[] = array('0', __('ALL'));
                    while ($gmd_d = $gmd_q->fetch_row()) {
                        $gmd_options[] = array($gmd_d[0], $gmd_d[1]);
                    }
                    echo simbio_form_element::selectList('gmd[]', $gmd_options, '', 'multiple="multiple" size="5" class="form-control col-3"');
                    ?><small class="text-muted"><?php echo __('Press Ctrl and click to select multiple entries'); ?></small>
                </div>
                <div class="form-group divRow">
                    <label><?php echo __('Tipe Koleksi'); ?></label>
                    <?php
                    $coll_type_q = $dbs->query('SELECT coll_type_id, coll_type_name FROM mst_coll_type');
                    $coll_type_options = array();
                    $coll_type_options[] = array('0', __('ALL'));
                    while ($coll_type_d = $coll_type_q->fetch_row()) {
                        $coll_type_options[] = array($coll_type_d[0], $coll_type_d[1]);
                    }
                    echo simbio_form_element::selectList('tipeKoleksi[]', $coll_type_options, '', 'multiple="multiple" size="5" class="form-control col-3"');
                    ?>
                </div>
                <div class="form-group divRow">
                    <label><?php echo __('Lokasi Ruangan'); ?></label>
                    <?php
                    $loc_q = $dbs->query('SELECT location_id, location_name FROM mst_location');
                    $loc_options = array();
                    $loc_options[] = array('0', __('ALL'));
                    while ($loc_d = $loc_q->fetch_row()) {
                        $loc_options[] = array($loc_d[0], $loc_d[1]);
                    }
                    echo simbio_form_element::selectList('lokasiRuangan', $loc_options, '', 'class="form-control col-2"');
                    ?>
                </div>
                <div class="form-group divRow">
                    <label><?php echo __('Tanggal Penjajaran'); ?></label>
                    <div class="divRowContent">
                        <div id="range">
                            <input type="text" name="tglMulaiPengatalogan">
                            <span><?= __('to') ?></span>
                            <input type="text" name="tglSelesaiPengatalogan">
                        </div>
                    </div>
                </div>
                <div class="form-group divRow">
                    <label><?php echo __('Tahun Terbit'); ?></label>
                    <?php echo simbio_form_element::textField('text', 'tahunTerbit', '', 'class="form-control col-1"'); ?>
                </div>
                <div class="form-group divRow">
                    <label><?php echo __('Total setiap halaman'); ?></label>
                    <input type="text" name="totalSetiapHalaman" size="3" class="form-control col-1" maxlength="3" value="<?php echo $num_recs_show; ?>" />
                    <small class="text-muted"><?php echo __('Set between 20 and 200'); ?></small>
                </div>
            </div>
            <input type="button" name="moreFilter" class="btn btn-default" value="<?php echo __('Show More Filter Options'); ?>" />
            <input type="submit" name="applyFilter" class="btn btn-primary" value="<?php echo __('Apply Filter'); ?>" />
            <input type="hidden" name="reportView" value="true" />
        </form>
    </div>
    <script>
        $(document).ready(function(){
            const elem = document.getElementById('range');
            const dateRangePicker = new DateRangePicker(elem, {
                language: '<?= substr($sysconf['default_lang'], 0,2) ?>',
                format: 'yyyy-mm-dd',
            });
        })
    </script>
    <!-- filter end -->
    <div class="paging-area">
        <div class="pt-3 pr-3" id="pagingBox"></div>
    </div>
    <iframe name="reportView" id="reportView" src="<?php echo $_SERVER['PHP_SELF'].'?' .httpQuery(['reportView' => 'true']); ?>" frameborder="0" style="width: 100%; height: 500px;"></iframe>
<?php
} else {
    ob_start();


       $table_spec = 'search_biblio AS b
                   LEFT JOIN item AS i ON i.biblio_id=b.biblio_id
                   LEFT JOIN mst_coll_type AS ct ON i.coll_type_id=ct.coll_type_id
                   LEFT JOIN mst_item_status AS mis ON i.item_status_id=mis.item_status_id';

    // create datagrid
    $reportgrid = new report_datagrid();
    $reportgrid->table_attr = 'class="s-table table table-sm table-bordered"';
    $reportgrid->setSQLColumn(
        'DATE(i.last_update) AS \'' . __('Tanggal Penjajaran') . '\'',
        'b.title AS \'' . __('Judul') . '\'',
        'IF(COUNT(i.item_id)>0, COUNT(i.item_id), "<strong style=\"color: #f00;\">'. __('None') .'</strong>") AS \''.__('Eksemplar').'\'',
        'b.gmd AS \'' . __('GMD') . '\'',
        'b.edition AS \'' . __('Edisi') . '\'',
        'b.isbn_issn AS \'' . __('ISBN/ISSN') . '\'',
        'b.author AS \'' . __('Pengarang') . '\'',
        'b.publisher AS \'' . __('Penerbit') . '\'',
        'b.publish_year AS \'' . __('Tahun') . '\'',
        'b.publish_place AS \'' . __('Kota') . '\'',
        'ct.coll_type_name AS \'' . __('Tipe Koleksi') . '\'',
        'b.classification AS \'' . __('Klasifikasi') . '\'',
        'b.topic AS \'' . __('Subjek') . '\'',
        'b.call_number  AS \'' . __('No Panggil') . '\'',
        'i.biblio_id'
    );
    $reportgrid->setSQLorder('b.title ASC');

    // is there any search
    $criteria = 'b.biblio_id IS NOT NULL AND i.site IS NOT NULL AND i.site != \'\'';
    //$criteria .= 'AND ba.author_id IN (SELECT DISTINCT author_id FROM mst_author) AND ba.biblio_id=i.biblio_id';
    if (isset($_GET['judul']) and !empty($_GET['judul'])) {
        $keyword = $dbs->escape_string(trim($_GET['judul']));
        $words = explode(' ', $keyword);
        if (count($words) > 1) {
            $concat_sql = ' AND (';
            foreach ($words as $word) {
                $concat_sql .= " (b.title LIKE '%$word%' OR b.isbn_issn LIKE '%$word%') AND";
            }
            // remove the last AND
            $concat_sql = substr_replace($concat_sql, '', -3);
            $concat_sql .= ') ';
            $criteria .= $concat_sql;
        } else {
            $criteria .= ' AND (b.title LIKE \'%' . $keyword . '%\' OR b.isbn_issn LIKE \'%' . $keyword . '%\')';
        }
    }
    if (isset($_GET['tipeKoleksi'])) {
        $coll_type_IDs = '';
        foreach ($_GET['tipeKoleksi'] as $id) {
            $id = (int)$id;
            if ($id) {
                $coll_type_IDs .= "$id,";
            }
        }
        $coll_type_IDs = substr_replace($coll_type_IDs, '', -1);
        if ($coll_type_IDs) {
            $criteria .= " AND i.coll_type_id IN($coll_type_IDs)";
        }
    }
    if (isset($_GET['gmd']) and !empty($_GET['gmd'])) {
        $gmd_IDs = '';
        foreach ($_GET['gmd'] as $id) {
            $id = (int)$id;
            if ($id) {
                $gmd_IDs .= "$id,";
            }
        }
        $gmd_IDs = substr_replace($gmd_IDs, '', -1);
        if ($gmd_IDs) {
            $criteria .= " AND b.gmd_id IN($gmd_IDs)";
        }
    }
    if (isset($_GET['lokasiRuangan']) and !empty($_GET['lokasiRuangan'])) {
        $location = $dbs->escape_string(trim($_GET['lokasiRuangan']));
        $criteria .= ' AND i.location_id=\'' . $location . '\'';
    }
    if (isset($_GET['tahunTerbit']) and !empty($_GET['tahunTerbit'])) {
        $publish_year = $dbs->escape_string(trim($_GET['tahunTerbit']));
        $criteria .= ' AND b.publish_year LIKE \'%' . $publish_year . '%\'';
    }
    if (isset($_GET['tglMulaiPengatalogan']) AND !empty($_GET['tglMulaiPengatalogan']) && isset($_GET['tglSelesaiPengatalogan']) AND !empty($_GET['tglSelesaiPengatalogan'])) {
        $pengataloganDateStart = $dbs->escape_string(trim($_GET['tglMulaiPengatalogan']));
        $pengataloganDateEnd = $dbs->escape_string(trim($_GET['tglSelesaiPengatalogan']));
        $criteria .= ' AND (DATE(i.last_update) >= \'' . $pengataloganDateStart . '\' AND DATE(i.last_update) <= \'' . $pengataloganDateEnd . '\')';
    }
    if (isset($_GET['totalSetiapHalaman'])) {
        $recsEachPage = (int)$_GET['totalSetiapHalaman'];
        $num_recs_show = ($recsEachPage >= 20 && $recsEachPage <= 200) ? $recsEachPage : $num_recs_show;
    }

    $reportgrid->sql_group_by = 'b.biblio_id';
    $reportgrid->setSQLCriteria($criteria);

    // callback function to show title and authors
    function showTitleAuthors($obj_db, $array_data)
    {
        if (!$array_data[5]) {
            return;
        }
        // author name query
        $_biblio_q = $obj_db->query('SELECT b.title, a.author_name FROM biblio AS b
            LEFT JOIN biblio_author AS ba ON b.biblio_id=ba.biblio_id
            LEFT JOIN mst_author AS a ON ba.author_id=a.author_id
            WHERE b.biblio_id=' . $array_data[5]);
        $_authors = '';
        while ($_biblio_d = $_biblio_q->fetch_row()) {
            $_title = $_biblio_d[0];
            $_authors .= $_biblio_d[1] . ' - ';
        }
        $_authors = substr_replace($_authors, '', -3);
        $_output = $_title . '<br /><i>' . $_authors . '</i>' . "\n";
        return $_output;
    }
    function showStatus($obj_db, $array_data)
    {
        $output = __('Available');
        $q = $obj_db->query('SELECT item_status_name FROM mst_item_status WHERE item_status_id=\'' . $array_data[3] . '\'');
        if (!empty($q->num_rows)) {
            $d = $q->fetch_row();
            $s = $d[0];
            $output = $s;
        }

        return $output;
    }
    // modify column value
    //$reportgrid->modifyColumnContent(1, 'callback{showTitleAuthors}');
    //$reportgrid->modifyColumnContent(3, 'callback{showStatus}');
    $reportgrid->invisible_fields = array(14);

    // show spreadsheet export button
    $reportgrid->show_spreadsheet_export = true;
    $reportgrid->spreadsheet_export_btn = '<a href="' . AWB . 'modules/reporting/spreadsheet.php" class="s-btn btn btn-default">'.__('Export to spreadsheet format').'</a>';

    // put the result into variables
    echo $reportgrid->createDataGrid($dbs, $table_spec, $num_recs_show);

    echo '<script type="text/javascript">' . "\n";
    echo 'parent.$(\'#pagingBox\').html(\'' . str_replace(array("\n", "\r", "\t"), '', $reportgrid->paging_set) . '\');' . "\n";
    echo '</script>';

    $xlsquery = "SELECT DATE(i.last_update) AS '" . __('TANGGAL PENJAJARAN') . "',
            b.title AS '" . __('JUDUL') . "',
            IF(COUNT(i.item_id)>0, COUNT(i.item_id), '" . __('None') . "') AS '" . __('EKSEMPLAR') . "',
            b.gmd AS '" . __('GMD') . "',
            b.edition AS '" . __('EDISI') . "',
            b.isbn_issn AS '" . __('ISBN/ISSN') . "',
            b.author AS '" . __('PENGARANG') . "',
            b.publisher AS '" . __('PENERBIT') . "',
            b.publish_year AS '" . __('TAHUN TERBIT') . "',
            b.publish_place AS '" . __('KOTA') . "',
            ct.coll_type_name AS '" . __('TIPE KOLEKSI') . "',
            b.classification AS '" . __('KLASIFIKASI') . "',
            b.topic AS '" . __('SUBJEK') . "',
            b.call_number AS '" . __('NO PANGGIL') . "' FROM " .
        $table_spec . " WHERE " . $criteria. "GROUP BY b.biblio_id";
    // echo $xlsquery;
    unset($_SESSION['xlsdata']);
    $_SESSION['xlsquery'] = $xlsquery;
    $_SESSION['tblout'] = "title_list_item";

    $content = ob_get_clean();
    // include the page template
    require SB . '/admin/' . $sysconf['admin_template']['dir'] . '/printed_page_tpl.php';
}