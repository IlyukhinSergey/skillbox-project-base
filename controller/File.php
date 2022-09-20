<?php

class File extends Admin
{
    public function files($token, $method, $param)
    {
        try {
            $statement = $this->conn->prepare("SELECT `id`, `role`, `token`, `name` FROM `user` WHERE token = :token");
            $statement->execute(['token' => $token]);
            $result = $statement->fetch();
            if ($token === $result['token']) {
                if ($method === 'GET' && empty($param)) {
                    $this->tableHtml("<h2>Доступный список файлов пользователя:</h2>" . PHP_EOL .
                        $this->listFiles($result));
                } elseif ($method === 'GET' && isset($param['id'])) {
                    $this->tableHtml("<h2>Файл с номером id:  " . $param['id'] . "</h2>" . PHP_EOL .
                        $this->showFile($param['id'], $result['id']));
                } elseif ($method === 'POST' && empty($param) && empty($_POST['method'])) {
                    $this->tableHtml("<h2>Результат добавления файла</h2>" . PHP_EOL . $this->addFile($result['id']));
                } elseif ($_POST['method'] === 'PUT') {
                    $this->tableHtml("<h2>Результат изменеия файла</h2>" . PHP_EOL . $this->putFile($result['id']));
                } elseif ($_POST['method'] === 'DELETE') {
                    $this->tableHtml("<h2>Результат удаления файла</h2>" . PHP_EOL . $this->deleteFile($result['id']));
                }
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }


    public function directory($token, $method, $param)
    {
        try {
            $statement = $this->conn->prepare("SELECT `id`, `role`, `token`, `name` FROM `user` WHERE token = :token");
            $statement->execute(['token' => $token]);
            $result = $statement->fetch();
            if ($token === $result['token']) {
                if ($method === 'POST' && empty($param) && empty($_POST['method'])) {
                    $this->tableHtml("<h2>Результат добавления директории</h2>" . PHP_EOL .
                        $this->addDirectory($_POST['storage']));
                } elseif (!empty($_POST['method']) && $_POST['method'] === 'PUT') {
                    $this->tableHtml("<h2>Результат изменеия директории</h2>" . PHP_EOL .
                        $this->putDirectory($_POST['storage'], $_POST['newStorage']));
                } elseif ($method === 'GET') {
                    $this->tableHtml("<h2>информацию о папке </h2>" . PHP_EOL . $this->getDirectory($_GET['storage']));
                } elseif (!empty($_POST['method']) && $_POST['method'] === 'DELETE') {
                    $this->tableHtml("<h2>Результат удаления директории </h2>" . PHP_EOL .
                        $this->deleteDirectory($_POST['storage']));
                }
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function filesShare($method, $param)
    {
        if ($method === 'GET' && empty($param)) {
            $statement = $this->conn->query("SELECT `id`, `file_name`, `user_id` FROM file_user");
            $statement->execute();
            $data = $statement->fetchAll();
            $this->filesShareHtml("<h2>Все файлы в БД</h2>" . PHP_EOL . json_encode($data));
        } elseif ($method === 'GET' && isset($param['id'])) {
            $this->filesShareHtml("<h2>Список пользователей имеющих доступ к файлу</h2>" . PHP_EOL .
                $this->listId($param['id']));
        } elseif (!empty($_POST['method']) && $_POST['method'] === 'PUT') {

            $info = $this->fileAccess($_POST['id'], $_POST['user_id']);
            $this->filesShareHtml("<h2>Результат добавления доступа к файлу</h2>" . PHP_EOL . $info);

        } elseif (!empty($_POST['method']) && $_POST['method'] === 'DELETE') {
            $this->filesShareHtml("<h2>Результат по прекращению доступа к файлу</h2>" . PHP_EOL .
                $this->fileNoAccess($_POST['id'], $_POST['user_id']));
        }
    }

    public function listFiles($result)
    {
        $statement = $this->conn->prepare("SELECT * FROM file_user WHERE user_id = :id");;
        $statement->execute(['id' => $result['id']]);
        $data = $statement->fetchAll();
        if ($data > 0) {
            return json_encode($data);
        } else {
            echo $result['name'] . ' у Вас нет загруженных файлов';
        }
    }

    public function showFile($id, $userId)
    {
        $statement = $this->conn->prepare("SELECT * FROM file_user WHERE id = :id AND user_id = :userId");
        $statement->execute(['id' => $id, 'userId' => $userId]);
        $data = $statement->fetch();
        if ($data > 0) {
            return json_encode($data);
        } else {
            return 'файл не найден';
        }
    }

    public function addFile($userId)
    {
        if (isset($_FILES['file']) && $_FILES['file']['size'] > 0) {
            try {
                $cipherName = 'file' . time() . '_' . $_FILES['file']['name'];
                $storage = './fileSave/';

                move_uploaded_file($_FILES['file']['tmp_name'], $storage . $cipherName);
                $statement = $this->conn->prepare("INSERT INTO file_user(file_name, cipher_name, storage, user_id) 
                    VALUE (:file_name, :cipher_name , :storage, :user_id)");
                $statement->execute(['file_name' => $_FILES['file']['name'],
                    'cipher_name' => $cipherName,
                    'storage' => $storage,
                    'user_id' => $userId]);
                return 'файл добавлен';
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        } else {
            return 'файл отсутствует';
        }
    }

    public function putFile($userId)
    {
        if (!empty($id = $_POST['id']) && (!empty($_POST['storage']) || !empty($_POST['file_name']))) {
            try {
                $statement = $this->conn->prepare("SELECT * FROM file_user WHERE id = :id AND user_id = :user_id");;
                $statement->execute(['id' => $id, 'user_id' => $userId]);
                $data = $statement->fetch();

                if (!$data) {
                    return 'файл не найден';
                }

                if (!empty($_POST['file_name'])) {
                    $fileExtension = explode('.', $data['file_name']);
                    $fileName = $_POST['file_name'] . '.' . $fileExtension[1];
                    $cipherName = 'file' . time() . '_' . $fileName;
                } else {
                    $fileName = $data['file_name'];
                    $cipherName = $data['cipher_name'];
                }

                if (!empty($_POST['storage'])) {
                    if (preg_match("/^(?!.*\.\.)[\w().-]+$/", $_POST['storage'])) {
                        $storage = './fileSave/' . $_POST['storage'] . '/';
                        $i = true;
                    } else {
                        return 'в пути файла присутствуют недопустимы символы';
                    }
                } else {
                    $i = false;
                    $storage = $data['storage'];
                }


                if ($i) {
                    if (!file_exists($storage)) {
                        return 'Не корректно указан путь к файлу, данная директория отсутствует';
                    }
                }

                $statement = $this->conn->prepare("UPDATE `file_user` SET `file_name`= :file_name, 
                   `cipher_name`= :cipher_name, `storage`= :storage WHERE id = :id AND user_id = :user_id");
                $statement->execute(['id' => $id, 'file_name' => $fileName, 'cipher_name' => $cipherName,
                    'storage' => $storage, 'user_id' => $userId]);

                rename($data['storage'] . $data['cipher_name'], $storage . $cipherName);
                return 'файл изменен';


            } catch (Exception $e) {
                echo $e->getMessage();
            }

        } else {
            return 'данные внесены не корректно';
        }
    }

    public function deleteFile($userId)
    {
        if (!empty($id = $_POST['id'])) {
            try {
                $statement = $this->conn->prepare("SELECT * FROM file_user WHERE id = :id AND user_id = :user_id");;
                $statement->execute(['id' => $id, 'user_id' => $userId]);
                $data = $statement->fetch();
                if (empty($data)) {
                    return 'не корректный id file';
                }

                $statement = $this->conn->prepare("DELETE FROM `file_user` WHERE `id` = :id AND user_id = :user_id");
                $statement->execute(['id' => $id, 'user_id' => $userId]);
                unlink($data['storage'] . $data['cipher_name']);
                return 'файл удален';
            } catch (Exception $e) {
                echo $e->getMessage();
            }

        } else {
            return 'не указан номер файла';
        }
    }

    public function addDirectory($storage): string
    {
        if (empty($storage)) {
            return 'поле ИМЯ ПАПКИ не заполнено';
        }
        if (!file_exists($storage) && !preg_match("/\\s/", $storage) && !strpos($storage, '.')) {
            mkdir('./fileSave/' . $storage);
            return 'Папка создана';
        } else {
            return 'не корректно указана имя папки';
        }
    }

    public function putDirectory($storage, $newStorage): string
    {
        $dir = './fileSave/' . $storage;
        $newDir = './fileSave/' . $newStorage;

        if (empty($storage) && empty($newStorage)) {
            return 'Все поля необходимо заполнить';
        } elseif (!file_exists($dir)) {
            return 'данная директория отсутствует';
        } elseif (!preg_match("/\\s/", $newStorage) && !strpos($newStorage, '.')) {
            rename($dir, $newDir);
            return 'Папка переименована';
        } else {
            return 'не корректно указана имя папки';
        }
    }

    public function getDirectory($storage)
    {
            return json_encode(scandir('./fileSave/' . $storage));
    }

    public function deleteDirectory($storage)
    {
        $dir = './fileSave/' . $storage;

        if (is_dir($dir) && !empty($storage) && count(scandir($dir)) == 2) {
            rmdir($dir);
            return 'Директория удалена';
        } else {
            return 'Указана неверная директория, либо в ней существуют файлы';
        }
    }

    public function listId($id)
    {
        $user = [];
        $statement = $this->conn->prepare("SELECT * FROM file_user WHERE id = :id");
        $statement->execute(['id' => $id]);
        $data = $statement->fetch();
        if ($data) {
            $statement = $this->conn->prepare("SELECT `id`, `name` FROM user WHERE id = :id");
            $statement->execute(['id' => $data['user_id']]);
            $admin = $statement->fetch();
            if ($data['access']) {
                $users = explode(',', $data['access']);
                foreach ($users as $value) {
                    $statement = $this->conn->prepare("SELECT `id`, `name` FROM user WHERE email = :id");
                    $statement->execute(['id' => $value]);
                    $user[] = $statement->fetch();
                }
                $user = json_encode($user);
            } else{
                $user = 'у других пользователя нет доступа к файлу';
            }
        } else {
            return 'нет доступных файлов с указанным id';
        }
        return '<b>файл создал </b>' . json_encode($admin) . PHP_EOL . '<b>доступ к файлу имеют: </b>' . $user;
    }

    public function fileAccess($id, $userId)
    {
        if (!empty($id) && !empty($userId)) {
            try {
                $statement = $this->conn->prepare("SELECT * FROM user WHERE id = :id");
                $statement->execute(['id' => $userId]);
                if ($dataUser = $statement->fetch()) {
                    $access = $dataUser['email'];
                } else {
                    return 'пользователь не найден с таким user_id';
                }

                $statement = $this->conn->prepare("SELECT `access` FROM file_user WHERE id = :id");
                $statement->execute(['id' => $id]);
                $data = $statement->fetch();
                if ($data['access']) {
                    $emails = explode(',', $data['access']);
                    if (in_array($dataUser['email'], $emails)) {
                        return 'пользователь имеет уже права доступа';
                    }
                    $access = $data['access'] . ',' . $dataUser['email'];
                }
                $statement = $this->conn->prepare("UPDATE file_user SET `access` = :access WHERE id = :id");
                $statement->execute(['access' => $access, 'id' => $id]);
                return 'права доступа даны для пользователя ' . $dataUser['name'];
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        } else {
            return 'заполните все поля';
        }
    }

    public function fileNoAccess($id, $userId)
    {
        if (!empty($id) && !empty($userId)) {
            try{
                $statement = $this->conn->prepare("SELECT * FROM user WHERE id = :id");
                $statement->execute(['id' => $userId]);
                if ($dataUser = $statement->fetch()) {
                    $access = $dataUser['email'];
                } else {
                    return 'пользователь не найден с таким user_id';
                }

                $statement = $this->conn->prepare("SELECT `access` FROM file_user WHERE id = :id");
                $statement->execute(['id' => $id]);
                $data = $statement->fetch();
                $emails = explode(',', $data['access']);
                if (in_array($dataUser['email'], $emails)) {
                    unset($emails[array_search($dataUser['email'], $emails)]);
                    $access = implode(',', $emails);
                } else {
                    return 'у этого пользователя не было прав';
                }

                $statement = $this->conn->prepare("UPDATE file_user SET `access` = :access WHERE id = :id");
                $statement->execute(['access' => $access, 'id' => $id]);
                return 'права доступа удалены для пользователя ' . $dataUser['name'];

            } catch (Exception $e) {
                echo $e->getMessage();
            }
        } else {
            return 'заполните все поля';
        }
    }

    public function tableHtml($info)
    {
        ?>
        <html>
        <head>
        </head>
        <body>
        <h1>/file/</h1>
        <?php
        print_r($info)
        ?>
        <form action="http://localhost/file/" method="GET" enctype="application/x-www-form-urlencoded">
            <h5>Вывести список файлов</h5>
            <input type="submit" value="go">
        </form>
        <form action="http://localhost/file/" method="GET" enctype="application/x-www-form-urlencoded"">
            <h5>Получить информацию о конкретном файле</h5>
            <label>id: <input type="number" name="id"></label>
            <input type="submit" value="go"">
        </form>

        <form action="http://localhost/file/" method="POST" enctype="multipart/form-data">
            <h5>Добавить файл</h5>
            <p><input type="file" name="file">
                <input type="submit" value="Отправить"></p>
        </form>


        <form action="http://localhost/file/" method="POST" enctype="application/x-www-form-urlencoded">
            <h5>Изменения данных</h5>
            <label>id файла: <input type="number" name="id"></label>
            <label>название файла: <input type="text" name="file_name"></label>
            <label>путь к файлу: <input type="text" name="storage"></label>
            <input type="hidden" name="method" value="PUT">
                <input type="submit" value="Отправить"></p>
        </form>

        <form action="http://localhost/file/" method="POST" enctype="application/x-www-form-urlencoded">
            <h5>Удаления данных</h5>
            <label>id файла: <input type="number" name="id"></label>
            <input type="hidden" name="method" value="DELETE">
            <input type="submit" value="Отправить"></p>
        </form>

        <form action="http://localhost/directory/" method="POST" enctype="application/x-www-form-urlencoded">
            <h5>Добавить папку (директорию)</h5>
            <label>Имя папки: <input type="text" name="storage"></label>
            <input type="submit" value="Отправить"></p>
        </form>

        <form action="http://localhost/directory/" method="POST" enctype="application/x-www-form-urlencoded">
            <h5>Переименовать папку (директорию)</h5>
            <label>Имя папки: <input type="text" name="storage"></label>
            <label>Новое имя папки: <input type="text" name="newStorage"></label>
            <input type="hidden" name="method" value="PUT">
            <input type="submit" value="Отправить"></p>
        </form>

        <form action="http://localhost/directory/" method="GET" enctype="application/x-www-form-urlencoded">
        <h5>Получить информацию о папке (список файлов папки)</h5>
        <label>папка: <input placeholder="fileSave" type="text" name="storage"></label>
        <input type="submit" value="go"">
        </form>

        <form action="http://localhost/directory/" method="POST" enctype="application/x-www-form-urlencoded">
            <h5>Удаления папки</h5>
            <label>папка: <input type="text" name="storage"></label>
            <input type="hidden" name="method" value="DELETE">
            <input type="submit" value="Отправить"></p>
        </form>

        <form action="http://localhost/user/" method="GET">
            <h5>Перейти на страницу /user/</h5>
            <input type="submit" value="go">
        </form>
        <form action="http://localhost/admin/user/" method="GET">
            <h5>Перейти на страницу /admin/user/</h5>
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

    public function htmlNoFile()
    {
        ?>
        <html>
        <head>
        </head>
        <body>
        <h1>/file/</h1>
        <h2>Страница доступна зарегистрированному пользователю</h2>
        <form action="http://localhost/user/" method="GET">
            <h5>Перейти на страницу /user/ <залогиниться></залогиниться></h5>
            <input type="submit" value="go">
        </form>
        <form action="http://localhost/admin/user/" method="GET">
            <h5>Перейти на страницу /admin/user/</h5>
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

    public function filesShareHtml($info)
    {
        ?>
        <html>
        <head>
        </head>
        <body>
        <h1>/files/share/</h1>
        <?php
        print_r($info)
        ?>
        <form action="http://localhost/files/share/" method="GET" enctype="application/x-www-form-urlencoded">
            <h5>Список файлов</h5>
            <input type="submit" value="go">
        </form>
        <form action="http://localhost/files/share/" method="GET" enctype="application/x-www-form-urlencoded"">
        <h5>Получить список пользователей, имеющих доступ к файлу</h5>
        <label>id: <input type="number" name="id"></label>
        <input type="submit" value="go"">
        </form>
        <form action="http://localhost/files/share/" method="POST" enctype="application/x-www-form-urlencoded">
            <h5>Добавить доступ к файлу пользователю с id и user_id</h5>
            <label>id файла: <input type="number" name="id"></label>
            <label>user_id файла: <input type="number" name="user_id"></label>
            <input type="hidden" name="method" value="PUT">
            <input type="submit" value="Отправить"></p>
        </form>
        <form action="http://localhost/files/share/" method="POST" enctype="application/x-www-form-urlencoded">
            <h5>Прекратить доступ к файлу пользователю с id и user_id</h5>
            <label>id файла: <input type="number" name="id"></label>
            <label>user_id файла: <input type="number" name="user_id"></label>
            <input type="hidden" name="method" value="DELETE">
            <input type="submit" value="Отправить"></p>
        </form>
        <form action="http://localhost/user/" method="GET">
            <h5>Перейти на страницу /user/</h5>
            <input type="submit" value="go">
        </form>
        <form action="http://localhost/admin/user/" method="GET">
            <h5>Перейти на страницу /admin/user/</h5>
            <input type="submit" value="go">
        </form>
        <form action="http://localhost/file/" method="GET">
            <h5>Перейти на страницу /file/</h5>
            <input type="submit" value="go">
        </form>
        </body>
        </html>
        <?php
    }
}
