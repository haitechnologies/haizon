# Flash Message Refactoring Plan

## Problem
~630 redirects across ~115 files pass flash messages via URL query params (`?success_message=...`).
On browser F5/refresh, the message persists because the query string remains in the URL.

## Solution
Use `src/Core/FlashMessage.php` (session-based) + global helpers `flash_success()`/`flash_error()`
already defined in `config/globals.php`. Renderer `admin_elements/messages.php` already reads
`FlashMessage::all()` as priority source, then renders + clears.

## Phases

### Phase 1: Controllers (69 files, ~520 redirects)
Replace `Response::redirect("listing_xxx.php?success_message=" . urlencode($msg))` with:
```php
flash_success($msg);
return Response::redirect("listing_xxx.php");
```
Replace `Response::redirect("listing_xxx.php?error_message=" . urlencode($msg))` with:
```php
flash_error($msg);
return Response::redirect("listing_xxx.php");
```
Same pattern for form page redirects:
```php
flash_error($msg);
return Response::redirect("form_page.php");
```

### Phase 2: Controller show*() readers (21 files)
Add fallback after `$request->getString('error_message')` to extract from FlashMessage::all():
```php
$error_message = $request->getString('error_message');
if (empty($error_message)) {
    foreach (\App\Core\FlashMessage::all() as $fm) {
        if ($fm['type'] === 'danger') { $error_message = $fm['message']; break; }
    }
}
```

### Phase 3: listing_handler.php (1 file, line 65)
```php
flash_success($success_message);
header("Location: listing_{$module}.php");
// was: header("Location: listing_{$module}.php?success_message=" . urlencode($success_message));
```

### Phase 4: Inline listing pages (47 files, ~48 header() calls)
Replace `header("Location: listing_$module.php?success_message=$success_message")` with:
```php
flash_success($success_message);
header("Location: listing_$module.php");
```

### Phase 5: Other dashboard files (29 files, ~65 header() calls)
Same pattern — replace URL query param messages with `flash_success()`/`flash_error()`.

### Phase 6: Cleanup standalone flash readers (3 files)
- `view_payslip.php`: Remove manual `$_SESSION['success_message']` read (messages.php handles it)
- `view_payroll_run.php`: Remove `$_GET['success_message']` + manual `$_SESSION` handling
- `payments_made_overview.php`: Remove `$_REQUEST['success_message']` read

## Key Design
- **Listing pages**: Include messages.php via listing_template.php -> breadcrumb.php -> messages.php.
  FlashMessage::all() is consumed and cleared there.
- **Form pages**: Do NOT include messages.php. Controller show*() methods read
  FlashMessage::all() and pass $error_message to view.
- **No double-consumption**: Each message consumed exactly once.
