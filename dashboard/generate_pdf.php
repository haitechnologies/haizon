<?php

require_once __DIR__ . '/admin_elements/error_handler_init.php';

use App\Core\DB;
require_once __DIR__ . '/../config/session.php';
startDashboardSession();
header("Content-Type: text/html; charset=utf-8");
require('../config/globals.php');
require('../config/database.php');
include('admin_elements/error_logger.php');

// Register custom error/exception/shutdown handlers
if (function_exists('custom_error_handler')) {
	set_error_handler('custom_error_handler');
}
if (function_exists('custom_exception_handler')) {
	set_exception_handler('custom_exception_handler');
}
if (function_exists('handle_fatal_error')) {
	register_shutdown_function('handle_fatal_error');
}
if (function_exists('backend_log_coverage_heartbeat')) {
	backend_log_coverage_heartbeat();
}

include('../config/images.php');
include('admin_elements/security.php');
include('admin_elements/grab_vars.php');


//============================================================+
// File name   : example_001.php
// Begin       : 2008-03-04
// Last Update : 2013-05-14
//
// Description : Example 001 for TCPDF class
//               Default Header and Footer
//
// Author: Nicola Asuni
//
// (c) Copyright:
//               Nicola Asuni
//               Tecnick.com LTD
//               www.tecnick.com
//               info@tecnick.com
//============================================================+

/**
 * Creates an example PDF TEST document using TCPDF
 * @package com.tecnick.tcpdf
 * @abstract TCPDF - Example: Default Header and Footer
 * @author Nicola Asuni
 * @since 2008-03-04
 * @group header
 * @group footer
 * @group page
 * @group pdf
 */

// Include the main TCPDF library (search for installation path).
require_once('../tcpdf/examples/tcpdf_include.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->setCreator(PDF_CREATOR);
$pdf->setAuthor('HaiTechnologies');
$pdf->setTitle('Invoice');
$pdf->setSubject('na');
// $pdf->setKeywords('TCPDF, PDF, example, test, guide');

// set default header data
// $pdf->setHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 001', PDF_HEADER_STRING, array(0, 64, 255), array(0, 64, 128));
// $pdf->setFooterData(array(0, 64, 0), array(0, 64, 128));

// set header and footer fonts
$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->setDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
// $pdf->setMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
// $pdf->setHeaderMargin(PDF_MARGIN_HEADER);

$pdf->SetMargins(10, 3, 10, true);


// $pdf->setFooterMargin(PDF_MARGIN_FOOTER);
$pdf->setFooterMargin(5);

// set auto page breaks
$pdf->setAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);


// set bacground image
// $img_file = K_PATH_IMAGES . 'image_demo.jpg';
// $img_file = 'images/background.jpg';
// $pdf->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
    require_once(dirname(__FILE__) . '/lang/eng.php');
    $pdf->setLanguageArray($l);
}


// set some language-dependent strings (optional)
// $pdf->setLanguageArray($lg);

// // add a page
// $pdf->AddPage();

// $pdf->SetFontSize(10);

// // print newline
// $pdf->Ln();

// // Persian and English content
// $htmlpersiantranslation = '<span color="#0000ff">Hi, At last Problem of Persian PDF Solved completely. This is a example for it.<br />Problem of "jeh" letter in some word like "ویژه" (=special) fix too.<br />The joining of laa and alf letter fix now.<br />Special thanks to "Nicola Asuni" and "Mohamad Ali Golkar" for Persian support.</span>';
// $pdf->WriteHTML($htmlpersiantranslation, true, 0, true, 0);

// // Restore RTL direction
// $pdf->setRTL(true);

// // set font
// $pdf->SetFont('aealarabiya', '', 18);

// // print newline
// $pdf->Ln();

// // Arabic and English content
// $pdf->Cell(0, 12, 'بِسْمِ اللهِ الرَّحْمنِ الرَّحِيمِ', 0, 1, 'C');
// $htmlcontent = 'تمَّ بِحمد الله حلّ مشكلة الكتابة باللغة العربية في ملفات الـ<span color="#FF0000">PDF</span> مع دعم الكتابة <span color="#0000FF">من اليمين إلى اليسار</span> و<span color="#009900">الحركَات</span> .<br />تم الحل بواسطة <span color="#993399">صالح المطرفي و Asuni Nicola</span>  . ';
// $pdf->WriteHTML($htmlcontent, true, 0, true, 0);

