<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Core\Container;
use App\Core\DB;
use App\Core\Database;
use App\Http\Request;
use App\Http\Response;
use App\Service\LeadQuotationService;
use App\Security\Roles;
use App\Exception\ValidationException;
use App\Exception\NotFoundException;
use App\Helper\DateHelper;

class LeadQuotationController extends BaseController
{
    private LeadQuotationService $leadQuotationService;

    public function __construct(
        Database $db,
        int $userId,
        int $roleId,
        int $orgId,
        LeadQuotationService $leadQuotationService,
    ) {
        parent::__construct($db, $userId, $roleId, $orgId);
        $this->leadQuotationService = $leadQuotationService;
    }

    public function __invoke(Request $request): Response
    {
        $this->requiresModule('lead_quotations', 'Lead Quotation');

        if (!$this->canView()) {
            return new Response('Forbidden', 403);
        }

        if ($request->isPost() && !$this->validateCsrf($request)) {
            return new Response('Invalid security token.', 403);
        }

        $id = $request->getInt('id');
        $action = $request->getString('action');

        return match (true) {
            $request->isPost() && $action === 'update_lead_quotations' && $id > 0 && $this->canEdit()
                => $this->handleUpdate($request, $id),
            $request->isPost() && $action === 'add_lead_quotations' && $this->canCreate()
                => $this->handleCreate($request),
            default => $this->showForm($request, $id),
        };
    }

    private function handleUpdate(Request $request, int $id): Response
    {
        $quotationData = $this->buildLeadQuotationData($request);
        $itemsData = $this->buildItemsData($request);

        try {
            $this->leadQuotationService->updateQuotation($id, $quotationData, $itemsData, $this->orgId, $this->userId);

            flash_success('The Lead Quotation has been updated successfully.');
            return Response::redirect('listing_lead_quotations.php?lead_id=' . urlencode($request->getString('lead_id')));
        } catch (ValidationException $e) {
            $error = current($e->getErrors());
            flash_error($error);
            return Response::redirect("lead_quotations.php?id=$id&action=edit_lead_quotations");
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
            return Response::redirect("lead_quotations.php?id=$id&action=edit_lead_quotations");
        }
    }

    private function handleCreate(Request $request): Response
    {
        $quotationData = $this->buildLeadQuotationData($request);
        $itemsData = $this->buildItemsData($request);

        try {
            $newQuotation = $this->leadQuotationService->createQuotation($quotationData, $itemsData, $this->orgId, $this->userId);
            $id = $newQuotation->id;

            flash_success('The Lead Quotation has been saved successfully.');
            return Response::redirect('listing_lead_quotations.php?lead_id=' . urlencode($request->getString('lead_id')));
        } catch (ValidationException $e) {
            $error = current($e->getErrors());
            flash_error($error);
            return Response::redirect("lead_quotations.php");
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
            return Response::redirect("lead_quotations.php");
        }
    }

    private function buildLeadQuotationData(Request $request): array
    {
        return [
            'lead_id' => $request->getString('lead_id'),
            'quotation_date' => $request->getString('quotation_date'),
            'expiry_date' => $request->getString('expiry_date'),
            'warehouse_id' => $request->getString('warehouse_id'),
            'terms_and_conditions' => $request->getString('terms_and_conditions'),
            'customer_notes' => $request->getString('customer_notes'),
            'grand_subtotal' => $request->getString('grand_subtotal'),
            'grand_discount_type' => $request->getString('grand_discount_type'),
            'grand_discount_type_value' => $request->getString('grand_discount_type_value'),
            'grand_discount_amount' => $request->getString('grand_discount_amount'),
            'grand_after_discount' => $request->getString('grand_after_discount'),
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
        $module = 'lead_quotations';
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

        $lead_id = $request->getString('lead_id', '0');
        $lead_name = '';
        $quotation_no = '';
        $quotation_status = 'draft';
        $quotation_date = date('Y-m-d');
        $expiry_date = '';
        $warehouse_id = '0';
        $terms_and_conditions = '';
        $customer_notes = '';
        $grand_subtotal = '0.00';
        $grand_discount_type = '';
        $grand_discount_type_value = '';
        $grand_discount_amount = '';
        $grand_after_discount = '';
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
                    $quotation = $this->leadQuotationService->getQuotation($id, $this->orgId);
                    $lead_id = (string)$quotation->leadId;
                    $quotation_no = $quotation->quotationNo;
                    $quotation_status = $quotation->quotationStatus;
                    $quotation_date = $quotation->quotationDate;
                    $expiry_date = $quotation->expiryDate;
                    $warehouse_id = (string)$quotation->warehouseId;
                    $terms_and_conditions = (string)$quotation->termsAndConditions;
                    $customer_notes = (string)$quotation->customerNotes;
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

                    $quotationItems = $this->leadQuotationService->getQuotationItems($id, $this->orgId);
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

        if (!empty($lead_id) && $lead_id !== '0') {
            try {
                $leadRow = $this->db->fetchOne("SELECT lead_name FROM `" . DB::LEADS . "` WHERE id = :id", ['id' => (int)$lead_id]);
                $lead_name = $leadRow ? (string)$leadRow['lead_name'] : '';
            } catch (\Throwable $e) {
                $lead_name = '';
            }
        }

        try {
            $orgList = $this->db->fetchAll("SELECT id, warehouse_name FROM `" . DB::ORGANIZATIONS . "` WHERE is_active=1");
        } catch (\Throwable $e) {
            $orgList = [];
        }
        try {
            $itemsList = $this->db->fetchAll("SELECT id, item_name FROM `" . DB::ITEMS . "` WHERE is_active=1 AND item_type='services' ORDER BY item_name");
        } catch (\Throwable $e) {
            $itemsList = [];
        }

        return Response::html($this->view->render('lead_quotations/form.php', [
            'id' => $id,
            'module' => $module,
            'moduleCaption' => $moduleCaption,
            'moduleId' => $moduleId,
            'session_user_id' => $session_user_id,
            'session_role_id' => $session_role_id,
            'error_message' => $error_message,
            'lead_id' => $lead_id,
            'lead_name' => $lead_name,
            'quotation_no' => $quotation_no,
            'quotation_status' => $quotation_status,
            'quotation_date' => $quotation_date,
            'expiry_date' => $expiry_date,
            'warehouse_id' => $warehouse_id,
            'terms_and_conditions' => $terms_and_conditions,
            'customer_notes' => $customer_notes,
            'grand_subtotal' => $grand_subtotal,
            'grand_discount_type' => $grand_discount_type,
            'grand_discount_type_value' => $grand_discount_type_value,
            'grand_discount_amount' => $grand_discount_amount,
            'grand_after_discount' => $grand_after_discount,
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
            'orgList' => $orgList,
            'itemsList' => $itemsList,
            'canCreate' => $this->canCreate(),
            'canEdit' => $this->canEdit(),
        ]));
    }
}
