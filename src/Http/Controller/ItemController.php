<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Core\Database;
use App\Http\Request;
use App\Http\Response;
use App\Service\ItemService;
use App\Exception\ValidationException;

class ItemController extends BaseController
{
    private ItemService $service;

    public function __construct(
        Database $db,
        int $userId,
        int $roleId,
        int $orgId,
        ItemService $service,
    ) {
        parent::__construct($db, $userId, $roleId, $orgId);
        $this->service = $service;
    }

    public function __invoke(Request $request): Response
    {
        $this->requiresModule('items', 'Item');

        if (!$this->canView()) {
            return new Response('Forbidden', 403);
        }

        if ($request->isPost() && !$this->validateCsrf($request)) {
            flash_error('Invalid security token.');
            return Response::redirect('items.php');
        }

        $id = $request->getInt('id');
        $action = $request->getString('action');

        return match (true) {
            $request->isPost() && $action === 'update_items' && $id > 0 && $this->canEdit()
            => $this->handleUpdate($request, $id),
            $request->isPost() && $action === 'add_items' && $this->canCreate()
            => $this->handleCreate($request),
            default => $this->showForm($id),
        };
    }

    private function handleUpdate(Request $request, int $id): Response
    {
        try {
            $this->service->update($id, [
                'item_type' => $request->post('item_type', 'services'),
                'item_name' => $request->post('item_name', ''),
                'unit_price' => $request->post('unit_price', '0'),
                'is_excise' => $request->has('is_excise') ? 1 : 0,
                'is_active' => $request->has('is_active') ? 1 : 0,
            ], $this->userId);
            flash_success('Item updated successfully.');
            return Response::redirect('listing_items.php');
        } catch (ValidationException $e) {
            $error = current($e->getErrors());
            flash_error($error);
            return Response::redirect("items.php?id=$id&action=edit_items");
        } catch (\Throwable) {
            flash_error('Item could not be updated.');
            return Response::redirect("items.php?id=$id&action=edit_items");
        }
    }

    private function handleCreate(Request $request): Response
    {
        try {
            $this->service->create([
                'item_type' => $request->post('item_type', 'services'),
                'item_name' => $request->post('item_name', ''),
                'unit_price' => $request->post('unit_price', '0'),
                'is_excise' => $request->has('is_excise') ? 1 : 0,
                'is_active' => $request->has('is_active') ? 1 : 0,
            ], $this->userId);
            flash_success('Item saved successfully.');
            return Response::redirect('listing_items.php');
        } catch (ValidationException $e) {
            $error = current($e->getErrors());
            flash_error($error);
            return Response::redirect("items.php");
        } catch (\Throwable) {
            flash_error('Item could not be saved.');
            return Response::redirect("items.php");
        }
    }

    private function showForm(int $id): Response
    {
        $itemType = 'services';
        $itemName = '';
        $unitPrice = '0';
        $isExcise = false;
        $publish = 1;

        if ($id > 0) {
            $item = $this->service->getById($id);
            if ($item === null) {
                flash_error('Record not found.');
                return Response::redirect('listing_items.php');
            }
            $itemType = $item->itemType;
            $itemName = $item->itemName;
            $unitPrice = $item->unitPrice;
            $isExcise = $item->isExcise;
            $publish = $item->isActive ? 1 : 0;
        }

        return Response::html($this->view->render('items/form.php', [
            'id' => $id,
            'itemType' => $itemType,
            'itemName' => $itemName,
            'unitPrice' => $unitPrice,
            'isExcise' => $isExcise,
            'publish' => $publish,
            'moduleCaption' => $this->moduleCaption,
            'module' => 'items',
            'canCreate' => $this->canCreate(),
            'canEdit' => $this->canEdit(),
        ]));
    }
}
