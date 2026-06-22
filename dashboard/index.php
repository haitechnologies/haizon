<?php

declare(strict_types=1);

include('admin_elements/admin_header.php');
?>
<div class="content-wrapper">
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1">
                <h1 class="h5 mb-0">Dashboard</h1>
            </div>
        </div>
    </div>
    <div class="content-inner">
        <div class="content">
            <div class="text-center mb-4">
                <h1>Flash ERP</h1>
            </div>

            <div class="row">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="d-inline-flex bg-success bg-opacity-10 text-success rounded-pill p-2 mb-3 mt-1">
                                <i class="ph-lifebuoy ph-2x m-1"></i>
                            </div>
                            <h5 class="card-title">Shipping &amp; Logistics System</h5>
                            <p class="mb-3">Smart system for managing shipping, logistics, and deliveries.</p>
                            <a href="dashboard_shipping.php" class="btn btn-success mb-1">Dashboard</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="d-inline-flex bg-warning bg-opacity-10 text-warning rounded-pill p-2 mb-3 mt-1">
                                <i class="ph-stack ph-2x m-1"></i>
                            </div>
                            <h5 class="card-title">Accounting System</h5>
                            <p class="mb-3">Efficient system for managing business finance and accounting.</p>
                            <a href="dashboard_accounting.php" class="btn btn-warning mb-1">Dashboard</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="d-inline-flex bg-primary bg-opacity-10 text-primary rounded-pill p-2 mb-3 mt-1">
                                <i class="ph-files ph-2x m-1"></i>
                            </div>
                            <h5 class="card-title">CRM</h5>
                            <p class="mb-3">Powerful CRM to manage leads, customers, and sales pipeline.</p>
                            <a href="dashboard_crm.php" class="btn btn-primary mb-1">Dashboard</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="d-inline-flex bg-indigo bg-opacity-10 text-primary rounded-pill p-2 mb-3 mt-1">
                                <i class="ph-users ph-2x m-1"></i>
                            </div>
                            <h5 class="card-title">HR</h5>
                            <p class="mb-3">Streamlined HR system for employee records and payroll.</p>
                            <a href="dashboard_hr.php" class="btn btn-indigo mb-1">Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="live-status-container text-center mt-4">
                <div class="pulse-dots">
                    <span></span><span></span><span></span>
                </div>
                <p>System is live...</p>
            </div>

            <style>
                .pulse-dots {
                    display: inline-flex;
                    gap: 12px;
                    margin-bottom: 10px;
                }
                .pulse-dots span {
                    width: 16px;
                    height: 16px;
                    background-color: #007bff;
                    border-radius: 50%;
                    animation: pulse 1.4s infinite ease-in-out;
                }
                .pulse-dots span:nth-child(1) { animation-delay: 0s; }
                .pulse-dots span:nth-child(2) { animation-delay: 0.2s; }
                .pulse-dots span:nth-child(3) { animation-delay: 0.4s; }
                @keyframes pulse {
                    0%, 80%, 100% { transform: scale(0.8); opacity: 0.6; }
                    40% { transform: scale(1.2); opacity: 1; }
                }
            </style>
        </div>
        <?php include('admin_elements/copyright.php'); ?>
    </div>
</div>
<?php
include('admin_elements/admin_footer.php');
