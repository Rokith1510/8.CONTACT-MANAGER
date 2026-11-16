<?php
session_start();
require_once 'db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function message($msg, $type = 'success') {
    $_SESSION['flash'] = ['message' => $msg, 'type' => $type];
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        message("Security check failed!", "error");
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'save') {

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $category = trim($_POST['category'] ?? 'Personal');
        $notes = trim($_POST['notes'] ?? '');

        if ($name === '' || $email === '' || $phone === '') {
            message("Please fill all required fields!", "error");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            message("Enter a valid email address!", "error");
        }

        if ($id > 0) {

            $stmt = $pdo->prepare(
                "UPDATE contacts
                 SET name=?, email=?, phone=?, address=?,
                     category=?, notes=?
                 WHERE id=?"
            );

            $stmt->execute([
                $name, $email, $phone,
                $address, $category, $notes, $id
            ]);

            message("Contact updated successfully!");

        } else {

            $stmt = $pdo->prepare(
                "INSERT INTO contacts
                 (name,email,phone,address,category,notes)
                 VALUES (?,?,?,?,?,?)"
            );

            $stmt->execute([
                $name, $email, $phone,
                $address, $category, $notes
            ]);

            message("Contact added successfully!");
        }
    }

    if ($action === 'delete') {

        $id = (int)($_POST['id'] ?? 0);

        $stmt = $pdo->prepare(
            "DELETE FROM contacts WHERE id=?"
        );

        $stmt->execute([$id]);

        message("Contact deleted successfully!");
    }
}

$contacts = $pdo->query(
    "SELECT * FROM contacts ORDER BY name ASC"
)->fetchAll();

$total = count($contacts);

$personal = count(array_filter(
    $contacts,
    fn($c) => $c['category'] === 'Personal'
));

$work = count(array_filter(
    $contacts,
    fn($c) => $c['category'] === 'Work'
));

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Contactly - Contact Management</title>

<link rel="stylesheet" href="style.css">

</head>

<body>

<div class="app-shell">

<aside class="sidebar">

    <a class="brand" href="index.php">
        <span class="brand-mark">C</span>
        contactly<span class="brand-dot">.</span>
    </a>

    <div class="side-label">WORKSPACE</div>

    <a class="nav-link active" href="index.php">
        ▦ &nbsp; All Contacts
        <span class="nav-count"><?= $total ?></span>
    </a>

    <div class="sidebar-bottom">
        <div class="profile-avatar">CM</div>
        <div>
            <strong>Contact Manager</strong>
            <small>Personal Workspace</small>
        </div>
    </div>

</aside>

<main class="main-content">

<header class="topbar">
    <div class="breadcrumb">
        Workspace / <strong>Contacts</strong>
    </div>

    <span class="secure-label">● DATABASE CONNECTED</span>
</header>

<section class="page-heading">

    <div>
        <div class="eyebrow">YOUR NETWORK, IN ONE PLACE</div>

        <h1>Contacts<span class="heading-period">.</span></h1>

        <p class="subtitle">
            Keep the people who matter just one click away.
        </p>
    </div>

    <button class="primary-btn" id="addContactBtn">
        + Add Contact
    </button>

</section>

<?php if ($flash): ?>

<div class="alert <?= e($flash['type']) ?>" id="flashMessage">
    <?= e($flash['message']) ?>
</div>

<?php endif; ?>

<section class="stats-grid">

    <div class="stat-card">
        <div class="stat-top">Total Contacts</div>
        <div class="stat-number"><?= $total ?></div>
        <div class="stat-foot">People in your address book</div>
    </div>

    <div class="stat-card">
        <div class="stat-top">Personal</div>
        <div class="stat-number"><?= $personal ?></div>
        <div class="stat-foot">Friends and family</div>
    </div>

    <div class="stat-card">
        <div class="stat-top">Work</div>
        <div class="stat-number"><?= $work ?></div>
        <div class="stat-foot">Professional connections</div>
    </div>

</section>

