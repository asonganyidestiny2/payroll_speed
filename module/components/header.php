<header>
    <h1>Welcome, <?= htmlspecialchars($username) ?> (Admin)</h1>
    <form method="POST" action="../logout.php">
        <button type="submit" class="logout-btn">Logout</button>
    </form>
</header>