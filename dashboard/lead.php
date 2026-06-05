<?php

include('admin_elements/admin_header.php');

$module = 'leads';
$module_caption = 'Lead';
$tbl_name = DB::LEADS;
$error_message = '';
$success_message = '';

/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
|
*/
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();


// CHECK IF NOT SUPER ADMIN
// FOR - LEAD OWNER - ASSGINED TO - CREATED BY
if ($session_role_id > 2 && !empty($id)) {
    $rs_verify = $mysqli->query("SELECT id FROM `" . DB::LEADS  . "` WHERE id=$id AND (lead_owner = $session_user_id OR assigned_to = $session_user_id OR created_by = $session_user_id)");
    if ($rs_verify->num_rows == 0) {
        header("Location:listing_leads.php?error_message=Leads Permissions not Valid.");
    }
}


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
    $id     = e_s__($_REQUEST['id']);
}


if (empty($id)) header("Location:listing_$module.php");;


// ---------------------- Tags Array -----------------------------

$tags_arr           = array();


/*
|--------------------------------------------------------------------------
| CONVERT LEAD TO CUSTOMER
|--------------------------------------------------------------------------
|
*/
if ($action == "convert" && !empty($id)) {

    // IF ALREADY CONVERTED - EXIT
    $rs = $mysqli->query("SELECT * FROM `" . DB::LEADS . "` WHERE id=$id AND is_converted=1");

    if ($rs->num_rows > 0) {
        $error_message = 'This lead is already Converted into Customer.';

        // IF NOT CONVERTED - Then Continue
    } else {

        $mysqli->query("INSERT INTO `" . DB::CUSTOMERS . "` (
            lead_id,
            customer_owner,
            customer_type,
            customer_status,
            customer_source,
            assigned_to,
            salutation,
            first_name,
            last_name,
            display_name,
            address,
            email,
            phone,
            mobile,
            trn,
            website,
            department,
            designation,
            x,
            facebook,
            instagram,
            photo,
            description,
            tags,
            contacted_date,
            publish,
            created_at,
            updated_at,
            created_by
            )
            SELECT
                $id,
                lead_owner,
                lead_type,
                lead_status,
                lead_source,
                assigned_to,
                salutation,
                first_name,
                last_name,
                display_name,
                address,
                email,
                phone,
                mobile,
                trn,
                website,
                department,
                designation,
                x,
                facebook,
                instagram,
                photo,
                description,
                tags,
                contacted_date,
                publish,
                now(),
                now(),
                $session_user_id
            FROM `" . DB::LEADS . "`
            WHERE id =  $id");

        // SET CONVERTED = 1
        $mysqli->query("UPDATE `" . DB::LEADS . "` SET is_converted=1 WHERE id=$id");

        // REDIRECT
        header("Location:lead.php?id=$id");
    }
}



/*
|--------------------------------------------------------------------------
| EDIT
|--------------------------------------------------------------------------
|
*/
$lead_name = '';

if (!empty($id)) {

    $result = $mysqli->query("SELECT * FROM `$tbl_name` WHERE id=$id");
    $row = $result->fetch_array();

    $is_converted          = s__($row['is_converted']);


    $lead_owner             = s__($row['lead_owner']);
    $lead_owner             = getTableAttr('full_name', tbl_users, $lead_owner);

    $lead_status            = s__($row['lead_status']);
    $lead_status            = getTableAttr('status', tbl_setup_statuses, $lead_status);

    $lead_source            = s__($row['lead_source']);
    $lead_source            = getTableAttr('source', tbl_setup_sources, $lead_source);

    $assigned_to            = s__($row['assigned_to']);
    $assigned_to            = getTableAttr('full_name', tbl_users, $assigned_to);

    $lead_type              = s__($row['lead_type']);
    $salutation             = ((!empty($row['salutation']) ? ucwords(s__($row['salutation'])) : ''));
    $first_name             = s__($row['first_name']);
    $last_name              = s__($row['last_name']);
    $display_name           = s__($row['display_name']);
    $address                = s__($row['address']);
    $email                  = s__($row['email']);
    $phone                  = s__($row['phone']);
    $mobile                 = s__($row['mobile']);
    $trn                    = s__($row['trn']);

    $contacted_date         = s__($row['contacted_date']);
    $contacted_date         = ($contacted_date == '1970-01-01 00:00:00' ? '' : date('d-m-Y h:i A', strtotime($contacted_date)));

    $description            = s__($row['description']);

    // -- Tags
    $tags                   = s__($row['tags']);
    $tags_arr               = array();
    $tags_captions = '';

    if ($tags != NULL) {
        $tags_arr               = explode(',', $tags);

        // $tags_captions = '';

        foreach ($tags_arr as $tag_id) {
            $tags_captions .= '<span class="badge bg-light text-dark">' . getTableAttr('tag', tbl_setup_tags, $tag_id) . '</span> &nbsp;';
        }
    }



    $street1                = s__($row['street1']);
    $street2                = s__($row['street2']);
    $city                   = s__($row['city']);
    $state                  = s__($row['state']);
    $state                  = getTableAttr('state_name', tbl_geo_states, $state);
    $pobox                  = s__($row['pobox']);
    $country                = s__($row['country']);
    $country                = getTableAttr('country_name', tbl_geo_countries, $country);

    $service                = s__($row['service']);
    $service_name           = getTableAttr('item_name', tbl_items, $service);

    $website                = s__($row['website']);
    $department             = s__($row['department']);
    $designation            = s__($row['designation']);
    $x                      = s__($row['x']);
    $facebook               = s__($row['facebook']);
    $instagram              = s__($row['instagram']);
    $publish                = s__($row['publish']);
}

