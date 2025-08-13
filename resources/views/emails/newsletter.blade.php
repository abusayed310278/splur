<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $newsletter->subject }}</title>
    <style>
        /* Fallback fonts & responsiveness */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f7;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 30px auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }

        .header {
            background-color: #0d6efd;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .content {
            padding: 30px 20px;
            line-height: 1.6;
            font-size: 16px;
        }

        .content p {
            margin: 0 0 1em;
        }

        .footer {
            padding: 15px 20px;
            text-align: center;
            font-size: 13px;
            color: #999;
            background: #f1f1f1;
        }

        .footer a {
            color: #0d6efd;
            text-decoration: none;
        }

        @media only screen and (max-width: 600px) {
            .container {
                width: 95% !important;
            }

            .content, .footer {
                padding: 20px !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $newsletter->subject }}</h1>
        </div>

        <div class="content">
            <!-- {!! nl2br(e($messageContent)) !!} -->
            {!! $messageContent !!}


        </div>

        <div class="footer">
            <p>You received this email because you subscribed to Splurjj updates.</p>
            <!-- <p><a href="#">Unsubscribe</a> | <a href="#">View Online</a></p> -->
        </div>
    </div>
</body>
</html>
