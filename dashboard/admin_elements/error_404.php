<?php
/**
 * 404 Error Page
 * 
 * This page is displayed when a requested resource is not found
 * Includes navigation back to dashboard and other helpful links
 */

// If this is included from admin_header, headers are already sent
// If called directly, send 404 header
if (php_sapi_name() !== 'cli' && !headers_sent()) {
    http_response_code(404);
    header("Content-Type: text/html; charset=utf-8");
}

// Get requested URL if available
$requested_url = $_SERVER['REQUEST_URI'] ?? 'unknown';
$user_id = $_SESSION[$project_pre]['DASHBOARD']['user_id'] ?? 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | Haipulse</title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        
        .error-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 60px 40px;
            text-align: center;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .error-code {
            font-size: 120px;
            font-weight: 900;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 20px;
        }
        
        .error-icon {
            font-size: 80px;
            color: #667eea;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .error-message h2 {
            color: #333;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .error-message p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        
        .error-details {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 30px 0;
            border-radius: 6px;
            text-align: left;
            font-size: 14px;
            color: #666;
        }
        
        .error-details .detail-label {
            font-weight: 600;
            color: #333;
        }
        
        .error-actions {
            display: flex;
            gap: 15px;
            margin-top: 40px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-custom {
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .btn-secondary-custom {
            background: #f0f0f0;
            color: #333;
            border: 2px solid #ddd;
        }
        
        .btn-secondary-custom:hover {
            background: #e0e0e0;
            border-color: #667eea;
            color: #667eea;
            text-decoration: none;
        }
        
        .footer-text {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 1px solid #eee;
            color: #999;
            font-size: 12px;
        }
        
        .footer-text a {
            color: #667eea;
            text-decoration: none;
        }
        
        .footer-text a:hover {
            text-decoration: underline;
        }
        
        /* Responsive */
        @media (max-width: 600px) {
            .error-container {
                padding: 40px 20px;
            }
            
            .error-code {
                font-size: 80px;
            }
            
            .error-message h2 {
                font-size: 24px;
            }
            
            .error-actions {
                flex-direction: column;
                gap: 10px;
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
        
        <!-- Error Icon -->
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        
        <!-- Error Code -->
        <div class="error-code">404</div>
        
        <!-- Error Message -->
        <div class="error-message">
            <h2>Page Not Found</h2>
            <p>Sorry, the page you're looking for doesn't exist or has been removed.</p>
        </div>
        
        <!-- Error Details -->
        <div class="error-details">
            <p>
                <span class="detail-label">Requested URL:</span>
                <br>
                <small><?php echo htmlspecialchars($requested_url); ?></small>
            </p>
        </div>
        
        <!-- Suggestions -->
        <div style="background: #e7f3ff; padding: 20px; border-radius: 6px; margin: 20px 0;">
            <h6 style="color: #0066cc; margin-bottom: 10px;">
                <i class="fas fa-lightbulb"></i> What you can do:
            </h6>
            <ul style="text-align: left; margin: 0; padding-left: 20px; color: #333; font-size: 14px;">
                <li>Check the URL and try again</li>
                <li>Return to the main dashboard</li>
                <li>Browse available modules</li>
                <li>Contact support if you need help</li>
            </ul>
        </div>
        
        <!-- Action Buttons -->
        <div class="error-actions">
            <a href="index.php" class="btn-custom btn-primary-custom">
                <i class="fas fa-home"></i> Back to Dashboard
            </a>
            <a href="listing_modules.php" class="btn-custom btn-secondary-custom">
                <i class="fas fa-th-list"></i> Browse Modules
            </a>
        </div>
        
        <!-- Footer -->
        <div class="footer-text">
            <p>Haipulse © 2026 | Error Code: 404</p>
            <p>
                <a href="#" onclick="history.back(); return false;">
                    <i class="fas fa-arrow-left"></i> Go Back
                </a>
            </p>
        </div>
        
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
