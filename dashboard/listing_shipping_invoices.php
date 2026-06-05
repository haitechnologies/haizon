<?php

use App\Core\DB;
include('admin_elements/admin_header.php');
$module = 'shipping_invoices';
$module_caption = 'Shipping Invoice';
$tbl_name = DB::SHIPPING_INVOICES;
$error_message = '';
$success_message = '';

//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\SMTP;
// use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require 'vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| GENEATE QR CODE AND PDF BOOKING
|--------------------------------------------------------------------------
|
*/
// --- Get From DB where qrcode=''
?>
<!-- <img src="generate_qrcode.php" alt=""> -->
<!-- <img src="generate.php?code=12345" alt=""> -->

<!-- <iframe src="generate_quotation_qrcode.php" width="1" height="1"></iframe> -->

<?php
// --- Get From DB where pdf=''
// include_once('pdf_quotation.php');

/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
|
*/
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

// if ($action == 'send_email' && !empty($id)) {

//     $quotation_date     = getTableAttr('quotation_date', DB::QUOTATIONS, $id);
//     $quotation_date     = processDateYtoD($quotation_date);
//     $pax                = getTableAttr('pax', DB::QUOTATIONS, $id);
//     $quotation_status   = getTableAttr('quotation_status', DB::QUOTATIONS, $id);
//     $quotation_status   = normalQuotationStatus($quotation_status);

//     $client_id      = getTableAttr('client_id', DB::QUOTATIONS, $id);
//     $client_name    = getTableAttr('partner_name', tbl_tpartners, $client_id);

//     $agent_id       = getTableAttr('agent_id', DB::QUOTATIONS, $id);
//     $agent_name     = getTableAttr('full_name', tbl_booking_agents, $agent_id);
//     $agent_email    = getTableAttr('email', tbl_booking_agents, $agent_id);

//     // $agent_email    = 'imrangconnect@gmail.com';

//     $email_body = "

// Dear " . $agent_name . " / " . $client_name . ",<br /><br />

// We are delighted to inform you that your Quotation request has been successfully confirmed for your upcoming trip with The Z Phoenix. We can't wait to welcome you on board and provide you with an unforgettable experience.<br /><br />

// Here are the details of your confirmed quotation:<br /><br />

// Quotation Date: " . $quotation_date . "<br />
// Number of Participants: " . $pax . "<br /><br />

// We want to assure you that we are committed to ensuring your safety, comfort, and enjoyment throughout the duration of your trip. Our team has meticulously planned every aspect of your experience to ensure it exceeds your expectations.<br /><br />

// Please take a moment to review the attached itinerary, which includes all the important details regarding your trip, such as visiting points, activities, and contact information for our team members.<br /><br />

// Should you have any questions or require further assistance, please do not hesitate to contact us at support@thezphoenix.com. Our friendly and knowledgeable team is here to help make your trip as seamless and enjoyable as possible.<br /><br />

// Once again, thank you for choosing The Z Phoenix for your travel needs. We are honored to have the opportunity to serve you and look forward to creating lasting memories together.<br /><br />

// Safe travels!<br /><br />

// Warm regards,<br /><br />

// The Z Phoenix";




//     // SEND INTRODUCTORY EMAIL
//     $mail = new PHPMailer(true);                            // Passing `true` enables exceptions
//     try {

//         //Server settings
//         $mail->SMTPDebug = SMTP::DEBUG_SERVER;                   //Enable verbose debug output
//         // $mail->SMTPDebug = 0;                                       //Disable verbose debug output
//         $mail->isSMTP();                                            //Send using SMTP
//         $mail->Host       = 'smtp.titan.email';                     //Set the SMTP server to send through
//         $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
//         $mail->Username   = 'bookings@thezphoenix.com';             //SMTP username
//         $mail->Password   = ':jGcn##C*98L~"v';                      //SMTP password
//         $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
//         $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

//         //Recipients
//         $mail->setFrom('bookings@thezphoenix.com',          'The Z Phoenix System');
//         // $mail->addAddress('imrangconnect@gmail.com',        'Imran N');
//         $mail->addAddress($agent_email,    $agent_name);
//         $mail->addBCC('imrangconnect@gmail.com',        'Imran N');
//         $mail->addReplyTo('support@thezphoenix.com',    'Support Team');


//         $mail->isHTML(true);

//         $pdf   = getTableAttr('pdf', DB::QUOTATIONS, $id);

//         //Attachments
//         // $mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
//         // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

//         if (isRemote()) {
//             $path = '/home/u926809517/domains/thezphoenix.online/public_html/pdfs_quotations/' . $pdf . '.pdf';
//         } else {
//             $path = '../pdfs_quotations/' . $pdf . '.pdf';
//         }


//         $mail->AddAttachment($path, 'Quotation PDF');
//         $mail->Subject         = 'The Z Phoenix - Quotation is ' . $quotation_status . ' - ' . $client_name . ' - ' . $quotation_date;

//         $mail->Body            = $email_body;
//         $mail->AltBody         = "n/a";
//         $mail->send();

//         $success_message = 'Email Sent Successfully to ' . $agent_email;
//         header("Location:listing_$module.php?success_message=$success_message");
//     } catch (Exception $e) {
//         echo 'Mailer Error: ' . $mail->ErrorInfo;
//         // echo 'error sending the email.';

