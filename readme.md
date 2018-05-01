# Простое MVC приложение на базе кастомного PHP фреймворка

Мы создадим свою реализацию PHP фреймворка. Каждый HTTP запрос будет проходить через единственный файл *index.php* и чтобы это реализовать мы будем использовать файл *.htaccess*, который будет переопределять дефолтное поведение сервера Apache и перезаписывать правила URL.

Например мы можем создать класс `Posts` в котором мы будем создавать методы, доступ к которым будет происходить по такому принципу, например:

* `http://app.loc/index.php?url=posts/index`
* `http://app.loc/index.php?url=posts/add`
* `http://app.loc/index.php?url=posts/edit/1`

```php
class Posts{
    public function __construct(){
        $this->postModel = $this->model('post');
    }
    public function index(){
        //..
    }
    public function add(){
        //..
    }
    public function edit($id){
        $post = $this->postModel->fetchPost($id);

        $this->view('edit', ['post' => $post]);
    }
}

<h1><?php echo $data['title'];?></h1>
```

Это всего лишь псеводокод, но очень похож на реальный, который мы напишем в будущем.

`http://app.loc/index.php?url=posts/edit/1` - такой URL выглядит не очень в глазах поисковых систем и мы можем сделать его короче, чтобы он стал такого вида `http://app.loc/posts/edit/1`. Это мы реализуем написав правило в файле *.htaccess*.

Также нам нужно определить дефолтный контроллер при загрузке главной страницы сайта, а также дефолтный метод каждого контроллера, например `index()`. Такие принципы используют почти в каждом PHP фреймворке.

## Создание файловой структуры проекта

В папке *public/* будет находится файл *index.php* который будет обрабатывать абсолютно все HTTP запросы, а также статически файлы шаблона, такие как CSS, JavaScript.

В папке *app/libraries/* будут находится высокоуровневные файлы нашего приложения, например *Core.php*, *Database.php*, *Controller.php*.

Также в папке *app/* будут находится файлы моделей *app/models/*, виды *app/views/* и контроллеры *app/controllers/*.

Также здесь у нас будет находится папка с хелперами *app/helpers/*, которые будут выполнять какую то небольшую работу, например функции для работы с редиректами, сессиями и т. д.

В папке *app/config/* будут файлы конфигурации.

С помощью файла *app/bootstrap.php* мы будем подгружать всё что нам нужно в нашем приложении.

Чтобы не было прямого доступа к содержимому папки *app/* мы создадим файл *.htaccess* в котором запишем следующее:

```
Options -Indexes
```

Теперь при переходе в папку *app/* мы будем получать **Access forbidden!** и 403 ошибку. Дефолтное значение, которое отрывает доступ к текущей папке выглядит так - `Options +Indexes`.

## Единая точка входа

В папке *public/* мы создадим файл *.htaccess* и положим туда следующи код:

*public/.htaccess*

```
<IfModule mod_rewrite.c>
    Options -Multiviews
    RewriteEngine On
    RewriteBase /public
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.+)$ index.php?url=$1 [QSA,L]
</IfModule>
```

При обращении в папку *public/* все запросы будут попадать в файл *index.php*, при условии, что там нет реального файла, который бы соответствовал запросу.

В файл *public/index.php* мы подключим *app/bootstrap.php*.

*public/index.php*

```php
<?php

require_once "../app/bootstrap.php";
```

Для достоверности добавит текст и проверим:

*app/bootstrap.php*

```php
BOOTSTRAP
```

Чтобы все запросы по умолчанию переадресововались в папку *public/* нужно создать ещё один файл *.htaccess* в корне приложения:

*.htaccess*

```
<IfModule mod_rewrite.c>
  RewriteEngine on
  RewriteRule ^$ public/ [L]
  RewriteRule (.*) public/$1 [L]
</IfModule>
```

Теперь подключим файлы ядра:

*app/bootstrap.php*

```php
<?php

// Load libraries

require_once "libraries/Core.php";
require_once "libraries/Database.php";
require_once "libraries/Controller.php";
```

В файле ядра создадим класс в котором определим параметры по умолчанию - дефолтный контроллер, метод и набор параметров:

*app/libraries/Core.php*

```php
<?php

class Core{
    protected $currentController = 'Pages';
    protected $currentMethod = 'index';
    protected $params = [];
}
```

Теперь создадим функцию где будем получать текущий URL и через констуктор будет её вызывать автоматически при создании нового объекта `Core`.

*app/libraries/Core.php*

```php
<?php

class Core{
    protected $currentController = 'Pages';
    protected $currentMethod = 'index';
    protected $params = [];

    public function __construct(){
        return $this->getUrl();
    }

    public function getUrl(){
        echo $_GET['url'];
    }
}
```

Инициализируем этот класс:

*public/index.php*

```php
<?php

require_once "../app/bootstrap.php";

// Init Core Library
$url = new Core();
```

Проверим результат запустив примерно такой запрос в строку браузера - `http://mvcapp.loc/index.php?url=test` и на выходе мы должны получить **test** в окно браузера.

Если взглянуть в файл *public/.htaccess*, то мы увидим что у нас есть это строка `RewriteRule ^(.+)$ index.php?url=$1 [QSA,L]`, а это означает, что мы можем вовсе убрать с URL `index.php?url=`, а вместо это просто передать параметр, который автоматически будет параметром `url` и если мы введем `http://mvcapp.loc/test`, то мы получим всё тот же результат.

А если мы запустим запрос, который близок к реальности, например `http://mvcapp.loc/posts/edit/1`, то мы получим то что нам нужно.

## Загружаем контроллер через URL

Для начала нам нужно сделать проверку, существует ли у нас `$_GET['url']`, затем обрезаем слеш в конце с помощью `rtrim()`, фильтруем URL с помощью `filter_var()`. Затем нам вырезать из URL строки каждый параметр и поместить в массив с помощью функции `explode()`.

Далее в конструкторе распечатаем наш массив, чтобы удостовериться что всё у нас работает на данном этапе.

Затем мы проверяем существует ли у нас файл контроллера с названием, который был передан через URL параметр и если это так, то мы делаем его текущим контроллером. В конечном итоге мы подключаем текущий контроллер с помощью `require_once` и инициируем класс данного контроллера.

*app/libraries/Core.php*

```php
<?php

/**
 * App Core Class
 * Creates URL & load core controller
 * URL FORMAT - /controller/method/params
 */

