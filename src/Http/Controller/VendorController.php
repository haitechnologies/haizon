<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Core\Database;
use App\Http\Request;
use App\Http\Response;
use App\Service\VendorService;
use App\Exception\ValidationException;

class VendorController extends BaseController
{
    private VendorService $service;

    public function __construct(
        Database $db,
        int $userId,
        int $roleId,
        int $orgId,
        VendorService $service,
    ) {
        parent::__construct($db, $userId, $roleId, $orgId);
        $this->service = $service;
    }

    public function __invoke(Request $request): Response
    {
        $this->requiresModule('vendor_types', 'Vendor');

        if (!$this->canView()) {
            return new Response('Forbidden', 403);
        }

        if ($request->isPost() && !$this->validateCsrf($request)) {
            flash_error('Invalid security token.');
            return Response::redirect('vendor_types.php');
        }

        $id = $request->getInt('id');
        $action = $request->getString('action');

        return match (true) {
            $request->isPost() && $action === 'update_vendor_types' && $id > 0 && $this->canEdit()
            => $this->handleUpdate($request, $id),
            $request->isPost() && $action === 'add_vendor_types' && $this->canCreate()
            => $this->handleCreate($request),
            default => $this->showForm($id),
        };
    }

    private function handleUpdate(Request $request, int $id): Response
    {
        try {
            $this->service->update($id, [
                'vendor_type' => $request->post('vendor_type', ''),
                'description' => $request->post('description', ''),
                'is_active' => $request->has('is_active') ? 1 : 0,
            ], $this->userId);
            flash_success('The Vendor has been updated successfully.');
            return Response::redirect('listing_vendor_types.php');
        } catch (ValidationException $e) {
            $error = current($e->getErrors());
            flash_error($error);
            return Response::redirect("vendor_types.php?id=$id&action=edit_vendor_types");
        } catch (\Throwable) {
            flash_error('The Vendor could not be updated.');
            return Response::redirect("vendor_types.php?id=$id&action=edit_vendor_types");
        }
    }

    private function handleCreate(Request $request): Response
    {
        try {
            $this->service->create([
                'vendor_type' => $request->post('vendor_type', ''),
                'description' => $request->post('description', ''),
                'is_active' => $request->has('is_active') ? 1 : 0,
            ], $this->userId);
            flash_success('The Vendor has been saved successfully.');
            return Response::redirect('listing_vendor_types.php');
        } catch (ValidationException $e) {
            $error = current($e->getErrors());
            flash_error($error);
            return Response::redirect("vendor_types.php");
        } catch (\Throwable) {
            flash_error('The Vendor could not be saved.');
            return Response::redirect("vendor_types.php");
        }
    }

    private function showForm(int $id): Response
    {
        $vendorType = '';
        $description = '';
        $publish = 1;

        if ($id > 0) {
            $item = $this->service->getById($id);
            if ($item === null) {
                flash_error('Record not found.');
                return Response::redirect('listing_vendor_types.php');
            }
            $vendorType = $item->vendorType;
            $description = $item->description;
            $publish = $item->isActive ? 1 : 0;
        }

        return Response::html($this->view->render('vendor_types/form.php', [
            'id' => $id,
            'vendorType' => $vendorType,
            'description' => $description,
            'publish' => $publish,
            'moduleCaption' => $this->moduleCaption,
            'module' => 'vendor_types',
            'canCreate' => $this->canCreate(),
            'canEdit' => $this->canEdit(),
        ]));
    }
}