// // set LTR direction for english translation
// $pdf->setRTL(false);

// // print newline
// $pdf->Ln();

// $pdf->SetFont('aealarabiya', '', 18);

// // Arabic and English content
// $htmlcontent2 = '<span color="#0000ff">This is Arabic "العربية" Example With TCPDF.</span>';
// $pdf->WriteHTML($htmlcontent2, true, 0, true, 0);

// // ---------------------------------------------------------


// ---------------------------------------------------------

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Set font
// dejavusans is a UTF-8 Unicode font, if you only need to
// print standard ASCII chars, you can use core fonts like
// helvetica or times to reduce file size.
$pdf->setFont('dejavusans', '', 14, '', true);

// remove default header
$pdf->setPrintHeader(false);

/*
|--------------------------------------------------------------------------
| 	ARABIC SUPPORT
|--------------------------------------------------------------------------
*/
// set some language dependent data:
// $lg = array();
// $lg['a_meta_charset'] = 'UTF-8';
// $lg['a_meta_dir'] = 'rtl';
// $lg['a_meta_language'] = 'fa';
// $lg['w_page'] = 'page';

// set some language-dependent strings (optional)
// $pdf->setLanguageArray($lg);

/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/


// Add a page
// This method has several options, check the source code documentation for more information.
$pdf->AddPage();

// set text shadow effect
// $pdf->setTextShadow(array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(196, 196, 196), 'opacity' => 1, 'blend_mode' => 'Normal'));

// $pdf->Write(0, 'Example of HTML tables', '', 0, 'L', true, 0, false, false, 0);

$pdf->setFont('helvetica', '', 8);

// -----------------------------------------------------------------------------

// -- set new background ---

// get the current page break margin
$bMargin = $pdf->getBreakMargin();
// get current auto-page-break mode
$auto_page_break = $pdf->getAutoPageBreak();
// disable auto-page-break
$pdf->SetAutoPageBreak(false, 0);
// set bacground image
// $img_file = K_PATH_IMAGES . 'image_demo.jpg';
$img_file = '../assets/custom_images/background.jpg';
$pdf->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
// restore auto-page-break status
$pdf->SetAutoPageBreak($auto_page_break, $bMargin);
// set the starting point for the page content
$pdf->setPageMark();

// -----------------------------------------------------------------------------


/*
|--------------------------------------------------------------------------
| 	ARABIC
|--------------------------------------------------------------------------
*/
// set some language dependent data:
// $lg = array();
// $lg['a_meta_charset'] = 'UTF-8';
// $lg['a_meta_dir'] = 'rtl';
// $lg['a_meta_language'] = 'fa';
// $lg['w_page'] = 'page';

// // set some language-dependent strings (optional)
// $pdf->setLanguageArray($lg);

// $pdf->SetFont('aealarabiya', '', 8);

// ---------------------------------------------------------



/*
|--------------------------------------------------------------------------
| 	SECURITY
|--------------------------------------------------------------------------
|
*/


/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/



if (isset($_REQUEST['id']) && !empty($_REQUEST['id']))  $id     = e_s__($_REQUEST['id']);
else $id = 0;

if (isset($_REQUEST['token']) && !empty($_REQUEST['token']))  $token     = e_s__($_REQUEST['token']);
else $token = '';


if (!isset($_REQUEST['token']) || empty($_REQUEST['token'])) {
    header("Location:index.php");
}


$sent_token = hash("sha512", 'bushogai' . $id);


if ($token != $sent_token) die('');

$row_bg = 'background-color: #dce9f7;';
// $in_out = '';

