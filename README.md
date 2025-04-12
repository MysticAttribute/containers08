# Integrare continuă cu Github Actions

## Scopul lucrării: În cadrul acestei lucrări studenții vor învăța să configureze integrarea continuă cu ajutorul Github Actions

### Sarcina: Crearea unei aplicații Web, scrierea testelor pentru aceasta și configurarea integrării continue cu ajutorul Github Actions pe baza containerelor

### Mod de lucru

1. Cream direcorul *site* in directorul radacina

2. Cream in directorul *site* un director nou cu numele *modules* cu urmatorul continut:

    ```php

    <?php
    class Database {
        private $pdo;

        public function __construct($path) {
            $this->pdo = new PDO("sqlite:" . $path);
        }

        public function Execute($sql, $params = []) {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        }

        public function Fetch($sql, $params = []) {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function Create($table, $data) {
            $columns = implode(", ", array_keys($data));
            $placeholders = ":" . implode(", :", array_keys($data));
            $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
            return $this->Execute($sql, $data);
        }

        public function Read($table, $id) {
            $sql = "SELECT * FROM $table WHERE id = :id";
            $rows = $this->Fetch($sql, ["id" => $id]);
            return $rows[0] ?? [];
        }

        public function Update($table, $id, $data) {
            $fields = implode(", ", array_map(fn($k) => "$k = :$k", array_keys($data)));
            $sql = "UPDATE $table SET $fields WHERE id = :id";
            $data["id"] = $id;
            return $this->Execute($sql, $data);
        }

        public function Delete($table, $id) {
            $sql = "DELETE FROM $table WHERE id = :id";
            return $this->Execute($sql, ["id" => $id]);
        }

        public function Count($table) {
            $sql = "SELECT COUNT(*) as count FROM $table";
            $rows = $this->Fetch($sql);
            return $rows[0]['count'] ?? 0;
        }
    }
    ```

3. In acelasi director cream un pisier *page.php* care va fi folosit pentru lucurul cu paginile

    ```php
        <?php
    class Page {
        private $template;

        public function __construct($template) {
            $this->template = $template;
        }

        public function Render($data) {
            $output = file_get_contents($this->template);
            foreach ($data as $key => $value) {
                $output = str_replace("{{ $key }}", $value, $output);
            }
            return $output;
        }
    }
    ```

4. Cream fisierul *index.php* care si va fi pagina noastra

    ```php
    <?php
    require_once __DIR__ . '/modules/database.php';
    require_once __DIR__ . '/modules/page.php';
    require_once __DIR__ . '/config.php';

    $db = new Database($config["db"]["path"]);
    $page = new Page(__DIR__ . '/templates/index.tpl');
    $pageId = $_GET['page'] ?? 1;
    $data = $db->Read("page", $pageId);
    echo $page->Render($data);
    ```

5. Cream fisierul *index.tpl* care va fi ca un template pentru pagina mea php

    ```tpl
    <!DOCTYPE html>
    <html>
    <head>
        <title>{{ title }}</title>
        <link rel="stylesheet" href="/styles/style.css">
    </head>
    <body>
        <h1>{{ title }}</h1>
        <div>{{ content }}</div>
    </body>
    </html>
    ```

6. Cream un fisier *config.php* care va efectua conexiune la BD

    ```php
    <?php
    $config = [
        "db" => [
            "path" => "/var/www/db/db.sqlite"
        ]
    ];
    ```

7. In final cream fisierul SQL care va contine baza noastra de date si cateva inserari

    ```sql
    CREATE TABLE page (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT,
        content TEXT
    );

    INSERT INTO page (title, content) VALUES ('Page 1', 'Content 1');
    INSERT INTO page (title, content) VALUES ('Page 2', 'Content 2');
    INSERT INTO page (title, content) VALUES ('Page 3', 'Content 3');
    ```

8. Cream un director in care vom adauga fisiere ce vor efectua testele cu numele *test* si cream in el fisierul *testframework.php*

    ```php
    <?php
    function message($type, $message) {
        $time = date('Y-m-d H:i:s');
        echo "{$time} [{$type}] {$message}" . PHP_EOL;
    }
    function info($message) { message('INFO', $message); }
    function error($message) { message('ERROR', $message); }
    function assertExpression($expression, $pass = 'Pass', $fail = 'Fail'): bool {
        if ($expression) { info($pass); return true; }
        error($fail); return false;
    }
    class TestFramework {
        private $tests = [];
        private $success = 0;
        public function add($name, $test) { $this->tests[$name] = $test; }
        public function run() {
            foreach ($this->tests as $name => $test) {
                info("Running test {$name}");
                if ($test()) $this->success++;
                info("End test {$name}");
            }
        }
        public function getResult() {
            return "{$this->success} / " . count($this->tests);
        }
    }
    ```

