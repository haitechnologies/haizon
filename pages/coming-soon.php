<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HAIPULSE - Coming Soon. A premier business directory and classifieds platform.">
    <meta name="robots" content="noindex, nofollow">
    <title>HAIPULSE - Coming Soon</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #1a3a52 0%, #2d5f7f 50%, #1a3a52 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .coming-soon-container {
            text-align: center;
            max-width: 600px;
            background: rgba(255, 255, 255, 0.98);
            padding: 60px 40px;
            border-radius: 8px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .logo {
            font-size: 32px;
            font-weight: 800;
            color: #1e5ba8;
            margin-bottom: 30px;
            letter-spacing: -0.5px;
        }

        h1 {
            font-size: 42px;
            color: #1e3a52;
            margin-bottom: 16px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .subtitle {
            font-size: 18px;
            color: #3f5168;
            line-height: 1.6;
            margin-bottom: 40px;
            font-weight: 400;
        }

        .divider {
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, transparent, #1e5ba8, transparent);
            margin: 30px auto 40px;
        }

        .description {
            font-size: 15px;
            color: #6b7c8f;
            line-height: 1.8;
            margin-bottom: 45px;
        }

        .cta-section {
            margin-top: 45px;
        }

        .cta-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #1e5ba8;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .email-form {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
        }

        .email-form input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e1e8f0;
            border-radius: 4px;
            font-size: 14px;
            color: #1e3a52;
            transition: border-color 0.3s ease;
        }

        .email-form input:focus {
            outline: none;
            border-color: #1e5ba8;
        }

        .email-form input::placeholder {
            color: #6b7c8f;
        }

        .email-form button {
            padding: 12px 28px;
            background: #1e5ba8;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
            white-space: nowrap;
        }

        .email-form button:hover {
            background: #1a4a95;
            transform: translateY(-1px);
        }

        .email-form button:active {
            transform: translateY(0);
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-top: 30px;
        }

        .social-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f0f4f9;
            color: #1e5ba8;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 18px;
        }

        .social-link:hover {
            background: #1e5ba8;
            color: white;
            transform: translateY(-3px);
        }

        .footer-text {
            font-size: 12px;
            color: #6b7c8f;
            margin-top: 30px;
            border-top: 1px solid #e1e8f0;
            padding-top: 20px;
        }

        @media (max-width: 640px) {
            .coming-soon-container {
                padding: 40px 24px;
            }

            h1 {
                font-size: 32px;
            }

            .subtitle {
                font-size: 16px;
            }

            .email-form {
                flex-direction: column;
            }

            .email-form button {
                width: 100%;
            }
        }

        /* Success message */
        .success-message {
            display: none;
            background: #f0f4f9;
            border-left: 4px solid #247445;
            padding: 16px;
            border-radius: 4px;
            margin-bottom: 20px;
            color: #247445;
            font-size: 14px;
        }

        .success-message.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="coming-soon-container">
        <div class="logo">HAIPULSE</div>
        
        <h1>Coming Soon</h1>
        
        <div class="divider"></div>
        
        <p class="subtitle">
            We're building something amazing for you
        </p>

        <p class="description">
            HAIPULSE is a premier business directory and classifieds platform designed to connect businesses and opportunities. 
            Our platform is being enhanced to bring you the best experience. 
            Stay tuned for the launch.
        </p>

        <div class="cta-section">
            <div class="cta-label">Get Notified</div>
            <form id="notifyForm" class="email-form">
                <input 
                    type="email" 
                    name="email" 
                    placeholder="Enter your email address" 
                    required
                    aria-label="Email address"
                >
                <button type="submit">Notify Me</button>
            </form>
            <div id="successMessage" class="success-message">
                ✓ Thank you! We'll notify you when we launch.
            </div>
        </div>

        <div class="footer-text">
            For inquiries, contact us at <strong>info@haipulse.com</strong>
        </div>
    </div>

    <script>
        const form = document.getElementById('notifyForm');
        const successMessage = document.getElementById('successMessage');

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = form.querySelector('input[name="email"]').value;
            
            // Simple client-side feedback (replace with actual backend integration if needed)
            successMessage.classList.add('show');
            form.style.display = 'none';
            
            // Reset after 5 seconds for demo
            setTimeout(() => {
                form.style.display = 'flex';
                form.reset();
                successMessage.classList.remove('show');
            }, 5000);
        });
    </script>
</body>
</html>