class Core{
    protected $currentController = 'Pages';
    protected $currentMethod = 'index';
    protected $params = [];

    public function __construct(){
        // print_r($this->getUrl());
        $url = $this->getUrl();
        // Look in controllers for this value
        if(file_exists('../app/controllers/' . ucwords($url['0']) . '.php')){
            // If exists, set as controller
            $this->currentController = ucwords($url['0']);
            // Unset 0 Index
            unset($url[0]);
        }
        // Require the controller
        require_once '../app/controllers/' . $this->currentController . '.php';
        // Instantiate controller class
        $this->currentController = new $this->currentController;
    }

    public function getUrl(){
        if(isset($_GET['url'])){
            $url = rtrim($_GET['url'], '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            $url = explode('/', $url);
            return $url;
        }
    }
}
```

Теперь создадим контроллеры *Pages.php* и *Posts.php* чтобы убедиться что у нас всё работает так как нам нужно.

*app/controllers/Pages.php*

```php
<?php

class Pages{
    public function __construct(){
        echo "Pages loaded";
    }
}
```

Проверяем работает ли у нас контроллер по умолчанию, для этого передавать какие либо параметры не объязательно, просто запускаем главную страницу сайта.

*app/controllers/Posts.php*

```php
<?php

class Pages{
    public function __construct(){
        echo "Pages loaded";
    }
}
```

Чтобы проверить что у нас загружается требуемый нам контроллер нам теперь нужно передать параметры, например `http://mvcapp.loc/posts/edit/1`.

## Проверка и получение метода и его параметров

Далее в конструкторе мы сделаем проверку, если у нас существует второй сегмент в URL, тогда мы проверяем существует ли такой метод внутри текущего контроллера и если да, тогда мы указываем его в качестве текущего метода. Для проверки выведем текущих контроллер.

*app/libraries/Core.php*

```php
// Check for second part of url
if(isset($url[1])){
    // Check to see if method exists in controller
    if(method_exists($this->currentController, $url[1])){
        $this->currentMethod = $url[1];
        unset($url[1]);
    }
}
echo $this->currentMethod;
```

И не забываем добавить данный метод в наш контроллер:

*app/controllers/Pages.php*

```php
<?php

class Pages{
    public function __construct(){
        
    }

    public function about(){

    }
}
```

Чтобы в массив URL нам прилетели одни параметры, на каждом из предыдущих этапов мы удаляли в конце текущий ключ массива. Далее, мы проверяем, если у нас есть параметры, то мы их сохраняем в массив, а если нет, то возвращаем пустой массив. После чего с помощью функции `call_user_func_array()` мы вызываем функции с переданными параметрами.

*app/libraries/Core.php*

```php
// Get params
$this->params = $url ? array_values($url) : [];
// Call a callback with array of params
call_user_func_array([$this->currentController, $this->currentMethod], $this->params);
```

В нашем контролле создадим функцию и попробуем к ней обратится через URL, например таким образом `http://mvcapp.loc/pages/about/1`:

*app/controllers/Pages.php*

```php
<?php

class Pages{
    public function __construct(){

    }

    public function index(){
        echo "Index function";
    }

    public function about($id){
        echo "This is about function<br>";
        echo "This is ID - {$id}";
    }
}
```

Кроме этого мы создали функцию `index()` чтобы у нас не возникало ошибки, если мы не укажем второй сегметр URL `http://mvcapp.loc/pages/`.

В итоге мы должны получить такой контроллер ядра:

*app/libraries/Core.php*

```php
<?php

/**
 * App Core Class
 * Creates URL & load core controller
 * URL FORMAT - /controller/method/params
 */

class Core{
    protected $currentController = 'Pages';
    protected $currentMethod = 'index';
    protected $params = [];

    public function __construct(){
        // print_r($this->getUrl());
        $url = $this->getUrl();
        // Look in controllers for this value
        if(file_exists('../app/controllers/' . ucwords($url['0']) . '.php')){
            // If exists, set as controller
            $this->currentController = ucwords($url['0']);
            // Unset 0 Index
            unset($url[0]);
        }
        // Require the controller
        require_once '../app/controllers/' . $this->currentController . '.php';
        // Instantiate controller class
        $this->currentController = new $this->currentController;
        // Check for second part of url
        if(isset($url[1])){
            // Check to see if method exists in controller
            if(method_exists($this->currentController, $url[1])){
                $this->currentMethod = $url[1];
                // Unset 1 index
                unset($url[1]);
            }
        }
        // Get params
        $this->params = $url ? array_values($url) : [];
        // Call a callback with array of params
        call_user_func_array([$this->currentController, $this->currentMethod], $this->params);
    }

    public function getUrl(){
        if(isset($_GET['url'])){
            $url = rtrim($_GET['url'], '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            $url = explode('/', $url);
            return $url;
        }
    }
}
```

## Класс базового контроллера

В этом классе мы будем загружать модели и виды. В функцию `model()` мы будем передавать название модели, которую нужно загрузить в наш контроллер и инициализировать новый объект модели.

В функцию `view()` мы будем передавать два параметра - название файла и данные, которые мы можем получить из модели и использовать в виде.

*app/libraries/Controller.php*

```php
<?php

/**
 * Base Controller
 * Load the models and views
 */

class Controller{
    // Load model
    public function model($model){
        // Require model file
        require_once '../app/models/' . $model . '.php';
        // Instatiante model
        return new $model;
    }
    // Load view
    public function view($view, $data = []){
        // Check for view file
        if(file_exists('../app/views/' . $view . '.php')){
            // Require view file
            require_once '../app/views/' . $view . '.php';
        }
        else{
            // View does not exists
            die('View does not exists');
        }
    }
}
```

Чтобы проверить работу, можем вызвать метод `view()` внутри контроллера `Pages`.

*app/controllers/Pages.php*

```php
<?php

class Pages extends Controller{
    public function __construct(){

    }

    public function index(){
        $this->view("pages");
    }

    //..
}
```

Каждый наш контроллер должен наследовать базовый контроллер, который мы подключаем в файле *app/bootstrap.php*. Пробуем обратится к нашему контроллера `Pages` - `http://mvcapp.loc/pages/` и если мы получим сообщение **View does not exists** значит на данном этапе мы справились с заданием.

## Подгружаем вид

На данном этапе мы можем создать новый вид и вызвать его из контроллера и всё должно работать.

*app/controllers/Pages.php*

```
public function index(){
    $this->view("index");
}
```

*app/views/index.php*

```
HOME PAGE
```

Мы даже можем создавать папки и в них размещать наши виды и это также будет работать - `$this->view("pages/index")`.

Мы также можем передавать данные в контроллер.

*app/controllers/Pages.php*

```php
public function index(){
    $this->view("pages/index", ['title' => 'Welcome']);
}
```

Чтобы не загромождать функцию `view()` данными, мы можем их создавать в отдельной переменной и это будет выглядеть таким образом:

*app/controllers/Pages.php*

```php
public function index(){
    $data = [
        'title' => 'Welcome'
    ];

    $this->view("pages/index", $data);
}
```

А в виде мы можем обратиться к этим данным таким образом:

*app/views/pages/index.php*

```php
<h1><?php echo $data['title'] ?></h1>
```

Пока что данные в виде массива мы создаём вручную, но со временем мы будем получать данные из модели, а затем использовать эти данные в виде.

## Файл конфигурации

В файле конфигурации мы создадим несколько констант, но перед этим нам его нужно подключить в файле *app/bootstrap.php*.

* `APPROOT` - определяет полный путь к корню нашего приложения на сервере. Чтобы получить его нам подобилось использовать магическую константу `__FILE__` и дважды вызывать функцию `dirname()`.

Вот что у нас получилось в итоге:

*app/config/config.php*

```php
<?php

// App Root
define('APPROOT', dirname(dirname(__FILE__)));
// URL Root
define('URLROOT', 'http://mvcapp.loc');
// Site Name
define('SITENAME', 'Simple MVC');
```

И чтобы опять убедиться что всё у нас работает мы выведем эти переменные в виде:

*app/views/pages/index.php*

```php
<h1><?php echo $data['title'] ?></h1>

<?php echo APPROOT . '<br>'; ?>
<?php echo URLROOT . '<br>'; ?>
<?php echo SITENAME . '<br>'; ?>
```

## Автозагрузка библиотек

Со временем мы можем добавить добавлять всё больше и больше библиотек и чтобы каждый раз не писать конструкцию `required` мы будем использовать автозагрузку. В нашем случае всё просто, потому что имя класса совпадает с именем файла класса.

*app/bootstrap.php*

```php
// Autoload Core Libraries
spl_autoload_register(function($className){
    require_once 'libraries/' . $className . '.php';
});
```