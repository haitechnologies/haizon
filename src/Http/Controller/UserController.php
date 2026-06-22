<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Core\DB;
use App\Core\Database;
use App\Http\Request;
use App\Http\Response;
use App\Service\UserService;
use App\Service\UserDocumentService;
use App\Security\Roles;
use App\Exception\ValidationException;
use App\Exception\NotFoundException;

class UserController extends BaseController
{
    private UserService $userService;
    private UserDocumentService $documentService;
    private string $projectPre;

    public function __construct(
        Database $db,
        int $userId,
        int $roleId,
        int $orgId,
        UserService $userService,
        UserDocumentService $documentService,
        string $projectPre = '',
    ) {
        parent::__construct($db, $userId, $roleId, $orgId);
        $this->userService = $userService;
        $this->documentService = $documentService;
        $this->projectPre = $projectPre;
    }

    public function __invoke(Request $request): Response
    {
        $this->requiresModule('users', 'Employee');

        if (!$this->canView()) {
            return new Response('Forbidden', 403);
        }

        if ($request->isPost() && !$this->validateCsrf($request)) {
            return new Response('Invalid security token.', 403);
        }

        $id = $request->getInt('id');
        $action = $request->getString('action');

        return match (true) {
            $request->isPost() && $action === 'update_users' && $id > 0 && $this->canEdit()
                => $this->handleUpdate($request, $id),
            $request->isPost() && $action === 'add_users' && $this->canCreate()
                => $this->handleCreate($request),
            $request->isPost() && $action === 'list_user_docs' && $id > 0
                => $this->handleListUserDocs($id),
            $request->isPost() && $action === 'upload_user_doc' && $this->canEdit()
                => $this->handleUploadUserDoc($request),
            $request->isPost() && $action === 'delete_user_doc' && $id > 0 && $this->canEdit()
                => $this->handleDeleteUserDoc($request, $id),
            default => $this->showForm($request, $id),
        };
    }

    private function handleUpdate(Request $request, int $id): Response
    {
        $data = [
            'role_id' => $request->getString('role_id'),
            'first_name' => $request->getString('first_name'),
            'last_name' => $request->getString('last_name'),
            'email' => $request->getString('email'),
            'password' => $request->getString('password'),
            'contact1' => $request->getString('contact1'),
            'contact2' => $request->getString('contact2'),
            'address' => $request->getString('address'),
            'dob' => $request->getString('dob'),
            'can_access_system' => $request->get('can_access_system') ? true : false,
            'is_active' => $request->get('is_active') ? true : false,
        ];

        $isFullAccess = function_exists('has_full_access') && has_full_access();
        if (!$isFullAccess) {
            unset($data['role_id'], $data['password']);
        }

        try {
            $this->userService->update($id, $data);
            flash_success('Employee profile updated successfully.');
            return Response::redirect('listing_users.php');
        } catch (ValidationException $e) {
            $error = current($e->getErrors());
            flash_error($error);
            return Response::redirect("users.php?id=$id&action=edit_users");
        } catch (NotFoundException $e) {
            flash_error($e->getMessage());
            return Response::redirect("users.php?id=$id&action=edit_users");
        } catch (\Throwable $e) {
            flash_error('Unable to update employee profile: ' . get_class($e) . ': ' . $e->getMessage());
            return Response::redirect("users.php?id=$id&action=edit_users");
        }
    }

    private function handleCreate(Request $request): Response
    {
        $data = [
            'role_id' => $request->getString('role_id'),
            'first_name' => $request->getString('first_name'),
            'last_name' => $request->getString('last_name'),
            'email' => $request->getString('email'),
            'password' => $request->getString('password'),
            'contact1' => $request->getString('contact1'),
            'contact2' => $request->getString('contact2'),
            'address' => $request->getString('address'),
            'dob' => $request->getString('dob'),
            'can_access_system' => $request->get('can_access_system') ? true : false,
            'is_active' => $request->get('is_active') ? true : false,
        ];

        $isFullAccess = function_exists('has_full_access') && has_full_access();
        if (!$isFullAccess) {
            unset($data['role_id']);
        }

        try {
            $newUser = $this->userService->create($data, $this->userId);
            flash_success('Employee account created successfully.');
            return Response::redirect('listing_users.php');
        } catch (ValidationException $e) {
            $error = current($e->getErrors());
            flash_error($error);
            return Response::redirect("users.php");
        } catch (\Throwable $e) {
            flash_error('Unable to create employee account.');
            return Response::redirect("users.php");
        }
    }

