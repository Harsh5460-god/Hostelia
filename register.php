<?php

include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = $_POST["full_name"];
    $student_id = $_POST["student_id"];
    $email = $_POST["email"];
    $phone = $_POST["phone"];
    $gender = $_POST["gender"];
    $room_type = $_POST["room_type"];
    $address = $_POST["address"];

    try {

        // Prepare SQL query
        $sql = "INSERT INTO students
                (full_name, student_id, email, phone, gender, room_type, address)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "sssssss",
            $full_name,
            $student_id,
            $email,
            $phone,
            $gender,
            $room_type,
            $address
        );

        // Execute query
        $stmt->execute();

        // Registration successful
        header("Location: success.php");
        exit;

    } catch (mysqli_sql_exception $e) {

        // Duplicate Student ID
        if ($e->getCode() == 1062) {

            echo "
            <!DOCTYPE html>
            <html>
            <head>
                <title>Registration Error | Hostelia</title>

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

                    a:hover {
                        background: #5d4c39;
                    }
                </style>
            </head>

            <body>

                <div class='error-box'>

                    <h2>Student ID Already Registered</h2>

                    <p>
                        The Student ID you entered is already registered.
                        Please use a different Student ID.
                    </p>

                    <a href='registration.html'>
                        TRY AGAIN
                    </a>

                </div>

            </body>
            </html>
            ";

        } else {

            // Other database errors

            echo "
            <h2>Registration Failed</h2>

            <p>
                Something went wrong while submitting your registration.
            </p>

            <a href='registration.html'>
                Try Again
            </a>
            ";
        }
    }

    $conn->close();

}

?>