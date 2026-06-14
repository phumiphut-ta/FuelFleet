<?php
namespace App\Core;

class Response {
    public function setStatusCode(int $code): void {
        http_response_code($code);
    }

    public function redirect(string $url): void {
        if (str_starts_with($url, '/')) {
            $url = Request::getBasePath() . $url;
        }
        header("Location: " . $url);
        exit;
    }

    public function json(mixed $data, int $statusCode = 200): void {
        $this->setStatusCode($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function html(string $content, int $statusCode = 200): void {
        $this->setStatusCode($statusCode);
        header('Content-Type: text/html; charset=utf-8');
        echo $content;
        exit;
    }
}
