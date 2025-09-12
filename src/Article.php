<?php

namespace App;

use PDO;

class Article
{
    private PDO $db;
    
    public function __construct(Database $database)
    {
        $this->db = $database->get_connection();
    }
    
    /**
     * @return array<int, array{id: int, title: string, content: string, created_at: string, updated_at: string}>
     */
    public function get_all_articles(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM articles ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * @return array{id: int, title: string, content: string, created_at: string, updated_at: string}|false
     */
    public function get_article_by_title(string $title): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM articles WHERE title = ?");
        $stmt->execute([$title]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * @return array{id: int, title: string, content: string, created_at: string, updated_at: string}|false
     */
    public function get_article_by_id(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function create_article(string $title, string $content): bool
    {
        $stmt = $this->db->prepare("INSERT INTO articles (title, content) VALUES (?, ?)");
        return $stmt->execute([$title, $content]);
    }
    
    public function generate_article_link(string $title): string
    {
        return '/search.php?search=' . urlencode($title);
    }
}