<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Core\Database;
use App\Http\Request;
use App\Http\Response;
use App\Service\LeaveRequestService;
use App\Exception\ValidationException;
use App\Exception\NotFoundException;

class LeaveRequestController extends BaseController
{
    private LeaveRequestService $leaveRequestService;

    public function __construct(
        Database $db,
        int $userId,
        int $roleId,
        int $orgId,
        LeaveRequestService $leaveRequestService,
    ) {
        parent::__construct($db, $userId, $roleId, $orgId);
        $this->leaveRequestService = $leaveRequestService;
    }

    public function __invoke(Request $request): Response
    {
        $this->requiresModule('leave_requests', 'Leave Request');

        if (!$this->canView()) {
            return new Response('Forbidden', 403);
        }

        if ($request->isPost() && !$this->validateCsrf($request)) {
            flash_error('Invalid security token.');
            return Response::redirect('listing_leave_requests.php');
        }

        $id = $request->getInt('id');
        $action = $request->getString('action');

        return match (true) {
            $request->isPost() && $action === 'update_leave_requests' && $id > 0 && $this->canEdit()
                => $this->handleUpdate($request, $id),
            $request->isPost() && $action === 'add_leave_requests' && $this->canCreate()
                => $this->handleCreate($request),
            default => $this->showForm($id),
        };
    }

    private function handleUpdate(Request $request, int $id): Response
    {
        $data = [
            'employee_id' => $request->getInt('employee_id'),
            'leave_type_id' => $request->getInt('leave_type_id'),
            'start_date' => $request->getString('start_date'),
            'end_date' => $request->getString('end_date'),
            'total_days' => $request->getString('total_days'),
            'reason' => $request->getString('reason'),
            'status' => $request->getString('status', 'pending'),
            'medical_report_provided' => (bool)$request->get('medical_report_provided'),
        ];

        $uploadedFile = $request->getFile('medical_report_file');
        if ($uploadedFile !== null && $uploadedFile['error'] === UPLOAD_ERR_OK) {
            $data['medical_report_file'] = $this->handleMedicalReportUpload($uploadedFile);
        }

        try {
            $this->leaveRequestService->update($id, $data, $this->orgId);
            flash_success('The Leave Request has been updated successfully.');
            return Response::redirect('listing_leave_requests.php');
        } catch (ValidationException $e) {
            $error = current($e->getErrors());
            flash_error($error);
            return Response::redirect("leave_requests.php?id=$id&action=edit_leave_requests");
        } catch (NotFoundException $e) {
            flash_error($e->getMessage());
            return Response::redirect("leave_requests.php?id=$id&action=edit_leave_requests");
        } catch (\Throwable $e) {
            flash_error('The Leave Request could not be updated.');
            return Response::redirect("leave_requests.php?id=$id&action=edit_leave_requests");
        }
    }

    private function handleCreate(Request $request): Response
    {
        $data = [
            'employee_id' => $request->getInt('employee_id'),
            'leave_type_id' => $request->getInt('leave_type_id'),
            'start_date' => $request->getString('start_date'),
            'end_date' => $request->getString('end_date'),
            'total_days' => (float)$request->getString('total_days'),
            'reason' => $request->getString('reason'),
            'status' => $request->getString('status', 'pending'),
            'medical_report_provided' => (bool)$request->get('medical_report_provided'),
        ];

        $uploadedFile = $request->getFile('medical_report_file');
        if ($uploadedFile !== null && $uploadedFile['error'] === UPLOAD_ERR_OK) {
            $data['medical_report_file'] = $this->handleMedicalReportUpload($uploadedFile);
        }

        try {
            $this->leaveRequestService->create($data, $this->orgId);
            flash_success('The Leave Request has been saved successfully.');
            return Response::redirect('listing_leave_requests.php');
        } catch (ValidationException $e) {
            $error = current($e->getErrors());
            flash_error($error);
            return Response::redirect("leave_requests.php");
        } catch (\Throwable $e) {
            flash_error('The Leave Request could not be saved.');
            return Response::redirect("leave_requests.php");
        }
    }

    private function showForm(int $id): Response
    {
        $employeeId = 0;
        $leaveTypeId = 0;
        $startDate = '';
        $endDate = '';
        $totalDays = 0.0;
        $reason = '';
        $status = 'pending';
        $medicalReportProvided = false;
        $medicalReportFile = null;
        $error_message = '';
        $moduleCaption = $this->moduleCaption;
        $module = 'leave_requests';
        $moduleId = $this->moduleId;
        $session_user_id = $this->userId;

        if ($id > 0) {
            try {
                $req = $this->leaveRequestService->getById($id, $this->orgId);
                $employeeId = $req->employeeId;
                $leaveTypeId = $req->leaveTypeId;
                $startDate = $req->startDate;
                $endDate = $req->endDate;
                $totalDays = $req->totalDays;
                $reason = $req->reason ?? '';
                $status = $req->status;
                $medicalReportProvided = $req->medicalReportProvided;
                $medicalReportFile = $req->medicalReportFile;
            } catch (NotFoundException $e) {
                $error_message = $e->getMessage();
            }
        }

        $users = \App\Core\Container::getInstance()->get(\App\Repository\UserRepository::class)->findAll();
        $leaveTypes = \App\Core\Container::getInstance()->get(\App\Service\LeaveTypeService::class)->list($this->orgId);

        $this->cleanupOldMedicalReports();
        $uploadPath = __DIR__ . '/../../../uploads/leave_requests/';

        return Response::html($this->view->render('leave_requests/form.php', [
            'id' => $id,
            'employeeId' => $employeeId,
            'leaveTypeId' => $leaveTypeId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalDays' => $totalDays,
            'reason' => $reason,
            'status' => $status,
            'error_message' => $error_message,
            'moduleCaption' => $moduleCaption,
            'module' => $module,
            'moduleId' => $moduleId,
            'session_user_id' => $session_user_id,
            'medicalReportProvided' => $medicalReportProvided,
            'medicalReportFile' => $medicalReportFile,
            'uploadPath' => '../uploads/leave_requests/',
            'canCreate' => $this->canCreate(),
            'canEdit' => $this->canEdit(),
            'users' => $users,
            'leaveTypes' => $leaveTypes,
        ]));
    }

    private function handleMedicalReportUpload(array $file): string
    {
        $uploadDir = __DIR__ . '/../../../uploads/leave_requests/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        if (!in_array($ext, $allowed, true)) {
            throw new ValidationException(['medical_report_file' => 'Only PDF, DOC, DOCX, JPG, PNG files are allowed.']);
        }

        $filename = 'medical_report_' . uniqid() . '_' . time() . '.' . $ext;
        $destPath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new \RuntimeException('Failed to upload medical report file.');
        }

        return $filename;
    }

    private function cleanupOldMedicalReports(): void
    {
        $maxAgeDays = 90;
        $uploadDir = __DIR__ . '/../../../uploads/leave_requests/';
        if (!is_dir($uploadDir)) {
            return;
        }
        $cutoff = time() - ($maxAgeDays * 86400);
        $files = glob($uploadDir . 'medical_report_*');
        if ($files === false) {
            return;
        }
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }
}
