<?php
require_once __DIR__ . '/auth.php';
?>
<nav class="nav-wrapper" role="navigation" aria-label="Main navigation">
    <div class="container">
        <div class="nav">
            <a href="index.php" class="logo" aria-label="Flix Home">
                <i class='bx bx-movie-play bx-tada main-color' aria-hidden="true"></i>Fl<span class="main-color">i</span>x
            </a>
            <ul class="nav-menu" id="nav-menu" role="menubar">
                <li role="none"><a href="index.html" role="menuitem">Home</a></li>
                <li role="none"><a href="index.html#movies" role="menuitem">Movies</a></li>
                <li role="none" class="search-container">
                    <form action="search.php" method="GET" role="search" aria-label="Search movies and series">
                        <input type="search" name="q" id="search-input" placeholder="Search..." aria-label="Search input" autocomplete="off">
                        <button type="submit" aria-label="Submit search">
                            <i class="bx bx-search" aria-hidden="true"></i>
                        </button>
                    </form>
                </li>
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <li role="none">
                            <a href="admin.php" class="btn btn-hover" role="menuitem">
                                <span>Admin</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    <li role="none">
                        <span class="btn" style="cursor: default; pointer-events: none;">
                            <span><?php echo htmlspecialchars(strtolower($_SESSION['username'])); ?></span>
                        </span>
                    </li>
                    <li role="none">
                        <a href="logout.php" class="btn btn-hover" role="menuitem">
                            <span>Logout</span>
                        </a>
                    </li>
                <?php else: ?>
                    <li role="none">
                        <a href="register.php" class="btn btn-hover" role="menuitem">
                            <span>Sign Up</span>
                        </a>
                    </li>
                    <li role="none">
                        <a href="login.php" class="btn btn-hover" role="menuitem">
                            <span>Sign in</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            <button class="hamburger-menu" id="hamburger-menu" aria-label="Toggle navigation menu" aria-expanded="false">
                <div class="hamburger"></div>
            </button>
        </div>
    </div>
</nav>

