<?php
class ArticleModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function ensureTable(array $defaults = []): void {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS articles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            badge VARCHAR(80) NOT NULL DEFAULT 'TIN MỚI',
            badge_class VARCHAR(40) NOT NULL DEFAULT 'bg-primary',
            article_date VARCHAR(30) NOT NULL,
            read_time VARCHAR(30) NOT NULL DEFAULT '5 phút đọc',
            image VARCHAR(255) NOT NULL,
            summary TEXT NOT NULL,
            content LONGTEXT NOT NULL,
            is_published TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $escapedNewline = chr(92) . 'n';
        $normalize = $this->pdo->prepare('UPDATE articles SET content = REPLACE(content, ?, ?) WHERE LOCATE(?, content) > 0');
        $normalize->execute([$escapedNewline, "\n", $escapedNewline]);

        if ((int)$this->pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn() === 0 && $defaults) {
            $stmt = $this->pdo->prepare('INSERT INTO articles (title, badge, badge_class, article_date, read_time, image, summary, content, is_published) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)');
            foreach ($defaults as $article) {
                $stmt->execute([
                    $article['title'],
                    $article['badge'],
                    $article['badge_class'],
                    $article['date'],
                    $article['read_time'],
                    $article['image'],
                    $article['summary'],
                    str_replace('\\n', "\n", $article['content'])
                ]);
            }
        }
    }

    public function getPublished(): array {
        $stmt = $this->pdo->query("SELECT id, title, badge, badge_class, article_date AS date, read_time, image, summary, content FROM articles WHERE is_published = 1 ORDER BY id ASC");
        return $stmt->fetchAll();
    }
}
