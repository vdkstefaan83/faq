<?php

namespace App;

use PDO;

class Auth
{
    private PDO $db;
    
    public function __construct(Database $database)
    {
        $this->db = $database->get_connection();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function login(string $username, string $password): bool
    {
        $stmt = $this->db->prepare("SELECT id, username, password_hash FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        
        $session_id = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $stmt = $this->db->prepare("INSERT INTO admin_sessions (session_id, admin_user_id, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$session_id, $user['id'], $expires_at]);
        
        $_SESSION['admin_session_id'] = $session_id;
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        
        return true;
    }
    
    public function logout(): void
    {
        if (isset($_SESSION['admin_session_id'])) {
            $stmt = $this->db->prepare("DELETE FROM admin_sessions WHERE session_id = ?");
            $stmt->execute([$_SESSION['admin_session_id']]);
        }
        
        session_unset();
        session_destroy();
    }
    
    public function is_authenticated(): bool
    {
        if (!isset($_SESSION['admin_session_id']) || !isset($_SESSION['admin_user_id'])) {
            return false;
        }
        
        $stmt = $this->db->prepare("SELECT id FROM admin_sessions WHERE session_id = ? AND admin_user_id = ? AND expires_at > NOW()");
        $stmt->execute([$_SESSION['admin_session_id'], $_SESSION['admin_user_id']]);
        
        return $stmt->fetch() !== false;
    }
    
    public function get_current_user(): ?string
    {
        if ($this->is_authenticated()) {
            return $_SESSION['admin_username'] ?? null;
        }
        
        return null;
    }
    
    public function require_auth(): void
    {
        if (!$this->is_authenticated()) {
            header('Location: /admin/login');
            exit;
        }
    }
    
    public function cleanup_expired_sessions(): void
    {
        $stmt = $this->db->prepare("DELETE FROM admin_sessions WHERE expires_at <= NOW()");
        $stmt->execute();
    }
    
    public function change_password(int $user_id, string $current_password, string $new_password): bool
    {
        $stmt = $this->db->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($current_password, $user['password_hash'])) {
            return false;
        }
        
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
        
        return $stmt->execute([$new_password_hash, $user_id]);
    }
    
    public function create_admin_user(string $username, string $password): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->fetch()) {
            return false; // User already exists
        }
        
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
        
        return $stmt->execute([$username, $password_hash]);
    }
    
    /**
     * @return array<int, array{id: int, username: string, created_at: string}>
     */
    public function get_all_admin_users(): array
    {
        $stmt = $this->db->prepare("SELECT id, username, created_at FROM admin_users ORDER BY created_at ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function get_current_user_id(): ?int
    {
        if ($this->is_authenticated()) {
            return $_SESSION['admin_user_id'] ?? null;
        }
        
        return null;
    }
}