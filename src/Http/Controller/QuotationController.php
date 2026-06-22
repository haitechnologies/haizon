<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Core\Container;
use App\Core\DB;
use App\Core\Database;
use App\Http\Request;
use App\Http\Response;
use App\Service\QuotationService;
use App\Security\Roles;
use App\Exception\ValidationException;
use App\Exception\NotFoundException;
use App\Helper\DateHelper;

class QuotationController extends BaseController
{
    private QuotationService $quotationService;

    public function __construct(
        Database $db,
        int $userId,
        int $roleId,
        int $orgId,
        QuotationService $quotationService,
    ) {
        parent::__construct($db, $userId, $roleId, $orgId);
        $this->quotationService = $quotationService;
    }

    public function __invoke(Request $request): Response
    {
        $this->requiresModule('quotations', 'Quotation');

        if (!$this->canView()) {
            return new Response('Forbidden', 403);
        }

        if ($request->isPost() && !$this->validateCsrf($request)) {
            return new Response('Invalid security token.', 403);
        }

        $id = $request->getInt('id');
        $action = $request->getString('action');

        return match (true) {
            $request->isPost() && $action === 'update_quotations' && $id > 0 && $this->canEdit()
                => $this->handleUpdate($request, $id),
            $request->isPost() && $action === 'add_quotations' && $this->canCreate()
                => $this->handleCreate($request),
            default => $this->showForm($request, $id),
        };
    }

    private function handleUpdate(Request $request, int $id): Response
    {
        $quotationData = $this->buildQuotationData($request);
        $itemsData = $this->buildItemsData($request);

        try {
            $this->quotationService->updateQuotation($id, $quotationData, $itemsData, $this->orgId, $this->userId);

            if ($request->get('save_and_send') == 1) {
                return Response::redirect("send_email.php?current_module=quotations&id=$id");
            }
            flash_success('The Quotation has been updated successfully.');
            return Response::redirect('listing_quotations.php');
        } catch (ValidationException $e) {
            $error = current($e->getErrors());
            flash_error($error);
            return Response::redirect("quotations.php?id=$id&action=edit_quotations");
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
            return Response::redirect("quotations.php?id=$id&action=edit_quotations");
        }
    }

    private function handleCreate(Request $request): Response
    {
        $quotationData = $this->buildQuotationData($request);
        $itemsData = $this->buildItemsData($request);

        try {
            $newQuotation = $this->quotationService->createQuotation($quotationData, $itemsData, $this->orgId, $this->userId);
            $id = $newQuotation->id;

            if ($request->get('save_and_send') == 1) {
                return Response::redirect("send_email.php?current_module=quotations&id=$id");
            }
            flash_success('The Quotation has been saved successfully.');
            return Response::redirect('listing_quotations.php');
        } catch (ValidationException $e) {
            $error = current($e->getErrors());
            flash_error($error);
            return Response::redirect("quotations.php");
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
            return Response::redirect("quotations.php");
        }
    }

    private function buildQuotationData(Request $request): array
    {
        return [
            'customer_id' => $request->getString('customer_id'),
            'lead_id' => $request->getString('lead_id'),
            'quotation_date' => $request->getString('quotation_date'),
            'expiry_date' => $request->getString('expiry_date'),
            'warehouse_id' => $request->getString('warehouse_id'),
            'expected_shipment_date' => $request->getString('expected_shipment_date'),
            'payment_term' => $request->getString('payment_term'),
            'shipment_type' => $request->getString('shipment_type'),
            'sales_person' => $request->getString('sales_person'),
            'job_reference_no' => $request->getString('job_reference_no'),
            'master_awb_no' => $request->getString('master_awb_no'),
            'shipper' => $request->getString('shipper'),
            'consignee' => $request->getString('consignee'),
            'origin' => $request->getString('origin'),
            'destination' => $request->getString('destination'),
            'no_of_packs' => $request->getString('no_of_packs'),
            'gross_weight' => $request->getString('gross_weight'),
            'chargeable_weight' => $request->getString('chargeable_weight'),
            'volume' => $request->getString('volume'),
            'terms_and_conditions' => $request->getString('terms_and_conditions'),
            'grand_subtotal' => $request->getString('grand_subtotal'),
            'grand_discount_type' => $request->getString('grand_discount_type'),
            'grand_discount_type_value' => $request->getString('grand_discount_type_value'),
            'grand_discount_amount' => $request->getString('grand_discount_amount'),
            'grand_after_discount' => $request->getString('grand_after_discount'),
            'customer_notes' => $request->getString('customer_notes'),
            'grand_tax' => $request->getString('grand_tax'),
            'grand_total' => $request->getString('grand_total'),
            'publish' => $request->get('publish') ? true : false,
            'quotation_status' => $request->getString('quotation_status', 'draft'),
        ];
    }

