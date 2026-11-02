<?php
$host = 'localhost';
$port = '3307'; // మీ XAMPP లో MySQL 3307 పోర్ట్ లో రన్ అవుతోంది కాబట్టి ఇది యాడ్ చేశాం
$dbname = 'ecommerce';
$user = 'root';
$password = ''; 

try {
    // ఇక్కడ dsn లో port=$port అని అదనంగా చేర్చాము
    $conn = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("డేటాబేస్ కనెక్ట్ అవ్వలేదు! Error: " . $e->getMessage());
}
?>