    private function showForm(Request $request, int $id): Response
    {
        $roleId = '';
        $firstName = '';
        $lastName = '';
        $email = '';
        $password = '';
        $contact1 = '';
        $contact2 = '';
        $address = '';
        $dob = '';
        $canAccessSystem = 1;
        $isActive = 0;
        $error_message = '';
        $moduleCaption = $this->moduleCaption;
        $module = 'users';
        $moduleId = $this->moduleId;
        $session_user_id = $this->userId;

        $userDocuments = [];
        $documentCategories = [];

        if ($id > 0) {
            try {
                $user = $this->userService->getById($id);
                $roleId = (string)$user->roleId;
                $firstName = $user->firstName ?? '';
                $lastName = $user->lastName ?? '';
                $email = $user->email;
                $password = $user->password ?? '';
                $contact1 = $user->contact1 ?? '';
                $contact2 = $user->contact2 ?? '';
                $address = $user->address ?? '';
                $dob = $user->dob ? \App\Helper\DateHelper::toDbDate($user->dob) : '';
                $canAccessSystem = $user->canAccessSystem ? 1 : 0;
                $isActive = $user->isActive ? 1 : 0;
                $userDocuments = $this->documentService->getDocumentsByUser($id, $this->orgId);
                $docCats = $this->db->fetchAll("SELECT id, document_category FROM `" . DB::DOCUMENT_CATEGORIES . "` WHERE is_active=1 AND document_category_type='employees' ORDER BY document_category");
                foreach ($docCats as $dc) {
                    $documentCategories[] = $dc;
                }
            } catch (NotFoundException $e) {
                $error_message = $e->getMessage();
            }
        }

        $uploadPath = '../uploads/user_documents/';

        $rolesHtml = '';
        $roleRows = $this->db->fetchAll("SELECT * FROM DB::ROLES WHERE is_active = 1 AND id > 2 ORDER BY role_name ASC");
        foreach ($roleRows as $rows_roles) {
            $sel = $rows_roles['id'] == $roleId ? 'selected' : '';
            $rolesHtml .= '<option value="' . $rows_roles['id'] . '" ' . $sel . '>' . htmlspecialchars($rows_roles['role_name']) . '</option>';
        }

        return Response::html($this->view->render('users/form.php', [
            'id' => $id,
            'roleId' => $roleId,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email,
            'password' => $password,
            'contact1' => $contact1,
            'contact2' => $contact2,
            'address' => $address,
            'dob' => $dob,
            'canAccessSystem' => $canAccessSystem,
            'isActive' => $isActive,
            'error_message' => $error_message,
            'moduleCaption' => $moduleCaption,
            'module' => $module,
            'moduleId' => $moduleId,
            'session_user_id' => $session_user_id,
            'canCreate' => $this->canCreate(),
            'canEdit' => $this->canEdit(),
            'rolesHtml' => $rolesHtml,
            'userDocuments' => $userDocuments,
            'documentCategories' => $documentCategories,
            'uploadPath' => $uploadPath,
        ]));
    }

    private function handleListUserDocs(int $userId): Response
    {
        try {
            $docs = $this->documentService->getDocumentsByUser($userId, $this->orgId);
            $categories = [];
            $catRows = $this->db->fetchAll("SELECT id, document_category FROM `" . DB::DOCUMENT_CATEGORIES . "` WHERE is_active=1");
            foreach ($catRows as $cr) {
                $categories[$cr['id']] = $cr['document_category'];
            }
            $uploadPath = '../uploads/user_documents/';
            $rows = [];
            foreach ($docs as $doc) {
                $rows[] = [
                    'id' => $doc->id,
                    'category' => $categories[$doc->documentType] ?? 'Uncategorized',
                    'filename' => $doc->filename,
                    'original_filename' => $doc->originalFilename,
                    'file_url' => $uploadPath . $doc->filename,
                    'description' => $doc->description,
                    'issued_date' => $doc->issuedDate,
                    'expiry_date' => $doc->expiryDate,
                ];
            }
            return Response::json(['success' => true, 'data' => $rows]);
        } catch (\Throwable $e) {
            return Response::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function handleUploadUserDoc(Request $request): Response
    {
        $userId = $request->getInt('user_id');
        if ($userId <= 0) {
            return Response::json(['success' => false, 'error' => 'Invalid user ID.'], 400);
        }
        $data = [
            'user_id' => (string)$userId,
            'document_type' => $request->post('document_type', ''),
            'description' => $request->post('description', ''),
            'issued_date' => $request->post('issued_date', ''),
            'expiry_date' => $request->post('expiry_date', ''),
        ];
        $file = $request->getFile('document');
        try {
            $this->documentService->createDocument($data, $file, $this->orgId, $this->userId);
            return Response::json(['success' => true]);
        } catch (\App\Exception\ValidationException $e) {
            return Response::json(['success' => false, 'error' => current($e->getErrors())], 400);
        } catch (\Throwable $e) {
            return Response::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function handleDeleteUserDoc(Request $request, int $id): Response
    {
        $isSuperAdmin = Roles::hasFullAccess($this->roleId);
        try {
            $this->documentService->deleteDocument($id, $this->orgId, $this->userId, $isSuperAdmin);
            return Response::json(['success' => true]);
        } catch (\Throwable $e) {
            return Response::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
