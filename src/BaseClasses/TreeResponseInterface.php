<?php
declare(strict_types=1);
namespace BaseClasses;

/**
 * Interface for generating responses to different Ajax requests
 */
interface TreeResponseInterface
{
    /** Check root of tree in storage @return array */
    public function checkRoot(): array;

    /** Load tree when we have root @return array */
    public function load (): array;

    /** Delete node with ID and cascade delete all child nodes (mySql) @return array
     * @param int $id */
    public function remove(int $id): array;

    /** Add node with parent_id=$parent_id and name=$name @return array
     * @param $parent_id
     * @param string $name */
    public function add($parent_id, string $name): array;

    /** Change node's name  @return array
     * @param string $name
     * @param int $id */
    public function update(int $id, string $name): array;

    /** Returns info about a particular implementation of an interface @return array */
    public function getStatus() : array;
}
