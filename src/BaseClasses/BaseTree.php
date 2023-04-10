<?php
declare(strict_types=1);
namespace BaseClasses;

/**
 * Base class for tree without any responses
 */
class BaseTree
{
    /**
     * field names of our structured data table
     */
    const FIELDS = ['id' => 'id', 'parent_id' => 'parent_id', 'name' => 'name'];
    /**
     * Recursive build tree for all nodes
     * @param array $items
     * @param int $parent
     * @return array
     */
    protected function buildTree(array $items, int $parent = 0): array
    {
        $tree = array();
        foreach ($items as $item) {
            if ($item['parent_id'] == $parent) {
                $children = $this->buildTree($items, (int) $item['id']);
                if ($children) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }
}
