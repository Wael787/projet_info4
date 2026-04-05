<?php
// includes/session.php
// À inclure en TOUT PREMIER dans chaque page PHP (avant tout echo/html)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