if (!empty($id)) {

    $result = $mysqli->query("SELECT * FROM `" . DB::INVOICES . "` WHERE id=$id");
    $row = $result->fetch_array();

    $client_id              = s__($row['client_id']);

    $invoice_status         = s__($row['invoice_status']);
    $invoice_date           = s__($row['invoice_date']);

    $invoice_status         = s__($row['invoice_status']);
    $invoice_date           = s__($row['invoice_date']);


    $total_amount           = s__($row['total_amount']);
    $total_vat              = s__($row['total_vat']);
    $grand_total            = s__($row['grand_total']);

    $created_at             = s__($row['created_at']);
    $created_time           = date('h:i:s', strtotime($created_at));
    $created_date           = date('d-m-Y', strtotime($created_at));


    $created_by             = s__($row['created_by']);
    $created_by             = getUsernameByID($created_by);


    // $grand_total_in_words  = ucwords(convert_number_to_words($grand_total));

    // Need to enable Extension intl in php.ini

    $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
    $spell_out = $f->format($grand_total);

    $spell_out = str_ireplace(' point ', '.', ucwords($spell_out));

    if (str_contains($spell_out, '.')) {
        $spell_out .= ' Baisa';
    }

    // if (Str::contains($haystack, 'needles'))

    $grand_total_in_words  = ucwords($spell_out);

    $is_active = s__($row['is_active']);

    $invoice_date       = date("d-M-Y", strtotime($invoice_date));

    // $company_name       = getTableAttr('company_name', tbl_companies, $company_id);
    // $company_code       = getTableAttr('company_code', tbl_companies, $company_id);
    // $company_email      = getTableAttr('email', tbl_companies, $company_id);
    // $company_mobile     = getTableAttr('mobile', tbl_companies, $company_id);
    // $company_address    = getTableAttr('address', tbl_companies, $company_id);

    $row_no = 1;
    $item_row = '';

    // ------------------ TOTAL INVOICE ITEMS ------------------
    $result_invoice_items     = $mysqli->query("SELECT * FROM `" . DB::INVOICE_ITEMS . "` WHERE invoice_id=$id");
    $total_rows                 = $result_invoice_items->num_rows;

    if ($total_rows > 0) {
        while ($row_invoice_items = $result_invoice_items->fetch_array()) {

            $service        = $row_invoice_items['service'];

            $service_name   = getTableAttr('service', tbl_services, $service);


            // $service_name_ar   = '<img src="services_ar/services_ar.png" alt="Services Ar">';
            // $service_name_ar   = '<img src="services_ar/' . $service . '.png" alt="Services Ar">';
            // $service_name_ar   = '<img src="services_ar/' . $service . '.jpg" alt="Services Ar">';
            
            // $service_name_ar   = getTableAttr('service_ar', tbl_services, $service);

            $qty            = $row_invoice_items['qty'];
            $rate           = $row_invoice_items['rate'];
            $vat            = $row_invoice_items['vat'];
            $vat            = $row_invoice_items['vat'];
            $amount         = $row_invoice_items['amount'];

            // $qty            = (($qty == 1)  ? '': $qty);
            // $rate           = (($rate == 0)  ? '': $rate);
            // $vat            = (($vat == 0)  ? '': $vat);
            $vat_percent       = ((!empty($vat))  ? '5%' : '');

            if ($row_no % 2 == 0) {
                $row_bg = 'background-color: #dce9f7;';
            } else {
                $row_bg = 'background-color: #ffffff;';
            }


            // set font
            // $pdf->SetFont('aealarabiya', '', 9);
            $pdf->SetFont('aefurat', '', 9);
            // $pdf->SetFont('ae_alarabiya', '', 9);

            $item_row .= '
            <tr>
                <td align="left" style="border:1px solid silver"> service_name  </td>
                <td align="right" style="border:1px solid silver"> service_name_ar</td>
                <td align="center" style="border:1px solid silver"> amount  </td>
                <td align="center" style="border:1px solid silver"> vat  </td>
            </tr>';
        } // while
    }


    $benificiary            = getSystemSetting('beneficiary', getSystemSetting('benificiary', ''));
    $account_number         = getSystemSetting('account_number', '');
    $swift_no               = getSystemSetting('swift_no', '');
    $bank_name              = getSystemSetting('bank_name', '');

    $trn                    = getSystemSetting('trn', '');
    $mobile                 = getSystemSetting('phone', getSystemSetting('mobile', ''));
    $email                  = getSystemSetting('email', '');
}


// -----------------------------------------------------------------------------

/*
|--------------------------------------------------------------------------|
|------------ PAGE WIDTH = 670 ---------------------------------------------|
|--------------------------------------------------------------------------|
*/


$tbl = <<<EOD
<table cellpadding="0" cellspacing="2">
<tr>
<td width="670"><img src="../assets/custom_images/invoice_header.jpg" alt="Invoice Header"></td>
</tr>
</table>
EOD;

$pdf->writeHTML($tbl, true, false, false, false, '');


// -----------------------------------------------------------------------------

$mobile             = getSystemSetting('phone', getSystemSetting('mobile', ''));
$address            = trim(getSystemSetting('street1', '') . ' ' . getSystemSetting('street2', ''));
$pobox              = getSystemSetting('pobox', '');
$trn                = getSystemSetting('trn', '');

