<?php

class Admin extends User
{

    public function checkAccess($token, $method, $param)
    {
        try {
            $statement = $this->conn->prepare("SELECT `id`, `role`, `token` FROM `user`");
            $statement->execute();
            $statement->fetch();
            while ($result = $statement->fetch()) {
                if ($token === $result['token'] && $result['role'] === 'admin') {
                    if ($method === 'GET' && empty($param)) {
                        $this->htmlAdmin("<h2>Список пользователей:</h2>" . PHP_EOL . $this->list());
                    } elseif ($method === 'GET' && !empty($param['id'])) {
                        $this->htmlAdmin("<h2>Информация по конкретному пользователю:</h2>" . PHP_EOL
                            . $this->showUser($param['id']));
                    } elseif (!empty($_POST['method']) && $_POST['method'] === 'DELETE') {
                        if (!empty($_POST['id'])) {
                            $info = $this->delete($_POST['id']);
                        } else {
                            $info = 'id не введено';
                        }
                        $this->htmlAdmin("<h2>Результат удаления пользователя</h2>" . PHP_EOL . $info);
                    } elseif (!empty($_POST['method']) && $_POST['method'] === 'PUT') {
                        $this->htmlAdmin("<h2>Результат изменения пользователя</h2>" . PHP_EOL . $this->update());
                    }
                } elseif ($token === $result['token'] && $result['role'] === 'user') {
                    $this->htmlNoAdmin();
                }
            }

        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function delete(int $userId)
    {
        try {
            $statement = $this->conn->prepare("SELECT `id` FROM `user` WHERE id = :id");
            $statement->execute(['id' => $userId]);
            if ($statement->fetch()) {
                $statement = $this->conn->prepare("DELETE FROM `user` WHERE `id` = :id");
                $statement->execute(['id' => $userId]);
                if (!empty($_COOKIE['token'])) {
                    $statement = $this->conn->prepare("SELECT `id`, `token` FROM `user` 
                     WHERE `id` = :id AND `token` = :token");
                    $statement->execute(['id' => $userId, 'token' => $_COOKIE['token']]);
                    $result = $statement->fetch();
                    if (!empty($result)) {
                        $this->logout();
                    }
                    return 'пользователь удален';
                }
            } else {
                return 'пользователь с таким id не найден';
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function logout()
    {
        if (!empty($_COOKIE['token'])) {
            setcookie('token', '0', time() - 3600, '/');
        } else {
            return 'нет активных сессий';
        }
    }

    public function htmlAdmin($info)
    {
        ?>
        <html>
        <head>
        </head>
        <body>
        <h1>/admin/user/</h1>
        <?php
        print_r($info)
        ?>
        <form action="http://localhost/admin/user/" method="GET">
            <h5>Вывести список пользователей</h5>
            <input type="submit" value="list users">
        </form>
        <form action="http://localhost/admin/user/" method="GET">
            <h5>Вывести пользователя по id</h5>
            <label>id: <input type="number" name="id"></label>
            <input type="submit" value="go">
        </form>
        <form action="http://localhost/admin/user/" method="POST" enctype="application/x-www-form-urlencoded">
            <h5>Удаления пользователя</h5>
            <label>id: <input type="number" name="id"></label>
            <input type="hidden" name="method" value="DELETE">
            <input type="submit" value="Отправить"></p>
        </form>
        <form action="http://localhost/admin/user/" method="POST" enctype="application/x-www-form-urlencoded">
            <h5>Изменить данные пользователя</h5>
            <label>id: <input type="text" name="id"></label>
            <label>name: <input type="text" name="name"></label>
            <label>email: <input type="text" name="email"></label>
            <label>password: <input type="text" name="password"></label>
            <label>role: <input type="text" name="role"></label>
            <label>age: <input type="text" name="age"></label>
            <label>sex: <input type="text" name="sex"></label>
            <input type="hidden" name="method" value="PUT">
            <input type="submit" value="update user">
        </form>
        <form action="http://localhost/user/" method="GET">
            <h5>Перейти на страницу /user/</h5>
            <input type="submit" value="go">
        </form>
        <form action="http://localhost/file/" method="GET">
            <h5>Перейти на страницу /file/</h5>
            <input type="submit" value="go">
        </form>
        <form action="http://localhost/files/share/" method="GET">
            <h5>Перейти на страницу /files/share/</h5>
            <input type="submit" value="go">
        </form>
        </body>
        </html>
        <?php
    }

    public function htmlNoAdmin()
    {
    ?>
        <html>
        <head>
        </head>
        <body>
        <h1>/admin/user/</h1>
        <h2>Страница доступна зарегистрированному пользователю с правами admin</h2>
        <form action="http://localhost/user/" method="GET">
            <h5>Перейти на страницу /user/</h5>
            <input type="submit" value="go">
        </form>
        <form action="http://localhost/file/" method="GET">
            <h5>Перейти на страницу /file/</h5>
            <input type="submit" value="go">
        </form>
        <form action="http://localhost/files/share/" method="GET">
            <h5>Перейти на страницу /files/share/</h5>
            <input type="submit" value="go">
        </form>
        </body>
        </html>
   <?php
    }
}