<?php
/**
 * Created by PhpStorm.
 * PHP Version: 7.4
 *
 * @category
 * @author     Oleh Boiko <developer@mackais.com>
 * @copyright  2018-2020 @MackRais
 * @link       <https://mackrais.com>
 * @date       2020-02-17
 */

declare(strict_types=1);

namespace TicTacToe\Storage;

interface IStorage
{
    /**
     * Return a value previously stored.
     * If the value does not exists, return $default.
     *
     * @param string $id
     * @param null $default
     * @return mixed
     */
    public function get(string $id, $default = null);

    /**
     * Stores a value.
     *
     * @param string $id
     * @param $value
     *
     * @return IStorage
     */
    public function set(string $id, $value): IStorage;

    /**
     * Check whether a values exists or not.
     *
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool;

    /**
     * Delete a value.
     * If it does not exists, no error will be throw.
     *
     * @param string $id
     *
     * @return IStorage
     */
    public function delete(string $id): IStorage;
}
