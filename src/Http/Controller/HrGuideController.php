<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Core\Database;
use App\Http\Request;
use App\Http\Response;

class HrGuideController extends BaseController
{
    public function __construct(
        Database $db,
        int $userId,
        int $roleId,
        int $orgId,
    ) {
        parent::__construct($db, $userId, $roleId, $orgId);
    }

    public function __invoke(Request $request): Response
    {
        return Response::html($this->view->render('hr_guide/index.php', [
            'module' => 'hr_guide',
            'moduleCaption' => 'HR Help Guide',
        ]));
    }
}
