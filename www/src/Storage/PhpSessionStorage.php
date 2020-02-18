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

/**
 * This storage use the PHP's global $_SESSION variable to store values.
 */
class PhpSessionStorage implements IStorage
{
    private const KEY = 'mcTicTacToe';

    public function get(string $id, $default = null)
    {
        if ($this->has($id)) {
            return $_SESSION[self::KEY][$id];
        }

        return $default;
    }

    public function set(string $id, $value): IStorage
    {
        $_SESSION[self::KEY][$id] = $value;

        return $this;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $_SESSION[self::KEY] ?? []);
    }

    public function delete(string $id): IStorage
    {
        unset($_SESSION[self::KEY][$id]);

        return $this;
    }
}