9. Cream in acelasi director un alt fisier cu numele *tests.php* care si va efectua testele si va afisa rezultatele lor

    ```php
    <?php
    require_once __DIR__ . '/testframework.php';
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../modules/database.php';
    require_once __DIR__ . '/../modules/page.php';

    $testFramework = new TestFramework();

    function testDbConnection() {
        global $config;
        try {
            new Database($config['db']['path']);
            return assertExpression(true, "Database connected");
        } catch (Exception $e) {
            return assertExpression(false, "Connection failed");
        }
    }

    function testDbCount() {
        global $config;
        $db = new Database($config['db']['path']);
        $count = $db->Count("page");
        return assertExpression($count >= 3, "Count is $count");
    }

    function testDbCreate() {
        global $config;
        $db = new Database($config['db']['path']);
        return assertExpression(
            $db->Create("page", ["title" => "Test", "content" => "123"]),
            "Row inserted"
        );
    }

    function testDbRead() {
        global $config;
        $db = new Database($config['db']['path']);
        $row = $db->Read("page", 1);
        return assertExpression(isset($row['title']), "Read row: " . $row['title']);
    }

    $testFramework->add('Database connection', 'testDbConnection');
    $testFramework->add('Count test', 'testDbCount');
    $testFramework->add('Create test', 'testDbCreate');
    $testFramework->add('Read test', 'testDbRead');

    $testFramework->run();
    echo $testFramework->getResult();
    ```

10. Cream in directorul radacina *containers08* un director cu numele *.github* iar in el un alt director cu numele workflows

11. In acest nou director creat vom crea fisierul pentru GITHub Actions cu numele *main.yml*

    ```yml
    name: CI

    on:
    push:
        branches:
        - main

    jobs:
    build:
        runs-on: ubuntu-latest
        steps:
        - name: Checkout
            uses: actions/checkout@v4
        - name: Build the Docker image
            run: docker build -t containers08 .
        - name: Create `container`
            run: docker create --name container --volume database:/var/www/db containers08
        - name: Copy tests to the container
            run: docker cp ./tests container:/var/www/html
        - name: Up the container
            run: docker start container
        - name: Run tests
            run: docker exec container php /var/www/html/tests/tests.php
        - name: Stop the container
            run: docker stop container
        - name: Remove the container
            run: docker rm container
    ```

12. In radacina proiectului sau direct zis in directorul *containers08* cream fisierul Docerfile cu urmatorul continut

    ```shell
    FROM php:7.4-fpm as base

    RUN apt-get update && \
        apt-get install -y sqlite3 libsqlite3-dev && \
        docker-php-ext-install pdo_sqlite

    VOLUME ["/var/www/db"]

    COPY site/sql/schema.sql /var/www/db/schema.sql

    RUN echo "prepare database" && \
        cat /var/www/db/schema.sql | sqlite3 /var/www/db/db.sqlite && \
        chmod 777 /var/www/db/db.sqlite && \
        rm -rf /var/www/db/schema.sql && \
        echo "database is ready"

    COPY site /var/www/html
    ```

13. Facem push pe GITHub si intram in compartimentul Actions si observam succesul testelor

### Intrebari

1. Ce este integrarea continuă?

    - Integrarea continua este o practica de dezvoltare a software-ului care presupune integrarea schimbarilor unui cod intr-un mediu comun unde se vor efectua automat testele pentru a identifica erorile si a le rezolva

2. Pentru ce sunt necesare testele unitare? Cât de des trebuie să fie executate?

    - Testele unitare sunt importante pentru a verifica executia unei secvente de cod sau functii si daca acestea lucreaza independent. Ele trebuie efectuate de regula la fiecare schimbare adaugata, dar daca marimea proiectului este prea mare se foloseste un sistem automat de verificare configurat de compania care lucreaza la acest proiect care singur este setat cand sa faca testele si cat de des

3. Care modificări trebuie făcute în fișierul .github/workflows/main.yml pentru a rula testele la fiecare solicitare de trage (Pull Request)?

    - Pentru a face acest lucru, trebuie sa adaugam un trigger asemanator cu cel pentru push, dar pentru pull

    ```shell
        pull_request:
            branches:
            - main
    ```

4. Ce trebuie adăugat în fișierul .github/workflows/main.yml pentru a șterge imaginile create după testare?

    - Pentru a face asa ceva adaugam urmatoarele linii la sfarsitul fisierului *main.yml*

    ```shell
        - name: Remove the image of container
        run: docker rmi containers08
    ```

### Concluzie

In urma realizarii sarcinii propuse am capatat abilitati de a integra o aplicatie cu BD intr-un container, dar spre deosebire de lucrarea trecuta deja am configurat si un fisier *main.yml* care a automatizat procesul de testare a aplicatiei noastre in momentul cand am incarcat-o pe GITHub. Prima data la push pe GITHub am observat o eroare la crearea containerului deoarece calea pentru BD care trebuia copiata in mediul containerului era prea scurta. Acest lucru l-am aflat in compartimentul Actions si a ajutat sa gasesc foarte rapid eroarea ca la incarcarea repetata a proiectului pe platforma GITHub testele sa ruleze cu succes. In cocnluzie pot afirma ca aceasta lucrare ne accentueaza si chiar in practica ne arata importanta testelor aplicatiilor noastre care sunt facute automat de catre medii specializate. Efectuand aceste testari noi putem usor sa gasim problema si mai usor sa o rezolvam, ceea ce e un lucru foarte util mai ales daca vom crea o aplicatie care va fi publicata publicului larg
