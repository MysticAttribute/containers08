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