<?php
/**
 * 403 Forbidden Page
 * 
 * This page is shown when a user tries to access a page without proper role permissions
 * Variables available from calling code:
 * - $error_message: Custom error message
 * - $required_role_ids: Array of role IDs required for access
 */

// Set HTTP response code
http_response_code(403);

// Get user info from session
global $project_pre;
$user_name = $_SESSION[$project_pre]['DASHBOARD']['name'] ?? 'User';
$user_role_id = $_SESSION[$project_pre]['DASHBOARD']['role_id'] ?? 0;
$current_role_name = Roles::getName($user_role_id);

// Build required roles text
$required_roles_text = '';
if (isset($required_role_ids) && is_array($required_role_ids)) {
    $role_names = array_map(function($rid) {
        return Roles::getName($rid);
    }, $required_role_ids);
    $required_roles_text = implode(' or ', $role_names);
}

// Default message if none provided
if (!isset($error_message) || !$error_message) {
    $error_message = $required_roles_text 
        ? "You need $required_roles_text access to view this page" 
        : 'You do not have permission to access this page';
}

// Get requested page info
$requested_page = $_SERVER['REQUEST_URI'] ?? 'Unknown Page';
$requested_file = basename($_SERVER['PHP_SELF'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>403 - Access Forbidden</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="../assets/custom_css/bootstrap.min.css">
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        
        .error-container {
            background: white;
            padding: 50px 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 600px;
            width: 100%;
        }
        
        .error-icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 30px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.9;
            }
        }
        
        .error-icon svg {
            width: 70px;
            height: 70px;
            fill: white;
        }
        
        .error-code {
            font-size: 80px;
            font-weight: 700;
            color: #dc3545;
            margin: 0 0 20px;
            line-height: 1;
        }
        
        h1 {
            font-size: 28px;
            font-weight: 600;
            margin: 0 0 15px;
            color: #333;
        }
        
        .error-message {
            font-size: 16px;
            color: #666;
            margin: 0 0 25px;
            line-height: 1.6;
        }
        
        .details-box {
            background: #f8f9fa;
            border-left: 4px solid #dc3545;
            padding: 20px;
            margin: 25px 0;
            text-align: left;
            border-radius: 4px;
        }
        
        .details-box h3 {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin: 0 0 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #555;
            font-size: 14px;
        }
        
        .detail-value {
            color: #666;
            font-size: 14px;
            text-align: right;
            max-width: 60%;
            word-break: break-word;
        }
        
        .badge-role {
            display: inline-block;
            padding: 4px 12px;
            background: #dc3545;
            color: white;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-required {
            display: inline-block;
            padding: 4px 12px;
            background: #28a745;
            color: white;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            margin: 2px;
        }
        
        .btn-group-custom {
            margin-top: 30px;
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-custom {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            font-size: 15px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-secondary-custom {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary-custom:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
            color: white;
        }
        
        .info-text {
            font-size: 13px;
            color: #888;
            margin-top: 20px;
            font-style: italic;
        }
        
        @media (max-width: 576px) {
            .error-container {
                padding: 30px 20px;
            }
            
            .error-code {
                font-size: 60px;
            }
            
            h1 {
                font-size: 22px;
            }
            
            .btn-group-custom {
                flex-direction: column;
            }
            
            .btn-custom {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <!-- Lock Icon -->
        <div class="error-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/>
            </svg>
        </div>
        
        <!-- Error Code -->
        <p class="error-code">403</p>
        
        <!-- Title -->
        <h1>Access Forbidden</h1>
        
        <!-- Error Message -->
        <p class="error-message">
            <?php echo htmlspecialchars($error_message); ?>
        </p>
        
        <!-- Access Details Box -->
        <div class="details-box">
            <h3>Access Details</h3>
            
            <div class="detail-row">
                <span class="detail-label">Your Role:</span>
                <span class="detail-value">
                    <span class="badge-role"><?php echo htmlspecialchars($current_role_name); ?></span>
                </span>
            </div>
            
            <?php if ($required_roles_text): ?>
            <div class="detail-row">
                <span class="detail-label">Required Role:</span>
                <span class="detail-value">
                    <?php 
                    if (is_array($required_role_ids)) {
                        foreach ($required_role_ids as $rid) {
                            echo '<span class="badge-required">' . htmlspecialchars(Roles::getName($rid)) . '</span> ';
                        }
                    } else {
                        echo '<span class="badge-required">' . htmlspecialchars($required_roles_text) . '</span>';
                    }
                    ?>
                </span>
            </div>
            <?php endif; ?>
            
            <div class="detail-row">
                <span class="detail-label">Requested Page:</span>
                <span class="detail-value"><?php echo htmlspecialchars($requested_file); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">User:</span>
                <span class="detail-value"><?php echo htmlspecialchars($user_name); ?></span>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="btn-group-custom">
            <a href="index.php" class="btn-custom btn-primary-custom">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4.5a.5.5 0 0 0 .5-.5v-4h2v4a.5.5 0 0 0 .5.5H14a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L8.354 1.146zM2.5 14V7.707l5.5-5.5 5.5 5.5V14H10v-4a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v4H2.5z"/>
                </svg>
                Go to Dashboard
            </a>
            
            <a href="javascript:history.back()" class="btn-custom btn-secondary-custom">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                </svg>
                Go Back
            </a>
        </div>
        
        <!-- Info Text -->
        <p class="info-text">
            If you believe you should have access to this page, please contact your system administrator.
        </p>
    </div>
</body>
</html>
