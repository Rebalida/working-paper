<?php

require_once __DIR__ . '/../Model.php';

class User extends Model {
    protected $table = 'users';

    /**
     * Find a user by email
     */
    public function findByEmail($email) {
        $stmt = $this->query("SELECT * FROM {$this->table} WHERE email = ?", [$email]);
        return $stmt->fetch();
    }

    /**
     * Create a user with hashed password
     */
    public function createUser($name, $email, $password, $role = 'admin') {
        return $this->create([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role
        ]);
    }

    /**
     * Verify password
     */
    public function verifyPassword($plainPassword, $hashedPassword) {
        return password_verify($plainPassword, $hashedPassword);
    }
}