<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Honeypot check
    if (!empty($_POST["honeypot"])) {
        die("Bot detected. Access denied.");
    }

    // Verify Google reCAPTCHA
    $recaptcha_secret = "6LeLT7IqAAAAAIZ25bj3MuxduDpNCo-dF8JiuPMA";
    $recaptcha_response = $_POST["g-recaptcha-response"];

    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_data = [
        'secret' => $recaptcha_secret,
        'response' => $recaptcha_response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($recaptcha_data)
        ]
    ];
    $context  = stream_context_create($options);
    $response = file_get_contents($recaptcha_url, false, $context);
    $recaptcha_result = json_decode($response, true);

    if (!$recaptcha_result['success']) {
        die("reCAPTCHA verification failed. Please try again.");
    }

    // Email configuration
    $to = "sales@dacqatar.com";
    $subject = "New Enquiry From " . htmlspecialchars($_POST["name"]);

    // HTML Email Message
    $message = '
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f9f9f9;
                margin: 0;
                padding: 0;
            }
            .email-container {
                background-color: #ffffff;
                max-width: 600px;
                margin: 20px auto;
                border: 1px solid #ddd;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }
            .email-header {
                background-color: #f7f7f7;
                padding: 20px;
                text-align: center;
            }
            .email-header img {
                max-width: 150px;
            }
            .email-body {
                padding: 20px;
            }
            .email-body h2 {
                color: #333;
                margin-bottom: 10px;
            }
            .email-body p {
                color: #555;
                line-height: 1.6;
            }
            .email-footer {
                background-color: #f7f7f7;
                text-align: center;
                padding: 10px;
                font-size: 12px;
                color: #888;
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <img src="https://dacqatar.com/assets/images/dac_white_bg_logo.png" alt="DAC Qatar">
            </div>
            <div class="email-body">
                <h2>New Enquiry Details</h2>
                <p><strong>Name:</strong> ' . htmlspecialchars($_POST["name"]) . '</p>
                <p><strong>Email:</strong> ' . htmlspecialchars($_POST["email"]) . '</p>
                <p><strong>Phone:</strong> ' . htmlspecialchars($_POST["phone"]) . '</p>
                <p><strong>Service:</strong> ' . htmlspecialchars($_POST["serviceSelect"]) . '</p>
                <p><strong>Message:</strong><br>' . nl2br(htmlspecialchars($_POST["message"])) . '</p>
            </div>
            <div class="email-footer">
                &copy; ' . date("Y") . ' DAC Qatar. All rights reserved.
            </div>
        </div>
    </body>
    </html>';

    // Headers for HTML email
    $headers = "From: " . htmlspecialchars($_POST["email"]) . "\r\n";
    $headers .= "Reply-To: " . htmlspecialchars($_POST["email"]) . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Bcc: edb@illforddigital.com\r\n";

    // Sending email to main recipient
    if (mail($to, $subject, $message, $headers)) {
        // Reply email
        $reply_subject = "Thank you for your message";
        $reply_message = '
        <html>
        <head>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f9f9f9;
                    margin: 0;
                    padding: 0;
                }
                .email-container {
                    background-color: #ffffff;
                    max-width: 600px;
                    margin: 20px auto;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                }
                .email-header {
                    background-color: #f7f7f7;
                    padding: 20px;
                    text-align: center;
                }
                .email-header img {
                    max-width: 150px;
                }
                .email-body {
                    padding: 20px;
                }
                .email-body h2 {
                    color: #333;
                    margin-bottom: 10px;
                }
                .email-body p {
                    color: #555;
                    line-height: 1.6;
                }
                .email-footer {
                    background-color: #f7f7f7;
                    text-align: center;
                    padding: 10px;
                    font-size: 12px;
                    color: #888;
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">
                    <img src="https://dacqatar.com/assets/images/dac_white_bg_logo.png" alt="DAC Qatar">
                </div>
                <div class="email-body">
                    <h2>Thank you for your message</h2>
                    <p>Dear ' . htmlspecialchars($_POST["name"]) . ',</p>
                    <p>Thank you for contacting us. We have received your message and will get back to you as soon as possible.</p>
                    <p>Best Regards,<br>Team DAC</p>
                </div>
                <div class="email-footer">
                    &copy; ' . date("Y") . ' DAC Qatar. All rights reserved.
                </div>
            </div>
        </body>
        </html>';

        mail(htmlspecialchars($_POST["email"]), $reply_subject, $reply_message, $headers);

        echo "<script type='text/javascript'>alert('Your message has been sent successfully.'); window.location.href='index.html'</script>";
    } else {
        echo "<script type='text/javascript'>alert('Failed to send message. Please try again later.'); window.location.href='index.html'</script>";
    }
} else {
    echo "<script type='text/javascript'>alert('Invalid request.'); window.location.href='index.html'</script>";
}
?>
