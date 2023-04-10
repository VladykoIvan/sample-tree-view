<?php

namespace BaseClasses;

/**
 * Session implementation for TreeResponseInterface
 */
class SessionTreeResponse extends BaseTree implements TreeResponseInterface
{
    /**
     * Variable name for autoincrement in $_SESSION
     */
    const NAME_DB_TABLE_AUTO_INCREMENT = 'dbTableAutoIncrement';
    /**
     * Variable name for tree array (table) in $_SESSION
     */
    const NAME_STORAGE_ARRAY = 'dbTableStorageArray';

    /**
     * Class constructor. Initialize our variables in $_SESSION
     */
    public function __construct()
    {
        session_start();
        if (!$_SESSION[self::NAME_DB_TABLE_AUTO_INCREMENT] || !isset($_SESSION[self::NAME_STORAGE_ARRAY])) {
            $_SESSION[self::NAME_DB_TABLE_AUTO_INCREMENT] = 1;
            $_SESSION[self::NAME_STORAGE_ARRAY] = [];
        }
    }

    /**
     * @inheritDoc
     */
    public function checkRoot(): array
    {
        $storageArray = $_SESSION[self::NAME_STORAGE_ARRAY];
        if(in_array(0, array_column($storageArray, BaseTree::FIELDS['parent_id']))) {
            return ['result' => 'success', 'message' => ['true']];
        }
        return ['result' => 'success', 'message' => []];
    }

    /**
     * @inheritDoc
     */
    public function load(): array
    {
        $storageArray = $_SESSION[self::NAME_STORAGE_ARRAY];
        $res = $this->buildTree($storageArray);
        return ['result' => 'success', 'message' => $res];
    }

    /**
     * @inheritDoc
     */
    public function remove(int $id): array
    {
        $storageArray = $_SESSION[self::NAME_STORAGE_ARRAY];
        $keys = array_keys(array_column($storageArray, BaseTree::FIELDS['id']), $id);
        if ($keys) {
            foreach ($keys as $key) {
                unset($storageArray[$key]);
            }
        }
        $this->removeCascade($storageArray, $id);
        $_SESSION[self::NAME_STORAGE_ARRAY] = array_values($storageArray);
        return['result' => 'success', 'message' => $keys];
    }

    /**
     * Remove all children in recursive way
     * @param array $storageArray
     * @param int $parent
     * @return void
     */
    private function removeCascade(array &$storageArray, int $parent)
    {
        foreach ($storageArray as $key => $item) {
            if ($item[BaseTree::FIELDS['parent_id']] == $parent) {
                unset ($storageArray[$key]);
                $this->removeCascade($storageArray, (int) $item[BaseTree::FIELDS['id']]);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function add($parent_id, string $name): array
    {
        $name = htmlspecialchars(strip_tags($name));
        $storageArray = $_SESSION[self::NAME_STORAGE_ARRAY];
        $autoIncrement = $_SESSION[self::NAME_DB_TABLE_AUTO_INCREMENT];
        $storageArray[] = [
            BaseTree::FIELDS['id'] => (int) $autoIncrement,
            BaseTree::FIELDS['parent_id'] => (int)$parent_id,
            BaseTree::FIELDS['name'] => $name,
        ];
        $_SESSION[self::NAME_STORAGE_ARRAY] = $storageArray;
        $lastInsertId = $autoIncrement;
        $autoIncrement++;
        $_SESSION[self::NAME_DB_TABLE_AUTO_INCREMENT] = $autoIncrement;
        $name = htmlspecialchars_decode($name);
        return ['result' => 'success', 'message' => ["id" => "$lastInsertId", "name" => "$name"]];
    }

    /**
     * @inheritDoc
     */
    public function update(int $id, string $name): array
    {
        $name = htmlspecialchars(strip_tags($name));
        $storageArray = $_SESSION[self::NAME_STORAGE_ARRAY];
        $key = array_search($id,array_column($storageArray, BaseTree::FIELDS['id']));
        $storageArray[$key][BaseTree::FIELDS['name']] = $name;
        $_SESSION[self::NAME_STORAGE_ARRAY] = $storageArray;
        $name = htmlspecialchars_decode($name);
        return ['result' => 'success', 'message' => ["id" => "$id", "name" => "$name"]];
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): array
    {
        return ['result' => 'success',
            'message' => ["text" => "db is not available", "color" => "text-danger"]];
    }
}
