<?php
require 'conn.php';

// Get NFC ID from the URL (this will be passed when the student badges)
$nfc_id = isset($_GET['nfc_id']) ? htmlspecialchars($_GET['nfc_id']) : null;

// Fetch all students from the users table
$eleves = [];
$sql = $pdo->prepare("SELECT * FROM users");
$sql->execute();
$eleves = $sql->fetchAll(PDO::FETCH_OBJ);

?>

<form method="post" style="margin-bottom:20px;">
  <button type="submit" name="refresh_tables" class="btn-primary">Refresh</button>
</form>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Interface Admin – NFC Présence</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"> <!-- FontAwesome for icons -->
  <style>
    body {
      font-family: 'Arial', sans-serif;
      background-color: #f4f6f9;
      color: #333;
      padding: 20px;
      margin: 0;
      box-sizing: border-box;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background-color: white;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    th, td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }

    th {
      background-color: #4CAF50;
      color: white;
    }

    tr:hover {
      background-color: #f1f1f1;
    }

    button {
      padding: 10px 20px;
      font-size: 16px;
      cursor: pointer;
      background-color: #4CAF50;
      color: white;
      border: none;
      border-radius: 5px;
      transition: background-color 0.3s ease;
    }

    button:hover {
      background-color: #45a049;
    }

    .presence {
      font-size: 1.5em;
      text-align: center;
    }

    .presence.present {
      color: green;
    }

    .presence.absent {
      color: red;
    }

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

    .header {
      font-size: 28px;
      font-weight: bold;
      margin-bottom: 20px;
    }

    /* Style for the form */
    .form-container {
      background-color: #ffffff;
      padding: 20px;
      margin-bottom: 30px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .form-container label {
      font-size: 16px;
      margin-right: 10px;
    }

    .form-container input {
      padding: 10px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 4px;
      width: 100%;
    }

    .form-container button {
      background-color: #4CAF50;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }

    .form-container button:hover {
      background-color: #45a049;
    }
  </style>
</head>

<body>
  <div class="header">Interface Admin – Enregistrement NFC</div>

  <!-- Button to open NFC Tools -->
  <button onclick="ouvrirNFCTools()" class="btn-primary">📲 Scanner via NFC Tools</button>

  <script>
    // Function to open NFC Tools app
    function ouvrirNFCTools() {
      window.location.href = 'nfc://scan/'; // Attempt to deep-link
      setTimeout(() => {
        alert("Si NFC Tools ne s'est pas ouvert automatiquement, ouvrez-le manuellement, scannez la carte puis revenez ici.");
      }, 1200); // Delay ≈ 1 second before message
    }
  </script>

  <hr>

  <!-- Add Student Form -->
  <div class="form-container">
    <form id="ajoutForm" method="post" action="">
      <label>Nom :</label>
      <input type="text" name="nom" required>
      <label>UID NFC :</label>
      <input type="text" name="nfc_id" placeholder="Ex : 04A32C995B3780" required>
      <label>Email :</label>
      <input type="email" name="email" required>
      <button type="submit" name="ajouter">Ajouter l'élève</button>
    </form>
  </div>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
    $nom = trim($_POST['nom']);
    $nfc_id = trim($_POST['nfc_id']);
    $email = trim($_POST['email']);

    if ($nom && $nfc_id && $email) {
        // Check if the NFC ID already exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE nfc_id = :nfc_id");
        $checkStmt->bindParam(':nfc_id', $nfc_id);
        $checkStmt->execute();
        $exists = $checkStmt->fetchColumn();
    
        if ($exists) {
            echo '<div id="log" style="color:red;">Erreur : cet UID NFC est déjà attribué à un élève.</div>';
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (nom, nfc_id, email, created_at) VALUES (:nom, :nfc_id, :email, NOW())");
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':nfc_id', $nfc_id);
            $stmt->bindParam(':email', $email);
            if ($stmt->execute()) {
                echo '<div id="log">Élève ajouté avec succès.</div>';
            } else {
                echo '<div id="log" style="color:red;">Erreur lors de l\'ajout.</div>';
            }
        }
    } else {
        echo '<div id="log" style="color:red;">Tous les champs sont obligatoires.</div>';
    }
}
?>

  <h2>Liste des élèves</h2>
  <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">
    <table>
      <thead>
        <tr>
          <th>Nom</th>
          <th>UID</th>
          <th>Mail</th>
          <th>Date du Badgeage</th>
          <th>Présence</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if (!empty($eleves)) {
          foreach ($eleves as $eleve) {
            // Check if the student is present
            $presence = ($nfc_id && $eleve->nfc_id === $nfc_id) ? '✅' : '❌';
            $presenceClass = ($presence === '✅') ? 'present' : 'absent';

            // Fetch the badge time
            $stmt = $pdo->prepare("SELECT * FROM attendance WHERE nfc_id = :nfc_id ORDER BY timestamp DESC LIMIT 1");
            $stmt->bindParam(':nfc_id', $eleve->nfc_id);
            $stmt->execute();
            $attendance = $stmt->fetch(PDO::FETCH_OBJ);
            $badge_time = $attendance ? $attendance->timestamp : 'Non renseigné';

            echo "<tr class='fade-in'>";
            echo "<td>" . htmlspecialchars($eleve->nom) . "</td>";
            echo "<td>" . htmlspecialchars($eleve->nfc_id) . "</td>";
            echo "<td>" . htmlspecialchars($eleve->email) . "</td>";
            echo "<td>" . htmlspecialchars($badge_time) . "</td>";
            echo "<td class='presence $presenceClass'>$presence</td>";
            echo "</tr>";
          }
        } else {
          echo "<tr><td colspan='5' style='text-align:center;'>Aucun élève trouvé.</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </form>

</body>
</html>