// $photo = getTableAttr('photo', $tbl_name, $id);
/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/
?>

<div class="content-wrapper">

    <!-- page header -->
    <div class="page-header page-header-light shadow">
        <div class="page-header-content d-lg-flex border-top">
            <div class="row mt-2">
                <div class="col-lg-12">
                    <?php include('admin_elements/lead_navbar.php'); ?>
                </div>

                <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                    <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                </a>
            </div>

            <?php if (isset($module_id) && granted('edit', $module_id)) { ?>
                <div class="collapse d-lg-block ms-lg-auto mt-1" id="breadcrumb_elements">
                    <div class="d-lg-flex mb-2 mt-1 mb-lg-0">

                        <button type="button" onclick="window.location.href='<?php echo $module; ?>.php?action=edit_leads&id=<?php echo $id; ?>';" class=" btn btn-primary btn-sm my-1 me-2">Edit</button>

                        <?php if ($is_converted == 0) { ?>
                            <button type="button" onclick="window.location.href='lead.php?action=convert&id=<?php echo $id; ?>';" class=" btn btn-indigo btn-sm my-1 me-2">Convert to Customer</button>
                        <?php } else { ?>
                            <button type="button" class=" btn btn-light my-1 me-2" disabled>Converted</button>
                        <?php } ?>

                        <!-- <button type="button" onclick="window.location.href='listing_<?php echo $module; ?>.php';" class=" btn btn-outline-dark my-1 me-2">Exit</button> -->
                    </div>
                </div>
            <?php } ?>

        </div>
    </div>
    <!-- /page header -->


    <div class="content-inner">
        <div class="content">

            <?php include('admin_elements/breadcrumb.php'); ?>

            <div class="row">

                <div class="col-lg-6">
                    <div class="card">

                        <div class="card-body">

                            <div class="row">
                                <label class="col-lg-3 col-form-label">&nbsp;</label>
                                <div class="col-lg-9">
                                    <div class="mt-2">
                                        <?php if ($lead_type == 'business') { ?> Business <?php } else { ?> Individual <?php } ?>
                                    </div>
                                </div>
                            </div>


                            <div class="row">
                                <label class="col-lg-3 col-form-label">Primary Contact:</label>

                                <div class="col-lg-3">
                                    <div class="mb-0 mt-2">
                                        <?php echo $salutation; ?>
                                    </div>
                                </div>

                                <div class="col-lg-3">
                                    <div class="mb-0 mt-2">
                                        <?php echo $first_name; ?>
                                    </div>
                                </div>

                                <div class="col-lg-3">
                                    <div class="mb-0 mt-2">
                                        <?php echo $last_name; ?>
                                    </div>
                                </div>

                            </div>


                            <div class="row mb-0">
                                <label class="col-lg-3 col-form-label"><span class="text-danger">Company Name:*</span></label>
                                <div class="col-lg-9 mt-2">
                                    <span class="text-danger"><?php echo $display_name; ?></span>
                                </div>
                            </div>


                            <div class="row mb-0">
                                <label class="col-lg-3 col-form-label"><span class="text-danger">Address:*</span></label>
                                <div class="col-lg-9 mt-2">
                                    <span class="text-danger"><?php echo $address; ?></span>
                                </div>
                            </div>


                            <div class="row mb-0">
                                <label class="col-lg-3 col-form-label">Email Address:</label>
                                <div class="col-lg-9 mt-2">
                                    <?php echo $email; ?>
                                </div>
                            </div>


                            <div class="row mb-0">
                                <label class="col-lg-3 col-form-label">Phone: </label>

                                <div class="col-lg-4">
                                    <div class="input-group mt-2">
                                        <?php echo $phone; ?>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="input-group mt-2">
                                        <?php echo $mobile; ?>
                                    </div>
                                </div>
                            </div>


                            <div class="row mb-0">
                                <label class="col-lg-3 col-form-label">Contacted: </label>
                                <div class="col-lg-4 mt-2">
                                    <?php echo $contacted_date; ?>
                                </div>
                            </div>


                            <div class="row mb-0">
                                <label class="col-lg-3 col-form-label">Description: </label>
                                <div class="col-lg-9 mt-2">
                                    <?php echo $description; ?>
                                </div>
                            </div>

                            <div class="mb-0 row">
                                <label class="col-lg-3 col-form-label">Tags: </label>
                                <div class="col-lg-9 mt-2">
                                    <?php echo $tags_captions; ?>
                                </div>
                            </div>

                            <div class="row border-top-black border-top-lg mb-3">

                                <div class="col-lg-4 mt-2">
                                    <div class="">
                                        <label class="form-label">Status:</label><br />
                                        <?php echo $lead_status; ?>
                                    </div>
                                </div>


                                <div class="col-lg-4">
                                    <div class="mt-2">
                                        <label class="form-label">Source:</label><br />
                                        <?php echo $lead_source; ?>
                                    </div>
                                </div>


                                <div class="col-lg-4">
                                    <div class="mt-2">
                                        <label class="form-label">Assigned To: </label><br />
                                        <?php echo $assigned_to; ?>
                                    </div>
                                </div>


                            </div>

                        </div>




                    </div>
                </div>


                <div class="col-lg-3">
                    <div class="card">

                        <div class="card-header">
                            <span class="mb-0 fw-semibold">Lead Owner</span>
                        </div>

                        <div class="content clearfix">
                            <div class="row mb-0">
                                <!-- <label class="col-lg-4 col-form-label">Lead Type:</label> -->
                                <div class="col-lg-12">
                                    <?php echo $lead_owner; ?>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="card">

                        <div class="card-header">
                            <span class="mb-0 fw-semibold">Address Information</span>
                        </div>


                        <div class="content clearfix">


                            <div class="row mb-0">
                                <label class="col-lg-3 col-form-label">Country:</label>
                                <div class="col-lg-9 mt-2">
                                    <?php echo $country; ?>
                                </div>
                            </div>


                            <div class="row mb-0">
                                <label class="col-lg-3 col-form-label">Street1:</label>
                                <div class="col-lg-9 mt-2">
                                    <?php echo $street1; ?>
                                </div>
                            </div>


                            <div class="row mb-0">
                                <label class="col-lg-3 col-form-label">Street2:</label>
                                <div class="col-lg-9 mt-2">
                                    <?php echo $street2; ?>
                                </div>
                            </div>


                            <div class="row mb-0">
                                <label class="col-lg-3 col-form-label">City:</label>
                                <div class="col-lg-9 mt-2">
                                    <?php echo $city; ?>
                                </div>
                            </div>


                            <div class="row mb-0">
                                <label class="col-lg-3 col-form-label">State:</label>
                                <div class="col-lg-9 mt-2">
                                    <?php echo $state; ?>
                                </div>
                            </div>

                            <div class="row mb-0">
                                <label class="col-lg-3 col-form-label">P.O Box:</label>
                                <div class="col-lg-9 mt-2">
                                    <?php echo $pobox; ?>
                                </div>
                            </div>


                        </div>

                    </div>
                </div>



                <div class="col-lg-3">

                    <div class="card">

                        <div class="card-header">
                            <span class="mb-0 fw-semibold">Service</span>
                        </div>

                        <div class="content clearfix">
                            <div class="row mb-0">
                                <label class="col-lg-4 col-form-label">Service: </label>
                                <div class="col-lg-8 mt-2">
                                    <?php echo $service_name; ?>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="card">

                        <div class="card-header">
                            <span class="mb-0 fw-semibold">Other Details</span>
                        </div>


                        <div class="content clearfix">


                            <div class="row mb-0">
                                <label class="col-lg-4 col-form-label">Website:</label>
                                <div class="col-lg-8">
                                    <div class="input-group mt-2">
                                        <?php echo $website; ?>
                                    </div>
                                </div>
                            </div>


                            <div class="row mb-0">
                                <label class="col-lg-4 col-form-label">Department:</label>
                                <div class="col-lg-8 mt-2">
                                    <?php echo $department; ?>
                                </div>
                            </div>


                            <div class="row mb-0">
                                <label class="col-lg-4 col-form-label">Designation:</label>
                                <div class="col-lg-8 mt-2">
                                    <?php echo $designation; ?>
                                </div>
                            </div>

                            <div class="row mb-0">
                                <label class="col-lg-4 col-form-label">X(Twitter):</label>
                                <div class="col-lg-8 mt-2">
                                    <?php echo $x; ?>
                                </div>
                            </div>

                            <div class="row mb-0">
                                <label class="col-lg-4 col-form-label">Facebook:</label>
                                <div class="col-lg-8 mt-2">
                                    <?php echo $facebook; ?>
                                </div>
                            </div>


                            <div class="row mb-0">
                                <label class="col-lg-4 col-form-label">Instagram:</label>
                                <div class="col-lg-8 mt-2">
                                    <?php echo $instagram; ?>
                                </div>
                            </div>


                            <div class="row mb-0">
                                <label class="col-lg-4 col-form-label">TRN #: </label>
                                <div class="col-lg-8 mt-2">
                                    <?php echo $trn; ?>
                                </div>
                            </div>



                        </div>

                    </div>
                </div>

            </div>
        </div>

        <?php include('admin_elements/copyright.php'); ?>
    </div>
</div>

<?php include('admin_elements/admin_footer.php'); ?>