<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;

class DummyService
{
    private Response $postConnection;
    private Response $usersConnection;

    public function connectPosts(): void
    {
        $this->postConnection = Http::get('https://dummyjson.com/posts?limit=150');
    }

    public function connectUsers(): void
    {
        $this->usersConnection = Http::get('https://dummyjson.com/users');
    }

    public function getPosts(): LengthAwarePaginator
    {
        $this->connectPosts();
        $this->connectUsers();

        $users = $this->usersConnection->json()['users'];
        $posts = $this->postConnection->json()['posts'];

        $posts = collect($posts)->map(static function ($post) use ($users) {
            $author = collect($users)->firstWhere('id', $post['userId']);

            $post['author_name'] = $author ? $author['firstName'] . ' ' . $author['lastName'] : '';

            return $post;
        });

        $perPage = 30;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $pagedData = $posts->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $posts = new LengthAwarePaginator($pagedData, $posts->count(), $perPage, $currentPage);
        $posts->withPath(Request::url());

        return $posts;
    }

    public function getPost(int $id)
    {
        $this->connectPosts();
        $this->connectUsers();

        $posts = $this->postConnection->json()['posts'];

        return collect($posts)->firstWhere('id', $id);
    }

    public function updatePost($data): string
    {
        try {
            $result =  Http::put('https://dummyjson.com/posts', $data);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $result;
    }

    public function addPost($data): string
    {
        try {
            $result =  Http::post('https://dummyjson.com/posts', $data);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $result;
    }
}