//  style="border:1px solid silver;"
$tbl = <<<EOD
<table cellpadding="0" cellspacing="0" border="0">


<tr>

<th width="80"> 
<span>MOBILE: </span>  <br />
<span> &nbsp;P.O.BOX: </span>  <br />
<span> &nbsp;ADDRESS: </span>  <br />
<span> &nbsp;T.R.N: </span>  
</th>

<th width="141">$mobile <br />
 &nbsp;$pobox <br />
 &nbsp;$address <br />
 &nbsp;$trn
</th>

<th width="205" align="center">  
<img src="../assets/custom_images/tax_invoice.jpg" width="150" alt="Tax Invoice">
</th>


<th width="145" align="rigth"> 
<span>$mobile </span>  <br />
<span> $pobox </span>  <br />
<span> حتا دبي، نزل حتا مكتب 36 </span>  <br />
<span> $trn </span>  
</th>

<th width="100" align="right">
متحرك : <br />
صندوق بريد : <br />
العنوان : <br />
رقم التسجل الضريبي :
</th>

</tr>

</table>
EOD;

$pdf->writeHTML($tbl, true, false, false, false, '0');


// : <img src="../images/ar_phone.jpg" width="25" alt="Ar Phone"> <br />
// : <img src="../images/ar_pobox.jpg" width="55" alt="Ar Pobox"> <br />
// : <img src="../images/ar_address.jpg" width="25" alt="Ar Address"> <br />
// : <img src="../images/ar_trn.jpg" width="80" alt="Ar Trn">

// <tr>
// <td style="background-color: #eeeef0; border:1px solid silver">  TRN# : $trn </td>
// <td></td>
// </tr>


// -----------------------------------------------------------------------------


$tbl = <<<EOD
<table cellpadding="0" cellspacing="0" border="0">

<tr>

<th width="220" style="border:1px solid silver;"> 
<span>NO: </span> 
رقم الفاتورة :
&nbsp;&nbsp;&nbsp;&nbsp; $id &nbsp;&nbsp;&nbsp;&nbsp;
</th>

<th width="230"> 
</th>

<th width="220" style="border:1px solid silver;" align="rigth"> 
<span>DATE: </span> 
&nbsp;&nbsp;&nbsp;&nbsp; $invoice_date &nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;

: التاريخ
</th>

</tr>

</table>
EOD;

$pdf->writeHTML($tbl, true, false, false, false, '0');

// -----------------------------------------------------------------------------


$tbl = <<<EOD
<table cellpadding="0" cellspacing="0" border="0">

<tr>

<td width="70" style="border:1px solid silver;"> 
<span>عمان </span> 
</td>

<td width="50" style="border:1px solid silver;"> 
C/O: 
</td>

<td width="430" style="border:1px solid silver;" align="center"> 
company_name 
</td>

<td width="120" style="border:1px solid silver;" align="rigth"> 
أسم الشركة/المؤسسة :
</td>

</tr>

</table>
EOD;

$pdf->writeHTML($tbl, true, false, false, false, '0');


 
// -----------------------------------------------------------------------------


$tbl = <<<EOD
<table cellpadding="0" cellspacing="0" border="0">

<tr>

<td width="260" style="border:1px solid silver;" align="center"> 
<span>vehicle_no </span> 
</td>

<td width="70" style="border:1px solid silver;" align="rigth"> 
رقم السيارة :
</td>

<td width="270" style="border:1px solid silver;" align="center"> 
driver_name 
</td>

<td width="70" style="border:1px solid silver;" align="rigth"> 
اسم السائق :
</td>

</tr>

</table>
EOD;

$pdf->writeHTML($tbl, true, false, false, false, '0');

 
// -----------------------------------------------------------------------------


$tbl = <<<EOD
<table cellpadding="0" cellspacing="0" border="0">

<tr>

<td width="100" style="border:1px solid silver;" align="center"> 
<span>BOE NO: </span> 
</td>

<td width="470" style="border:1px solid silver;" align="center"> 
boe_no 
</td>

<td width="100" style="border:1px solid silver;" align="rigth"> 
رقم البيان :
</td>

</tr>

</table>
EOD;

$pdf->writeHTML($tbl, true, false, false, false, '0');


 
// -----------------------------------------------------------------------------

