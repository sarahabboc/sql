<?php
// Database connection
$servername = "localhost";
$username = "unyuzk4x2suhb";  // Default username for MySQL is often 'root'
$password = "";  // Default password is usually empty
$dbname = "dbmudo43jgzhyt";  // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$first_name = $_GET['first_name'];
$last_name = $_GET['last_name'];
$special_instructions = $_GET['special_instructions'];
$pickup_time = $_GET['pickup_time'];

// Process each menu item and quantity
$order_details = [];
foreach ($_GET as $key => $value) {
    if (strpos($key, 'quantity_') === 0 && $value > 0) {
        $item_id = substr($key, 9); // Get item ID from key (e.g., 'quantity_1' becomes '1')
        $order_details[] = ['item_id' => $item_id, 'quantity' => $value];
}

// Calculate the total for the order
$total = 0;
foreach ($order_details as $order) {
    // Fetch item details from the database
    $sql = "SELECT * FROM menu WHERE id = " . $order['item_id'];
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    $item_total = $row['price'] * $order['quantity'];
    $total += $item_total;

    // Display the item details
    echo "<h3>" . $row['name'] . "</h3>";
    echo "<p>Quantity: " . $order['quantity'] . "</p>";
    echo "<p>Price: $" . number_format($row['price'], 2) . "</p>";
    echo "<p>Total for item: $" . number_format($item_total, 2) . "</p><br>";
}

// Calculate tax (6.25%)
$tax = $total * 0.0625;
$subtotal = $total;
$final_total = $subtotal + $tax;

// Display the order summary
echo "<h3>Order Summary</h3>";
echo "<p>Subtotal: $" . number_format($subtotal, 2) . "</p>";
echo "<p>Tax (6.25%): $" . number_format($tax, 2) . "</p>";
echo "<p>Total: $" . number_format($final_total, 2) . "</p>";
echo "<p>Pickup Time: $pickup_time</p>";
echo "<p>Name: $first_name $last_name</p>";
echo "<p>Special Instructions: $special_instructions</p>";

// Close the database connection
$conn->close();
?>