<section class="contacts-panel">

    <div class="panel-header">

        <div>
            <h2>All Contacts</h2>
            <p>Manage your connections</p>
        </div>

        <div class="search-wrap">
            <input
                type="search"
                id="searchInput"
                placeholder="Search contacts..."
            >
        </div>

    </div>

    <div class="table-wrap">

    <table>

        <thead>
            <tr>
                <th>CONTACT</th>
                <th>EMAIL ADDRESS</th>
                <th>PHONE NUMBER</th>
                <th>CATEGORY</th>
                <th>ACTIONS</th>
            </tr>
        </thead>

        <tbody id="contactsBody">

        <?php foreach ($contacts as $contact):

            $data = [
                'id' => $contact['id'],
                'name' => $contact['name'],
                'email' => $contact['email'],
                'phone' => $contact['phone'],
                'address' => $contact['address'],
                'category' => $contact['category'],
                'notes' => $contact['notes']
            ];

            $initials = strtoupper(
                substr(preg_replace('/\s+/', '', $contact['name']), 0, 2)
            );

        ?>

        <tr class="contact-row"
            data-search="<?= e(strtolower(
                $contact['name'].' '.$contact['email'].' '.$contact['phone'].' '.$contact['category']
            )) ?>">

            <td>
                <div class="person-cell">
                    <div class="person-avatar">
                        <?= e($initials) ?>
                    </div>

                    <strong><?= e($contact['name']) ?></strong>
                </div>
            </td>

            <td><?= e($contact['email']) ?></td>

            <td><?= e($contact['phone']) ?></td>

            <td>
                <span class="category-pill <?= strtolower(e($contact['category'])) ?>">
                    <?= e($contact['category']) ?>
                </span>
            </td>

            <td>

                <div class="action-buttons">

                    <button class="icon-btn view-btn"
                        data-contact="<?= e(json_encode($data)) ?>">
                        ◉
                    </button>

                    <button class="icon-btn edit-btn"
                        data-contact="<?= e(json_encode($data)) ?>">
                        ✎
                    </button>

                    <form method="POST"
                        onsubmit="return confirm('Delete this contact?')">

                        <input type="hidden"
                            name="csrf_token"
                            value="<?= e($_SESSION['csrf_token']) ?>">

                        <input type="hidden"
                            name="action"
                            value="delete">

                        <input type="hidden"
                            name="id"
                            value="<?= (int)$contact['id'] ?>">

                        <button class="icon-btn delete-btn">
                            ⌫
                        </button>

                    </form>

                </div>

            </td>

        </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

    <div class="no-results" id="noResults" hidden>
        No contacts match your search.
    </div>

    </div>

    <div class="panel-footer">
        Showing <?= $total ?> contacts
        <span>Made to keep you connected ♥</span>
    </div>

</section>

</main>
</div>


<!-- ADD / EDIT CONTACT MODAL -->

<div class="modal-backdrop" id="contactModal">

<div class="modal">

    <div class="modal-header">

        <div>
            <div class="eyebrow">CONTACT DETAILS</div>
            <h2 id="modalTitle">Add a Contact</h2>
        </div>

        <button class="close-btn" data-close>×</button>

    </div>

    <form method="POST" id="contactForm">

        <input type="hidden"
            name="csrf_token"
            value="<?= e($_SESSION['csrf_token']) ?>">

        <input type="hidden" name="action" value="save">

        <input type="hidden" name="id" id="contactId" value="0">

        <div class="form-grid">

            <label class="full">
                Full Name *
                <input type="text" name="name" id="name"
                    placeholder="Enter full name" required>
            </label>

            <label>
                Email Address *
                <input type="email" name="email" id="email"
                    placeholder="example@gmail.com" required>
            </label>

            <label>
                Phone Number *
                <input type="text" name="phone" id="phone"
                    placeholder="+91 9876543210" required>
            </label>

            <label>
                Category
                <select name="category" id="category">
                    <option>Personal</option>
                    <option>Work</option>
                    <option>Other</option>
                </select>
            </label>

            <label>
                Address
                <input type="text" name="address" id="address"
                    placeholder="Enter address">
            </label>

            <label class="full">
                Notes
                <textarea name="notes" id="notes"
                    placeholder="Additional information..."></textarea>
            </label>

        </div>

        <div class="modal-actions">

            <button type="button" class="secondary-btn" data-close>
                Cancel
            </button>

            <button type="submit" class="primary-btn">
                Save Contact
            </button>

        </div>

    </form>

</div>
</div>


<!-- VIEW CONTACT MODAL -->

<div class="modal-backdrop" id="viewModal">

<div class="modal">

    <div class="modal-header">
        <h2>Contact Details</h2>
        <button class="close-btn" data-close>×</button>
    </div>

    <div class="profile-summary">

        <div class="profile-big-avatar" id="viewInitials"></div>

        <div>
            <h3 id="viewName"></h3>
            <span class="category-pill" id="viewCategory"></span>
        </div>

    </div>

    <div class="detail-list">

        <div>
            <small>EMAIL</small>
            <strong id="viewEmail"></strong>
        </div>

        <div>
            <small>PHONE</small>
            <strong id="viewPhone"></strong>
        </div>

        <div>
            <small>ADDRESS</small>
            <strong id="viewAddress"></strong>
        </div>

        <div>
            <small>NOTES</small>
            <p id="viewNotes"></p>
        </div>

    </div>

    <div class="modal-actions">
        <button class="secondary-btn" data-close>Close</button>
    </div>

</div>
</div>

<script src="script.js"></script>

</body>
</html>