<?php
require_once __DIR__ . '/includes/init.php';
// Ensure this path is correct

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . hms_url('login.php'));
    exit();
}

$message = '';
$error = '';

// --- FORM HANDLING LOGIC ---

// 1. Handle ADDING a new item
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_item'])) {
    $item_name = mysqli_real_escape_string($connection, $_POST['item_name']);
    $description = mysqli_real_escape_string($connection, $_POST['description']);
    $quantity = intval($_POST['quantity']);

    if (!empty($item_name) && $quantity >= 0) {
        $query = "INSERT INTO inventory (item_name, description, quantity) VALUES ('$item_name', '$description', $quantity)";
        if (mysqli_query($connection, $query)) {
            $message = "New item added successfully!";
        } else {
            $error = "Error: Could not add item. It might already exist.";
        }
    } else {
        $error = "Item name and a valid quantity are required.";
    }
}

// 2. Handle UPDATING stock (Add/Remove)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_stock'])) {
    $item_id = intval($_POST['item_id']);
    $change_quantity = intval($_POST['change_quantity']);
    $action = $_POST['action'];

    if ($change_quantity > 0) {
        if ($action == 'add') {
            $query = "UPDATE inventory SET quantity = quantity + $change_quantity WHERE item_id = $item_id";
            $message = "Stock added successfully.";
        } elseif ($action == 'remove') {
            // Check to prevent stock from going below zero
            $query = "UPDATE inventory SET quantity = GREATEST(0, quantity - $change_quantity) WHERE item_id = $item_id";
            $message = "Stock removed successfully.";
        }
        mysqli_query($connection, $query);
    } else {
        $error = "Please enter a quantity greater than zero.";
    }
}

// 3. Handle DELETING an item
if (isset($_GET['delete'])) {
    $item_id = intval($_GET['delete']);
    $query = "DELETE FROM inventory WHERE item_id = $item_id";
    mysqli_query($connection, $query);
    $message = "Item deleted successfully.";
    // Redirect to clean the URL
    header("Location: inventory.php?message=" . urlencode($message));
    exit();
}


// --- DATA FETCHING ---
// Fetch all items to display in the table
$inventory_query = "SELECT item_id, item_name, description, quantity, last_updated FROM inventory ORDER BY item_name ASC";
$inventory_result = mysqli_query($connection, $inventory_query);

// Display message from redirect
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chamber Inventory Management</title>
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/font-awesome.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo hms_url('assets/css/style.css'); ?>">
</head>
<body>
    <?php include hms_path('includes/header.php'); // Assuming you have a header file ?>
    
    <div class="main-wrapper">
        <div class="page-wrapper">
            <div class="content">
                <div class="row">
                    <div class="col-sm-8 col-6">
                        <h4 class="page-title">Chamber Inventory</h4>
                    </div>
                    <div class="col-sm-4 col-6 text-right">
                        <button class="btn btn-primary btn-rounded" data-toggle="modal" data-target="#addItemModal">
                            <i class="fa fa-plus"></i> Add New Item
                        </button>
                    </div>
                </div>

                <!-- Display Success/Error Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                     <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card-box">
                            <div class="table-responsive">
                                <table class="table table-striped custom-table">
                                    <thead>
                                        <tr>
                                            <th>Item Name</th>
                                            <th>Description</th>
                                            <th class="text-center">Quantity</th>
                                            <th>Last Updated</th>
                                            <th class="text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($inventory_result)): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($row['item_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                                            <td class="text-center">
                                                <span class="badge <?php echo $row['quantity'] > 0 ? 'badge-success' : 'badge-danger'; ?>" style="font-size: 14px;">
                                                    <?php echo $row['quantity']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y H:i', strtotime($row['last_updated'])); ?></td>
                                            <td class="text-right">
                                                <button class="btn btn-success btn-sm" onclick="openStockModal('add', <?php echo $row['item_id']; ?>, '<?php echo htmlspecialchars(addslashes($row['item_name'])); ?>')"><i class="fa fa-plus"></i> Add</button>
                                                <button class="btn btn-warning btn-sm" onclick="openStockModal('remove', <?php echo $row['item_id']; ?>, '<?php echo htmlspecialchars(addslashes($row['item_name'])); ?>')"><i class="fa fa-minus"></i> Remove</button>
                                                <a href="inventory.php?delete=<?php echo $row['item_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this item permanently?');"><i class="fa fa-trash-o"></i></a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 1: Add New Item -->
    <div class="modal fade" id="addItemModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="inventory.php">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Inventory Item</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Item Name</label>
                            <input type="text" name="item_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Initial Quantity</label>
                            <input type="number" name="quantity" class="form-control" required min="0" value="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_item" class="btn btn-primary">Save Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal 2: Update Stock (Add/Remove) -->
    <div class="modal fade" id="updateStockModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="inventory.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateStockModalTitle">Update Stock</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Item: <strong id="modalItemName"></strong></p>
                        <input type="hidden" name="item_id" id="modalItemId">
                        <input type="hidden" name="action" id="modalAction">
                        <div class="form-group">
                            <label id="quantityLabel">Quantity to Add</label>
                            <input type="number" name="change_quantity" class="form-control" required min="1">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_stock" class="btn btn-primary" id="updateStockSubmitButton">Update Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="<?php echo hms_url('assets/js/jquery-3.2.1.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/popper.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/bootstrap.min.js'); ?>"></script>
    <script src="<?php echo hms_url('assets/js/app.js'); ?>"></script>
    <script>
        function openStockModal(action, itemId, itemName) {
            // Set values for the modal
            document.getElementById('modalItemId').value = itemId;
            document.getElementById('modalAction').value = action;
            document.getElementById('modalItemName').innerText = itemName;

            // Customize modal labels and button based on action
            if (action === 'add') {
                document.getElementById('updateStockModalTitle').innerText = 'Add Stock';
                document.getElementById('quantityLabel').innerText = 'Quantity to Add';
                document.getElementById('updateStockSubmitButton').className = 'btn btn-success';
                document.getElementById('updateStockSubmitButton').innerText = 'Add Stock';
            } else {
                document.getElementById('updateStockModalTitle').innerText = 'Remove Stock';
                document.getElementById('quantityLabel').innerText = 'Quantity to Remove';
                document.getElementById('updateStockSubmitButton').className = 'btn btn-warning';
                 document.getElementById('updateStockSubmitButton').innerText = 'Remove Stock';
            }
            
            // Show the modal
            $('#updateStockModal').modal('show');
        }
    </script>
</body>
</html>