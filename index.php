<?php

require_once 'vendor/autoload.php';

use App\Database;
use App\Article;
use App\Auth;
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
$auth = new Auth($database);

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$twig->addGlobal('base_url', $base_url);
$twig->addGlobal('current_user', $auth->get_current_user());
$twig->addGlobal('tinymce_api_key', $_ENV['TINYMCE_API_KEY'] ?? 'no-api-key');

$auth->cleanup_expired_sessions();

$router->get('/', function() use ($twig, $article_model) {
    $articles = $article_model->get_all_articles();

    // Add preview to each article
    foreach ($articles as &$article) {
        $article['preview'] = $article_model->get_article_preview($article['content'], 250);
    }

    echo $twig->render('public/index.html.twig', [
        'articles' => $articles
    ]);
});

$router->get('/admin/login', function() use ($twig, $auth) {
    if ($auth->is_authenticated()) {
        header('Location: /admin');
        return;
    }
    
    echo $twig->render('admin/login.html.twig');
});

$router->post('/admin/login', function() use ($twig, $auth) {
    if ($auth->is_authenticated()) {
        header('Location: /admin');
        return;
    }
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($auth->login($username, $password)) {
        header('Location: /admin');
        return;
    }
    
    echo $twig->render('admin/login.html.twig', [
        'error_message' => 'Invalid username or password.',
        'username' => $username
    ]);
});

$router->get('/admin/logout', function() use ($auth) {
    $auth->logout();
    header('Location: /');
});

$router->get('/admin/change-password', function() use ($twig, $auth) {
    $auth->require_auth();
    echo $twig->render('admin/change_password.html.twig');
});

$router->post('/admin/change-password', function() use ($twig, $auth) {
    $auth->require_auth();
    
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo $twig->render('admin/change_password.html.twig', [
            'error_message' => 'All fields are required.'
        ]);
        return;
    }
    
    if ($new_password !== $confirm_password) {
        echo $twig->render('admin/change_password.html.twig', [
            'error_message' => 'New password and confirm password do not match.'
        ]);
        return;
    }
    
    if (strlen($new_password) < 6) {
        echo $twig->render('admin/change_password.html.twig', [
            'error_message' => 'New password must be at least 6 characters long.'
        ]);
        return;
    }
    
    $user_id = $auth->get_current_user_id();
    if (!$user_id) {
        header('Location: /admin/login');
        return;
    }
    
    if ($auth->change_password($user_id, $current_password, $new_password)) {
        echo $twig->render('admin/change_password.html.twig', [
            'success_message' => 'Password changed successfully!'
        ]);
    } else {
        echo $twig->render('admin/change_password.html.twig', [
            'error_message' => 'Current password is incorrect.'
        ]);
    }
});

$router->get('/admin/create-user', function() use ($twig, $auth) {
    $auth->require_auth();
    $admin_users = $auth->get_all_admin_users();
    echo $twig->render('admin/create_user.html.twig', [
        'admin_users' => $admin_users
    ]);
});

$router->post('/admin/create-user', function() use ($twig, $auth) {
    $auth->require_auth();
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $admin_users = $auth->get_all_admin_users();
    
    if (empty($username) || empty($password) || empty($confirm_password)) {
        echo $twig->render('admin/create_user.html.twig', [
            'admin_users' => $admin_users,
            'error_message' => 'All fields are required.',
            'username' => $username
        ]);
        return;
    }
    
    if ($password !== $confirm_password) {
        echo $twig->render('admin/create_user.html.twig', [
            'admin_users' => $admin_users,
            'error_message' => 'Password and confirm password do not match.',
            'username' => $username
        ]);
        return;
    }
    
    if (strlen($username) < 3) {
        echo $twig->render('admin/create_user.html.twig', [
            'admin_users' => $admin_users,
            'error_message' => 'Username must be at least 3 characters long.',
            'username' => $username
        ]);
        return;
    }
    
    if (strlen($password) < 6) {
        echo $twig->render('admin/create_user.html.twig', [
            'admin_users' => $admin_users,
            'error_message' => 'Password must be at least 6 characters long.',
            'username' => $username
        ]);
        return;
    }
    
    if ($auth->create_admin_user($username, $password)) {
        $admin_users = $auth->get_all_admin_users(); // Refresh list
        echo $twig->render('admin/create_user.html.twig', [
            'admin_users' => $admin_users,
            'success_message' => "Admin user '{$username}' created successfully!"
        ]);
    } else {
        echo $twig->render('admin/create_user.html.twig', [
            'admin_users' => $admin_users,
            'error_message' => 'Username already exists. Please choose a different username.',
            'username' => $username
        ]);
    }
});

$router->get('/admin', function() use ($twig, $article_model, $auth) {
    $auth->require_auth();

    $articles = $article_model->get_all_articles();

    // Add preview to each article
    foreach ($articles as &$article) {
        $article['preview'] = $article_model->get_article_preview($article['content'], 200);
    }

    echo $twig->render('admin/dashboard.html.twig', [
        'articles' => $articles
    ]);
});

$router->post('/admin', function() use ($twig, $article_model, $base_url, $auth) {
    $auth->require_auth();
    
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    
    if (!empty($title) && !empty($content)) {
        $success = $article_model->create_article($title, $content);
        
        if ($success) {
            $article_link = $article_model->generate_article_link($title);
            $articles = $article_model->get_all_articles();

            // Add preview to each article
            foreach ($articles as &$article) {
                $article['preview'] = $article_model->get_article_preview($article['content'], 200);
            }

            echo $twig->render('admin/dashboard.html.twig', [
                'articles' => $articles,
                'success_message' => 'Article created successfully!',
                'article_link' => $article_link
            ]);
            return;
        }
    }
    
    $articles = $article_model->get_all_articles();

    // Add preview to each article
    foreach ($articles as &$article) {
        $article['preview'] = $article_model->get_article_preview($article['content'], 200);
    }

    echo $twig->render('admin/dashboard.html.twig', [
        'articles' => $articles,
        'error_message' => 'Failed to create article. Please try again.'
    ]);
});

$router->get('/admin/edit-article/(\d+)', function($id) use ($twig, $article_model, $auth) {
    $auth->require_auth();
    
    $article = $article_model->get_article_by_id((int)$id);
    if (!$article) {
        header('Location: /admin');
        return;
    }
    
    echo $twig->render('admin/edit_article.html.twig', [
        'article' => $article
    ]);
});

$router->post('/admin/edit-article/(\d+)', function($id) use ($twig, $article_model, $auth) {
    $auth->require_auth();
    
    $article = $article_model->get_article_by_id((int)$id);
    if (!$article) {
        header('Location: /admin');
        return;
    }
    
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    
    if (!empty($title) && !empty($content)) {
        if ($article_model->update_article((int)$id, $title, $content)) {
            $updated_article = $article_model->get_article_by_id((int)$id);
            echo $twig->render('admin/edit_article.html.twig', [
                'article' => $updated_article,
                'success_message' => 'Article updated successfully!'
            ]);
            return;
        }
    }
    
    echo $twig->render('admin/edit_article.html.twig', [
        'article' => $article,
        'error_message' => 'Failed to update article. Please try again.'
    ]);
});

$router->post('/admin/delete-article', function() use ($article_model, $auth) {
    $auth->require_auth();
    
    $article_id = $_POST['article_id'] ?? '';
    
    if (!empty($article_id) && $article_model->delete_article((int)$article_id)) {
        header('Location: /admin?deleted=1');
    } else {
        header('Location: /admin?error=delete_failed');
    }
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