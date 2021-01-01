<?php

class Database
{
    private string $server;
    private string $user;
    private string $password;
    private string $name;

    public function __construct(string $server, string $user, string $password, string $name)
    {
        $this->server = $server;
        $this->user = $user;
        $this->password = $password;
        $this->name = $name;
    }

    public function buildConnection(): PDO
    {
        $dsn = 'mysql:host=' . $this->server . ';dbname=' . $this->name;

        return new PDO($dsn, $this->user, $this->password);
    }
}
