<?php
session_start();
include("connection.php");

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // CSRF check
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        echo "Invalid CSRF token";
        exit();
    }

    $fname = filter_input(INPUT_POST,"fname", FILTER_SANITIZE_SPECIAL_CHARS);
        $lname = filter_input(INPUT_POST,"lname", FILTER_SANITIZE_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST,"email", FILTER_SANITIZE_SPECIAL_CHARS);
        $password = filter_input(INPUT_POST,"password", FILTER_SANITIZE_SPECIAL_CHARS);
        $dj_alias = filter_input(INPUT_POST,"dj_alias", FILTER_SANITIZE_SPECIAL_CHARS);
        $genre = filter_input(INPUT_POST,"genre", FILTER_SANITIZE_SPECIAL_CHARS);
        $equipment = filter_input(INPUT_POST,"equipment", FILTER_SANITIZE_SPECIAL_CHARS);

        if(empty($fname)){
            echo"Please enter firstname";
        }
        elseif(empty($lname)){
            echo"Please enter lastname";
        }elseif(empty($email)){
            echo"Please enter email address";
        }elseif(empty($password)){
            echo"Please enter password";
        }
        else{
            $hash = password_hash($password, PASSWORD_DEFAULT);
            // Include DJ-specific fields: dj_alias, genre, equipment
            $sql = "INSERT INTO users (fname,lname,email,password,dj_alias,genre,equipment) VALUES(?,?,?,?,?,?,?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssssss", $fname, $lname, $email, $hash, $dj_alias, $genre, $equipment);

            if(mysqli_stmt_execute($stmt)){
                header("Location: index.php");
                exit();
            } else {
                echo "Error: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
             

            
        }
}
