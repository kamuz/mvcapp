<?php

class Posts extends Controller{
    private $postModel;

    public function __construct(){
        $this->postModel = $this->model('Post');
    }

    public function index(){
        $posts = $this->postModel->getPosts();
        $data = [
            'title' => 'Welcome',
            'posts' => $posts
        ];

        $this->view("posts/index", $data);
    }
}