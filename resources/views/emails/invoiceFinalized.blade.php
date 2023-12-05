<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Invoice Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .header {
            background-color: #f4f4f4;
            padding: 10px;
            text-align: center;
        }
        .content {
            padding: 20px;
        }
        .footer {
            text-align: center;
            padding: 10px;
            background-color: #f4f4f4;
        }
        .button {
            background-color: orange;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src='https://www.fastforwardexpress.ca/images/pexels-norma-mortenson-4391470-resized.jpg' alt='Landing Page Image' style='width: 100%; height: auto;'>
    </div>

    <div class="content">
        <h1>New Invoice Available</h1>
        <p>Hello,</p>
        <p>We are pleased to inform you that your invoice has been finalized and is now available for viewing. </p>
        <p>Please log in to your account to see the details, or to print a copy of your invoice</p>
        <p>
            <a href="{{ url('/invoices/' . $invoiceId) }}" class="button">View Invoice</a>
        </p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} Fast Forward Express. All rights reserved.</p>
    </div>
</body>
</html>
