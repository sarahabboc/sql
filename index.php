<?php
// Database connection
$servername = "localhost";
$username = "unyuzk4x2suhb";  
$password = "";  // Default password is usually empty (unless you've set a different one)
$dbname = "dbmudo43jgzhyt";  // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Form</title>
</head>
<body>

<!-- Header for the page -->
<header>
    <h1>Restaurant Name</h1>
    <p>Hours: 11am - 10pm</p>
</header>

<h2>Order Form</h2>

<form method="get" action="process_order.php" onsubmit="return validateForm()">
    <?php
    // Query to fetch menu items
    $sql = "SELECT * FROM menu";  // Assuming your menu table exists with columns: name, description, price, image
    $result = $conn->query($sql);

    // Loop through the menu items and display them
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "<div class='menu-item'>";
            echo "<h3>" . $row['name'] . "</h3>";
            echo "<p>" . $row['description'] . "</p>";
            echo "<p>Price: $" . number_format($row['price'], 2) . "</p>";
            echo "<img src='" . $row['image'] . "' alt='" . $row['name'] . "' width='100'><br>";
            echo "<label for='quantity_" . $row['id'] . "'>Quantity:</label>";
            echo "<select name='quantity_" . $row['id'] . "' id='quantity_" . $row['id'] . "'>";
            for ($i = 0; $i <= 10; $i++) {
                echo "<option value='$i'>$i</option>";
            }
            echo "</select><br><br>";
            echo "</div>";
        }
    } else {
        echo "<p>No menu items found.</p>";
    }
    ?>

    <!-- Customer Information -->
    <label for="first_name">First Name:</label>
    <input type="text" id="first_name" name="first_name" required><br>

    <label for="last_name">Last Name:</label>
    <input type="text" id="last_name" name="last_name" required><br>

    <label for="special_instructions">Special Instructions:</label><br>
    <textarea id="special_instructions" name="special_instructions"></textarea><br>

    <!-- Hidden Pickup Time Field -->
    <input type="hidden" id="pickup_time" name="pickup_time"><br>

    <input type="submit" value="Submit Order">
</form>

<script>
// JavaScript for form validation and pickup time calculation
function validateForm() {
    var itemsOrdered = false;
    var nameValid = document.getElementById('first_name').value && document.getElementById('last_name').value;

    // Check if at least one item is ordered
    var selectElements = document.querySelectorAll('select');
    selectElements.forEach(function(select) {
        if (select.value > 0) {
            itemsOrdered = true;
        }
    });

    if (!itemsOrdered) {
        alert("At least one item must be ordered.");
        return false;
    }

    if (!nameValid) {
        alert("Please provide your name.");
        return false;
    }

    // Set the pickup time (20 minutes from the current time)
    var pickupDate = new Date();
    pickupDate.setMinutes(pickupDate.getMinutes() + 20); // Add 20 minutes to current time
    var pickupTime = pickupDate.toISOString().slice(0, 19).replace("T", " "); // Format as 'YYYY-MM-DD HH:MM:SS'
    document.getElementById('pickup_time').value = pickupTime;

    return true;  // Allow form submission
}
</script>

</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
