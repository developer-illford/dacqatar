<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = $_POST["text"];
    $email = $_POST["email"];
    $message = $_POST["message"];
    $headers = "New message";

    // Set recipient email
    $to = "manastom670@gmail.com";

    // Email subject
    $subject = "New message from $name";

    // Email content
    $email_content = "Name: $name\n";
    $email_content .= "Email: $email\n";
    $email_content .= "Message:\n$message\n";

    // Send email
    mail($to, $headers, $subject, $email_content);

    // Reply email to sender
    $reply_subject = "Thank you for your message";
    $reply_message = "Dear $name,\n\nThank you for contacting us! We have received your message and will get back to you as soon as possible.\n\nBest regards,\nThe DAC Team";
    mail($email, $reply_subject, $reply_message);

    // Success message
    echo "Thank you for your message!";
} else {
    // If not a POST request, redirect to the form page
    header("Location: ".$_SERVER["PHP_SELF"]);
    exit;
}
?>
