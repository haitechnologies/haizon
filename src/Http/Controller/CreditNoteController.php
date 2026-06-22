<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Core\DB;
use App\Core\Database;
use App\Http\Request;
use App\Http\Response;
use App\Service\CreditNoteService;
use App\Security\Roles;
use App\Exception\ValidationException;
use App\Helper\DateHelper;

class CreditNoteController extends BaseController
{
    private CreditNoteService $creditNoteService;

    public function __construct(
        Database $db,
        int $userId,
        int $roleId,
        int $orgId,
        CreditNoteService $creditNoteService,
    ) {
        parent::__construct($db, $userId, $roleId, $orgId);
        $this->creditNoteService = $creditNoteService;
    }

    public function __invoke(Request $request): Response
    {
        $this->requiresModule('credit_notes', 'Credit Note');

        if (!$this->canView()) {
            return new Response('Forbidden', 403);
        }

        if ($request->isPost() && !$this->validateCsrf($request)) {
            return new Response('Invalid security token.', 403);
        }

        $id = $request->getInt('id');
        $action = $request->getString('action');

        return match (true) {
            $request->isPost() && $action === 'update_credit_notes' && $id > 0 && $this->canEdit()
                => $this->handleUpdate($request, $id),
            $request->isPost() && $action === 'add_credit_notes' && $this->canCreate()
                => $this->handleCreate($request),
            default => $this->showForm($request, $id),
        };
    }

    private function handleUpdate(Request $request, int $id): Response
    {
        $noteData = $this->buildNoteData($request);
        $itemsData = $this->buildItemsData($request);

        try {
            $this->creditNoteService->updateNote($id, $noteData, $itemsData, $this->orgId, $this->userId);

            if ($request->get('save_and_send') == 1) {
                return Response::redirect("send_email.php?current_module=credit_notes&id=$id");
            }
            flash_success('The Credit Note has been updated successfully.');
            return Response::redirect('listing_credit_notes.php');
        } catch (ValidationException $e) {
            $error = current($e->getErrors());
            flash_error($error);
            return Response::redirect("credit_notes.php?id=$id&action=edit_credit_notes");
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
            return Response::redirect("credit_notes.php?id=$id&action=edit_credit_notes");
        }
    }

    private function handleCreate(Request $request): Response
    {
        $noteData = $this->buildNoteData($request);
        $itemsData = $this->buildItemsData($request);

        try {
            $newNote = $this->creditNoteService->createNote($noteData, $itemsData, $this->orgId, $this->userId);
            $id = $newNote->id;

            if ($request->get('save_and_send') == 1) {
                return Response::redirect("send_email.php?current_module=credit_notes&id=$id");
            }
            flash_success('The Credit Note has been saved successfully.');
            return Response::redirect('listing_credit_notes.php');
        } catch (ValidationException $e) {
            $error = current($e->getErrors());
            flash_error($error);
            return Response::redirect("credit_notes.php");
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
            return Response::redirect("credit_notes.php");
        }
    }