    private function buildItemsData(Request $request): array
    {
        $totalRows = (int)$request->getString('total_rows', '1');
        $items = [];

        for ($i = 0; $i < $totalRows; $i++) {
            $service = $request->getArrayItem('service', $i);
            $itemId = $request->getArrayItem('item_id', $i);

            if (empty($service) || (int)$service <= 0) {
                continue;
            }

            $items[] = [
                'id' => !empty($itemId) ? (int)$itemId : null,
                'service' => (int)$service,
                'description' => $request->getArrayItem('description', $i),
                'qty' => $request->getArrayItem('qty', $i, '1'),
                'rate' => $request->getArrayItem('rate', $i, '0'),
                'sub_total' => $request->getArrayItem('sub_total', $i, '0'),
                'tax' => $request->getArrayItem('tax', $i, '0'),
                'tax_amount' => $request->getArrayItem('tax_amount', $i, '0'),
                'total' => $request->getArrayItem('total', $i, '0'),
            ];
        }

        return $items;
    }

    private function showForm(Request $request, int $id): Response
    {
        $module = 'quotations';
        $moduleCaption = $this->moduleCaption;
        $moduleId = $this->moduleId;
        $session_user_id = $this->userId;
        $session_role_id = $this->roleId;
        $error_message = $request->getString('error_message');
        if (empty($error_message)) {
            foreach (\App\Core\FlashMessage::all() as $fm) {
                if ($fm['type'] === 'danger') { $error_message = $fm['message']; break; }
            }
        }
        $action = $request->getString('action');

        // Default values
        $customer_id = '0';
        $lead_id = '0';
        $quotation_no = '';
        $quotation_status = 'draft';
        $quotation_date = date('Y-m-d');
        $expiry_date = '';
        $warehouse_id = '0';
        $expected_shipment_date = '';
        $payment_term = '0';
        $shipment_type = '';
        $sales_person = '0';
        $job_reference_no = '';
        $master_awb_no = '';
        $shipper = '0';
        $consignee = '0';
        $origin = '0';
        $destination = '0';
        $no_of_packs = '0';
        $gross_weight = '0';
        $chargeable_weight = '0';
        $volume = '0';
        $terms_and_conditions = '';
        $grand_subtotal = '0.00';
        $grand_discount_type = '';
        $grand_discount_type_value = '';
        $grand_discount_amount = '';
        $grand_after_discount = '';
        $customer_notes = '';
        $grand_tax = '0.00';
        $grand_total = '0.00';
        $is_active = 1;

        $item_id_arr = [];
        $service_arr = [];
        $description_arr = [];
        $qty_arr = [];
        $rate_arr = [];
        $sub_total_arr = [];
        $tax_arr = [];
        $tax_amount_arr = [];
        $total_arr = [];
        $total_rows = 1;

        if ($id > 0) {
            $created_by = 0;
            try {
                $sql = "SELECT created_by FROM `" . DB::QUOTATIONS . "` WHERE id = :id";
                $row = $this->db->fetchOne($sql, ['id' => $id]);
                $created_by = $row ? (int)$row['created_by'] : 0;
            } catch (\Throwable $e) {
                $created_by = 0;
            }

            $canEdit = Roles::hasFullAccess($session_role_id) || $session_user_id === $created_by;

            if ($canEdit) {
                try {
                    $quotation = $this->quotationService->getQuotation($id, $this->orgId);
                    $customer_id = (string)$quotation->customerId;
                    $lead_id = (string)$quotation->leadId;
                    $quotation_no = $quotation->quotationNo;
                    $quotation_status = $quotation->quotationStatus;
                    $quotation_date = $quotation->quotationDate;
                    $expiry_date = $quotation->expiryDate;
                    $warehouse_id = (string)$quotation->warehouseId;
                    $expected_shipment_date = (string)$quotation->expectedShipmentDate;
                    $payment_term = (string)$quotation->paymentTerm;
                    $shipment_type = (string)$quotation->shipmentType;
                    $sales_person = (string)$quotation->salesPerson;
                    $job_reference_no = (string)$quotation->jobReferenceNo;
                    $master_awb_no = (string)$quotation->masterAwbNo;
                    $shipper = (string)$quotation->shipper;
                    $consignee = (string)$quotation->consignee;
                    $origin = (string)$quotation->origin;
                    $destination = (string)$quotation->destination;
                    $no_of_packs = (string)$quotation->noOfPacks;
                    $gross_weight = (string)$quotation->grossWeight;
                    $chargeable_weight = (string)$quotation->chargeableWeight;
                    $volume = (string)$quotation->volume;
                    $customer_notes = (string)$quotation->customerNotes;
                    $terms_and_conditions = (string)$quotation->termsAndConditions;
                    $grand_subtotal = (string)$quotation->grandSubtotal;
                    $grand_discount_type = (string)$quotation->grandDiscountType;
                    $grand_discount_type_value = (string)$quotation->grandDiscountTypeValue;
                    $grand_discount_amount = (string)$quotation->grandDiscountAmount;
                    $grand_after_discount = (string)$quotation->grandAfterDiscount;
                    $grand_tax = (string)$quotation->grandTax;
                    $grand_total = (string)$quotation->grandTotal;
                    $is_active = $quotation->isActive ? 1 : 0;

                    $quotation_date = \App\Helper\DateHelper::toDbDate($quotation_date);
                    $expiry_date = ($expiry_date === '1970-01-01') ? '' : DateHelper::toDisplayDate($expiry_date);
                    $expected_shipment_date = ($expected_shipment_date === '1970-01-01') ? '' : DateHelper::toDisplayDate($expected_shipment_date);

                    $quotationItems = $this->quotationService->getQuotationItems($id, $this->orgId);
                    $total_rows = count($quotationItems);

                    foreach ($quotationItems as $item) {
                        $item_id_arr[] = $item->id;
                        $service_arr[] = $item->service;
                        $description_arr[] = $item->description;
                        $qty_arr[] = $item->qty;
                        $rate_arr[] = $item->rate;
                        $sub_total_arr[] = $item->subTotal;
                        $tax_arr[] = $item->tax;
                        $tax_amount_arr[] = $item->taxAmount;
                        $total_arr[] = $item->total;
                    }
                } catch (\Throwable $e) {
                    $error_message = $e->getMessage();
                }
            }
        }

        if ($total_rows == 0) {
            $total_rows = 1;
        }

        // Fetch dropdown data
        try {
            $customersList = $this->db->fetchAll("SELECT id, display_name FROM `" . DB::CUSTOMERS . "` WHERE is_active=1 AND approved=1 ORDER BY id DESC");
        } catch (\Throwable $e) {
            $customersList = [];
        }
        try {
            $leadsList = $this->db->fetchAll("SELECT id, lead_name FROM `" . DB::LEADS . "` WHERE is_active=1 ORDER BY lead_name");
        } catch (\Throwable $e) {
            $leadsList = [];
        }
        try {
            $orgList = $this->db->fetchAll("SELECT id, warehouse_name FROM `" . DB::ORGANIZATIONS . "` WHERE is_active=1");
        } catch (\Throwable $e) {
            $orgList = [];
        }
        try {
            $shippersList = $this->db->fetchAll("SELECT id, shipper_name FROM `" . DB::SHIPPERS . "` WHERE is_active=1");
        } catch (\Throwable $e) {
            $shippersList = [];
        }
        try {
            $consigneesList = $this->db->fetchAll("SELECT id, consignee_name FROM `" . DB::CONSIGNEES . "` WHERE is_active=1");
        } catch (\Throwable $e) {
            $consigneesList = [];
        }
        try {
            $itemsList = $this->db->fetchAll("SELECT id, item_name FROM `" . DB::ITEMS . "` WHERE is_active=1 AND item_type='services' ORDER BY item_name");
        } catch (\Throwable $e) {
            $itemsList = [];
        }

        return Response::html($this->view->render('quotations/form.php', [
            'id' => $id,
            'module' => $module,
            'moduleCaption' => $moduleCaption,
            'moduleId' => $moduleId,
            'session_user_id' => $session_user_id,
            'session_role_id' => $session_role_id,
            'error_message' => $error_message,
            'customer_id' => $customer_id,
            'lead_id' => $lead_id,
            'quotation_no' => $quotation_no,
            'quotation_status' => $quotation_status,
            'quotation_date' => $quotation_date,
            'expiry_date' => $expiry_date,
            'warehouse_id' => $warehouse_id,
            'expected_shipment_date' => $expected_shipment_date,
            'shipment_type' => $shipment_type,
            'sales_person' => $sales_person,
            'job_reference_no' => $job_reference_no,
            'master_awb_no' => $master_awb_no,
            'shipper' => $shipper,
            'consignee' => $consignee,
            'origin' => $origin,
            'destination' => $destination,
            'no_of_packs' => $no_of_packs,
            'gross_weight' => $gross_weight,
            'chargeable_weight' => $chargeable_weight,
            'volume' => $volume,
            'terms_and_conditions' => $terms_and_conditions,
            'grand_subtotal' => $grand_subtotal,
            'grand_discount_type' => $grand_discount_type,
            'grand_discount_type_value' => $grand_discount_type_value,
            'grand_discount_amount' => $grand_discount_amount,
            'grand_after_discount' => $grand_after_discount,
            'customer_notes' => $customer_notes,
            'grand_tax' => $grand_tax,
            'grand_total' => $grand_total,
            'is_active' => $is_active,
            'total_rows' => $total_rows,
            'item_id_arr' => $item_id_arr,
            'service_arr' => $service_arr,
            'description_arr' => $description_arr,
            'qty_arr' => $qty_arr,
            'rate_arr' => $rate_arr,
            'sub_total_arr' => $sub_total_arr,
            'tax_arr' => $tax_arr,
            'tax_amount_arr' => $tax_amount_arr,
            'total_arr' => $total_arr,
            'customersList' => $customersList,
            'leadsList' => $leadsList,
            'orgList' => $orgList,
            'shippersList' => $shippersList,
            'consigneesList' => $consigneesList,
            'itemsList' => $itemsList,
            'canCreate' => $this->canCreate(),
            'canEdit' => $this->canEdit(),
        ]));
    }
}
