<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Email configuration
    $to = " qatardac@gmail.com";
    $subject = "Message from " . $_POST["name"];
    $message = "Name: " . $_POST["name"] . "\n";
    $message .= "Email: " . $_POST["email"] . "\n";
    $message .= "Message: " . $_POST["message"];

    // Additional headers for the main email
    $headers = "From: " . $_POST["email"] . "\r\n";
    $headers .= "Reply-To: " . $_POST["email"] . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Sending email to main recipient
    if (mail($to, $subject, $message, $headers)) {
        // Additional headers for the reply email
        $reply_subject = "Thank you for your message";
        $reply_message = "Dear " . $_POST["name"] . ",\n\n";
        $reply_message .= "Thank you for contacting us. We have received your message and will get back to you as soon as possible.\n\n";
        $reply_message .= "Best Regards,\nTeam DAC";

        // Sending reply email
        mail($_POST["email"], $reply_subject, $reply_message, $headers);

        echo "Your message has been sent successfully.";
    } else {
        echo "Failed to send message. Please try again later.";
    }
} else {
    echo "Invalid request.";
}

?>
