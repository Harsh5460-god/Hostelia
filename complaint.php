<?php

include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $student_id = $_POST["student_id"];
    $student_name = $_POST["student_name"];
    $room_number = $_POST["room_number"];
    $complaint_type = $_POST["complaint_type"];
    $priority = $_POST["priority"];
    $complaint_text = $_POST["complaint_text"];

    try {

        // Insert complaint into database
        $sql = "INSERT INTO complaints
                (student_id, student_name, room_number, complaint_type, priority, complaint_text)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ssssss",
            $student_id,
            $student_name,
            $room_number,
            $complaint_type,
            $priority,
            $complaint_text
        );

        // Execute query
        $stmt->execute();

        // Successful submission
        echo "
        <!DOCTYPE html>
        <html>
        <head>

            <title>Complaint Submitted | Hostelia</title>

            <meta name='viewport' content='width=device-width, initial-scale=1.0'>

            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'
                  rel='stylesheet'>

            <link rel='preconnect' href='https://fonts.googleapis.com'>

            <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>

            <link href='https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600&display=swap'
                  rel='stylesheet'>

            <style>

                body {
                    background: #f7f3eb;
                    font-family: 'Poppins', sans-serif;
                    color: #40382f;
                }

                .success-box {
                    max-width: 700px;
                    margin: 100px auto;
                    padding: 60px 40px;
                    text-align: center;
                    background: #fbf8f2;
                    border: 1px solid #d8cfc2;
                }

                .success-box h1 {
                    font-family: 'Playfair Display', serif;
                    font-size: 42px;
                    margin: 20px 0;
                }

                .success-box p {
                    color: #75695d;
                    font-size: 15px;
                }

                .success-box a {
                    display: inline-block;
                    margin-top: 25px;
                    padding: 12px 24px;
                    background: #40382f;
                    color: white;
                    text-decoration: none;
                    letter-spacing: 1px;
                    font-size: 13px;
                }

                .success-box a:hover {
                    background: #5d4c39;
                }

            </style>

        </head>

        <body>

            <div class='success-box'>

                <div>
                    ✓
                </div>

                <h1>
                    Complaint Submitted
                </h1>

                <p>
                    Your complaint has been submitted successfully.
                </p>

                <p>
                    Our hostel management team will review your complaint.
                </p>

                <a href='complaints.html'>
                    SUBMIT ANOTHER COMPLAINT
                </a>

                <br>

                <a href='index.html'>
                    BACK TO HOME
                </a>

            </div>

        </body>
        </html>
        ";

    } catch (mysqli_sql_exception $e) {

        echo "
        <!DOCTYPE html>
        <html>
        <head>

            <title>Complaint Error | Hostelia</title>

            <style>

                body {
                    background: #f7f3eb;
                    font-family: Arial, sans-serif;
                    text-align: center;
                    padding-top: 100px;
                    color: #40382f;
                }

                .error-box {
                    width: 500px;
                    max-width: 90%;
                    margin: auto;
                    background: #fbf8f2;
                    padding: 40px;
                    border: 1px solid #d8cfc2;
                }

                h2 {
                    margin-bottom: 15px;
                }

                p {
                    color: #75695d;
                }

                a {
                    display: inline-block;
                    margin-top: 20px;
                    padding: 12px 22px;
                    background: #40382f;
                    color: white;
                    text-decoration: none;
                }

            </style>

        </head>

        <body>

            <div class='error-box'>

                <h2>
                    Complaint Submission Failed
                </h2>

                <p>
                    Something went wrong while submitting your complaint.
                </p>

                <a href='complaints.html'>
                    TRY AGAIN
                </a>

            </div>

        </body>
        </html>
        ";
    }

    $stmt->close();
    $conn->close();

}

?>