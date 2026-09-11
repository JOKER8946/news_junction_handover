<?php
/**
 * Password compatibility layer for News Junction.
 *
 * Background: the `user` table in nj_cream historically stored passwords as
 * plaintext, and login compared them directly in SQL
 * ("WHERE email=? AND password=?"). This layer lets the app move to bcrypt
 * without invalidating a single existing password.
 *
 * How it works:
 *   - nj_password_verify() accepts BOTH a bcrypt hash and a legacy plaintext
 *     value, so logins keep working during and after the migration.
 *   - nj_password_upgrade_if_needed() silently re-hashes a legacy password the
 *     first time that user logs in, so the table heals itself over time.
 *
 * After migrate_passwords.php has been run once, every row is bcrypt and the
 * legacy branch simply stops being reached. It is kept as a safety net for any
 * row written by an un-migrated code path.
 */

/**
 * Is the stored value already a password_hash() result?
 */
function nj_password_is_hashed($stored)
{
    if (!is_string($stored) || $stored === '') {
        return false;
    }
    $info = password_get_info($stored);
    // PHP 7.4+: algo is 0 / null for a non-hash. PHP 8: null or '' .
    return !empty($info['algo']);
}

/**
 * Verify a submitted password against whatever is stored.
 * Handles bcrypt hashes and legacy plaintext transparently.
 */
function nj_password_verify($input, $stored)
{
    if (!is_string($input) || $input === '') {
        return false;
    }
    if (!is_string($stored) || $stored === '') {
        return false;
    }

    if (nj_password_is_hashed($stored)) {
        return password_verify($input, $stored);
    }

    // Legacy plaintext row. Constant-time compare so this does not leak
    // information through timing.
    return hash_equals($stored, $input);
}

/**
 * Produce a hash for storage.
 */
function nj_password_hash($plain)
{
    return password_hash($plain, PASSWORD_DEFAULT);
}

/**
 * If the stored value is legacy plaintext (or an outdated hash), replace it
 * with a fresh bcrypt hash. Call this immediately after a successful verify,
 * while the plaintext is still in hand.
 *
 * Returns true if the row was upgraded.
 */
function nj_password_upgrade_if_needed($db, $userId, $plain, $stored)
{
    $needs = false;

    if (!nj_password_is_hashed($stored)) {
        $needs = true;                       // legacy plaintext
    } elseif (password_needs_rehash($stored, PASSWORD_DEFAULT)) {
        $needs = true;                       // hash algo/cost moved on
    }

    if (!$needs) {
        return false;
    }

    $new = nj_password_hash($plain);

    if (!($stmt = $db->prepare("UPDATE user SET password=? WHERE id=?"))) {
        return false;
    }
    $stmt->bind_param("si", $new, $userId);
    $ok = $stmt->execute();
    $stmt->close();
    if (!$ok) {
        return false;
    }

    // CRITICAL: read the value back and confirm it still verifies.
    //
    // nj_cream.user.password was historically varchar(20). A bcrypt hash is 60
    // chars, and without STRICT sql_mode MySQL/MariaDB truncates it SILENTLY.
    // A truncated hash never verifies, which would lock the user out of their
    // own account on their next login. If the round-trip fails for any reason,
    // put the original value back and leave the row as legacy plaintext.
    $verified = false;
    if ($check = $db->prepare("SELECT password FROM user WHERE id=?")) {
        $check->bind_param("i", $userId);
        if ($check->execute()) {
            $res = $check->get_result();
            if ($row = $res->fetch_assoc()) {
                $verified = password_verify($plain, (string) $row['password']);
            }
        }
        $check->close();
    }

    if (!$verified) {
        // Roll back to the original stored value.
        if ($restore = $db->prepare("UPDATE user SET password=? WHERE id=?")) {
            $restore->bind_param("si", $stored, $userId);
            $restore->execute();
            $restore->close();
        }
        error_log(
            "nj_password: refusing to upgrade user id=$userId - stored hash did not "
            . "verify after write. Is user.password wide enough for bcrypt (60 chars)? "
            . "Run migrate_passwords.php, which widens the column first."
        );
        return false;
    }

    return true;
}
