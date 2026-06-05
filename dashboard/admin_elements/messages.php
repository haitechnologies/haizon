<?php
/**
 * Standardized Success/Error/Info Message Display (Render-Once)
 *
 * Root issue fixed:
 * - Many modules render messages from multiple places (messages.php + breadcrumb.php + inline blocks)
 * - Same message can appear 2-3 times on the same page.
 *
 * Strategy:
 * - Deduplicate by message content per request.
 * - Consume message variables after rendering so legacy inline blocks don't re-render.
 */

if (!function_exists('dashboard_render_messages')) {
    function dashboard_render_messages()
    {
        $successRequest = trim((string)($_REQUEST['success_message'] ?? ''));
        $errorRequest = trim((string)($_REQUEST['error_message'] ?? ''));
        $infoRequest = trim((string)($_REQUEST['info_message'] ?? ''));

        $successVar = trim((string)($GLOBALS['success_message'] ?? ''));
        $errorVar = trim((string)($GLOBALS['error_message'] ?? ''));
        $infoVar = trim((string)($GLOBALS['info_message'] ?? ''));

        // Request params take precedence for post-redirect messages.
        $success = $successRequest !== '' ? $successRequest : $successVar;
        $error = $errorRequest !== '' ? $errorRequest : $errorVar;
        $info = $infoRequest !== '' ? $infoRequest : $infoVar;

        if (!isset($GLOBALS['__dashboard_rendered_message_keys']) || !is_array($GLOBALS['__dashboard_rendered_message_keys'])) {
            $GLOBALS['__dashboard_rendered_message_keys'] = [];
        }

        $render = function ($type, $title, $iconClass, $message, $alertClass) {
            if ($message === '') {
                return;
            }

            $key = $type . '|' . $message;
            if (isset($GLOBALS['__dashboard_rendered_message_keys'][$key])) {
                return;
            }
            $GLOBALS['__dashboard_rendered_message_keys'][$key] = true;

            echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
            echo '<i class="' . $iconClass . ' me-2"></i>';
            echo '<strong>' . $title . '</strong> ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
        };

        $render('success', 'Success!', 'fas fa-check-circle', $success, 'alert-success');
        $render('error', 'Error!', 'fas fa-exclamation-circle', $error, 'alert-danger');
        $render('info', 'Info:', 'fas fa-info-circle', $info, 'alert-info');

        // Consume values to prevent legacy inline blocks from re-rendering same messages.
        $GLOBALS['success_message'] = '';
        $GLOBALS['error_message'] = '';
        $GLOBALS['info_message'] = '';
        if (isset($_REQUEST['success_message'])) {
            unset($_REQUEST['success_message']);
        }
        if (isset($_REQUEST['error_message'])) {
            unset($_REQUEST['error_message']);
        }
        if (isset($_REQUEST['info_message'])) {
            unset($_REQUEST['info_message']);
        }
    }
}

dashboard_render_messages();
