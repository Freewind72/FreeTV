<?php
function db() {
    static $db = null;
    if (!$db) {
        $db = new PDO('sqlite:' . dirname(__DIR__) . '/data.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return $db;
}
function get_settings() {
    try {
        $stmt = db()->query("SELECT key, value FROM settings");
        $arr = [];
        foreach ($stmt as $row) {
            $arr[$row['key']] = $row['value'];
        }
        return $arr;
    } catch (PDOException $e) {
        // 表不存在时返回空数组
        return [];
    }
}
