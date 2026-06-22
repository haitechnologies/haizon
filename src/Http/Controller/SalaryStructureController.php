<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Core\Database;
use App\Http\Request;
use App\Http\Response;
use App\Service\SalaryStructureService;
use App\Exception\ValidationException;

class SalaryStructureController extends BaseController
{
    private SalaryStructureService $service;

    public function __construct(
        Database $db,
        int $userId,
        int $roleId,
        int $orgId,
        SalaryStructureService $service,
    ) {
        parent::__construct($db, $userId, $roleId, $orgId);
        $this->service = $service;
    }

    public function __invoke(Request $request): Response
    {
        $this->requiresModule('effective_froms', 'Salary Structure');

        if (!$this->canView()) {
            return new Response('Forbidden', 403);
        }

        if ($request->isPost() && !$this->validateCsrf($request)) {
            flash_error('Invalid security token.');
            return Response::redirect('listing_effective_froms.php');
        }

        $id = $request->getInt('id');
        $action = $request->getString('action');

        return match (true) {
            $request->isPost() && $action === 'update_effective_froms' && $id > 0 && $this->canEdit()
            => $this->handleUpdate($request, $id),
            $request->isPost() && $action === 'add_effective_froms' && $this->canCreate()
            => $this->handleCreate($request),
            default => $this->showForm($id),
        };
    }

    private function handleUpdate(Request $request, int $id): Response
    {
        try {
            $this->service->update($id, [
                'effective_from' => $request->post('effective_from', ''),
                'description' => $request->post('description', ''),
            ], $this->userId);
            flash_success('The Salary Structure has been updated successfully.');
            return Response::redirect('listing_effective_froms.php');
        } catch (ValidationException $e) {
            $error = current($e->getErrors());
            flash_error($error);
            return Response::redirect("listing_effective_froms.php");
        } catch (\Throwable) {
            flash_error('The Salary Structure could not be updated.');
            return Response::redirect("listing_effective_froms.php");
        }
    }

    private function handleCreate(Request $request): Response
    {
        try {
            $this->service->create([
                'effective_from' => $request->post('effective_from', ''),
                'description' => $request->post('description', ''),
            ], $this->userId);
            flash_success('The Salary Structure has been saved successfully.');
            return Response::redirect('listing_effective_froms.php');
        } catch (ValidationException $e) {
            $error = current($e->getErrors());
            flash_error($error);
            return Response::redirect("listing_effective_froms.php");
        } catch (\Throwable) {
            flash_error('The Salary Structure could not be saved.');
            return Response::redirect("listing_effective_froms.php");
        }
    }

    private function showForm(int $id): Response
    {
        $effectiveFrom = '';
        $description = '';

        if ($id > 0) {
            $item = $this->service->getById($id);
            if ($item === null) {
                flash_error('Record not found.');
                return Response::redirect('listing_salary_structures.php');
            }
            $effectiveFrom = $item->effectiveFrom;
            $description = $item->description;
        }

        return Response::html($this->view->render('effective_froms/form.php', [
            'id' => $id,
            'effectiveFrom' => $effectiveFrom,
            'description' => $description,
            'moduleCaption' => $this->moduleCaption,
            'module' => 'salary_structures',
            'canCreate' => $this->canCreate(),
            'canEdit' => $this->canEdit(),
        ]));
    }
}
