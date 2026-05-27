<?php

require_once '../config/Database.php';

class AuditRepository
{
    public static function log(
        $userId,
        $action
    ) {

        $pdo = Database::connect();

        $stmt = $pdo->prepare("
            INSERT INTO audit_logs(
                user_id,
                action
            )
            VALUES(
                :user_id,
                :action
            )
        ");

        $stmt->execute([
            'user_id' => $userId,
            'action' => $action
        ]);
    }
}