//         $error_message = 'Sorry! Email could not be Sent to ' . $agent_email;
//         header("Location:listing_$module.php?error_message=$error_message");
//     }
// }


/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
|
*/
if (($action == "delete_$module" && !empty($id)) && granted('delete', $module_id)) {

    if (is_SuperAdmin()) {

        $mysqli->query("DELETE FROM `" . DB::SHIPPING_INVOICE_ITEMS . "` WHERE invoice_id=$id");
        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id");
    } else {

        $mysqli->query("DELETE FROM `" . DB::SHIPPING_INVOICE_ITEMS . "` WHERE invoice_id=$id AND created_by ='" . $session_user_id . "'");
        $mysqli->query("DELETE FROM `$tbl_name` WHERE id=$id AND created_by ='" . $session_user_id . "'");
    }


    if ($mysqli->affected_rows > 0) {
        $success_message = "Item deleted successfully.";
        header("Location:listing_$module.php?success_message=$success_message");
    } else {
        $error_message = "Action denied. You are not authorized to delete this record.";
    }
}

/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/
?>

<div class="content-wrapper">

    <!-- Page header -->
    <div class="page-header page-header-light shadow ">
        <div class="page-header-content d-lg-flex border-top">
            <div class="d-flex">
                <div class="breadcrumb py-2">
                    <a href="index.php" class="breadcrumb-item"><i class="ph-house"></i></a>
                    <a href="index.php" class="breadcrumb-item">Home</a>
                    <span class="breadcrumb-item active">Shipping Invoices</span>
                    <span class="breadcrumb-item active">Listing</span>
                </div>

                <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                    <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                </a>
            </div>

            <?php if (granted_('create', 'quotations')) { ?>
                <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
                    <div class="d-lg-flex mb-2 mb-lg-0">
                        <button type="button" onclick="window.location.href='<?php echo $module; ?>.php';" class=" btn btn-info my-1 me-2">Import <?php echo $module_caption; ?></button>
                    </div>
                </div>
            <?php } ?>

        </div>
    </div>
    <!-- /page header -->







    <div class="content datatable-enhanced">

        <?php include('admin_elements/breadcrumb.php'); ?>

        <div class="card">
            <div class="card-body">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
                    <thead>
                        <tr>
                            <th width="100">SR.</th>
                            <th width="150">INVOICE #</th>
                            <th width="150">DATE</th>
                            <th width="150">CUSTOMER</th>
                            <th width="150">TOTAL</th>
                            <th width="150">PKGS</th>
                            <th width="150">WEIGHT</th>
                            <th width="150">AWB</th>
                            <th width="150">CREATED</th>
                            <th width="100">STATUS</th>
                            <th width="150"></th>
                            <th width="130">ACTIONS</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>


        <!-- <div class="row">
            <div class="col-lg-12">
                Available Quotation Status: &nbsp;
                <span class="badge bg-primary"> Requested </span>
                <span class="badge bg-yellow">Waiting</span>
                <span class="badge bg-success">Confirmed</span>
                <span class="badge bg-black">Rejected</span>
                <span class="badge bg-danger">Cancelled</span>
            </div>
        </div> -->

        </div>


        <!-- <div class="card card-body">
            <h6>Available System Status</h6>

            <div class="dropdown-menu border-secondary border-width-2" style="display: block; position: static; width: 100%; margin-top: 0; float: none; z-index: 2;">

                <span class="badge bg-primary"> Requested </span>
                <span class="badge bg-yellow">Waiting</span>
                <span class="badge bg-success">Confirmed</span>
                <span class="badge bg-black">On Hold</span>
                <span class="badge bg-danger">Rejected</span>
                <span class="badge bg-indigo">Booked</span>
            </div>
        </div> -->


        <?php include('admin_elements/copyright.php'); ?>
    </div>
</div>

<script>
$(document).ready(function() {
    var tableSelector = '#grid-<?php echo $module; ?>';

    window.HAIDatatableInitializer.init(tableSelector, '<?php echo $module; ?>', {
        stateSave: false,
        deferRender: true,
        retrieve: false,
        columns: [
            { data: 0,  name: 'id',            title: 'SR.' },
            { data: 1,  name: 'invoice_no',    title: 'INVOICE #' },
            { data: 2,  name: 'invoice_date',  title: 'DATE' },
            { data: 3,  name: 'customer_name', title: 'CUSTOMER' },
            { data: 4,  name: 'grand_total',   title: 'TOTAL' },
            { data: 5,  name: 'no_of_packs',   title: 'PKGS' },
            { data: 6,  name: 'gross_weight',  title: 'WEIGHT' },
            { data: 7,  name: 'master_awb_no', title: 'AWB' },
            { data: 8,  name: 'created_at',    title: 'CREATED' },
            { data: 9,  name: 'publish',       title: 'STATUS' },
            { data: 10, title: '',             orderable: false, searchable: false },
            { data: 11, title: 'ACTION',       orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        responsive: true,
        autoWidth: false
    });

    $(document).on('click', 'a[data-action="delete_record"]', function(e) {
        e.preventDefault();
        var id     = $(this).data('id');
        var module = $(this).data('module');
        if (confirm('Are you sure you want to delete this record?')) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="delete_' + module + '"><input type="hidden" name="id" value="' + id + '">';
            document.body.appendChild(form);
            form.submit();
        }
    });
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>