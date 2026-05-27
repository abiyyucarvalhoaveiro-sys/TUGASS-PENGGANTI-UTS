<?php

require_once __DIR__ . '/../config/Database.php';

class DosenRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::connect();
    }

    public function paginate(
        $search = '',
        $page = 1,
        $limit = 5
    ) {

        $offset = ($page - 1) * $limit;

        $keyword = "%$search%";

        $sql = "
            SELECT *
            FROM dosen
            WHERE deleted_at IS NULL
            AND (
                nama LIKE ?
                OR nidn LIKE ?
            )
            ORDER BY id ASC
            LIMIT $limit OFFSET $offset
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            $keyword,
            $keyword
        ]);

        return $stmt->fetchAll();
    }

    public function create($data)
    {
        $sql = "
            INSERT INTO dosen(
                nidn,
                nama,
                email,
                foto
            )
            VALUES(
                ?,
                ?,
                ?,
                ?
            )
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            $data['nidn'],
            $data['nama'],
            $data['email'],
            $data['foto']
        ]);
    }

    public function find($id)
    {
        $sql = "
            SELECT *
            FROM dosen
            WHERE id = ?
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    public function update($id, $data)
    {
        $sql = "
            UPDATE dosen
            SET
                nidn = ?,
                nama = ?,
                email = ?
            WHERE id = ?
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            $data['nidn'],
            $data['nama'],
            $data['email'],
            $id
        ]);
    }

    public function softDelete($id)
    {
        $sql = "
            UPDATE dosen
            SET deleted_at = NOW()
            WHERE id = ?
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([$id]);
    }

    public function trash()
    {
        $sql = "
            SELECT *
            FROM dosen
            WHERE deleted_at IS NOT NULL
            ORDER BY id ASC
        ";

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll();
    }

    public function restore($id)
    {
        $sql = "
            UPDATE dosen
            SET deleted_at = NULL
            WHERE id = ?
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([$id]);
    }
}