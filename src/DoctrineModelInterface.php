<?php
/**
 * Copyright (c) vdeApps 2018
 */

namespace vdeApps\phpCore\DoctrineModel;

use Exception;

interface DoctrineModelInterface
{
    
    public function __construct($container);
    
    
    /**
     * Insertion
     *
     * @param array $aSet
     *
     * @return false|int lastinsertid
     * @throws Exception
     */
    public function create($aSet = []);
    
    /**
     * Create or Replace data
     *
     * @param array $aSet
     * @param array $aClauses
     *
     * @return bool|int (int: lastinsertid)
     * @throws Exception
     */
    public function createOrReplace($aSet = [], $aClauses = []);
    
    /**
     * Mise à jour
     *
     * @param array $aSet
     *
     * @param array $aClauses
     *
     * @return bool
     * @throws Exception
     */
    public function update($aSet = [], $aClauses = []);
    
    /**
     * Suppression
     *
     * @param array $aClauses
     *
     * @return bool
     * @throws Exception
     */
    public function delete($aClauses = []);
    
    /**
     * Converts a value from its PHP representation to its database representation
     * of this type.
     *
     * @param mixed  $value The value to convert.
     * @param string $empty
     *
     * @return mixed The database representation of the value.
     */
    public static function convertToDatabaseValue($value, $empty = null);
    
    /**
     * Converts a value from its database representation to its PHP representation
     * of this type.
     *
     * @param mixed $value The value to convert.
     *
     * @return mixed The PHP representation of the value.
     */
    public static function convertToPHPValue($value);
    
    
}
