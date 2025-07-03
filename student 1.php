<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .fade-in {
  animation: fadeIn 1s ease-out forwards;
  opacity: 0;
}

@keyframes fadeIn {
  0% {
    transform: translateY(-20px);
    opacity: 0;
  }
  100% {
    transform: translateY(0);
    opacity: 1;
  }
}
    </style>
    <link rel="stylesheet" href="style.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    
</body>
</html>

<?php

require 'conn.php';

// Get NFC ID from the URL (this will be passed when the student badges)
$nfc_id = isset($_GET['nfc_id']) ? $_GET['nfc_id'] : null;

if ($nfc_id) {
    // Query the database for student info based on NFC ID
    $stmt = $pdo->prepare("SELECT * FROM users WHERE nfc_id = :nfc_id");
    $stmt->bindParam(':nfc_id', $nfc_id);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_OBJ);

    // If the student exists
    if ($student) {
        // Display a welcome message
        echo "<h1 class='fade-in'>Welcome, " . htmlspecialchars($student->nom) . "!</h1>";

        // Insert attendance record into the database (mark present)
        $stmt = $pdo->prepare("INSERT INTO attendance (nfc_id, present) VALUES (:nfc_id, TRUE)");
        $stmt->bindParam(':nfc_id', $nfc_id);
        $stmt->execute();

        echo "<p class='fade-in'>Attendance recorded: Present (✅)</p>";
    } else {
        echo "<h1>Student not found. Please check your NFC card.</h1>";
    }
} else {
    echo "<h1>No NFC ID provided.</h1>";
}
?>
