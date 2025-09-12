<?php

namespace App;

use PDO;

class Article
{
    private PDO $db;
    private HtmlSanitizer $sanitizer;
    
    public function __construct(Database $database, ?HtmlSanitizer $sanitizer = null)
    {
        $this->db = $database->get_connection();
        $this->sanitizer = $sanitizer ?? new HtmlSanitizer();
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
        $sanitized_content = $this->sanitizer->sanitize($content);
        $stmt = $this->db->prepare("INSERT INTO articles (title, content) VALUES (?, ?)");
        return $stmt->execute([trim($title), $sanitized_content]);
    }
    
    public function generate_article_link(string $title): string
    {
        return '/search.php?search=' . urlencode($title);
    }
    
    public function update_article(int $id, string $title, string $content): bool
    {
        $sanitized_content = $this->sanitizer->sanitize($content);
        $stmt = $this->db->prepare("UPDATE articles SET title = ?, content = ? WHERE id = ?");
        return $stmt->execute([trim($title), $sanitized_content, $id]);
    }
    
    public function delete_article(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM articles WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function get_article_preview(string $content, int $length = 200): string
    {
        return $this->sanitizer->getTextPreview($content, $length);
    }
    
    public function sanitize_content(string $content): string
    {
        return $this->sanitizer->sanitize($content);
    }
}