    private function buildNoteData(Request $request): array
    {
        return [
            'customer_id' => $request->getString('customer_id'),
            'credit_note_date' => $request->getString('credit_note_date'),
            'credit_note_status' => $request->getString('credit_note_status', 'draft'),
            'reference_no' => $request->getString('reference_no'),
            'invoice_id' => $request->getString('invoice_id', '0'),
            'warehouse_id' => $request->getString('warehouse_id'),
            'sales_person' => $request->getString('sales_person'),
            'customer_notes' => $request->getString('customer_notes'),
            'terms_and_conditions' => $request->getString('terms_and_conditions'),
            'grand_subtotal' => $request->getString('grand_subtotal', '0.00'),
            'grand_discount_type' => $request->getString('grand_discount_type'),
            'grand_discount_type_value' => $request->getString('grand_discount_type_value'),
            'grand_discount_amount' => $request->getString('grand_discount_amount'),
            'grand_after_discount' => $request->getString('grand_after_discount'),
            'grand_tax' => $request->getString('grand_tax', '0.00'),
            'grand_total' => $request->getString('grand_total', '0.00'),
            'publish' => $request->get('publish') ? true : false,
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
        $module = 'credit_notes';
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

        $credit_note_no = '';
        $credit_note_date = date('d-m-Y');
        $credit_note_status = 'draft';
        $reference_no = '';
        $customer_id = '0';
        $invoice_id = '0';
        $warehouse_id = '0';
        $sales_person = '0';
        $customer_notes = '';
        $terms_and_conditions = '';
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
                $sql = "SELECT created_by FROM `" . DB::CREDIT_NOTES . "` WHERE id = :id";
                $row = $this->db->fetchOne($sql, ['id' => $id]);
                $created_by = $row ? (int)$row['created_by'] : 0;
            } catch (\Throwable $e) {
                $created_by = 0;
            }

            $canEdit = Roles::hasFullAccess($session_role_id) || $session_user_id === $created_by;

            if ($canEdit) {
                try {
                    $creditNote = $this->creditNoteService->getCreditNote($id, $this->orgId);
                    $credit_note_no = $creditNote->creditNoteNo;
                    $credit_note_status = $creditNote->creditNoteStatus;
                    $credit_note_date = DateHelper::toDisplayDate($creditNote->creditNoteDate) ?: $creditNote->creditNoteDate;
                    $reference_no = (string)$creditNote->referenceNo;
                    $customer_id = (string)$creditNote->customerId;
                    $invoice_id = (string)$creditNote->invoiceId;
                    $warehouse_id = (string)$creditNote->warehouseId;
                    $sales_person = (string)$creditNote->salesPerson;
                    $customer_notes = (string)$creditNote->customerNotes;
                    $terms_and_conditions = (string)$creditNote->termsAndConditions;
                    $grand_subtotal = (string)$creditNote->grandSubtotal;
                    $grand_discount_type = (string)$creditNote->grandDiscountType;
                    $grand_discount_type_value = (string)$creditNote->grandDiscountTypeValue;
                    $grand_discount_amount = (string)$creditNote->grandDiscountAmount;
                    $grand_after_discount = (string)$creditNote->grandAfterDiscount;
                    $grand_tax = (string)$creditNote->grandTax;
                    $grand_total = (string)$creditNote->grandTotal;
                    $is_active = $creditNote->isActive ? 1 : 0;

                    $items = $this->creditNoteService->getCreditNoteItems($id, $this->orgId);
                    $total_rows = count($items);

                    foreach ($items as $item) {
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

        $customersList = [];
        $warehousesList = [];
        $itemsList = [];
        try {
            $customersList = $this->db->fetchAll("SELECT id, display_name FROM `" . DB::CUSTOMERS . "` WHERE is_active=1 AND approved=1 ORDER BY id DESC");
        } catch (\Throwable $e) {
        }
        try {
            $warehousesList = $this->db->fetchAll("SELECT id, warehouse_name FROM `" . DB::WAREHOUSES . "` WHERE is_active=1");
        } catch (\Throwable $e) {
        }
        try {
            $itemsList = $this->db->fetchAll("SELECT id, item_name FROM `" . DB::ITEMS . "` WHERE is_active=1 AND item_type='services' ORDER BY item_name");
        } catch (\Throwable $e) {
        }

        return Response::html($this->view->render('credit_notes/form.php', [
            'id' => $id,
            'module' => $module,
            'moduleCaption' => $moduleCaption,
            'moduleId' => $moduleId,
            'session_user_id' => $session_user_id,
            'session_role_id' => $session_role_id,
            'error_message' => $error_message,
            'credit_note_no' => $credit_note_no,
            'credit_note_date' => $credit_note_date,
            'credit_note_status' => $credit_note_status,
            'reference_no' => $reference_no,
            'customer_id' => $customer_id,
            'invoice_id' => $invoice_id,
            'warehouse_id' => $warehouse_id,
            'sales_person' => $sales_person,
            'customer_notes' => $customer_notes,
            'terms_and_conditions' => $terms_and_conditions,
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
            'customersList' => $customersList,
            'warehousesList' => $warehousesList,
            'itemsList' => $itemsList,
            'canCreate' => $this->canCreate(),
            'canEdit' => $this->canEdit(),
        ]));
    }
}
