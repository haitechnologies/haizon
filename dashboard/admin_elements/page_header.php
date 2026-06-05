    <div class="page-header page-header-light ">
        <div class="page-header-content d-lg-flex border-top">
            <div class="row mt-3">
                <div class="col-lg-12">
                    <?php if (isset($module) && !empty($module)): ?>
                        <h1 class="ms-2 h5"> <a href="listing_<?php echo $module; ?>.php" class="text-dark">All <?php echo ucwords(str_ireplace('_', " ", $module)); ?></a></h1>
                    <?php else: ?>
                        <h1 class="ms-2 h5">Dashboard</h1>
                    <?php endif; ?>
                </div>

                <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
                    <i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
                </a>
            </div>

            <div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
                <div class="d-lg-flex mb-2 mb-lg-0">
                    <div class="mt-2 mb-2">
                        <?php if (empty($hide_add_button) && isset($module_id) && isset($module) && granted('create', $module_id)) { ?>
                            <a href="<?php echo $module; ?>.php" class="btn btn-primary btn-sm">
                                <i class="ph-plus ph-sm me-2 opacity-75"></i>New</button>
                            </a>
                        <?php } ?>
                    </div>
                </div>
            </div>

        </div>
    </div>