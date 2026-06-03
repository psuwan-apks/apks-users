<?php
class User {
    private static string $dataFile = __DIR__ . '/users.json';

    // Load all users from JSON file
    private static function loadUsers(): array {
        if (!file_exists(self::$dataFile)) {
            return [];
        }
        $json = file_get_contents(self::$dataFile);
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    // Save all users to JSON file
    private static function saveUsers(array $users): void {
        $json = json_encode($users, JSON_PRETTY_PRINT);
        file_put_contents(self::$dataFile, $json);
    }

    // Find a user by username
    public static function findByUsername(string $username): ?array {
        $users = self::loadUsers();
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                return $user;
            }
        }
        return null;
    }

    // Create a new user with hashed password
    public static function createUser(string $username, string $password): bool {
        // Prevent duplicate usernames
        if (self::findByUsername($username) !== null) {
            return false;
        }
        $users = self::loadUsers();
        $users[] = [
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ];
        self::saveUsers($users);
        return true;
    }
}

global $config;

$current_view = $config['PATH_TO_VIEW'].DS.'user'.DS;

$ACT2PROCESS = get("action");

switch ($ACT2PROCESS):
    default:
    case "user-login":
        $view = $current_view . "user-login.php";
        break;

    case "user-register":
        $view = $current_view . "user-register.php";
        break;

endswitch;
?>