if (str_contains($row_bg, '#ffffff')) {
    $row_bg_odd = 'background-color: #dce9f7;';
    $row_bg_even = 'background-color: #ffffff;';
} else {
    $row_bg_odd = 'background-color: #ffffff;';
    $row_bg_even = 'background-color: #dce9f7;';
}

// Restore RTL direction
// $pdf->setRTL(true);


$tbl = <<<EOD
<table cellpadding="5" cellspacing="0" border="0" style="border:1px solid grey;">
<tr>
<td width="260" style="border:1px solid silver;" align="left"> Description </td>
<td width="260" style="border:1px solid silver;" align="center"> التفاصيل </td>
<td width="75" style="border:1px solid silver" align="center"> القيمة </td>
<td width="75" style="border:1px solid silver" align="center"> VAT </td>
</tr>

$item_row

<tr>
<td colspan="4" > </td>
</tr>
<tr>
<td colspan="4" > </td>
</tr>
<tr>
<td colspan="4" > </td>
</tr>

<tr>
<td style=" border:1px solid silver"> TOTAL WITHOUT TAX </td>
<td style=" border:1px solid silver" align="right">المجموع بدون ضريبة</td>
<td colspan="2" style=" border:1px solid silver; background-color: #e6e6e6;" align="center"> $total_amount  </td>
</tr>

<tr>
<td style=" border:1px solid silver"> TOTAL VAT </td>
<td style=" border:1px solid silver" align="right">الضريبة</td>
<td colspan="2" style=" border:1px solid silver" align="center"> $total_vat  </td>
</tr>

<tr>
<td style=" border:1px solid silver"> TOTAL WITH TAX </td>
<td style=" border:1px solid silver" align="right">المجموع شامل الضريبة</td>
<td colspan="2" style=" border:1px solid silver; background-color: #e6e6e6;" align="center"> $grand_total  </td>
</tr>

</table>
EOD;

$pdf->writeHTML($tbl, true, false, false, false, '');

// Restore RTL direction
$pdf->setRTL(false);



// -----------------------------------------------------------------------------

$tbl = <<<EOD
<table cellpadding="5" cellspacing="0" border="0">

<tr>

<td>
    <table cellpadding="5" cellspacing="5" border="0">
        <tr><td style="border:1px solid silver;" align="center">محمد بو الحسن<br />+971508464205</td></tr>
        <tr>
            <td style="border:1px solid silver; background-color: #e6e6e6;" align="center"> <span style="color: red;"> cash_credit </span> </td>
        </tr>
        <tr>
            <td style="border:1px solid silver;">
            Signature : التوقيع/الختم<br />
            <img src="../assets/custom_images/stamp.jpg" width="50" alt="Stamp">
            </td>
        </tr>
    </table>
</td>
    
<td>
    <table cellpadding="5" cellspacing="0" border="0">
        <tr>
            <td align="center">بنك أم القيوين الوطني <br/> $bank_name</td>
        </tr>
        <tr>
            <td align="center" width="200"> 
                <table cellpadding="0" cellspacing="0" border="0" style="border:1px solid silver;">
                    <tr>
                        <td width="65">ACCOUNT</td>
                        <td width="70" align="center">$account_number </td>
                        <td width="65" align="right">رقم الحساب</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="center">$email <br/><br/> Manager <br />Mohammed Abu Al-Hassan<br />+971508464205</td>
        </tr>
    </table>
</td>


<td>
    <table cellpadding="5" cellspacing="5" border="0">
        <tr>
            <td style="border:1px solid silver;" align="center">
                <span>Accountant: </span> 
                المحاسب 
            </td>
        </tr>
        <tr>
            <td style="border:1px solid silver;" align="center">
                تاريخ اصدار الفاتورة <br />
                $created_at
            </td>
        </tr>
        <tr>
            <td style="border:1px solid silver;" align="center">
                DATE : <br />
                $created_at
            </td>
        </tr>
    </table>
</td>

</tr>

</table>
EOD;

$pdf->writeHTML($tbl, true, false, false, false, '');

// -----------------------------------------------------------------------------

// https://hooks.wbcomdesigns.com/reference/classes/tcpdf/writehtml/
// TCPDF::writeHTML( $html,  $ln = true,  $fill = false,  $reseth = false,  $cell = false,  $align = '' )
  
// -----------------------------------------------------------------------------

//Close and output PDF document
$pdf->Output('invoice_' . $id . '.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
