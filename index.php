<?php

require_once 'vendor/autoload.php';

use App\Database;
use App\Article;
use Bramus\Router\Router;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$router = new Router();

$loader = new FilesystemLoader('templates');
$twig = new Environment($loader);

$database = new Database();
$article_model = new Article($database);

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$twig->addGlobal('base_url', $base_url);

$router->get('/', function() use ($twig, $article_model) {
    $articles = $article_model->get_all_articles();
    echo $twig->render('public/index.html.twig', [
        'articles' => $articles
    ]);
});

$router->get('/admin', function() use ($twig, $article_model) {
    $articles = $article_model->get_all_articles();
    echo $twig->render('admin/dashboard.html.twig', [
        'articles' => $articles
    ]);
});

$router->post('/admin', function() use ($twig, $article_model, $base_url) {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    
    if (!empty($title) && !empty($content)) {
        $success = $article_model->create_article($title, $content);
        
        if ($success) {
            $article_link = $article_model->generate_article_link($title);
            $articles = $article_model->get_all_articles();
            
            echo $twig->render('admin/dashboard.html.twig', [
                'articles' => $articles,
                'success_message' => 'Article created successfully!',
                'article_link' => $article_link
            ]);
            return;
        }
    }
    
    $articles = $article_model->get_all_articles();
    echo $twig->render('admin/dashboard.html.twig', [
        'articles' => $articles,
        'error_message' => 'Failed to create article. Please try again.'
    ]);
});

$router->get('/search\.php', function() use ($twig, $article_model) {
    $search_term = $_GET['search'] ?? '';
    
    if (empty($search_term)) {
        header('Location: /');
        return;
    }
    
    $article = $article_model->get_article_by_title($search_term);
    
    if (!$article) {
        header('Location: /', true, 404);
        return;
    }
    
    echo $twig->render('public/article.html.twig', [
        'article' => $article
    ]);
});

$router->run();